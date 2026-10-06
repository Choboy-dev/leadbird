<?php

declare(strict_types=1);

final class SubscriptionService
{
    /** Retries happen on day 3 and day 5 after the due date (the first attempt is on the due date). */
    private const RENEWAL_RETRY_OFFSET_DAYS = [2, 4];

    private const RENEWAL_GRACE_DAYS = 5;

    public function hasActiveSubscription(string $userId): bool
    {
        return $this->getActiveSubscription($userId) !== null;
    }

    /**
     * Copy active user_subscriptions state onto businesses (plan, status, expiry).
     * Safe to call repeatedly; no-op when there is no business or no active subscription.
     */
    public function syncBusinessSubscription(string $userId): bool
    {
        $subscription = $this->getActiveSubscription($userId);
        if ($subscription === null) {
            return false;
        }

        $planSlug = (string) $subscription['plan_slug'];
        $periodEnd = $subscription['current_period_end'] ?? null;
        $expiresAt = is_string($periodEnd) && $periodEnd !== '' ? $periodEnd : null;
        $synced = false;

        foreach (RepositoryFactory::businesses()->listByUserId($userId) as $business) {
            if (
                ($business['subscription_status'] ?? '') === 'active'
                && ($business['subscription_plan'] ?? '') === $planSlug
                && (string) ($business['subscription_expires_at'] ?? '') === (string) ($expiresAt ?? '')
            ) {
                continue;
            }

            RepositoryFactory::businesses()->updateSubscription(
                (string) $business['id'],
                $planSlug,
                'active',
                $expiresAt
            );
            $synced = true;
        }

        return $synced;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getActiveSubscription(string $userId): ?array
    {
        return RepositoryFactory::subscriptions()->findActiveByUserId($userId);
    }

    /**
     * @return array{link: string, tx_ref: string}
     */
    public function beginCheckout(
        string $userId,
        string $planSlug,
        array $user,
        string $billingCycle = CheckoutPricing::CYCLE_MONTHLY
    ): array {
        $plan = PlanService::find($planSlug);
        if ($plan === null) {
            throw new InvalidArgumentException('Unknown plan.');
        }

        $activeSubscription = $this->getActiveSubscription($userId);
        if (
            $activeSubscription !== null
            && (string) ($activeSubscription['plan_slug'] ?? '') === $planSlug
        ) {
            throw new RuntimeException('You are already on this plan.');
        }

        RepositoryFactory::subscriptions()->cancelPendingForUser($userId);

        $pricing = (new CheckoutPricing())->forPlan($planSlug, $userId, $billingCycle);
        $txRef = $this->generateTxRef($userId, $planSlug);
        $subscriptionMeta = [
            'plan_name' => (string) $plan['name'],
            'checkout_context' => $activeSubscription === null ? 'new_subscription' : 'plan_change',
            'billing_cycle' => $billingCycle,
            'amount_usd' => $pricing['amount_usd'],
            'fx_ngn_per_usd' => $pricing['currency'] === 'NGN'
                ? (new FxRateService())->ngnPerUsd()
                : null,
        ];
        if ($activeSubscription !== null && !empty($activeSubscription['id'])) {
            $subscriptionMeta['replaces_subscription_id'] = (string) $activeSubscription['id'];
        }

        (new AffiliateService())->bindUserReferral($userId);
        $userRow = RepositoryFactory::users()->findById($userId);
        $affiliateId = trim((string) ($userRow['referred_by_affiliate_id'] ?? ''));
        if ($affiliateId === '') {
            $affiliateId = trim((string) ((new AffiliateService())->referredAffiliateIdFromCookie() ?? ''));
        }
        if ($affiliateId !== '') {
            $subscriptionMeta['affiliate_id'] = $affiliateId;
        }

        // Renewals are charged by renewDueSubscriptions() with the saved card token, so no
        // Flutterwave payment plan is attached (that would let Flutterwave charge a second time).
        RepositoryFactory::subscriptions()->createPending(
            $userId,
            $planSlug,
            (float) $pricing['amount'],
            $txRef,
            null,
            $subscriptionMeta,
            (string) $pricing['currency']
        );

        PlanSelection::set($planSlug);

        $redirectUrl = app_route('billing/callback');
        $checkoutDescription = $activeSubscription === null
            ? null
            : ('Change to ' . (string) $plan['name'] . ' plan');
        if ($billingCycle === CheckoutPricing::CYCLE_ANNUAL) {
            $checkoutDescription = ($checkoutDescription ?? (string) $plan['name'] . ' plan') . ' — annual';
        }

        $chargeAmount = (string) $pricing['amount'];
        if ($pricing['currency'] === 'USD' && str_contains($chargeAmount, '.')) {
            $chargeAmount = number_format((float) $pricing['amount'], 2, '.', '');
        }

        return (new FlutterwaveService())->initiateCheckout(
            $txRef,
            $chargeAmount,
            (string) $pricing['currency'],
            (string) ($user['email'] ?? ''),
            (string) ($user['name'] ?? ''),
            (string) $plan['name'],
            $redirectUrl,
            null,
            $checkoutDescription
        );
    }

    /**
     * @return array{ok: bool, redirect: string, message?: string}
     */
    public function completeCheckout(string $txRef, ?string $transactionId, ?string $status): array
    {
        $subscription = RepositoryFactory::subscriptions()->findByTxRef($txRef);
        if ($subscription === null) {
            return ['ok' => false, 'redirect' => app_route('pricing'), 'message' => 'Payment session not found.'];
        }

        $checkoutContext = $this->checkoutContext($subscription);
        $failureRedirect = $checkoutContext['is_plan_change'] ? app_route('billing') : app_route('pricing');
        $successRedirect = $checkoutContext['is_plan_change'] ? app_route('billing') : app_route('app');
        $successMessage = $checkoutContext['is_plan_change']
            ? 'Your plan has been updated successfully.'
            : 'Payment successful! Let\'s set up your business.';

        if (($subscription['status'] ?? '') === 'active') {
            PlanSelection::clear();
            return ['ok' => true, 'redirect' => $successRedirect, 'message' => $successMessage];
        }

        if ($status !== null && !in_array(strtolower($status), ['successful', 'completed', 'success'], true)) {
            return [
                'ok' => false,
                'redirect' => $failureRedirect,
                'message' => 'Payment was not completed. Choose a plan to try again.',
            ];
        }

        if ($transactionId === null || $transactionId === '') {
            return [
                'ok' => false,
                'redirect' => $failureRedirect,
                'message' => 'Missing payment reference. Please try again.',
            ];
        }

        $flutterwave = new FlutterwaveService();
        $verification = $flutterwave->verifyTransaction($transactionId);
        $data = is_array($verification['data'] ?? null) ? $verification['data'] : [];

        if (($data['status'] ?? '') !== 'successful') {
            return [
                'ok' => false,
                'redirect' => $failureRedirect,
                'message' => 'We could not verify your payment yet. If you were charged, contact support.',
            ];
        }

        $verifiedRef = (string) ($data['tx_ref'] ?? '');
        if ($verifiedRef !== '' && $verifiedRef !== $txRef) {
            return [
                'ok' => false,
                'redirect' => $failureRedirect,
                'message' => 'Payment reference mismatch.',
            ];
        }

        try {
            assert_flutterwave_payment_matches(
                $data,
                (float) ($subscription['amount'] ?? 0),
                (string) ($subscription['currency'] ?? 'USD'),
                money_payment_tolerance((string) ($subscription['currency'] ?? 'USD'))
            );
        } catch (RuntimeException $e) {
            error_log('[Subscription checkout] ' . $e->getMessage() . ' tx_ref=' . $txRef);
            return [
                'ok' => false,
                'redirect' => $failureRedirect,
                'message' => 'Payment could not be confirmed for this plan. Contact support if you were charged.',
            ];
        }

        $this->activateSubscription($subscription, (string) $transactionId, $data);
        PlanSelection::clear();

        return ['ok' => true, 'redirect' => $successRedirect, 'message' => $successMessage];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handleWebhook(array $payload): bool
    {
        $event = (string) ($payload['event'] ?? '');
        if ($event !== 'charge.completed') {
            return true;
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $txRef = (string) ($data['tx_ref'] ?? '');
        $transactionId = (string) ($data['id'] ?? '');

        if ($txRef === '' || $transactionId === '') {
            return false;
        }

        $subscription = RepositoryFactory::subscriptions()->findByTxRef($txRef);
        if ($subscription === null) {
            return false;
        }

        if (($subscription['status'] ?? '') === 'active') {
            return true;
        }

        if (($data['status'] ?? '') !== 'successful') {
            return true;
        }

        // Never trust webhook body alone — re-verify with Flutterwave API.
        $flutterwave = new FlutterwaveService();
        $verification = $flutterwave->verifyTransaction($transactionId);
        $verified = is_array($verification['data'] ?? null) ? $verification['data'] : [];

        if (($verified['status'] ?? '') !== 'successful') {
            error_log('[Subscription webhook] verify failed tx=' . $transactionId);
            return false;
        }

        $verifiedRef = (string) ($verified['tx_ref'] ?? '');
        if ($verifiedRef !== '' && $verifiedRef !== $txRef) {
            error_log('[Subscription webhook] tx_ref mismatch');
            return false;
        }

        try {
            assert_flutterwave_payment_matches(
                $verified,
                (float) ($subscription['amount'] ?? 0),
                (string) ($subscription['currency'] ?? 'USD'),
                money_payment_tolerance((string) ($subscription['currency'] ?? 'USD'))
            );
        } catch (RuntimeException $e) {
            error_log('[Subscription webhook] ' . $e->getMessage() . ' tx_ref=' . $txRef);
            return false;
        }

        $this->activateSubscription($subscription, $transactionId, $verified);

        return true;
    }

    public function cancelActiveSubscription(string $userId): bool
    {
        $subscription = $this->getActiveSubscription($userId);
        if ($subscription === null) {
            return false;
        }

        $flutterwaveSubscriptionId = trim((string) ($subscription['flutterwave_subscription_id'] ?? ''));
        if ($flutterwaveSubscriptionId !== '') {
            try {
                (new FlutterwaveService())->cancelSubscription($flutterwaveSubscriptionId);
            } catch (Throwable $e) {
                error_log('[Subscription cancel] Flutterwave: ' . $e->getMessage());
            }
        }

        RepositoryFactory::subscriptions()->markCancelled((string) $subscription['id']);

        $business = RepositoryFactory::businesses()->findByUserId($userId);
        if ($business !== null) {
            $periodEnd = $subscription['current_period_end'] ?? null;
            RepositoryFactory::businesses()->updateSubscription(
                (string) $business['id'],
                (string) $subscription['plan_slug'],
                'cancelled',
                is_string($periodEnd) && $periodEnd !== '' ? $periodEnd : null
            );
        }

        return true;
    }

    /**
     * @return array{processed: int, renewed: int, failed: int, expired: int}
     */
    public function renewDueSubscriptions(): array
    {
        $stats = ['processed' => 0, 'renewed' => 0, 'failed' => 0, 'expired' => 0];

        foreach (RepositoryFactory::subscriptions()->findDueForRenewal() as $subscription) {
            ++$stats['processed'];
            try {
                $outcome = $this->renewSubscriptionRow($subscription);
            } catch (Throwable $e) {
                error_log('[Subscription renew] ' . $e->getMessage());
                $outcome = $this->handleRenewalFailure($subscription, 'Unexpected error: ' . $e->getMessage(), true);
            }
            ++$stats[$outcome];
        }

        return $stats;
    }

    /**
     * Charges a past-due subscription straight away (after the customer adds a card or asks to retry).
     * A failure here does not use up one of the scheduled retries.
     *
     * @return bool|null null when nothing is past due
     */
    public function retryPastDueRenewal(string $userId): ?bool
    {
        $subscription = RepositoryFactory::subscriptions()->findPastDueByUserId($userId);
        if ($subscription === null) {
            return null;
        }

        try {
            return $this->renewSubscriptionRow($subscription, false) === 'renewed';
        } catch (Throwable $e) {
            error_log('[Subscription renew] manual retry: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @return array{attempts: int, next_attempt_at: ?string, final_attempt_at: ?string, grace_until: ?string}|null
     */
    public function pastDueState(string $userId): ?array
    {
        $subscription = RepositoryFactory::subscriptions()->findPastDueByUserId($userId);
        if ($subscription === null) {
            return null;
        }

        $periodEnd = (string) ($subscription['current_period_end'] ?? '');

        return [
            'attempts' => (int) ($subscription['renewal_attempts'] ?? 0),
            'next_attempt_at' => isset($subscription['next_renewal_attempt_at']) ? (string) $subscription['next_renewal_attempt_at'] : null,
            'final_attempt_at' => $periodEnd !== ''
                ? (new DateTimeImmutable($periodEnd))->modify('+' . self::finalRetryOffsetDays() . ' days')->format('Y-m-d H:i:s')
                : null,
            'grace_until' => isset($subscription['renewal_grace_until']) ? (string) $subscription['renewal_grace_until'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $subscription
     * @return 'renewed'|'failed'|'expired'
     */
    private function renewSubscriptionRow(array $subscription, bool $recordFailure = true): string
    {
        $userId = (string) ($subscription['user_id'] ?? '');
        $subscriptionId = (string) ($subscription['id'] ?? '');
        $planSlug = (string) ($subscription['plan_slug'] ?? '');
        $meta = $this->subscriptionMeta($subscription);

        if (!empty($meta['admin_override']) || !empty($meta['app_review_access'])) {
            // Complimentary access is never charged; it simply ends.
            RepositoryFactory::subscriptions()->markExpired($subscriptionId, 'Complimentary period ended');
            $this->markBusinessesExpired($userId, $planSlug, (string) ($subscription['current_period_end'] ?? ''));

            return 'expired';
        }

        $fail = function (string $reason, bool $hasCard) use ($subscription, $recordFailure): string {
            return $recordFailure ? $this->handleRenewalFailure($subscription, $reason, $hasCard) : 'failed';
        };

        $plan = PlanService::find($planSlug);
        $user = RepositoryFactory::users()->findById($userId);
        if ($userId === '' || $subscriptionId === '' || $plan === null || $user === null) {
            return $fail('Subscription, plan or user not found', true);
        }

        $paymentMethods = RepositoryFactory::paymentMethods()->listByUserId($userId);
        if ($paymentMethods === []) {
            return $fail('No saved card', false);
        }

        $storedAmount = (float) ($subscription['amount'] ?? 0);
        $currency = strtoupper((string) ($subscription['currency'] ?? 'USD'));
        $catalogUsd = isset($meta['amount_usd']) ? (float) $meta['amount_usd'] : 0.0;

        if ($currency === 'NGN' && $catalogUsd > 0) {
            // Recalculate each renewal from catalog USD × today's live Flutterwave rate.
            $amount = (float) (new FxRateService())->usdToNgn($catalogUsd);
        } elseif ($storedAmount > 0) {
            // Grandfathered legacy subscriptions (e.g. old Shop price) keep their stored amount.
            $amount = $storedAmount;
        } else {
            $pricing = (new CheckoutPricing())->forPlan($planSlug, $userId);
            $amount = (float) $pricing['amount'];
            $currency = (string) $pricing['currency'];
        }

        if ($amount <= 0) {
            return $fail('Could not work out the renewal amount', true);
        }

        $billingProfile = (new BillingProfileService())->findForUser($userId);
        $chargeAmount = $currency === 'USD'
            ? number_format($amount, 2, '.', '')
            : (string) (int) round($amount);

        $lastError = 'Card was declined';
        foreach ($paymentMethods as $paymentMethod) {
            $txRef = $this->generateRenewalTxRef($userId, $planSlug);
            try {
                $charge = (new FlutterwaveService())->tokenizedCharge(
                    (string) $paymentMethod['flutterwave_token'],
                    (string) ($user['email'] ?? ''),
                    $chargeAmount,
                    $currency,
                    $txRef,
                    (string) ($plan['name'] ?? 'Lead Bird') . ' renewal',
                    $billingProfile
                );
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
                continue;
            }

            $data = is_array($charge['data'] ?? null) ? $charge['data'] : [];
            if (strtolower((string) ($data['status'] ?? '')) !== 'successful') {
                $lastError = (string) ($data['processor_response'] ?? $charge['message'] ?? 'Card was declined');
                continue;
            }

            if (empty($paymentMethod['is_default'])) {
                RepositoryFactory::paymentMethods()->setDefault((string) $paymentMethod['id'], $userId);
            }

            $transactionId = (string) ($data['id'] ?? $data['flw_ref'] ?? $txRef);
            $this->completeRenewal($subscription, $transactionId, $planSlug, $meta);

            return 'renewed';
        }

        return $fail($lastError, true);
    }

    /**
     * @param array<string, mixed> $subscription
     * @param array<string, mixed> $meta
     */
    private function completeRenewal(array $subscription, string $transactionId, string $planSlug, array $meta): void
    {
        $userId = (string) $subscription['user_id'];
        $interval = (string) ($meta['billing_cycle'] ?? CheckoutPricing::CYCLE_MONTHLY) === CheckoutPricing::CYCLE_ANNUAL
            ? '+1 year'
            : '+1 month';

        // Keep the original renewal day unless the plan lapsed for a full cycle.
        $now = new DateTimeImmutable();
        $previousEnd = (string) ($subscription['current_period_end'] ?? '');
        $periodEnd = ($previousEnd !== '' ? new DateTimeImmutable($previousEnd) : $now)->modify($interval);
        if ($periodEnd <= $now) {
            $periodEnd = $now->modify($interval);
        }

        RepositoryFactory::subscriptions()->extendActivePeriod(
            (string) $subscription['id'],
            $transactionId,
            $periodEnd->format('Y-m-d H:i:s')
        );

        $this->syncBusinessSubscription($userId);

        (new TokenService())->grantMonthlyAllowance(
            $userId,
            $planSlug,
            'subscription_renewal',
            $transactionId
        );
    }

    /**
     * @param array<string, mixed> $subscription
     * @return 'failed'|'expired'
     */
    private function handleRenewalFailure(array $subscription, string $reason, bool $hasCard): string
    {
        $subscriptionId = (string) ($subscription['id'] ?? '');
        $userId = (string) ($subscription['user_id'] ?? '');
        $planSlug = (string) ($subscription['plan_slug'] ?? '');
        $attempts = (int) ($subscription['renewal_attempts'] ?? 0) + 1;
        $now = new DateTimeImmutable();
        $periodEnd = new DateTimeImmutable((string) ($subscription['current_period_end'] ?? 'now'));
        $graceUntil = $periodEnd->modify('+' . self::RENEWAL_GRACE_DAYS . ' days');
        $user = $userId !== '' ? RepositoryFactory::users()->findById($userId) : null;
        $plan = PlanService::find($planSlug);
        $planName = (string) ($plan['name'] ?? 'Lead Bird');

        error_log('[Subscription renew] failed sub=' . $subscriptionId . ' attempt=' . $attempts . ': ' . $reason);

        if ($attempts > count(self::RENEWAL_RETRY_OFFSET_DAYS) || $graceUntil <= $now) {
            RepositoryFactory::subscriptions()->markExpired($subscriptionId, $reason);
            $this->markBusinessesExpired($userId, $planSlug, $periodEnd->format('Y-m-d H:i:s'));
            if ($user !== null) {
                RenewalNotice::sendExpired($user, $planName);
            }

            return 'expired';
        }

        $nextAttempt = $periodEnd->modify('+' . self::RENEWAL_RETRY_OFFSET_DAYS[$attempts - 1] . ' days');
        if ($nextAttempt <= $now) {
            $nextAttempt = $now->modify('+1 day');
        }
        $finalAttempt = $periodEnd->modify('+' . self::finalRetryOffsetDays() . ' days');
        if ($finalAttempt < $nextAttempt) {
            $finalAttempt = $nextAttempt;
        }

        RepositoryFactory::subscriptions()->recordRenewalFailure(
            $subscriptionId,
            $attempts,
            $nextAttempt->format('Y-m-d H:i:s'),
            $graceUntil->format('Y-m-d H:i:s'),
            $reason
        );

        if ($user !== null) {
            RenewalNotice::sendFailed($user, $planName, $hasCard, $nextAttempt, $finalAttempt);
        }

        return 'failed';
    }

    private static function finalRetryOffsetDays(): int
    {
        return self::RENEWAL_RETRY_OFFSET_DAYS[count(self::RENEWAL_RETRY_OFFSET_DAYS) - 1];
    }

    private function markBusinessesExpired(string $userId, string $planSlug, string $periodEnd): void
    {
        if ($userId === '') {
            return;
        }

        foreach (RepositoryFactory::businesses()->listByUserId($userId) as $business) {
            RepositoryFactory::businesses()->updateSubscription(
                (string) $business['id'],
                $planSlug,
                'expired',
                $periodEnd !== '' ? $periodEnd : null
            );
        }
    }

    private function generateRenewalTxRef(string $userId, string $planSlug): string
    {
        $userPart = substr(str_replace('-', '', $userId), 0, 8);

        return 'lb_renew_' . $userPart . '_' . $planSlug . '_' . time() . '_' . bin2hex(random_bytes(3));
    }

    /**
     * @param array<string, mixed> $subscription
     * @param array<string, mixed> $paymentData
     */
    private function activateSubscription(array $subscription, string $transactionId, array $paymentData): void
    {
        $subscriptionId = isset($paymentData['payment_plan']) ? (string) $paymentData['payment_plan'] : null;
        $meta = $this->subscriptionMeta($subscription);
        $billingCycle = (string) ($meta['billing_cycle'] ?? CheckoutPricing::CYCLE_MONTHLY);
        $periodEnd = $billingCycle === CheckoutPricing::CYCLE_ANNUAL
            ? (new DateTimeImmutable('+1 year'))->format('Y-m-d H:i:s')
            : (new DateTimeImmutable('+1 month'))->format('Y-m-d H:i:s');
        $checkoutContext = $this->checkoutContext($subscription);

        RepositoryFactory::subscriptions()->markActive(
            (string) $subscription['id'],
            $transactionId,
            $subscriptionId,
            $periodEnd
        );

        if ($checkoutContext['replaces_subscription_id'] !== null) {
            $this->cancelReplacedSubscription($checkoutContext['replaces_subscription_id']);
        }

        try {
            (new PaymentMethodService())->saveFromPayment((string) $subscription['user_id'], $paymentData);
        } catch (Throwable $e) {
            error_log('[Subscription] save card: ' . $e->getMessage());
        }

        $this->syncBusinessSubscription((string) $subscription['user_id']);

        (new TokenService())->grantMonthlyAllowance(
            (string) $subscription['user_id'],
            (string) ($subscription['plan_slug'] ?? ''),
            'subscription_start',
            (string) $subscription['id']
        );

        try {
            $fresh = RepositoryFactory::subscriptions()->findById((string) $subscription['id']);
            (new CommissionService())->createForSubscription($fresh ?? $subscription);
        } catch (Throwable $e) {
            error_log('[Subscription] commission: ' . $e->getMessage());
        }
    }

    private function generateTxRef(string $userId, string $planSlug): string
    {
        $userPart = substr(str_replace('-', '', $userId), 0, 8);
        return 'lb_' . $userPart . '_' . $planSlug . '_' . time() . '_' . bin2hex(random_bytes(3));
    }

    /**
     * @param array<string, mixed> $subscription
     * @return array{is_plan_change: bool, replaces_subscription_id: string|null}
     */
    private function checkoutContext(array $subscription): array
    {
        $meta = $this->subscriptionMeta($subscription);
        $replacesSubscriptionId = trim((string) ($meta['replaces_subscription_id'] ?? ''));
        $context = strtolower(trim((string) ($meta['checkout_context'] ?? '')));

        return [
            'is_plan_change' => $context === 'plan_change' || $replacesSubscriptionId !== '',
            'replaces_subscription_id' => $replacesSubscriptionId !== '' ? $replacesSubscriptionId : null,
        ];
    }

    /**
     * @param array<string, mixed> $subscription
     * @return array<string, mixed>
     */
    private function subscriptionMeta(array $subscription): array
    {
        $meta = $subscription['meta'] ?? null;
        if (is_array($meta)) {
            return $meta;
        }

        if (!is_string($meta) || $meta === '') {
            return [];
        }

        try {
            $decoded = json_decode($meta, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function cancelReplacedSubscription(string $subscriptionId): void
    {
        $subscription = RepositoryFactory::subscriptions()->findById($subscriptionId);
        if ($subscription === null || ($subscription['status'] ?? '') !== 'active') {
            return;
        }

        $flutterwaveSubscriptionId = trim((string) ($subscription['flutterwave_subscription_id'] ?? ''));
        if ($flutterwaveSubscriptionId !== '') {
            try {
                (new FlutterwaveService())->cancelSubscription($flutterwaveSubscriptionId);
            } catch (Throwable $e) {
                error_log('[Subscription change] Flutterwave: ' . $e->getMessage());
            }
        }

        RepositoryFactory::subscriptions()->markCancelled((string) $subscription['id']);
    }
}
