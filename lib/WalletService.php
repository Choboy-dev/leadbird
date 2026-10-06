<?php

declare(strict_types=1);

/**
 * Flutterwave PSA wallets for affiliates (Refer & Earn).
 */
final class WalletService
{
    /**
     * Ensure affiliate has a PSA wallet when phone is present.
     *
     * @param array<string, mixed> $affiliate
     * @return array{ok: bool, affiliate?: array<string, mixed>, error?: string}
     */
    public function ensureAffiliateWallet(array $affiliate): array
    {
        if (!empty($affiliate['flw_barter_id']) || !empty($affiliate['flw_account_reference'])) {
            return ['ok' => true, 'affiliate' => $affiliate];
        }

        $phone = trim((string) ($affiliate['phone'] ?? ''));
        if ($phone === '') {
            return ['ok' => false, 'error' => 'Add a phone number before creating your wallet.'];
        }

        $flutterwave = new FlutterwaveService();
        if (!$flutterwave->isConfigured()) {
            return ['ok' => false, 'error' => 'Payouts are not configured yet.'];
        }

        try {
            $response = $flutterwave->createPayoutSubaccount(
                (string) ($affiliate['name'] ?? 'Lead Bird Affiliate'),
                (string) ($affiliate['email'] ?? ''),
                $phone
            );
        } catch (Throwable $e) {
            error_log('[Wallet] affiliate PSA: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Could not create wallet right now. You can still request bank payouts from available earnings.'];
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        RepositoryFactory::affiliates()->update((string) $affiliate['id'], [
            'flw_psa_id' => isset($data['id']) ? (string) $data['id'] : null,
            'flw_barter_id' => (string) ($data['barter_id'] ?? ''),
            'flw_account_reference' => (string) ($data['account_reference'] ?? ''),
            'kyc_status' => 'wallet_ready',
        ]);

        $fresh = RepositoryFactory::affiliates()->findById((string) $affiliate['id']);

        return ['ok' => true, 'affiliate' => $fresh ?? $affiliate];
    }

    /**
     * @param array<string, mixed> $commission
     */
    public function fundAffiliateCommission(array $commission): bool
    {
        $id = (string) ($commission['id'] ?? '');
        $affiliateId = (string) ($commission['affiliate_id'] ?? '');
        $amount = (float) ($commission['commission_amount'] ?? 0);
        if ($id === '' || $affiliateId === '' || $amount <= 0) {
            return false;
        }

        RepositoryFactory::affiliateCommissions()->markFunding($id);

        $affiliate = RepositoryFactory::affiliates()->findById($affiliateId);
        if ($affiliate === null) {
            return false;
        }

        $barterId = trim((string) ($affiliate['flw_barter_id'] ?? ''));
        if ($barterId === '') {
            // No PSA yet — leave as available for bank withdraw from main via PayoutService.
            Database::connection()->prepare(
                'UPDATE affiliate_commissions SET status = \'available\', updated_at = NOW() WHERE id = :id'
            )->execute(['id' => $id]);
            return false;
        }

        $flutterwave = new FlutterwaveService();
        $reference = 'lb_afund_' . substr(str_replace('-', '', $id), 0, 12) . '_' . time();

        try {
            $flutterwave->fundPayoutSubaccount($barterId, $amount, $reference);
            RepositoryFactory::affiliateCommissions()->markFunded($id);
            return true;
        } catch (Throwable $e) {
            error_log('[Wallet] fund commission: ' . $e->getMessage());
            Database::connection()->prepare(
                'UPDATE affiliate_commissions SET status = \'available\', updated_at = NOW() WHERE id = :id'
            )->execute(['id' => $id]);
            return false;
        }
    }
}
