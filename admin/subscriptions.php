<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'subscriptions';
$pageTitle = 'Subscriptions — Admin';
$pageHeading = 'Subscriptions';
$success = flash_get('success');
$error = flash_get('error');
$page = admin_page((int) ($_GET['page'] ?? 1));
$status = trim((string) ($_GET['status'] ?? ''));
$filter = trim((string) ($_GET['filter'] ?? ''));
$result = $adminService->repository()->listSubscriptions(
    $page,
    25,
    $status !== '' ? $status : null,
    $filter !== '' ? $filter : null
);
$baseUrl = app_route('admin/subscriptions', array_filter(['status' => $status ?: null, 'filter' => $filter ?: null]));
$actionUrl = app_route('admin/action');

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>
<div class="admin-toolbar">
    <?php admin_render_filter_links([
        ['label' => 'All', 'url' => app_route('admin/subscriptions'), 'active' => $status === '' && $filter === ''],
        ['label' => 'Active', 'url' => app_route('admin/subscriptions', ['status' => 'active']), 'active' => $status === 'active'],
        ['label' => 'Cancelled', 'url' => app_route('admin/subscriptions', ['status' => 'cancelled']), 'active' => $status === 'cancelled'],
        ['label' => 'Due renewal', 'url' => app_route('admin/subscriptions', ['filter' => 'due_renewal']), 'active' => $filter === 'due_renewal'],
    ]); ?>
</div>
<?php if ($result['rows'] === []): ?>
    <?php admin_render_empty('No subscriptions found.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['User', 'Plan', 'Amount', 'Status', 'Period end', '']); ?>
    <?php foreach ($result['rows'] as $row): ?>
        <tr>
            <td><a href="<?= e(app_route('admin/user', ['id' => (string) $row['user_id']])) ?>"><?= e((string) ($row['user_email'] ?? '')) ?></a></td>
            <td><?= e((string) ($row['plan_slug'] ?? '')) ?></td>
            <td><?= e(admin_format_money((float) ($row['amount'] ?? 0), (string) ($row['currency'] ?? 'USD'))) ?></td>
            <td><span class="<?= admin_status_class((string) ($row['status'] ?? '')) ?>"><?= e((string) ($row['status'] ?? '')) ?></span></td>
            <td><?= e(admin_format_datetime((string) ($row['current_period_end'] ?? ''))) ?></td>
            <td>
                <?php if ((string) ($row['status'] ?? '') === 'active'): ?>
                    <form method="post" action="<?= e($actionUrl) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="cancel_subscription">
                        <input type="hidden" name="subscription_id" value="<?= e((string) $row['id']) ?>">
                        <button type="submit" class="dash-btn dash-btn--ghost" data-admin-confirm="Cancel this subscription?" data-admin-confirm-title="Cancel subscription?" data-admin-confirm-label="Cancel" data-admin-confirm-tone="danger">Cancel</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
    <?php admin_render_pagination($baseUrl, $page, $result['total'], 25); ?>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
