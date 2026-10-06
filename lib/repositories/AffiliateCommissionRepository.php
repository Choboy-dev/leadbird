<?php

declare(strict_types=1);

final class AffiliateCommissionRepository extends BaseRepository
{
    public function findBySubscriptionId(string $subscriptionId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM affiliate_commissions WHERE subscription_id = :subscription_id LIMIT 1',
            ['subscription_id' => $subscriptionId]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO affiliate_commissions (
                id, affiliate_id, referred_user_id, subscription_id, tx_ref, plan_slug,
                gross_amount, commission_amount, currency, status, hold_until
            ) VALUES (
                :id, :affiliate_id, :referred_user_id, :subscription_id, :tx_ref, :plan_slug,
                :gross_amount, :commission_amount, :currency, :status, :hold_until
            )',
            [
                'id' => $id,
                'affiliate_id' => $data['affiliate_id'],
                'referred_user_id' => $data['referred_user_id'],
                'subscription_id' => $data['subscription_id'],
                'tx_ref' => $data['tx_ref'],
                'plan_slug' => $data['plan_slug'],
                'gross_amount' => $data['gross_amount'],
                'commission_amount' => $data['commission_amount'],
                'currency' => $data['currency'] ?? 'NGN',
                'status' => $data['status'] ?? 'pending',
                'hold_until' => $data['hold_until'] ?? null,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create commission');
    }

    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM affiliate_commissions WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listByAffiliateId(string $affiliateId, int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT * FROM affiliate_commissions WHERE affiliate_id = :id ORDER BY created_at DESC LIMIT '
            . max(1, min(100, $limit)),
            ['id' => $affiliateId]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listReadyForRelease(): array
    {
        return $this->fetchAll(
            'SELECT * FROM affiliate_commissions
             WHERE status = \'pending\' AND hold_until IS NOT NULL AND hold_until <= NOW()
             ORDER BY hold_until ASC LIMIT 100'
        );
    }

    public function markAvailable(string $id): void
    {
        $this->execute(
            'UPDATE affiliate_commissions
             SET status = \'available\', available_at = NOW(), updated_at = NOW()
             WHERE id = :id AND status = \'pending\'',
            ['id' => $id]
        );
    }

    public function markFunded(string $id): void
    {
        // Money moved into the affiliate PSA; still withdrawable until bank payout.
        $this->execute(
            'UPDATE affiliate_commissions
             SET status = \'available\', funded_at = NOW(), updated_at = NOW()
             WHERE id = :id AND status IN (\'available\', \'funding\')',
            ['id' => $id]
        );
    }

    public function markPaidOut(string $id): void
    {
        $this->execute(
            'UPDATE affiliate_commissions
             SET status = \'paid_out\', updated_at = NOW()
             WHERE id = :id AND status = \'available\'',
            ['id' => $id]
        );
    }

    public function markFunding(string $id): void
    {
        $this->execute(
            'UPDATE affiliate_commissions SET status = \'funding\', updated_at = NOW()
             WHERE id = :id AND status = \'available\'',
            ['id' => $id]
        );
    }

    public function clawback(string $id): void
    {
        $this->execute(
            'UPDATE affiliate_commissions
             SET status = \'clawed_back\', clawed_back_at = NOW(), updated_at = NOW()
             WHERE id = :id AND status IN (\'pending\', \'available\')',
            ['id' => $id]
        );
    }

    public function sumByStatus(string $affiliateId, string $status): float
    {
        $row = $this->fetchOne(
            'SELECT COALESCE(SUM(commission_amount), 0) AS total
             FROM affiliate_commissions WHERE affiliate_id = :id AND status = :status',
            ['id' => $affiliateId, 'status' => $status]
        );

        return (float) ($row['total'] ?? 0);
    }

    public function sumPaid(string $affiliateId): float
    {
        $row = $this->fetchOne(
            'SELECT COALESCE(SUM(commission_amount), 0) AS total
             FROM affiliate_commissions WHERE affiliate_id = :id AND status = \'paid_out\'',
            ['id' => $affiliateId]
        );

        return (float) ($row['total'] ?? 0);
    }

    public function countPaidConversions(string $affiliateId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM affiliate_commissions
             WHERE affiliate_id = :id AND status != \'clawed_back\'',
            ['id' => $affiliateId]
        );

        return (int) ($row['cnt'] ?? 0);
    }
}
