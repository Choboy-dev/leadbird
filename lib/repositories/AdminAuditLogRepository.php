<?php

declare(strict_types=1);

final class AdminAuditLogRepository extends BaseRepository
{
    /**
     * @param array<string, mixed>|null $details
     */
    public function create(
        string $adminUserId,
        string $action,
        ?string $targetType = null,
        ?string $targetId = null,
        ?array $details = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): string {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO admin_audit_log
             (id, admin_user_id, action, target_type, target_id, details, ip_address, user_agent)
             VALUES
             (:id, :admin_user_id, :action, :target_type, :target_id, :details, :ip_address, :user_agent)',
            [
                'id' => $id,
                'admin_user_id' => $adminUserId,
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'details' => $details !== null ? json_encode($details, JSON_THROW_ON_ERROR) : null,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 255) : null,
            ]
        );

        return $id;
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listFiltered(
        int $page = 1,
        int $perPage = 25,
        ?string $action = null,
        ?string $adminUserId = null,
        ?string $search = null
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if ($action !== null && $action !== '') {
            $where[] = 'a.action = :action';
            $params['action'] = $action;
        }
        if ($adminUserId !== null && $adminUserId !== '') {
            $where[] = 'a.admin_user_id = :admin_user_id';
            $params['admin_user_id'] = $adminUserId;
        }
        if ($search !== null && $search !== '') {
            $where[] = '(u.email LIKE :search OR a.action LIKE :search OR a.target_id LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $whereSql = implode(' AND ', $where);
        $countRow = $this->fetchOne(
            'SELECT COUNT(*) AS cnt
             FROM admin_audit_log a
             LEFT JOIN users u ON u.id = a.admin_user_id
             WHERE ' . $whereSql,
            $params
        );
        $total = (int) ($countRow['cnt'] ?? 0);

        $rows = $this->fetchAll(
            'SELECT a.*, u.email AS admin_email, u.name AS admin_name
             FROM admin_audit_log a
             LEFT JOIN users u ON u.id = a.admin_user_id
             WHERE ' . $whereSql . '
             ORDER BY a.created_at DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['rows' => $rows, 'total' => $total];
    }
}
