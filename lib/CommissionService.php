<?php

declare(strict_types=1);

/**
 * First-plan affiliate commissions with hold period.
 */
final class CommissionService
{
    /**
     * Create commission once when a subscription activates.
     *
     * @param array<string, mixed> $subscription
     */
    public function createForSubscription(array $subscription): ?array
    {
        $subscriptionId = (string) ($subscription['id'] ?? '');
        $userId = (string) ($subscription['user_id'] ?? '');
        $txRef = (string) ($subscription['flutterwave_tx_ref'] ?? '');
        if ($subscriptionId === '' || $userId === '' || $txRef === '') {
            return null;
        }

        if (RepositoryFactory::affiliateCommissions()->findBySubscriptionId($subscriptionId) !== null) {
            return null;
        }

        $user = RepositoryFactory::users()->findById($userId);
        $affiliateId = trim((string) ($user['referred_by_affiliate_id'] ?? ''));

        // Also honor checkout meta if set
        $meta = $subscription['meta'] ?? null;
        if (is_string($meta) && $meta !== '') {
            try {
                $meta = json_decode($meta, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable $e) {
                $meta = null;
            }
        }
        if (is_array($meta) && !empty($meta['affiliate_id'])) {
            $affiliateId = trim((string) $meta['affiliate_id']);
        }

        if ($affiliateId === '') {
            return null;
        }

        $affiliate = RepositoryFactory::affiliates()->findById($affiliateId);
        if ($affiliate === null || (string) ($affiliate['status'] ?? '') !== 'active') {
            return null;
        }

        if ((string) ($affiliate['user_id'] ?? '') === $userId) {
            return null;
        }

        // Only first successful subscription for this user pays commission once.
        $existingForUser = Database::connection()->prepare(
            'SELECT id FROM affiliate_commissions WHERE referred_user_id = :uid AND status != \'clawed_back\' LIMIT 1'
        );
        $existingForUser->execute(['uid' => $userId]);
        if ($existingForUser->fetch() !== false) {
            return null;
        }

        $gross = (float) ($subscription['amount'] ?? 0);
        if ($gross <= 0) {
            return null;
        }

        $currency = strtoupper((string) ($subscription['currency'] ?? 'USD'));
        $grossUsd = money_to_usd($gross, $currency);
        $commission = round($grossUsd * AffiliateService::COMMISSION_RATE, 2);
        $holdUntil = (new DateTimeImmutable('+' . AffiliateService::HOLD_DAYS . ' days'))->format('Y-m-d H:i:s');

        return RepositoryFactory::affiliateCommissions()->create([
            'affiliate_id' => $affiliateId,
            'referred_user_id' => $userId,
            'subscription_id' => $subscriptionId,
            'tx_ref' => $txRef,
            'plan_slug' => (string) ($subscription['plan_slug'] ?? ''),
            'gross_amount' => $grossUsd,
            'commission_amount' => $commission,
            'currency' => 'USD',
            'status' => 'pending',
            'hold_until' => $holdUntil,
        ]);
    }

    /**
     * @return array{released: int, funded: int, failed: int}
     */
    public function releaseHoldsAndFund(): array
    {
        $released = 0;
        $funded = 0;
        $failed = 0;
        $wallet = new WalletService();

        foreach (RepositoryFactory::affiliateCommissions()->listReadyForRelease() as $row) {
            RepositoryFactory::affiliateCommissions()->markAvailable((string) $row['id']);
            $released++;

            try {
                if ($wallet->fundAffiliateCommission($row)) {
                    $funded++;
                }
            } catch (Throwable $e) {
                $failed++;
                error_log('[Commission] fund failed ' . ($row['id'] ?? '') . ': ' . $e->getMessage());
            }
        }

        return ['released' => $released, 'funded' => $funded, 'failed' => $failed];
    }
}
