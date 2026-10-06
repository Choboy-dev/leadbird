<?php

declare(strict_types=1);

final class SubscriptionRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM user_subscriptions WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function findByTxRef(string $txRef): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM user_subscriptions WHERE flutterwave_tx_ref = :tx_ref LIMIT 1',
            ['tx_ref' => $txRef]
        );
    }

    public function findActiveByUserId(string $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM user_subscriptions
             WHERE user_id = :user_id AND status = :status
             AND (
                current_period_end IS NULL
                OR current_period_end > NOW()
                OR (renewal_grace_until IS NOT NULL AND renewal_grace_until > NOW())
             )
             ORDER BY paid_at DESC, created_at DESC
             LIMIT 1',
            ['user_id' => $userId, 'status' => 'active']
        );
    }

    /**
     * @param array<string, mixed>|null $meta
     */
    public function createPending(
        string $userId,
        string $planSlug,
        float $amount,
        string $txRef,
        ?string $paymentPlanId = null,
        ?array $meta = null,
        string $currency = 'USD'
    ): array {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO user_subscriptions
             (id, user_id, plan_slug, status, amount, currency, flutterwave_tx_ref, flutterwave_payment_plan_id, meta)
             VALUES
             (:id, :user_id, :plan_slug, :status, :amount, :currency, :tx_ref, :payment_plan_id, :meta)',
            [
                'id' => $id,
                'user_id' => $userId,
                'plan_slug' => $planSlug,
                'status' => 'pending',
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'tx_ref' => $txRef,
                'payment_plan_id' => $paymentPlanId,
                'meta' => $meta !== null ? json_encode($meta, JSON_THROW_ON_ERROR) : null,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create subscription');
    }

    public function markActive(
        string $id,
        string $flutterwaveTxId,
        ?string $flutterwaveSubscriptionId,
        ?string $periodEnd
    ): void {
        $this->execute(
            'UPDATE user_subscriptions SET
                status = :status,
                flutterwave_tx_id = :tx_id,
                flutterwave_subscription_id = :subscription_id,
                current_period_start = NOW(),
                current_period_end = :period_end,
                paid_at = NOW(),
                updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'status' => 'active',
                'tx_id' => $flutterwaveTxId,
                'subscription_id' => $flutterwaveSubscriptionId,
                'period_end' => $periodEnd,
            ]
        );
    }

    public function cancelPendingForUser(string $userId): void
    {
        $this->execute(
            'UPDATE user_subscriptions SET status = :status, updated_at = NOW()
             WHERE user_id = :user_id AND status = :pending',
            [
                'user_id' => $userId,
                'status' => 'cancelled',
                'pending' => 'pending',
            ]
        );
    }

    public function markCancelled(string $id): void
    {
        $this->execute(
            'UPDATE user_subscriptions SET status = :status, updated_at = NOW() WHERE id = :id',
            ['id' => $id, 'status' => 'cancelled']
        );
    }

    /** @return list<array<string, mixed>> */
    public function listByUserId(string $userId): array
    {
        return $this->fetchAll(
            'SELECT * FROM user_subscriptions WHERE user_id = :user_id ORDER BY created_at DESC',
            ['user_id' => $userId]
        );
    }

    /** @return list<array<string, mixed>> */
    public function findDueForRenewal(): array
    {
        return $this->fetchAll(
            'SELECT * FROM user_subscriptions
             WHERE status = :status
             AND current_period_end IS NOT NULL
             AND current_period_end <= NOW()
             AND (next_renewal_attempt_at IS NULL OR next_renewal_attempt_at <= NOW())
             ORDER BY current_period_end ASC',
            ['status' => 'active']
        );
    }

    /** Active subscription whose last renewal failed and is waiting for a retry. */
    public function findPastDueByUserId(string $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM user_subscriptions
             WHERE user_id = :user_id AND status = :status AND renewal_attempts > 0
             ORDER BY current_period_end DESC
             LIMIT 1',
            ['user_id' => $userId, 'status' => 'active']
        );
    }

    public function extendActivePeriod(string $id, string $transactionId, string $periodEnd): void
    {
        $this->execute(
            'UPDATE user_subscriptions SET
                flutterwave_tx_id = :tx_id,
                current_period_start = NOW(),
                current_period_end = :period_end,
                renewal_attempts = 0,
                next_renewal_attempt_at = NULL,
                renewal_grace_until = NULL,
                last_renewal_error = NULL,
                paid_at = NOW(),
                updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'tx_id' => $transactionId,
                'period_end' => $periodEnd,
            ]
        );
    }

    public function recordRenewalFailure(
        string $id,
        int $attempts,
        ?string $nextAttemptAt,
        ?string $graceUntil,
        string $error
    ): void {
        $this->execute(
            'UPDATE user_subscriptions SET
                renewal_attempts = :attempts,
                next_renewal_attempt_at = :next_attempt,
                renewal_grace_until = :grace_until,
                last_renewal_error = :error,
                updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'attempts' => $attempts,
                'next_attempt' => $nextAttemptAt,
                'grace_until' => $graceUntil,
                'error' => mb_substr($error, 0, 255),
            ]
        );
    }

    public function markExpired(string $id, ?string $error = null): void
    {
        $this->execute(
            'UPDATE user_subscriptions SET
                status = :status,
                next_renewal_attempt_at = NULL,
                renewal_grace_until = NULL,
                last_renewal_error = :error,
                updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'status' => 'expired',
                'error' => $error !== null ? mb_substr($error, 0, 255) : null,
            ]
        );
    }
}
