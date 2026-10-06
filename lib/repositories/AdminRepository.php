<?php

declare(strict_types=1);

final class AdminRepository extends BaseRepository
{
    public function countUsers(): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS cnt FROM users');

        return (int) ($row['cnt'] ?? 0);
    }

    public function countActiveSubscriptions(): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM user_subscriptions
             WHERE status = :status AND (current_period_end IS NULL OR current_period_end > NOW())',
            ['status' => 'active']
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function countBusinesses(): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS cnt FROM businesses');

        return (int) ($row['cnt'] ?? 0);
    }

    public function countSignupsSince(string $since): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM users WHERE created_at >= :since',
            ['since' => $since]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function estimateMrrUsd(): float
    {
        $rows = $this->fetchAll(
            'SELECT amount, currency
             FROM user_subscriptions
             WHERE status = :status AND (current_period_end IS NULL OR current_period_end > NOW())',
            ['status' => 'active']
        );

        $total = 0.0;
        foreach ($rows as $row) {
            $total += money_to_usd(
                (float) ($row['amount'] ?? 0),
                (string) ($row['currency'] ?? 'USD')
            );
        }

        return round($total, 2);
    }

    /** @deprecated Use estimateMrrUsd() */
    public function estimateMrrNgn(): float
    {
        return $this->estimateMrrUsd();
    }

    public function countFailedAutomationJobs(): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM automation_queue WHERE status = :status',
            ['status' => 'failed']
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function countOpenDisputes(): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM wallet_disputes WHERE status = :status',
            ['status' => 'open']
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function countMessagesToday(): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM messages WHERE created_at >= CURDATE()'
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function countAiRepliesToday(): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM messages WHERE sender_type = :sender AND created_at >= CURDATE()',
            ['sender' => 'ai']
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public function listRecentSignups(int $limit = 8): array
    {
        return $this->fetchAll(
            'SELECT u.*, s.plan_slug, s.status AS subscription_status
             FROM users u
             LEFT JOIN user_subscriptions s ON s.user_id = u.id AND s.status = :active
             ORDER BY u.created_at DESC
             LIMIT ' . max(1, min(20, $limit)),
            ['active' => 'active']
        );
    }

    /** @return list<array<string, mixed>> */
    public function listAlerts(int $limit = 10): array
    {
        $alerts = [];

        $failedJobs = $this->countFailedAutomationJobs();
        if ($failedJobs > 0) {
            $alerts[] = [
                'severity' => 'danger',
                'message' => $failedJobs . ' automation job' . ($failedJobs === 1 ? '' : 's') . ' failed',
                'url' => app_route('admin/automation', ['status' => 'failed']),
            ];
        }

        $dueRenewals = count(RepositoryFactory::subscriptions()->findDueForRenewal());
        if ($dueRenewals > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => $dueRenewals . ' subscription' . ($dueRenewals === 1 ? '' : 's') . ' due for renewal',
                'url' => app_route('admin/subscriptions', ['filter' => 'due_renewal']),
            ];
        }

        $openDisputes = $this->countOpenDisputes();
        if ($openDisputes > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => $openDisputes . ' open wallet dispute' . ($openDisputes === 1 ? '' : 's'),
                'url' => app_route('admin/disputes'),
            ];
        }

        $pendingMeta = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM meta_deletion_requests WHERE status = :status',
            ['status' => 'pending']
        );
        $pendingMetaCount = (int) ($pendingMeta['cnt'] ?? 0);
        if ($pendingMetaCount > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => $pendingMetaCount . ' pending Meta deletion request' . ($pendingMetaCount === 1 ? '' : 's'),
                'url' => app_route('admin/meta-deletion'),
            ];
        }

        return array_slice($alerts, 0, max(1, min(20, $limit)));
    }

    /** @return list<array{plan_slug: string, cnt: int}> */
    public function planDistribution(): array
    {
        return $this->fetchAll(
            'SELECT plan_slug, COUNT(*) AS cnt
             FROM user_subscriptions
             WHERE status = :status AND (current_period_end IS NULL OR current_period_end > NOW())
             GROUP BY plan_slug
             ORDER BY cnt DESC',
            ['status' => 'active']
        );
    }

    /** @return list<array{day: string, cnt: int}> */
    public function signupsByDay(int $days = 7): array
    {
        return $this->fetchAll(
            'SELECT DATE(created_at) AS day, COUNT(*) AS cnt
             FROM users
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC',
            ['days' => max(1, $days)]
        );
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listUsers(int $page = 1, int $perPage = 25, ?string $search = null, ?string $filter = null): array
    {
        return $this->listUsersQuery($page, $perPage, $search, $filter);
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    private function listUsersQuery(int $page, int $perPage, ?string $search, ?string $filter): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $where[] = '(u.email LIKE :search OR u.name LIKE :search OR u.id = :search_exact)';
            $params['search'] = '%' . trim($search) . '%';
            $params['search_exact'] = trim($search);
        }
        if ($filter === 'admin') {
            $where[] = 'u.role = :role';
            $params['role'] = AdminAuth::ROLE_ADMIN;
        } elseif ($filter === 'suspended') {
            $where[] = 'u.is_suspended = 1';
        }

        $whereSql = implode(' AND ', $where);
        $countRow = $this->fetchOne('SELECT COUNT(*) AS cnt FROM users u WHERE ' . $whereSql, $params);

        $rows = $this->fetchAll(
            'SELECT u.*,
                    s.plan_slug,
                    s.status AS subscription_status,
                    s.current_period_end,
                    tb.balance AS token_balance,
                    (SELECT COUNT(*) FROM businesses b WHERE b.user_id = u.id) AS business_count
             FROM users u
             LEFT JOIN user_subscriptions s ON s.user_id = u.id AND s.status = :active_sub
             LEFT JOIN user_token_balances tb ON tb.user_id = u.id
             WHERE ' . $whereSql . '
             ORDER BY u.created_at DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            array_merge($params, ['active_sub' => 'active'])
        );

        return ['rows' => $rows, 'total' => (int) ($countRow['cnt'] ?? 0)];
    }

    public function getUserDetail(string $userId): ?array
    {
        $user = RepositoryFactory::users()->findById($userId);
        if ($user === null) {
            return null;
        }

        return [
            'user' => $user,
            'businesses' => RepositoryFactory::businesses()->listByUserId($userId),
            'subscriptions' => RepositoryFactory::subscriptions()->listByUserId($userId),
            'token_balance' => RepositoryFactory::tokenBalances()->ensureRow($userId),
            'token_ledger' => RepositoryFactory::tokenLedger()->listByUserId($userId, 15),
            'payment_methods' => RepositoryFactory::paymentMethods()->listByUserId($userId),
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listBusinesses(int $page = 1, int $perPage = 25, ?string $search = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $where[] = '(b.business_name LIKE :search OR u.email LIKE :search OR b.id = :search_exact)';
            $params['search'] = '%' . trim($search) . '%';
            $params['search_exact'] = trim($search);
        }

        $whereSql = implode(' AND ', $where);
        $countRow = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM businesses b JOIN users u ON u.id = b.user_id WHERE ' . $whereSql,
            $params
        );

        $rows = $this->fetchAll(
            'SELECT b.*, u.email AS owner_email, u.name AS owner_name,
                    (SELECT COUNT(*) FROM connected_accounts ca WHERE ca.business_id = b.id AND ca.is_active = 1) AS channel_count,
                    (SELECT COUNT(*) FROM conversations c WHERE c.business_id = b.id) AS conversation_count,
                    (SELECT COUNT(*) FROM customers cu WHERE cu.business_id = b.id) AS customer_count
             FROM businesses b
             JOIN users u ON u.id = b.user_id
             WHERE ' . $whereSql . '
             ORDER BY b.updated_at DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['rows' => $rows, 'total' => (int) ($countRow['cnt'] ?? 0)];
    }

    public function getBusinessDetail(string $businessId): ?array
    {
        $business = RepositoryFactory::businesses()->findById($businessId);
        if ($business === null) {
            return null;
        }

        $owner = RepositoryFactory::users()->findById((string) $business['user_id']);

        return [
            'business' => $business,
            'owner' => $owner,
            'settings' => RepositoryFactory::businessSettings()->findByBusinessId($businessId),
            'channels' => RepositoryFactory::connectedAccounts()->listByBusinessId($businessId),
            'offerings_count' => count(RepositoryFactory::offerings()->listByBusinessId($businessId)),
            'conversations_count' => count(RepositoryFactory::conversations()->listByBusinessId($businessId)),
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listSubscriptions(int $page = 1, int $perPage = 25, ?string $status = null, ?string $filter = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 's.status = :status';
            $params['status'] = $status;
        }
        if ($filter === 'due_renewal') {
            $where[] = 's.status = :active AND s.current_period_end IS NOT NULL AND s.current_period_end <= NOW()';
            $params['active'] = 'active';
        }

        $whereSql = implode(' AND ', $where);
        $countRow = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM user_subscriptions s WHERE ' . $whereSql,
            $params
        );

        $rows = $this->fetchAll(
            'SELECT s.*, u.email AS user_email, u.name AS user_name
             FROM user_subscriptions s
             JOIN users u ON u.id = s.user_id
             WHERE ' . $whereSql . '
             ORDER BY s.created_at DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['rows' => $rows, 'total' => (int) ($countRow['cnt'] ?? 0)];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listTokenBalances(int $page = 1, int $perPage = 25, ?string $search = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $where[] = '(u.email LIKE :search OR u.name LIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }

        $whereSql = implode(' AND ', $where);
        $countRow = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM user_token_balances tb JOIN users u ON u.id = tb.user_id WHERE ' . $whereSql,
            $params
        );

        $rows = $this->fetchAll(
            'SELECT tb.*, u.email, u.name, s.plan_slug
             FROM user_token_balances tb
             JOIN users u ON u.id = tb.user_id
             LEFT JOIN user_subscriptions s ON s.user_id = u.id AND s.status = :active
             WHERE ' . $whereSql . '
             ORDER BY tb.balance ASC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            array_merge($params, ['active' => 'active'])
        );

        return ['rows' => $rows, 'total' => (int) ($countRow['cnt'] ?? 0)];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listChannels(int $page = 1, int $perPage = 25, ?string $platform = null, ?string $search = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['ca.is_active = 1'];
        $params = [];

        if ($platform !== null && $platform !== '') {
            $where[] = 'ca.platform = :platform';
            $params['platform'] = $platform;
        }
        if ($search !== null && trim($search) !== '') {
            $where[] = '(b.business_name LIKE :search OR u.email LIKE :search OR ca.display_name LIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }

        $whereSql = implode(' AND ', $where);
        $countRow = $this->fetchOne(
            'SELECT COUNT(*) AS cnt
             FROM connected_accounts ca
             JOIN businesses b ON b.id = ca.business_id
             JOIN users u ON u.id = b.user_id
             WHERE ' . $whereSql,
            $params
        );

        $rows = $this->fetchAll(
            'SELECT ca.*, b.business_name, u.email AS owner_email
             FROM connected_accounts ca
             JOIN businesses b ON b.id = ca.business_id
             JOIN users u ON u.id = b.user_id
             WHERE ' . $whereSql . '
             ORDER BY ca.created_at DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['rows' => $rows, 'total' => (int) ($countRow['cnt'] ?? 0)];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listAutomationJobs(int $page = 1, int $perPage = 25, ?string $status = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $whereSql = implode(' AND ', $where);
        $countRow = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM automation_queue WHERE ' . $whereSql,
            $params
        );

        $rows = $this->fetchAll(
            'SELECT * FROM automation_queue WHERE ' . $whereSql . ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['rows' => $rows, 'total' => (int) ($countRow['cnt'] ?? 0)];
    }

    public function retryAutomationJob(string $jobId): bool
    {
        $row = $this->fetchOne('SELECT id FROM automation_queue WHERE id = :id AND status = :status LIMIT 1', [
            'id' => $jobId,
            'status' => 'failed',
        ]);
        if ($row === null) {
            return false;
        }

        $this->execute(
            'UPDATE automation_queue SET status = :status, attempts = 0, last_error = NULL, available_at = NOW() WHERE id = :id',
            ['id' => $jobId, 'status' => 'pending']
        );

        return true;
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listConversations(int $page = 1, int $perPage = 25, ?string $search = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $where[] = '(cu.customer_name LIKE :search OR b.business_name LIKE :search OR u.email LIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }

        $whereSql = implode(' AND ', $where);
        $countRow = $this->fetchOne(
            'SELECT COUNT(*) AS cnt
             FROM conversations c
             JOIN businesses b ON b.id = c.business_id
             JOIN users u ON u.id = b.user_id
             LEFT JOIN customers cu ON cu.id = c.customer_id
             WHERE ' . $whereSql,
            $params
        );

        $rows = $this->fetchAll(
            'SELECT c.*, b.business_name, u.email AS owner_email, cu.customer_name, cu.platform
             FROM conversations c
             JOIN businesses b ON b.id = c.business_id
             JOIN users u ON u.id = b.user_id
             LEFT JOIN customers cu ON cu.id = c.customer_id
             WHERE ' . $whereSql . '
             ORDER BY c.last_message DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['rows' => $rows, 'total' => (int) ($countRow['cnt'] ?? 0)];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listAffiliates(int $page = 1, int $perPage = 25, ?string $search = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $where[] = '(a.code LIKE :search OR a.email LIKE :search OR a.name LIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }

        $whereSql = implode(' AND ', $where);
        $countRow = $this->fetchOne('SELECT COUNT(*) AS cnt FROM affiliates a WHERE ' . $whereSql, $params);

        $rows = $this->fetchAll(
            'SELECT a.*,
                    (SELECT COUNT(*) FROM affiliate_commissions ac WHERE ac.affiliate_id = a.id) AS commission_count,
                    (SELECT COALESCE(SUM(commission_amount), 0) FROM affiliate_commissions ac WHERE ac.affiliate_id = a.id AND ac.status = :paid) AS paid_total
             FROM affiliates a
             WHERE ' . $whereSql . '
             ORDER BY a.created_at DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            array_merge($params, ['paid' => 'paid_out'])
        );

        return ['rows' => $rows, 'total' => (int) ($countRow['cnt'] ?? 0)];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listMetaDeletionRequests(int $page = 1, int $perPage = 25, ?string $status = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $whereSql = implode(' AND ', $where);
        $countRow = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM meta_deletion_requests WHERE ' . $whereSql,
            $params
        );

        $rows = $this->fetchAll(
            'SELECT * FROM meta_deletion_requests WHERE ' . $whereSql . ' ORDER BY requested_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['rows' => $rows, 'total' => (int) ($countRow['cnt'] ?? 0)];
    }

    /** @return list<array<string, mixed>> */
    public function openAiUsageByBusiness(int $days = 30, int $limit = 20): array
    {
        return $this->fetchAll(
            'SELECT o.business_id, b.business_name, SUM(o.cost_usd) AS total_cost, SUM(o.total_tokens) AS total_tokens, COUNT(*) AS event_count
             FROM openai_usage_events o
             JOIN businesses b ON b.id = o.business_id
             WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY o.business_id, b.business_name
             ORDER BY total_cost DESC
             LIMIT ' . max(1, min(50, $limit)),
            ['days' => max(1, $days)]
        );
    }

    public function openAiPlatformCost(int $days = 30): float
    {
        $row = $this->fetchOne(
            'SELECT COALESCE(SUM(cost_usd), 0) AS total FROM openai_usage_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)',
            ['days' => max(1, $days)]
        );

        return (float) ($row['total'] ?? 0);
    }

    public function openAiPlatformTokens(int $days = 30): int
    {
        $row = $this->fetchOne(
            'SELECT COALESCE(SUM(total_tokens), 0) AS total FROM openai_usage_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)',
            ['days' => max(1, $days)]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function openAiPlatformEvents(int $days = 30): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS total FROM openai_usage_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)',
            ['days' => max(1, $days)]
        );

        return (int) ($row['total'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public function listAdmins(): array
    {
        $allowlist = AdminAuth::emailAllowlist();
        $params = ['role' => AdminAuth::ROLE_ADMIN];
        $sql = 'SELECT * FROM users WHERE role = :role';
        if ($allowlist !== []) {
            $placeholders = [];
            foreach ($allowlist as $i => $email) {
                $key = 'email_' . $i;
                $placeholders[] = ':' . $key;
                $params[$key] = $email;
            }
            $sql .= ' OR email IN (' . implode(', ', $placeholders) . ')';
        }
        $sql .= ' ORDER BY created_at ASC';

        return $this->fetchAll($sql, $params);
    }

    /** @return list<array<string, mixed>> */
    public function listFeatureFlags(): array
    {
        return $this->fetchAll('SELECT f.*, u.email AS updated_by_email FROM admin_feature_flags f LEFT JOIN users u ON u.id = f.updated_by ORDER BY f.flag_key ASC');
    }

    public function getFeatureFlag(string $key): ?array
    {
        return $this->fetchOne('SELECT * FROM admin_feature_flags WHERE flag_key = :key LIMIT 1', ['key' => $key]);
    }

    public function setFeatureFlag(string $key, mixed $value, string $adminUserId): void
    {
        $encoded = json_encode($value, JSON_THROW_ON_ERROR);
        $this->execute(
            'INSERT INTO admin_feature_flags (flag_key, flag_value, updated_by)
             VALUES (:key, :value, :updated_by)
             ON DUPLICATE KEY UPDATE flag_value = :value2, updated_by = :updated_by2, updated_at = NOW()',
            [
                'key' => $key,
                'value' => $encoded,
                'updated_by' => $adminUserId,
                'value2' => $encoded,
                'updated_by2' => $adminUserId,
            ]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listPaymentIntents(int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT pi.*, b.business_name
             FROM payment_intents pi
             JOIN businesses b ON b.id = pi.business_id
             ORDER BY pi.created_at DESC
             LIMIT ' . max(1, min(100, $limit))
        );
    }

    public function queueStats(): array
    {
        $rows = $this->fetchAll(
            'SELECT status, COUNT(*) AS cnt FROM automation_queue GROUP BY status'
        );
        $stats = ['pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $stats[(string) $row['status']] = (int) $row['cnt'];
        }

        return $stats;
    }

    public function executeImpersonationStart(string $sessionId, string $adminUserId, string $targetUserId): void
    {
        $this->execute(
            'INSERT INTO admin_impersonation_sessions (id, admin_user_id, target_user_id, is_active) VALUES (:id, :admin_user_id, :target_user_id, 1)',
            ['id' => $sessionId, 'admin_user_id' => $adminUserId, 'target_user_id' => $targetUserId]
        );
    }

    public function executeImpersonationEnd(string $sessionId): void
    {
        $this->execute(
            'UPDATE admin_impersonation_sessions SET is_active = 0, ended_at = NOW() WHERE id = :id',
            ['id' => $sessionId]
        );
    }
}
