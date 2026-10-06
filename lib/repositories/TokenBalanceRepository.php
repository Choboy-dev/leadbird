<?php

declare(strict_types=1);

final class TokenBalanceRepository extends BaseRepository
{
    public function findByUserId(string $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM user_token_balances WHERE user_id = :user_id LIMIT 1',
            ['user_id' => $userId]
        );
    }

    public function ensureRow(string $userId): array
    {
        $row = $this->findByUserId($userId);
        if ($row !== null) {
            return $row;
        }

        $this->execute(
            'INSERT INTO user_token_balances (user_id, balance) VALUES (:user_id, 0)',
            ['user_id' => $userId]
        );

        return $this->findByUserId($userId) ?? throw new RuntimeException('Failed to create token balance row.');
    }

    public function adjustBalance(string $userId, int $delta): int
    {
        $this->ensureRow($userId);
        $this->execute(
            'UPDATE user_token_balances
             SET balance = GREATEST(0, CAST(balance AS SIGNED) + :delta), updated_at = NOW()
             WHERE user_id = :user_id',
            ['user_id' => $userId, 'delta' => $delta]
        );

        $row = $this->findByUserId($userId);

        return (int) ($row['balance'] ?? 0);
    }

    public function setBalance(string $userId, int $balance): int
    {
        $this->ensureRow($userId);
        $balance = max(0, $balance);
        $this->execute(
            'UPDATE user_token_balances
             SET balance = :balance, updated_at = NOW()
             WHERE user_id = :user_id',
            ['user_id' => $userId, 'balance' => $balance]
        );

        return $balance;
    }
}
