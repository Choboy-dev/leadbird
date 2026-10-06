<?php

declare(strict_types=1);

/**
 * Active business + onboarding business resolution for multi-business accounts.
 */
final class BusinessContext
{
    private const SESSION_ACTIVE = 'active_business_id';
    private const SESSION_ONBOARDING = 'onboarding_business_id';

    /** @return list<array<string, mixed>> */
    public function listForUser(string $userId): array
    {
        return RepositoryFactory::businesses()->listByUserId($userId);
    }

    public function userOwnsBusiness(string $userId, string $businessId): bool
    {
        $business = RepositoryFactory::businesses()->findById($businessId);

        return $business !== null && (string) ($business['user_id'] ?? '') === $userId;
    }

    public function getActiveBusinessId(): ?string
    {
        $id = $_SESSION[self::SESSION_ACTIVE] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function setActiveBusinessId(string $businessId): void
    {
        $_SESSION[self::SESSION_ACTIVE] = $businessId;
    }

    public function getOnboardingBusinessId(): ?string
    {
        $id = $_SESSION[self::SESSION_ONBOARDING] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function setOnboardingBusinessId(string $businessId): void
    {
        $_SESSION[self::SESSION_ONBOARDING] = $businessId;
    }

    public function getActiveBusiness(string $userId): ?array
    {
        $activeId = $this->getActiveBusinessId();
        if ($activeId !== null && $this->userOwnsBusiness($userId, $activeId)) {
            $business = RepositoryFactory::businesses()->findById($activeId);
            if ($business !== null && $this->isBusinessComplete($business)) {
                return $business;
            }
        }

        $completed = RepositoryFactory::businesses()->findLatestCompleteByUserId($userId);
        if ($completed !== null) {
            $this->setActiveBusinessId((string) $completed['id']);

            return $completed;
        }

        return null;
    }

    public function getOnboardingBusiness(string $userId): ?array
    {
        $onboardingId = $this->getOnboardingBusinessId();
        if ($onboardingId !== null && $this->userOwnsBusiness($userId, $onboardingId)) {
            $business = RepositoryFactory::businesses()->findById($onboardingId);
            if ($business !== null && !$this->isBusinessComplete($business)) {
                return $business;
            }
        }

        return RepositoryFactory::businesses()->findLatestInProgressByUserId($userId);
    }

  /**
     * Draft business for connect-first onboarding or "Add business".
     */
    public function ensureOnboardingBusiness(string $userId): array
    {
        $existing = $this->getOnboardingBusiness($userId);
        if ($existing !== null) {
            $this->setOnboardingBusinessId((string) $existing['id']);

            return $existing;
        }

        $planSlug = (new OnboardingService())->getActivePlanSlug($userId) ?? 'starter';
        $business = RepositoryFactory::businesses()->createDraft($userId, $planSlug);
        $this->setOnboardingBusinessId((string) $business['id']);

        return $business;
    }

    public function resolveActiveBusiness(string $userId): ?array
    {
        $business = $this->getActiveBusiness($userId);
        if ($business !== null) {
            return $business;
        }

        return null;
    }

    public function countUserChannels(string $userId): int
    {
        return RepositoryFactory::connectedAccounts()->countActiveByUserId($userId);
    }

    public function countCompletedBusinesses(string $userId): int
    {
        return RepositoryFactory::businesses()->countCompleteByUserId($userId);
    }

    public function hasAnyCompleteBusiness(string $userId): bool
    {
        return $this->countCompletedBusinesses($userId) > 0;
    }

    public function isBusinessComplete(array $business): bool
    {
        if (!empty($business['onboarding_completed_at'])) {
            return true;
        }

        return (int) ($business['onboarding_step'] ?? 1) >= OnboardingService::STEP_COMPLETE;
    }

    public function getBusinessLimitForUser(string $userId): ?int
    {
        return PlanLimits::businessLimit((new OnboardingService())->getActivePlanSlug($userId));
    }

    public function canAddBusiness(string $userId): bool
    {
        $limit = $this->getBusinessLimitForUser($userId);
        if ($limit === null) {
            return true;
        }

        return count($this->listForUser($userId)) < $limit;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listWithChannelSummary(string $userId): array
    {
        $rows = [];
        foreach ($this->listForUser($userId) as $business) {
            $businessId = (string) $business['id'];
            $channels = RepositoryFactory::connectedAccounts()->listByBusinessId($businessId, true);
            $rows[] = array_merge($business, [
                'is_complete' => $this->isBusinessComplete($business),
                'channel_count' => count($channels),
                'channels' => $channels,
            ]);
        }

        return $rows;
    }
}
