<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'users';
$pageTitle = 'Users — Admin';
$pageHeading = 'Users';
$success = flash_get('success');
$error = flash_get('error');
$page = admin_page((int) ($_GET['page'] ?? 1));
$search = trim((string) ($_GET['q'] ?? ''));
$filter = trim((string) ($_GET['filter'] ?? ''));
$result = $adminService->repository()->listUsers($page, 25, $search !== '' ? $search : null, $filter !== '' ? $filter : null);
$baseUrl = app_route('admin/users', array_filter(['q' => $search ?: null, 'filter' => $filter ?: null]));

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>
<div class="admin-toolbar">
    <?php admin_toolbar_search('Search by email or name…', $search); ?>
    <div class="admin-actions">
        <?php admin_render_filter_links([
            ['label' => 'All', 'url' => app_route('admin/users', array_filter(['q' => $search ?: null])), 'active' => $filter === ''],
            ['label' => 'Admins', 'url' => app_route('admin/users', array_filter(['q' => $search ?: null, 'filter' => 'admin'])), 'active' => $filter === 'admin'],
            ['label' => 'Suspended', 'url' => app_route('admin/users', array_filter(['q' => $search ?: null, 'filter' => 'suspended'])), 'active' => $filter === 'suspended'],
        ]); ?>
    </div>
</div>

<?php if ($result['rows'] === []): ?>
    <?php admin_render_empty('No users match your search.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['User', 'Plan', 'Businesses', 'Tokens', 'Status', 'Joined']); ?>
    <?php foreach ($result['rows'] as $row): ?>
        <tr>
            <td>
                <a href="<?= e(app_route('admin/user', ['id' => (string) $row['id']])) ?>"><?= e((string) ($row['email'] ?? '')) ?></a>
                <?php if (!empty($row['name'])): ?><br><span style="color:rgb(0 0 0 / 0.55);font-size:0.8125rem"><?= e((string) $row['name']) ?></span><?php endif; ?>
            </td>
            <td><?= e((string) ($row['plan_slug'] ?? '—')) ?></td>
            <td><?= e((string) ($row['business_count'] ?? '0')) ?></td>
            <td><?= e((string) ($row['token_balance'] ?? '0')) ?></td>
            <td>
                <?php if (!empty($row['is_suspended'])): ?>
                    <span class="admin-badge admin-badge--danger">Suspended</span>
                <?php elseif ((string) ($row['role'] ?? '') === AdminAuth::ROLE_ADMIN): ?>
                    <span class="admin-badge admin-badge--success">Admin</span>
                <?php else: ?>
                    <span class="<?= admin_status_class((string) ($row['subscription_status'] ?? 'none')) ?>"><?= e((string) ($row['subscription_status'] ?? 'none')) ?></span>
                <?php endif; ?>
            </td>
            <td><?= e(admin_format_datetime((string) ($row['created_at'] ?? ''))) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
    <?php admin_render_pagination($baseUrl, $page, $result['total'], 25); ?>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
