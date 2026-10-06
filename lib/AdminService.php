<?php

declare(strict_types=1);

final class AdminService
{
    private AdminRepository $repo;

    public function __construct()
    {
        $this->repo = RepositoryFactory::admin();
    }

    public function overview(): array
    {
        return [
            'total_users' => $this->repo->countUsers(),
            'active_subscriptions' => $this->repo->countActiveSubscriptions(),
            'total_businesses' => $this->repo->countBusinesses(),
            'mrr_usd' => $this->repo->estimateMrrUsd(),
            'failed_jobs' => $this->repo->countFailedAutomationJobs(),
            'open_disputes' => $this->repo->countOpenDisputes(),
            'messages_today' => $this->repo->countMessagesToday(),
            'ai_replies_today' => $this->repo->countAiRepliesToday(),
            'signups_7d' => $this->repo->countSignupsSince(date('Y-m-d H:i:s', strtotime('-7 days'))),
            'signups_30d' => $this->repo->countSignupsSince(date('Y-m-d H:i:s', strtotime('-30 days'))),
            'recent_signups' => $this->repo->listRecentSignups(8),
            'alerts' => $this->repo->listAlerts(10),
            'plan_distribution' => $this->repo->planDistribution(),
            'signup_chart' => $this->repo->signupsByDay(7),
            'queue_stats' => $this->repo->queueStats(),
            'openai_cost_30d' => $this->repo->openAiPlatformCost(30),
        ];
    }

    public function repository(): AdminRepository
    {
        return $this->repo;
    }

    public function grantTokens(string $adminUserId, string $targetUserId, int $amount, string $note): int
    {
        $balance = (new TokenService())->grant(
            $targetUserId,
            $amount,
            'admin_grant',
            'admin',
            $adminUserId,
            ['note' => $note]
        );

        (new AdminAuditService())->log($adminUserId, 'grant_tokens', 'user', $targetUserId, [
            'amount' => $amount,
            'note' => $note,
            'balance_after' => $balance,
        ]);

        return $balance;
    }

    public function suspendUser(string $adminUserId, string $targetUserId, bool $suspend): void
    {
        RepositoryFactory::users()->setSuspended($targetUserId, $suspend);
        (new AdminAuditService())->log($adminUserId, $suspend ? 'suspend_user' : 'reactivate_user', 'user', $targetUserId);
    }

    public function setUserRole(string $adminUserId, string $targetUserId, string $role): void
    {
        RepositoryFactory::users()->setRole($targetUserId, $role);
        (new AdminAuditService())->log($adminUserId, 'set_user_role', 'user', $targetUserId, ['role' => $role]);
    }

    public function cancelSubscription(string $adminUserId, string $subscriptionId): void
    {
        $subscription = RepositoryFactory::subscriptions()->findById($subscriptionId);
        if ($subscription === null) {
            throw new InvalidArgumentException('Subscription not found.');
        }

        RepositoryFactory::subscriptions()->markCancelled($subscriptionId);
        (new SubscriptionService())->syncBusinessSubscription((string) $subscription['user_id']);

        (new AdminAuditService())->log($adminUserId, 'cancel_subscription', 'subscription', $subscriptionId, [
            'user_id' => $subscription['user_id'],
        ]);
    }

    public function overrideSubscription(
        string $adminUserId,
        string $userId,
        string $planSlug,
        int $days
    ): void {
        if (!PlanService::isValidSlug($planSlug)) {
            throw new InvalidArgumentException('Unknown plan.');
        }

        $plan = PlanService::find($planSlug);
        $amount = (float) ($plan['price'] ?? 0);
        $txRef = 'admin_comp_' . bin2hex(random_bytes(8));
        $periodEnd = date('Y-m-d H:i:s', strtotime('+' . max(1, $days) . ' days'));

        $active = RepositoryFactory::subscriptions()->findActiveByUserId($userId);
        if ($active !== null) {
            RepositoryFactory::subscriptions()->markCancelled((string) $active['id']);
        }

        $sub = RepositoryFactory::subscriptions()->createPending($userId, $planSlug, $amount, $txRef, null, [
            'admin_override' => true,
            'granted_by' => $adminUserId,
        ]);
        RepositoryFactory::subscriptions()->markActive((string) $sub['id'], 'admin_override', null, $periodEnd);
        (new SubscriptionService())->syncBusinessSubscription($userId);

        (new AdminAuditService())->log($adminUserId, 'override_subscription', 'user', $userId, [
            'plan_slug' => $planSlug,
            'days' => $days,
            'subscription_id' => $sub['id'],
        ]);
    }

    public function disconnectChannel(string $adminUserId, string $accountId): void
    {
        $account = RepositoryFactory::connectedAccounts()->findById($accountId);
        if ($account === null) {
            throw new InvalidArgumentException('Channel not found.');
        }

        RepositoryFactory::connectedAccounts()->update((string) $account['id'], ['is_active' => 0]);
        (new AdminAuditService())->log($adminUserId, 'disconnect_channel', 'connected_account', $accountId, [
            'business_id' => $account['business_id'],
            'platform' => $account['platform'],
        ]);
    }

    public function startImpersonation(string $adminUserId, string $targetUserId): void
    {
        if ($adminUserId === $targetUserId) {
            throw new InvalidArgumentException('Cannot impersonate yourself.');
        }

        $target = RepositoryFactory::users()->findById($targetUserId);
        if ($target === null) {
            throw new InvalidArgumentException('User not found.');
        }

        $sessionId = Uuid::v4();
        RepositoryFactory::admin()->executeImpersonationStart($sessionId, $adminUserId, $targetUserId);

        $_SESSION[AdminAuth::IMPERSONATOR_SESSION_KEY] = [
            'id' => $adminUserId,
            'email' => RepositoryFactory::users()->findById($adminUserId)['email'] ?? '',
            'session_id' => $sessionId,
        ];

        (new SessionAuth())->login((new UserService())->toSessionUser($target));
        (new AdminAuditService())->log($adminUserId, 'impersonate_start', 'user', $targetUserId);
    }

    public function stopImpersonation(): void
    {
        $impersonator = AdminAuth::impersonator();
        if ($impersonator === null) {
            redirect(app_route('admin'));
        }

        $adminId = (string) ($impersonator['id'] ?? '');
        $sessionId = (string) ($impersonator['session_id'] ?? '');
        if ($sessionId !== '') {
            RepositoryFactory::admin()->executeImpersonationEnd($sessionId);
        }

        unset($_SESSION[AdminAuth::IMPERSONATOR_SESSION_KEY]);

        $admin = RepositoryFactory::users()->findById($adminId);
        if ($admin !== null) {
            (new SessionAuth())->login((new UserService())->toSessionUser($admin));
            (new AdminAuditService())->log($adminId, 'impersonate_stop', 'user', (new SessionAuth())->userId());
        }

        redirect(app_route('admin/users'));
    }
}
