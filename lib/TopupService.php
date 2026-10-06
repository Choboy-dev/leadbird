<?php

declare(strict_types=1);

final class TopupService
{
    /**
     * @return array{link: string, tx_ref: string}
     */
    public function initiateCheckout(string $userId, string $packageSlug): array
    {
        $packages = token_topup_packages();
        if (!isset($packages[$packageSlug])) {
            throw new InvalidArgumentException('Invalid top-up package.');
        }

        $package = $packages[$packageSlug];
        $user = RepositoryFactory::users()->findById($userId);
        if ($user === null) {
            throw new RuntimeException('User not found.');
        }

        if (!(new SubscriptionService())->hasActiveSubscription($userId)) {
            throw new RuntimeException('An active subscription is required before topping up tokens.');
        }

        $pricing = (new CheckoutPricing())->forTopup($package, $userId);
        $txRef = $this->generateTxRef($userId, $packageSlug);
        RepositoryFactory::tokenTopups()->createPending(
            $userId,
            $packageSlug,
            (int) $package['tokens'],
            (float) $pricing['amount'],
            $txRef,
            (string) $pricing['currency']
        );

        $chargeAmount = (string) $pricing['amount'];
        if ($pricing['currency'] === 'USD' && str_contains($chargeAmount, '.')) {
            $chargeAmount = number_format((float) $pricing['amount'], 2, '.', '');
        }

        return (new FlutterwaveService())->initiateCheckout(
            $txRef,
            $chargeAmount,
            (string) $pricing['currency'],
            (string) ($user['email'] ?? ''),
            trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))),
            (string) $package['name'] . ' — tokens',
            app_route('billing/topup-callback'),
            null,
            'Token top-up'
        );
    }

    /**
     * @return array{success: bool, message: string, tokens?: int}
     */
    public function completeFromCallback(string $txRef, string $transactionId): array
    {
        $order = RepositoryFactory::tokenTopups()->findByTxRef($txRef);
        if ($order === null) {
            return ['success' => false, 'message' => 'Top-up order not found.'];
        }

        if (($order['status'] ?? '') === 'paid') {
            return [
                'success' => true,
                'message' => 'Top-up already applied.',
                'tokens' => (int) ($order['tokens'] ?? 0),
            ];
        }

        $flutterwave = new FlutterwaveService();
        $verification = $flutterwave->verifyTransaction($transactionId);
        $data = is_array($verification['data'] ?? null) ? $verification['data'] : [];
        $status = strtolower((string) ($data['status'] ?? ''));
        $verifiedRef = (string) ($data['tx_ref'] ?? '');

        if ($status !== 'successful' || $verifiedRef !== $txRef) {
            return ['success' => false, 'message' => 'Payment could not be verified.'];
        }

        $currency = (string) ($order['currency'] ?? 'USD');
        try {
            assert_flutterwave_payment_matches(
                $data,
                (float) ($order['amount'] ?? 0),
                $currency,
                money_payment_tolerance($currency)
            );
        } catch (RuntimeException $e) {
            error_log('[Topup] ' . $e->getMessage() . ' tx_ref=' . $txRef);
            return ['success' => false, 'message' => 'Payment amount could not be confirmed.'];
        }

        RepositoryFactory::tokenTopups()->markPaid((string) $order['id'], $transactionId);

        $tokens = (int) ($order['tokens'] ?? 0);
        (new TokenService())->grant(
            (string) $order['user_id'],
            $tokens,
            'topup',
            'topup_order',
            (string) $order['id'],
            ['package_slug' => $order['package_slug'] ?? '', 'tx_ref' => $txRef]
        );

        return [
            'success' => true,
            'message' => sprintf('Added %d tokens to your account.', $tokens),
            'tokens' => $tokens,
        ];
    }

    private function generateTxRef(string $userId, string $packageSlug): string
    {
        $userPart = substr(str_replace('-', '', $userId), 0, 8);

        return 'lb_topup_' . $userPart . '_' . $packageSlug . '_' . time() . '_' . bin2hex(random_bytes(3));
    }
}
