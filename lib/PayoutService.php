<?php

declare(strict_types=1);

/**
 * Bank verification and withdrawals for Refer & Earn affiliates.
 */
final class PayoutService
{
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
     * @return array{ok: bool, payout?: array<string, mixed>, error?: string}
     */
    public function withdrawAffiliate(array $affiliate, float $amountUsd): array
    {
        $affiliateId = (string) ($affiliate['id'] ?? '');
        $amountUsd = round($amountUsd, 2);
        if ($affiliateId === '') {
            return ['ok' => false, 'error' => 'Affiliate not found.'];
        }

        if ($amountUsd < AffiliateService::MIN_WITHDRAW_USD) {
            return ['ok' => false, 'error' => 'Minimum withdrawal is ' . money_format(AffiliateService::MIN_WITHDRAW_USD) . '.'];
        }

        $available = (new AffiliateService())->withdrawableBalance($affiliateId);
        if ($amountUsd > $available + 0.001) {
            return ['ok' => false, 'error' => 'You only have ' . money_format($available) . ' available to withdraw.'];
        }

        $bankCode = trim((string) ($affiliate['bank_code'] ?? ''));
        $accountNumber = trim((string) ($affiliate['account_number'] ?? ''));
        $accountName = trim((string) ($affiliate['account_name'] ?? ''));
        if ($bankCode === '' || $accountNumber === '' || $accountName === '') {
            return ['ok' => false, 'error' => 'Save a verified bank account before withdrawing.'];
        }

        $quote = (new FxRateService())->quoteForPayout();
        $ngnAmount = (int) round($amountUsd * $quote['rate']);
        if ($ngnAmount <= 0) {
            return ['ok' => false, 'error' => 'Could not calculate a payout amount. Please try again.'];
        }

        $reference = 'lb_apayout_' . substr(str_replace('-', '', $affiliateId), 0, 8) . '_' . time();
        $payout = RepositoryFactory::affiliatePayouts()->create([
            'affiliate_id' => $affiliateId,
            'amount' => (float) $ngnAmount,
            'currency' => 'NGN',
            'bank_code' => $bankCode,
            'bank_name' => $affiliate['bank_name'] ?? null,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'flw_reference' => $reference,
            'status' => 'processing',
        ]);

        $this->consumeAffiliateAvailable($affiliateId, $amountUsd);

        $debitSubaccount = trim((string) ($affiliate['flw_barter_id'] ?? '')) ?: null;
        $flutterwave = new FlutterwaveService();

        try {
            $response = $flutterwave->createTransfer(
                $bankCode,
                $accountNumber,
                (float) $ngnAmount,
                'Lead Bird affiliate payout',
                $reference,
                $debitSubaccount,
                'NGN'
            );
            $transferId = isset($response['data']['id']) ? (string) $response['data']['id'] : null;
            RepositoryFactory::affiliatePayouts()->markStatus((string) $payout['id'], 'successful', $transferId);
            $payout = RepositoryFactory::affiliatePayouts()->findById((string) $payout['id']) ?? $payout;

            return [
                'ok' => true,
                'payout' => $payout,
                'amount_usd' => $amountUsd,
                'amount_ngn' => $ngnAmount,
                'fx_rate' => $quote['rate'],
            ];
        } catch (Throwable $e) {
            error_log('[Payout] affiliate transfer: ' . $e->getMessage());
            RepositoryFactory::affiliatePayouts()->markStatus((string) $payout['id'], 'failed', null, 'Transfer failed');
            Database::connection()->prepare(
                'UPDATE affiliate_commissions SET status = \'available\', updated_at = NOW()
                 WHERE affiliate_id = :aid AND status = \'paid_out\'
                   AND updated_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)'
            )->execute(['aid' => $affiliateId]);

            return ['ok' => false, 'error' => 'Withdrawal failed. Please try again or contact support.'];
        }
    }

    private function consumeAffiliateAvailable(string $affiliateId, float $amountUsd): void
    {
        $remaining = $amountUsd;
        $rows = RepositoryFactory::affiliateCommissions()->listByAffiliateId($affiliateId, 100);
        foreach ($rows as $row) {
            if ($remaining <= 0) {
                break;
            }
            if ((string) ($row['status'] ?? '') !== 'available') {
                continue;
            }
            $commissionAmount = money_to_usd(
                (float) ($row['commission_amount'] ?? 0),
                (string) ($row['currency'] ?? 'USD')
            );
            RepositoryFactory::affiliateCommissions()->markPaidOut((string) $row['id']);
            $remaining -= $commissionAmount;
        }
    }
}
