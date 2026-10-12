<?php

declare(strict_types=1);

/**
 * Bank verification and withdrawals for Lead Bird partners.
 *
 * A withdrawal is a request for the partner's full available balance. An admin approves it
 * before any money leaves, and Flutterwave's transfer webhook confirms the final result.
 */
final class PayoutService
{
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REJECTED = 'rejected';

    /**
     * @return array{ok: bool, account_name?: string, error?: string}
     */
    public function verifyBankAccount(string $bankCode, string $accountNumber): array
    {
        try {
            $resolved = (new FlutterwaveService())->resolveBankAccount($accountNumber, $bankCode);
        } catch (Throwable $e) {
            error_log('[Payout] resolve: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Could not verify that account. Check the details and try again.'];
        }

        if ($resolved === null) {
            return ['ok' => false, 'error' => 'Account could not be verified.'];
        }

        return ['ok' => true, 'account_name' => $resolved['account_name'], 'account_number' => $resolved['account_number']];
    }

    /**
     * @param array<string, mixed> $affiliate
     * @return array{ok: bool, payout?: array<string, mixed>, amount_usd?: float, error?: string}
     */
    public function requestAffiliateWithdrawal(array $affiliate): array
    {
        $affiliateId = (string) ($affiliate['id'] ?? '');
        if ($affiliateId === '') {
            return ['ok' => false, 'error' => 'Partner account not found.'];
        }

        $available = (new AffiliateService())->withdrawableBalance($affiliateId);
        if ($available < AffiliateService::MIN_WITHDRAW_USD) {
            return ['ok' => false, 'error' => 'Minimum withdrawal is ' . money_format(AffiliateService::MIN_WITHDRAW_USD) . '.'];
        }

        $bankCode = trim((string) ($affiliate['bank_code'] ?? ''));
        $accountNumber = trim((string) ($affiliate['account_number'] ?? ''));
        $accountName = trim((string) ($affiliate['account_name'] ?? ''));
        if ($bankCode === '' || $accountNumber === '' || $accountName === '') {
            return ['ok' => false, 'error' => 'Save a verified bank account before withdrawing.'];
        }

        $payouts = RepositoryFactory::affiliatePayouts();
        $commissions = RepositoryFactory::affiliateCommissions();
        $payout = $payouts->create([
            'affiliate_id' => $affiliateId,
            'amount' => 0,
            'currency' => 'NGN',
            'bank_code' => $bankCode,
            'bank_name' => $affiliate['bank_name'] ?? null,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'flw_reference' => 'lb_apayout_' . substr(str_replace('-', '', $affiliateId), 0, 8) . '_' . time(),
            'status' => self::STATUS_PENDING_REVIEW,
        ]);
        $payoutId = (string) $payout['id'];

        $commissions->reserveAvailableForPayout($affiliateId, $payoutId);
        $amountUsd = $this->reservedUsd($payoutId);
        if ($amountUsd < AffiliateService::MIN_WITHDRAW_USD) {
            $commissions->releasePayout($payoutId);
            $payouts->delete($payoutId);

            return ['ok' => false, 'error' => 'Your balance changed. Please refresh and try again.'];
        }

        $quote = (new FxRateService())->quoteForPayout();
        $payouts->setAmounts($payoutId, (float) round($amountUsd * $quote['rate']), $amountUsd);

        return [
            'ok' => true,
            'payout' => $payouts->findById($payoutId) ?? $payout,
            'amount_usd' => $amountUsd,
        ];
    }

    /**
     * Send the money for a reviewed payout. The final result arrives through the transfer webhook.
     *
     * @return array{ok: bool, error?: string}
     */
    public function approve(string $payoutId, string $adminUserId): array
    {
        $payouts = RepositoryFactory::affiliatePayouts();
        $payout = $payouts->findById($payoutId);
        if ($payout === null || (string) $payout['status'] !== self::STATUS_PENDING_REVIEW) {
            return ['ok' => false, 'error' => 'This payout is not waiting for review.'];
        }

        $amountUsd = $this->reservedUsd($payoutId);
        $quote = (new FxRateService())->quoteForPayout();
        $amountNgn = (float) round($amountUsd * $quote['rate']);
        if ($amountNgn <= 0) {
            return ['ok' => false, 'error' => 'Could not calculate the naira amount. Try again.'];
        }

        if (!$payouts->transition($payoutId, self::STATUS_PENDING_REVIEW, self::STATUS_PROCESSING, $adminUserId)) {
            return ['ok' => false, 'error' => 'This payout was already handled.'];
        }
        $payouts->setAmounts($payoutId, $amountNgn, $amountUsd);

        try {
            $response = (new FlutterwaveService())->createTransfer(
                (string) $payout['bank_code'],
                (string) $payout['account_number'],
                $amountNgn,
                'Lead Bird partner payout',
                (string) $payout['flw_reference'],
                null,
                'NGN'
            );
        } catch (Throwable $e) {
            error_log('[Payout] transfer ' . $payoutId . ': ' . $e->getMessage());
            $this->fail($payoutId, self::STATUS_PROCESSING, 'Flutterwave rejected the transfer: ' . $e->getMessage());

            return ['ok' => false, 'error' => 'Flutterwave rejected the transfer. The partner keeps their balance. ' . $e->getMessage()];
        }

        $transferId = isset($response['data']['id']) ? (string) $response['data']['id'] : null;
        if ($transferId !== null) {
            $payouts->markStatus($payoutId, self::STATUS_PROCESSING, $transferId);
        }

        return ['ok' => true];
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public function reject(string $payoutId, string $adminUserId, string $reason): array
    {
        $reason = trim($reason) !== '' ? trim($reason) : 'Rejected by Lead Bird';
        if (!RepositoryFactory::affiliatePayouts()->transition($payoutId, self::STATUS_PENDING_REVIEW, self::STATUS_REJECTED, $adminUserId, $reason)) {
            return ['ok' => false, 'error' => 'This payout is not waiting for review.'];
        }
        RepositoryFactory::affiliateCommissions()->releasePayout($payoutId);

        return ['ok' => true];
    }

    /**
     * Handle Flutterwave's transfer.completed event. Returns false when the transfer is not a partner payout.
     *
     * @param array<string, mixed> $payload
     */
    public function handleTransferWebhook(array $payload): bool
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $reference = (string) ($data['reference'] ?? '');
        if (!str_starts_with($reference, 'lb_apayout_')) {
            return false;
        }

        $payouts = RepositoryFactory::affiliatePayouts();
        $payout = $payouts->findByReference($reference);
        if ($payout === null) {
            return true;
        }

        $verified = $this->verifiedTransferStatus($data);
        if ($verified === 'SUCCESSFUL') {
            if ($payouts->transition((string) $payout['id'], self::STATUS_PROCESSING, self::STATUS_SUCCESSFUL)) {
                RepositoryFactory::affiliateCommissions()->markPayoutPaid((string) $payout['id']);
            }
        } elseif ($verified === 'FAILED') {
            $message = trim((string) ($data['complete_message'] ?? '')) ?: 'Transfer failed';
            $this->fail((string) $payout['id'], self::STATUS_PROCESSING, $message);
        }

        return true;
    }

    /**
     * Confirm the webhook's status with Flutterwave before trusting it.
     *
     * @param array<string, mixed> $data
     */
    private function verifiedTransferStatus(array $data): string
    {
        $transferId = (string) ($data['id'] ?? '');
        if ($transferId !== '') {
            try {
                $fetched = (new FlutterwaveService())->fetchTransfer($transferId);
                $status = strtoupper((string) ($fetched['data']['status'] ?? ''));
                if ($status !== '') {
                    return $status;
                }
            } catch (Throwable $e) {
                error_log('[Payout] verify transfer ' . $transferId . ': ' . $e->getMessage());
            }
        }

        return strtoupper((string) ($data['status'] ?? ''));
    }

    private function fail(string $payoutId, string $from, string $reason): void
    {
        if (RepositoryFactory::affiliatePayouts()->transition($payoutId, $from, self::STATUS_FAILED, null, mb_substr($reason, 0, 255))) {
            RepositoryFactory::affiliateCommissions()->releasePayout($payoutId);
        }
    }

    private function reservedUsd(string $payoutId): float
    {
        $total = 0.0;
        foreach (RepositoryFactory::affiliateCommissions()->listByPayoutId($payoutId) as $row) {
            $total += money_to_usd((float) ($row['commission_amount'] ?? 0), (string) ($row['currency'] ?? 'USD'));
        }

        return round($total, 2);
    }
}
