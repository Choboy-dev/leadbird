<?php

declare(strict_types=1);

final class AffiliatePayoutRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM affiliate_payouts WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO affiliate_payouts (
                id, affiliate_id, amount, currency, bank_code, bank_name,
                account_number, account_name, flw_reference, status
            ) VALUES (
                :id, :affiliate_id, :amount, :currency, :bank_code, :bank_name,
                :account_number, :account_name, :flw_reference, :status
            )',
            [
                'id' => $id,
                'affiliate_id' => $data['affiliate_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'NGN',
                'bank_code' => $data['bank_code'],
                'bank_name' => $data['bank_name'] ?? null,
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
                'flw_reference' => $data['flw_reference'] ?? null,
                'status' => $data['status'] ?? 'pending',
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create payout');
    }

    public function markStatus(string $id, string $status, ?string $transferId = null, ?string $failure = null): void
    {
        $this->execute(
            'UPDATE affiliate_payouts SET
                status = :status,
                flw_transfer_id = COALESCE(:flw_transfer_id, flw_transfer_id),
                failure_reason = :failure_reason,
                updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'status' => $status,
                'flw_transfer_id' => $transferId,
                'failure_reason' => $failure,
            ]
        );
    }

    public function findByReference(string $reference): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM affiliate_payouts WHERE flw_reference = :reference LIMIT 1',
            ['reference' => $reference]
        );
    }

    public function setAmounts(string $id, float $amountNgn, float $amountUsd): void
    {
        $this->execute(
            'UPDATE affiliate_payouts SET amount = :amount, amount_usd = :amount_usd, updated_at = NOW() WHERE id = :id',
            ['id' => $id, 'amount' => $amountNgn, 'amount_usd' => $amountUsd]
        );
    }

    /**
     * Move a payout from one status to another only if it is still in the expected status.
     */
    public function transition(string $id, string $from, string $to, ?string $reviewedBy = null, ?string $failure = null): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE affiliate_payouts SET
                status = :to,
                failure_reason = COALESCE(:failure_reason, failure_reason),
                reviewed_by = COALESCE(:reviewed_by, reviewed_by),
                reviewed_at = IF(:reviewed_by_set IS NULL, reviewed_at, NOW()),
                updated_at = NOW()
             WHERE id = :id AND status = :from'
        );
        $stmt->execute([
            'id' => $id,
            'from' => $from,
            'to' => $to,
            'failure_reason' => $failure,
            'reviewed_by' => $reviewedBy,
            'reviewed_by_set' => $reviewedBy,
        ]);

        return $stmt->rowCount() === 1;
    }

    public function delete(string $id): void
    {
        $this->execute('DELETE FROM affiliate_payouts WHERE id = :id', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listForAdmin(int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT p.*, a.name AS affiliate_name, a.email AS affiliate_email, a.code AS affiliate_code
             FROM affiliate_payouts p
             INNER JOIN affiliates a ON a.id = p.affiliate_id
             ORDER BY FIELD(p.status, \'processing\', \'pending_review\') DESC, p.created_at DESC
             LIMIT ' . max(1, min(100, $limit))
        );
    }

    /** @return list<array<string, mixed>> */
    public function listByAffiliateId(string $affiliateId, int $limit = 30): array
    {
        return $this->fetchAll(
            'SELECT * FROM affiliate_payouts WHERE affiliate_id = :id ORDER BY created_at DESC LIMIT '
            . max(1, min(50, $limit)),
            ['id' => $affiliateId]
        );
    }

    public function sumSuccessful(string $affiliateId): float
    {
        $row = $this->fetchOne(
            'SELECT COALESCE(SUM(amount), 0) AS total FROM affiliate_payouts
             WHERE affiliate_id = :id AND status IN (\'successful\', \'pending\', \'processing\')',
            ['id' => $affiliateId]
        );

        return (float) ($row['total'] ?? 0);
    }
}
