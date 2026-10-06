<?php

declare(strict_types=1);

final class PaymentMethodService
{
    /** Verification charge per checkout currency (card entry happens on Flutterwave's hosted page). */
    private const CARD_VERIFY_AMOUNTS = ['NGN' => 100, 'USD' => 1];

    private const VERIFY_SESSION_KEY = 'lb_card_verification';

    /** @return list<array<string, mixed>> */
    public function listForUser(string $userId): array
    {
        return RepositoryFactory::paymentMethods()->listByUserId($userId);
    }

    public function syncFromActiveSubscription(string $userId): void
    {
        if (RepositoryFactory::paymentMethods()->countByUserId($userId) > 0) {
            return;
        }

        $subscription = (new SubscriptionService())->getActiveSubscription($userId);
        if ($subscription === null) {
            return;
        }

        $transactionId = trim((string) ($subscription['flutterwave_tx_id'] ?? ''));
        if ($transactionId === '') {
            return;
        }

        try {
            $verification = (new FlutterwaveService())->verifyTransaction($transactionId);
        } catch (Throwable) {
            return;
        }

        $this->storeCardFromPaymentData($userId, is_array($verification['data'] ?? null) ? $verification['data'] : []);
    }

    /**
     * Saves billing details, then sends the user to Flutterwave's hosted card checkout
     * for a small verification charge. Card details never reach this server.
     *
     * @param array{id: string, email: string, name?: ?string} $user
     * @param array<string, mixed> $billingInput
     * @return array{ok: bool, redirect?: string, message?: string}
     */
    public function beginCardVerification(string $userId, array $user, array $billingInput): array
    {
        $flutterwave = new FlutterwaveService();
        if (!$flutterwave->isConfigured()) {
            return ['ok' => false, 'message' => 'Card payments are not configured yet. Please contact support.'];
        }

        $billingProfileService = new BillingProfileService();
        $validated = $billingProfileService->validateInput($billingInput);
        if (!($validated['ok'] ?? false)) {
            return ['ok' => false, 'message' => (string) ($validated['message'] ?? 'Invalid billing details.')];
        }
        $billingProfileService->saveForUser($userId, $validated['billing']);

        $currency = (new CheckoutPricing())->checkoutCurrencyForUser($userId);
        $amount = self::CARD_VERIFY_AMOUNTS[$currency] ?? self::CARD_VERIFY_AMOUNTS['USD'];
        $txRef = $this->generateCardTxRef($userId);

        $_SESSION[self::VERIFY_SESSION_KEY] = [
            'user_id' => $userId,
            'tx_ref' => $txRef,
            'amount' => (float) $amount,
            'currency' => $currency,
        ];

        try {
            $checkout = $flutterwave->initiateCheckout(
                $txRef,
                (string) $amount,
                $currency,
                (string) ($user['email'] ?? ''),
                (string) ($user['name'] ?? ''),
                'Card verification',
                app_route('billing/card-callback'),
                null,
                'Card verification for automatic renewals',
                'card'
            );
        } catch (Throwable $e) {
            unset($_SESSION[self::VERIFY_SESSION_KEY]);
            error_log('[PaymentMethod] card verification checkout failed for user ' . $userId . ': ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Could not open secure card checkout. Please try again.'];
        }

        return ['ok' => true, 'redirect' => $checkout['link']];
    }

    /**
     * Handles the return from Flutterwave: verifies the charge and stores the card token.
     *
     * @return array{ok: bool, message: string}
     */
    public function completeCardVerification(string $userId, string $txRef, string $transactionId, string $status): array
    {
        $pending = $_SESSION[self::VERIFY_SESSION_KEY] ?? null;
        unset($_SESSION[self::VERIFY_SESSION_KEY]);

        if (
            !is_array($pending)
            || ($pending['user_id'] ?? '') !== $userId
            || $txRef === ''
            || !hash_equals((string) ($pending['tx_ref'] ?? ''), $txRef)
        ) {
            return ['ok' => false, 'message' => 'Card verification expired. Please add your card again.'];
        }

        if ($transactionId === '' || in_array(strtolower($status), ['cancelled', 'failed'], true)) {
            return ['ok' => false, 'message' => 'Your card was not added.'];
        }

        try {
            $verification = (new FlutterwaveService())->verifyTransaction($transactionId);
        } catch (Throwable $e) {
            error_log('[PaymentMethod] card verification verify failed for user ' . $userId . ': ' . $e->getMessage());

            return ['ok' => false, 'message' => 'We could not confirm your card. Please try again.'];
        }

        $data = is_array($verification['data'] ?? null) ? $verification['data'] : [];
        if (strtolower((string) ($data['status'] ?? '')) !== 'successful' || (string) ($data['tx_ref'] ?? '') !== $txRef) {
            return ['ok' => false, 'message' => 'Card verification was not successful. Please try again.'];
        }

        $currency = (string) ($pending['currency'] ?? 'USD');
        try {
            assert_flutterwave_payment_matches($data, (float) ($pending['amount'] ?? 0), $currency, money_payment_tolerance($currency));
        } catch (RuntimeException $e) {
            error_log('[PaymentMethod] card verification ' . $e->getMessage() . ' tx_ref=' . $txRef);

            return ['ok' => false, 'message' => 'Card verification could not be confirmed. Please try again.'];
        }

        if ($this->storeCardFromPaymentData($userId, $data) === null) {
            return ['ok' => false, 'message' => 'We could not save this card. Please try a different card.'];
        }

        $renewed = (new SubscriptionService())->retryPastDueRenewal($userId);
        if ($renewed === true) {
            return ['ok' => true, 'message' => 'Card saved and your plan has been renewed. Thank you!'];
        }
        if ($renewed === false) {
            return ['ok' => false, 'message' => 'Card saved, but the renewal charge did not go through. Check with your bank or try another card.'];
        }

        return ['ok' => true, 'message' => 'Card saved. We will use it for automatic renewals.'];
    }

    /**
     * Saves the reusable card token from a verified Flutterwave payment (no-op for non-card payments).
     *
     * @param array<string, mixed> $paymentData
     */
    public function saveFromPayment(string $userId, array $paymentData): void
    {
        $this->storeCardFromPaymentData($userId, $paymentData);
    }

    public function removeCard(string $userId, string $methodId): bool
    {
        return RepositoryFactory::paymentMethods()->deleteForUser($userId, $methodId);
    }

    public function makeDefault(string $userId, string $methodId): bool
    {
        return RepositoryFactory::paymentMethods()->setDefault($methodId, $userId);
    }

    /**
     * @param array<string, mixed> $method
     * @return array<string, mixed>
     */
    public function presentMethod(array $method): array
    {
        $brand = strtoupper((string) ($method['card_brand'] ?? 'CARD'));
        [$brandName, $brandShort] = match (true) {
            str_contains($brand, 'MASTER') => ['Mastercard', 'MC'],
            str_contains($brand, 'VISA') => ['Visa', 'VISA'],
            str_contains($brand, 'VERVE') => ['Verve', 'VERVE'],
            str_contains($brand, 'AMERICAN'), str_contains($brand, 'AMEX') => ['Amex', 'AMEX'],
            $brand === '' || $brand === 'CARD' => ['Card', 'CARD'],
            default => [ucwords(strtolower($brand)), substr($brand, 0, 4)],
        };
        $expMonth = (int) ($method['card_exp_month'] ?? 0);
        $expYear = (int) ($method['card_exp_year'] ?? 0);
        $isExpired = $expYear > 0 && $expMonth > 0
            && ($expYear < (int) date('Y') || ($expYear === (int) date('Y') && $expMonth < (int) date('n')));

        return [
            'brand_name' => $brandName,
            'brand_short' => $brandShort,
            'is_expired' => $isExpired,
            'id' => (string) ($method['id'] ?? ''),
            'brand' => $brand,
            'last4' => (string) ($method['card_last4'] ?? ''),
            'exp_month' => (string) ($method['card_exp_month'] ?? ''),
            'exp_year' => (string) ($method['card_exp_year'] ?? ''),
            'holder' => (string) ($method['card_holder'] ?? ''),
            'is_default' => (bool) ($method['is_default'] ?? false),
            'label' => trim($brand . ' •••• ' . (string) ($method['card_last4'] ?? '')),
            'expires' => str_pad((string) ($method['card_exp_month'] ?? ''), 2, '0', STR_PAD_LEFT)
                . '/' . substr((string) ($method['card_exp_year'] ?? ''), -2),
        ];
    }

    /**
     * @param array<string, mixed> $paymentData
     */
    private function storeCardFromPaymentData(string $userId, array $paymentData, ?string $fallbackHolder = null): ?array
    {
        $card = is_array($paymentData['card'] ?? null) ? $paymentData['card'] : [];
        $token = (string) ($card['token'] ?? $paymentData['token'] ?? '');
        if ($token === '') {
            return null;
        }

        if (RepositoryFactory::paymentMethods()->tokenExistsForUser($userId, $token)) {
            return RepositoryFactory::paymentMethods()->findDefaultByUserId($userId);
        }

        $last4 = (string) ($card['last_4digits'] ?? $card['last4'] ?? '');
        if ($last4 === '' && !empty($card['first_6digits'])) {
            $last4 = '0000';
        }
        if ($last4 === '') {
            return null;
        }

        $expiry = (string) ($card['expiry'] ?? '');
        $expMonth = '01';
        $expYear = (string) date('Y');
        if (preg_match('/^(\d{2})\/(\d{2,4})$/', $expiry, $matches)) {
            $expMonth = $matches[1];
            $expYear = $this->normalizeExpiryYear($matches[2]);
        }

        return RepositoryFactory::paymentMethods()->create($userId, [
            'flutterwave_token' => $token,
            'card_brand' => isset($card['type']) ? (string) $card['type'] : null,
            'card_last4' => substr($last4, -4),
            'card_exp_month' => str_pad($expMonth, 2, '0', STR_PAD_LEFT),
            'card_exp_year' => $expYear,
            'card_holder' => $fallbackHolder ?? (isset($card['name']) ? (string) $card['name'] : null),
            'is_default' => true,
        ]);
    }

    private function normalizeExpiryYear(string $year): string
    {
        $year = preg_replace('/\D+/', '', $year) ?? '';
        if (strlen($year) === 2) {
            return '20' . $year;
        }

        return $year;
    }

    private function generateCardTxRef(string $userId): string
    {
        $userPart = substr(str_replace('-', '', $userId), 0, 8);

        return 'lb_card_' . $userPart . '_' . time() . '_' . bin2hex(random_bytes(3));
    }
}
