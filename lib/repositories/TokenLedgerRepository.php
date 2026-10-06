<?php

declare(strict_types=1);

final class TokenLedgerRepository extends BaseRepository
{
    /**
     * @param array<string, mixed>|null $meta
     */
    public function create(
        string $userId,
        int $delta,
        int $balanceAfter,
        string $reason,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?array $meta = null
    ): void {
        $this->execute(
            'INSERT INTO user_token_ledger
             (id, user_id, delta, balance_after, reason, reference_type, reference_id, meta)
             VALUES
             (:id, :user_id, :delta, :balance_after, :reason, :reference_type, :reference_id, :meta)',
            [
                'id' => Uuid::v4(),
                'user_id' => $userId,
                'delta' => $delta,
                'balance_after' => max(0, $balanceAfter),
                'reason' => $reason,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'meta' => $meta !== null ? json_encode($meta, JSON_THROW_ON_ERROR) : null,
            ]
        );
    }

    public function findByReference(string $userId, string $reason, string $referenceId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM user_token_ledger
             WHERE user_id = :user_id AND reason = :reason AND reference_id = :reference_id
             LIMIT 1',
            ['user_id' => $userId, 'reason' => $reason, 'reference_id' => $referenceId]
        );
    }

    public function existsByReference(string $userId, string $reason, string $referenceId): bool
    {
        return $this->findByReference($userId, $reason, $referenceId) !== null;
    }

    /** @return list<array<string, mixed>> */
    public function listByUserId(string $userId, int $limit = 20): array
    {
        return $this->fetchAll(
            'SELECT * FROM user_token_ledger WHERE user_id = :user_id ORDER BY created_at DESC LIMIT ' . max(1, min(100, $limit)),
            ['user_id' => $userId]
        );
    }
}
