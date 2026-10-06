<?php

declare(strict_types=1);

final class TokenService
{
    public function getBalance(string $userId): int
    {
        $row = RepositoryFactory::tokenBalances()->ensureRow($userId);

        return (int) ($row['balance'] ?? 0);
    }

    public function hasUnlimitedTokens(string $userId): bool
    {
        // All plans are metered. Kept for backward-compatible callers.
        unset($userId);

        return false;
    }

    public function canSpend(string $userId, int $amount = 1): bool
    {
        if ($amount < 1) {
            return true;
        }

        return $this->getBalance($userId) >= $amount;
    }

    /**
     * @param array<string, mixed>|null $meta
     */
    public function grant(
        string $userId,
        int $amount,
        string $reason,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?array $meta = null
    ): int {
        if ($amount < 1) {
            return $this->getBalance($userId);
        }

        if ($referenceType !== null && $referenceId !== null && $referenceId !== ''
            && RepositoryFactory::tokenLedger()->existsByReference($userId, $reason, $referenceId)) {
            return $this->getBalance($userId);
        }

        $balanceAfter = RepositoryFactory::tokenBalances()->adjustBalance($userId, $amount);
        RepositoryFactory::tokenLedger()->create(
            $userId,
            $amount,
            $balanceAfter,
            $reason,
            $referenceType,
            $referenceId,
            $meta
        );

        return $balanceAfter;
    }

    /**
     * Refund tokens (e.g. failed social publish). Idempotent by reason + referenceId.
     *
     * @param array<string, mixed>|null $meta
     */
    public function refund(
        string $userId,
        int $amount,
        string $reason,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?array $meta = null
    ): int {
        return $this->grant($userId, $amount, $reason, $referenceType, $referenceId, $meta);
    }

    /**
     * @param array<string, mixed>|null $meta
     */
    public function spend(
        string $userId,
        int $amount,
        string $reason,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?array $meta = null
    ): bool {
        if ($amount < 1) {
            return true;
        }

        if ($referenceType !== null && $referenceId !== null && $referenceId !== ''
            && RepositoryFactory::tokenLedger()->existsByReference($userId, $reason, $referenceId)) {
            return true;
        }

        if (!$this->canSpend($userId, $amount)) {
            return false;
        }

        $balanceAfter = RepositoryFactory::tokenBalances()->adjustBalance($userId, -$amount);
        RepositoryFactory::tokenLedger()->create(
            $userId,
            -$amount,
            $balanceAfter,
            $reason,
            $referenceType,
            $referenceId,
            $meta
        );

        return true;
    }

    /**
     * Plan renewal / activation: unused tokens expire; balance is reset to the plan grant.
     */
    public function grantMonthlyAllowance(string $userId, ?string $planSlug, string $reason, ?string $referenceId = null): void
    {
        $tokens = PlanLimits::monthlyTokens($planSlug);
        if ($tokens === null || $tokens < 1) {
            return;
        }

        if ($referenceId !== null && $referenceId !== ''
            && RepositoryFactory::tokenLedger()->existsByReference($userId, $reason, $referenceId)) {
            return;
        }

        $previous = $this->getBalance($userId);
        $balanceAfter = RepositoryFactory::tokenBalances()->setBalance($userId, $tokens);
        $delta = $balanceAfter - $previous;

        RepositoryFactory::tokenLedger()->create(
            $userId,
            $delta,
            $balanceAfter,
            $reason,
            'subscription',
            $referenceId,
            [
                'plan_slug' => $planSlug,
                'monthly_tokens' => $tokens,
                'previous_balance' => $previous,
                'unused_expired' => max(0, $previous),
                'reset' => true,
            ]
        );
    }

    /**
     * @return array{
     *   balance: int,
     *   unlimited: bool,
     *   monthly_grant: int|null,
     *   low_balance: bool,
     *   depleted: bool,
     *   label: string,
     *   percent_remaining: float|null,
     *   price_per_reply: string|null
     * }
     */
    public function getUsageSummary(string $userId): array
    {
        $planSlug = (new OnboardingService())->getActivePlanSlug($userId);
        $monthlyGrant = PlanLimits::monthlyTokens($planSlug);
        $balance = $this->getBalance($userId);

        // Shop and all plans are metered — never treat as unlimited.
        if ($monthlyGrant === null || $monthlyGrant < 1) {
            $monthlyGrant = 500;
        }

        $grant = max(1, (int) $monthlyGrant);
        $percentRemaining = min(100.0, max(0.0, ($balance / $grant) * 100));
        $lowThreshold = max(5, (int) ceil($grant * 0.2));
        $depleted = $balance < 1;
        $lowBalance = !$depleted && $balance <= $lowThreshold;

        return [
            'balance' => $balance,
            'unlimited' => false,
            'monthly_grant' => $grant,
            'low_balance' => $lowBalance,
            'depleted' => $depleted,
            'label' => $depleted
                ? 'No tokens left'
                : sprintf('%d of %d tokens', $balance, $grant),
            'percent_remaining' => $percentRemaining,
            'price_per_reply' => null,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function recentActivity(string $userId, int $limit = 8): array
    {
        return RepositoryFactory::tokenLedger()->listByUserId($userId, $limit);
    }
}
