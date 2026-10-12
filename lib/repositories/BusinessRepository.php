<?php

declare(strict_types=1);

final class BusinessRepository extends BaseRepository
{
    public function findByUserId(string $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM businesses WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1',
            ['user_id' => $userId]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listByUserId(string $userId): array
    {
        return $this->fetchAll(
            'SELECT * FROM businesses WHERE user_id = :user_id ORDER BY created_at DESC',
            ['user_id' => $userId]
        );
    }

    public function findLatestInProgressByUserId(string $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM businesses
             WHERE user_id = :user_id AND onboarding_completed_at IS NULL
             ORDER BY created_at DESC
             LIMIT 1',
            ['user_id' => $userId]
        );
    }

    public function findLatestCompleteByUserId(string $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM businesses
             WHERE user_id = :user_id AND onboarding_completed_at IS NOT NULL
             ORDER BY onboarding_completed_at DESC
             LIMIT 1',
            ['user_id' => $userId]
        );
    }

    public function countCompleteByUserId(string $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM businesses
             WHERE user_id = :user_id AND onboarding_completed_at IS NOT NULL',
            ['user_id' => $userId]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function createDraft(string $userId, string $planSlug): array
    {
        return $this->create($userId, [
            'business_name' => 'New business',
            'subscription_plan' => $planSlug,
            'onboarding_step' => OnboardingService::STEP_CHANNELS,
        ]);
    }

    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM businesses WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function create(string $userId, array $data): array
    {
        $id = Uuid::v4();
        $onboardingStep = isset($data['onboarding_step'])
            ? max(1, min(6, (int) $data['onboarding_step']))
            : 1;

        $this->execute(
            'INSERT INTO businesses (id, user_id, business_name, owner_name, owner_email, owner_phone, subscription_plan, onboarding_step)
             VALUES (:id, :user_id, :business_name, :owner_name, :owner_email, :owner_phone, :subscription_plan, :onboarding_step)',
            [
                'id' => $id,
                'user_id' => $userId,
                'business_name' => $data['business_name'],
                'owner_name' => $data['owner_name'] ?? null,
                'owner_email' => $data['owner_email'] ?? null,
                'owner_phone' => $data['owner_phone'] ?? null,
                'subscription_plan' => $data['subscription_plan'] ?? 'free',
                'onboarding_step' => $onboardingStep,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create business');
    }

    public function updateOnboardingStep(string $id, int $step): void
    {
        $this->execute(
            'UPDATE businesses SET onboarding_step = :step, updated_at = NOW() WHERE id = :id',
            ['id' => $id, 'step' => max(1, min(6, $step))]
        );
    }

    public function markOnboardingComplete(string $id): void
    {
        $this->execute(
            'UPDATE businesses SET onboarding_step = 7, onboarding_completed_at = NOW(), updated_at = NOW() WHERE id = :id',
            ['id' => $id]
        );
    }

    public function update(string $id, array $data): void
    {
        $this->execute(
            'UPDATE businesses SET business_name = :business_name, owner_name = :owner_name, owner_email = :owner_email,
             owner_phone = :owner_phone, updated_at = NOW() WHERE id = :id',
            [
                'id' => $id,
                'business_name' => $data['business_name'],
                'owner_name' => $data['owner_name'] ?? null,
                'owner_email' => $data['owner_email'] ?? null,
                'owner_phone' => $data['owner_phone'] ?? null,
            ]
        );
    }

    /**
     * @param array{business_name: string, industry: string, website: ?string, owner_role: string, team_size: string} $data
     */
    public function updateWelcomeProfile(string $id, array $data): void
    {
        $this->execute(
            'UPDATE businesses SET business_name = :business_name, industry = :industry, website = :website,
             owner_role = :owner_role, team_size = :team_size, updated_at = NOW() WHERE id = :id',
            [
                'id' => $id,
                'business_name' => $data['business_name'],
                'industry' => $data['industry'],
                'website' => $data['website'],
                'owner_role' => $data['owner_role'],
                'team_size' => $data['team_size'],
            ]
        );
    }

    public function updateSubscription(string $id, string $planSlug, string $status, ?string $expiresAt): void
    {
        $this->execute(
            'UPDATE businesses SET subscription_plan = :plan, subscription_status = :status,
             subscription_expires_at = :expires_at, updated_at = NOW() WHERE id = :id',
            [
                'id' => $id,
                'plan' => $planSlug,
                'status' => $status,
                'expires_at' => $expiresAt,
            ]
        );
    }

    public function deleteById(string $id): void
    {
        $this->execute('DELETE FROM businesses WHERE id = :id', ['id' => $id]);
    }
}
