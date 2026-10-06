<?php

declare(strict_types=1);

final class TokenTopupRepository extends BaseRepository
{
    public function findByTxRef(string $txRef): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM token_topup_orders WHERE flutterwave_tx_ref = :tx_ref LIMIT 1',
            ['tx_ref' => $txRef]
        );
    }

    public function createPending(
        string $userId,
        string $packageSlug,
        int $tokens,
        float $amount,
        string $txRef,
        string $currency = 'USD'
    ): array {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO token_topup_orders
             (id, user_id, package_slug, tokens, amount, currency, flutterwave_tx_ref, status)
             VALUES
             (:id, :user_id, :package_slug, :tokens, :amount, :currency, :tx_ref, :status)',
            [
                'id' => $id,
                'user_id' => $userId,
                'package_slug' => $packageSlug,
                'tokens' => $tokens,
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'tx_ref' => $txRef,
                'status' => 'pending',
            ]
        );

        return $this->fetchOne('SELECT * FROM token_topup_orders WHERE id = :id LIMIT 1', ['id' => $id])
            ?? throw new RuntimeException('Failed to create top-up order.');
    }

    public function markPaid(string $id, string $transactionId): void
    {
        $this->execute(
            'UPDATE token_topup_orders SET
                status = :status,
                flutterwave_tx_id = :tx_id,
                paid_at = NOW(),
                updated_at = NOW()
             WHERE id = :id',
            ['id' => $id, 'status' => 'paid', 'tx_id' => $transactionId]
        );
    }
}
