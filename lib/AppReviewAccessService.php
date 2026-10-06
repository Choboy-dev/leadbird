<?php

declare(strict_types=1);

/**
 * Dedicated Meta App Review tester account — shop plan, completed onboarding, dashboard access.
 */
final class AppReviewAccessService
{
    private const PLAN_SLUG = 'shop';
    private const BUSINESS_NAME = 'App Review Demo';
    private const SUBSCRIPTION_DAYS = 365;

    public function isEnabled(): bool
    {
        $config = app_config()['app_review'] ?? [];

        return !empty($config['enabled'])
            && trim((string) ($config['email'] ?? '')) !== ''
            && trim((string) ($config['password'] ?? '')) !== '';
    }

    public function configuredEmail(): string
    {
        return normalize_email(trim((string) (app_config()['app_review']['email'] ?? '')));
    }

    public function credentialsMatch(string $email, string $password): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $config = app_config()['app_review'];

        return hash_equals($this->configuredEmail(), normalize_email(trim($email)))
            && hash_equals((string) ($config['password'] ?? ''), $password);
    }

    public function isReviewUser(string $userId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $user = RepositoryFactory::users()->findById($userId);

        return $user !== null
            && hash_equals($this->configuredEmail(), normalize_email((string) ($user['email'] ?? '')));
    }

    /**
     * @return array<string, mixed>
     */
    public function provisionSessionUser(): array
    {
        if (!$this->isEnabled()) {
            throw new RuntimeException('App review access is disabled.');
        }

        $user = (new UserService())->findOrCreateByEmail($this->configuredEmail(), 'Meta App Review');
        $userId = (string) $user['id'];

        $this->ensureShopSubscription($userId);
        $business = $this->ensureReviewBusiness($userId);

        $ctx = new BusinessContext();
        $ctx->setActiveBusinessId((string) $business['id']);
        unset($_SESSION['onboarding_business_id']);

        $this->ensureTokenAllowance($userId);

        return (new UserService())->toSessionUser($user);
    }

    private function ensureShopSubscription(string $userId): void
    {
        $active = RepositoryFactory::subscriptions()->findActiveByUserId($userId);
        if (
            $active !== null
            && (string) ($active['plan_slug'] ?? '') === self::PLAN_SLUG
        ) {
            (new SubscriptionService())->syncBusinessSubscription($userId);

            return;
        }

        if ($active !== null) {
            RepositoryFactory::subscriptions()->markCancelled((string) $active['id']);
        }

        $plan = PlanService::find(self::PLAN_SLUG);
        $amount = (float) ($plan['price'] ?? 99);
        $txRef = 'app_review_' . bin2hex(random_bytes(8));
        $periodEnd = date('Y-m-d H:i:s', strtotime('+' . self::SUBSCRIPTION_DAYS . ' days'));

        $sub = RepositoryFactory::subscriptions()->createPending(
            $userId,
            self::PLAN_SLUG,
            $amount,
            $txRef,
            null,
            ['app_review_access' => true],
        );
        RepositoryFactory::subscriptions()->markActive((string) $sub['id'], 'app_review_access', null, $periodEnd);
        (new SubscriptionService())->syncBusinessSubscription($userId);
    }

    /**
     * @return array<string, mixed>
     */
    private function ensureReviewBusiness(string $userId): array
    {
        $business = RepositoryFactory::businesses()->findLatestCompleteByUserId($userId);
        if ($business === null) {
            $business = RepositoryFactory::businesses()->findByUserId($userId);
        }

        if ($business === null) {
            $business = RepositoryFactory::businesses()->create($userId, [
                'business_name' => self::BUSINESS_NAME,
                'owner_email' => $this->configuredEmail(),
                'subscription_plan' => self::PLAN_SLUG,
                'onboarding_step' => OnboardingService::STEP_CHANNELS,
            ]);
        }

        $businessId = (string) $business['id'];
        if (trim((string) ($business['business_name'] ?? '')) === '' || (string) ($business['business_name'] ?? '') === 'New business') {
            RepositoryFactory::businesses()->update($businessId, [
                'business_name' => self::BUSINESS_NAME,
                'owner_name' => (string) ($business['owner_name'] ?? 'Meta App Review'),
                'owner_email' => $this->configuredEmail(),
                'owner_phone' => $business['owner_phone'] ?? null,
            ]);
        }

        RepositoryFactory::businesses()->markOnboardingComplete($businessId);
        RepositoryFactory::businesses()->updateSubscription($businessId, self::PLAN_SLUG, 'active', null);

        return RepositoryFactory::businesses()->findById($businessId) ?? $business;
    }

    private function ensureTokenAllowance(string $userId): void
    {
        $monthlyGrant = PlanLimits::monthlyTokens(self::PLAN_SLUG) ?? 0;
        $balance = (new TokenService())->getBalance($userId);

        if ($monthlyGrant > 0 && $balance >= (int) floor($monthlyGrant * 0.5)) {
            return;
        }

        (new TokenService())->grantMonthlyAllowance(
            $userId,
            self::PLAN_SLUG,
            'app_review_access',
            'app_review_access'
        );
    }
}
