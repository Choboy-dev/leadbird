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

    /**
     * Reserve every available commission for a payout request so it cannot be withdrawn twice.
     */
    public function reserveAvailableForPayout(string $affiliateId, string $payoutId): void
    {
        $this->execute(
            'UPDATE affiliate_commissions
             SET status = \'withdrawing\', payout_id = :payout_id, updated_at = NOW()
             WHERE affiliate_id = :affiliate_id AND status = \'available\'',
            ['affiliate_id' => $affiliateId, 'payout_id' => $payoutId]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listByPayoutId(string $payoutId): array
    {
        return $this->fetchAll(
            'SELECT * FROM affiliate_commissions WHERE payout_id = :payout_id',
            ['payout_id' => $payoutId]
        );
    }

    public function markPayoutPaid(string $payoutId): void
    {
        $this->execute(
            'UPDATE affiliate_commissions
             SET status = \'paid_out\', updated_at = NOW()
             WHERE payout_id = :payout_id AND status = \'withdrawing\'',
            ['payout_id' => $payoutId]
        );
    }

    /**
     * Return reserved commissions to the partner's balance after a rejected or failed payout.
     */
    public function releasePayout(string $payoutId): void
    {
        $this->execute(
            'UPDATE affiliate_commissions
             SET status = \'available\', payout_id = NULL, updated_at = NOW()
             WHERE payout_id = :payout_id AND status = \'withdrawing\'',
            ['payout_id' => $payoutId]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listOpenForAdmin(int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT ac.*, a.name AS affiliate_name, a.code AS affiliate_code, u.email AS referred_email
             FROM affiliate_commissions ac
             INNER JOIN affiliates a ON a.id = ac.affiliate_id
             LEFT JOIN users u ON u.id = ac.referred_user_id
             WHERE ac.status IN (\'pending\', \'available\')
             ORDER BY ac.created_at DESC
             LIMIT ' . max(1, min(100, $limit))
        );
    }

    public function clawback(string $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE affiliate_commissions
             SET status = \'clawed_back\', clawed_back_at = NOW(), updated_at = NOW()
             WHERE id = :id AND status IN (\'pending\', \'available\')'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() === 1;
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
