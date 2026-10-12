<?php

declare(strict_types=1);

final class FlutterwaveService
{
    private const API_BASE = 'https://api.flutterwave.com/v3';

    /**
     * @return array{link: string, tx_ref: string}
     */
    public function initiateCheckout(
        string $txRef,
        int|float|string $amount,
        string $currency,
        string $customerEmail,
        string $customerName,
        string $planName,
        string $redirectUrl,
        ?string $paymentPlanId = null,
        ?string $checkoutDescription = null,
        ?string $paymentOptions = null
    ): array {
        $secretKey = $this->secretKey();
        if ($secretKey === '') {
            throw new RuntimeException('FLUTTERWAVE_SECRET_KEY is not configured.');
        }

        $payload = [
            'tx_ref' => $txRef,
            'amount' => (string) $amount,
            'currency' => $currency,
            'redirect_url' => $redirectUrl,
            'payment_options' => $paymentOptions !== null && $paymentOptions !== ''
                ? $paymentOptions
                : 'card,banktransfer,ussd',
            'customer' => [
                'email' => $customerEmail,
                'name' => $customerName !== '' ? $customerName : $customerEmail,
            ],
            'customizations' => [
                'title' => app_config()['brand_name'],
                'description' => $checkoutDescription ?? ($planName . ' subscription'),
            ],
        ];

        if ($paymentPlanId !== null && $paymentPlanId !== '') {
            $payload['payment_plan'] = (int) $paymentPlanId;
        }

        $response = $this->request('POST', self::API_BASE . '/payments', $payload, $secretKey);
        $link = (string) ($response['data']['link'] ?? '');

        if ($link === '') {
            throw new RuntimeException('Flutterwave did not return a checkout link.');
        }

        return ['link' => $link, 'tx_ref' => $txRef];
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $transactionId): array
    {
        $secretKey = $this->secretKey();
        if ($secretKey === '') {
            throw new RuntimeException('FLUTTERWAVE_SECRET_KEY is not configured.');
        }

        return $this->request('GET', self::API_BASE . '/transactions/' . rawurlencode($transactionId) . '/verify', null, $secretKey);
    }

    public function verifyWebhookHash(?string $hash): bool
    {
        $secretHash = app_config()['flutterwave']['secret_hash'] ?? '';
        if ($secretHash === '') {
            return false;
        }

        return is_string($hash) && hash_equals($secretHash, $hash);
    }

    public function cancelSubscription(string $subscriptionId): void
    {
        $secretKey = $this->secretKey();
        if ($secretKey === '') {
            throw new RuntimeException('FLUTTERWAVE_SECRET_KEY is not configured.');
        }

        $this->request(
            'PUT',
            self::API_BASE . '/subscriptions/' . rawurlencode($subscriptionId) . '/cancel',
            null,
            $secretKey
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function tokenizedCharge(
        string $token,
        string $email,
        int|float|string $amount,
        string $currency,
        string $txRef,
        string $narration,
        ?array $billingProfile = null
    ): array {
        $secretKey = $this->secretKey();
        if ($secretKey === '') {
            throw new RuntimeException('FLUTTERWAVE_SECRET_KEY is not configured.');
        }

        $payload = [
            'token' => $token,
            'email' => $email,
            'amount' => $amount,
            'currency' => $currency,
            'tx_ref' => $txRef,
            'narration' => $narration,
        ];

        if ($billingProfile !== null) {
            $payload = array_merge($payload, (new BillingProfileService())->flutterwaveTokenizedFields($billingProfile));
        }

        return $this->request(
            'POST',
            self::API_BASE . '/tokenized-charges',
            $payload,
            $secretKey
        );
    }

    public function isConfigured(): bool
    {
        return $this->secretKey() !== '';
    }

    /**
     * Create a Payout Subaccount (wallet) for an affiliate or merchant.
     *
     * @return array<string, mixed>
     */
    public function createPayoutSubaccount(
        string $accountName,
        string $email,
        string $mobileNumber,
        string $country = 'NG'
    ): array {
        $secretKey = $this->secretKey();
        if ($secretKey === '') {
            throw new RuntimeException('FLUTTERWAVE_SECRET_KEY is not configured.');
        }

        $payload = [
            'account_name' => $accountName,
            'email' => $email,
            'mobilenumber' => preg_replace('/\D+/', '', $mobileNumber) ?: '08000000000',
            'country' => $country,
        ];

        return $this->request('POST', self::API_BASE . '/payout-subaccounts', $payload, $secretKey);
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function listBanks(string $country = 'NG'): array
    {
        $secretKey = $this->secretKey();
        if ($secretKey === '') {
            return [];
        }

        try {
            $response = $this->request(
                'GET',
                self::API_BASE . '/banks/' . rawurlencode($country),
                null,
                $secretKey
            );
        } catch (Throwable $e) {
            error_log('[Flutterwave] listBanks: ' . $e->getMessage());
            return [];
        }

        $banks = [];
        foreach ($response['data'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            if ($code === '' || $name === '') {
                continue;
            }
            $banks[] = ['code' => $code, 'name' => $name];
        }

        return $banks;
    }

    /**
     * @return array{account_number: string, account_name: string}|null
     */
    public function resolveBankAccount(string $accountNumber, string $bankCode): ?array
    {
        $secretKey = $this->secretKey();
        if ($secretKey === '') {
            throw new RuntimeException('FLUTTERWAVE_SECRET_KEY is not configured.');
        }

        $query = http_build_query([
            'account_number' => preg_replace('/\D+/', '', $accountNumber),
            'account_bank' => $bankCode,
        ]);

        $response = $this->request(
            'GET',
            self::API_BASE . '/accounts/resolve?' . $query,
            null,
            $secretKey
        );

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $number = trim((string) ($data['account_number'] ?? ''));
        $name = trim((string) ($data['account_name'] ?? ''));
        if ($number === '' || $name === '') {
            return null;
        }

        return ['account_number' => $number, 'account_name' => $name];
    }

    /**
     * Transfer from main balance or a PSA wallet (debit_subaccount = PSA id / barter id).
     *
     * @return array<string, mixed>
     */
    public function createTransfer(
        string $bankCode,
        string $accountNumber,
        float $amount,
        string $narration,
        string $reference,
        ?string $debitSubaccount = null,
        string $currency = 'NGN'
    ): array {
        $secretKey = $this->secretKey();
        if ($secretKey === '') {
            throw new RuntimeException('FLUTTERWAVE_SECRET_KEY is not configured.');
        }

        $payload = [
            'account_bank' => $bankCode,
            'account_number' => preg_replace('/\D+/', '', $accountNumber),
            'amount' => (int) round($amount),
            'narration' => $narration,
            'currency' => $currency,
            'reference' => $reference,
            'debit_currency' => $currency,
        ];

        if ($debitSubaccount !== null && $debitSubaccount !== '') {
            $payload['debit_subaccount'] = $debitSubaccount;
        }

        return $this->request('POST', self::API_BASE . '/transfers', $payload, $secretKey);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchTransfer(string $transferId): array
    {
        $secretKey = $this->secretKey();
        if ($secretKey === '') {
            throw new RuntimeException('FLUTTERWAVE_SECRET_KEY is not configured.');
        }

        return $this->request('GET', self::API_BASE . '/transfers/' . rawurlencode($transferId), null, $secretKey);
    }

    /**
     * Wallet-to-wallet fund into a PSA (when supported). Falls back to logging for ops.
     *
     * @return array<string, mixed>
     */
    public function fundPayoutSubaccount(string $barterId, float $amount, string $reference, string $currency = 'NGN'): array
    {
        $secretKey = $this->secretKey();
        if ($secretKey === '') {
            throw new RuntimeException('FLUTTERWAVE_SECRET_KEY is not configured.');
        }

        $payload = [
            'currency' => $currency,
            'amount' => (int) round($amount),
            'reference' => $reference,
        ];

        return $this->request(
            'POST',
            self::API_BASE . '/payout-subaccounts/' . rawurlencode($barterId) . '/fund',
            $payload,
            $secretKey
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayoutSubaccount(string $accountReference): array
    {
        $secretKey = $this->secretKey();
        if ($secretKey === '') {
            throw new RuntimeException('FLUTTERWAVE_SECRET_KEY is not configured.');
        }

        return $this->request(
            'GET',
            self::API_BASE . '/payout-subaccounts/' . rawurlencode($accountReference),
            null,
            $secretKey
        );
    }

    private function secretKey(): string
    {
        return (string) (app_config()['flutterwave']['secret_key'] ?? '');
    }

    /**
     * Live USD → NGN quote for affiliate payouts and checkout conversion.
     */
    public function fetchUsdToNgnRate(): ?float
    {
        $secretKey = $this->secretKey();
        if ($secretKey === '') {
            return null;
        }

        try {
            $response = $this->request(
                'GET',
                self::API_BASE . '/rates?from=USD&to=NGN&amount=1',
                null,
                $secretKey
            );
            $data = is_array($response['data'] ?? null) ? $response['data'] : [];
            if (isset($data[0]['rate'])) {
                return (float) $data[0]['rate'];
            }
            if (isset($data['rate'])) {
                return (float) $data['rate'];
            }
        } catch (Throwable $e) {
            error_log('[Flutterwave] rates: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $url, ?array $body, string $secretKey): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL');
        }

        $headers = [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 45,
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Flutterwave request failed: ' . $error);
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid Flutterwave response.');
        }

        if ($status < 200 || $status >= 300 || ($decoded['status'] ?? '') !== 'success') {
            $message = is_string($decoded['message'] ?? null) ? $decoded['message'] : 'Unknown Flutterwave error';
            throw new RuntimeException('Flutterwave API error (' . $status . '): ' . $message);
        }

        return $decoded;
    }
}
