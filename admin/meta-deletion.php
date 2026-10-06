<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'meta_deletion';
$pageTitle = 'Meta deletion — Admin';
$pageHeading = 'Meta data deletion requests';
$success = flash_get('success');
$error = flash_get('error');
$page = admin_page((int) ($_GET['page'] ?? 1));
$status = trim((string) ($_GET['status'] ?? ''));
$result = $adminService->repository()->listMetaDeletionRequests($page, 25, $status !== '' ? $status : null);
$baseUrl = app_route('admin/meta-deletion', array_filter(['status' => $status ?: null]));

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>
<div class="admin-toolbar">
    <?php admin_render_filter_links([
        ['label' => 'All', 'url' => app_route('admin/meta-deletion'), 'active' => $status === ''],
        ['label' => 'Pending', 'url' => app_route('admin/meta-deletion', ['status' => 'pending']), 'active' => $status === 'pending'],
        ['label' => 'Completed', 'url' => app_route('admin/meta-deletion', ['status' => 'completed']), 'active' => $status === 'completed'],
        ['label' => 'Failed', 'url' => app_route('admin/meta-deletion', ['status' => 'failed']), 'active' => $status === 'failed'],
    ]); ?>
</div>
<?php if ($result['rows'] === []): ?>
    <?php admin_render_empty('No Meta deletion requests found.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['Code', 'Meta user', 'Status', 'Businesses', 'Requested', 'Completed']); ?>
    <?php foreach ($result['rows'] as $row): ?>
        <tr>
            <td><?= e((string) ($row['confirmation_code'] ?? '')) ?></td>
            <td><?= e((string) ($row['meta_user_id'] ?? '')) ?></td>
            <td><span class="<?= admin_status_class((string) ($row['status'] ?? '')) ?>"><?= e((string) ($row['status'] ?? '')) ?></span></td>
            <td><?= e((string) ($row['businesses_affected'] ?? '—')) ?></td>
            <td><?= e(admin_format_datetime((string) ($row['requested_at'] ?? ''))) ?></td>
            <td><?= e(admin_format_datetime((string) ($row['completed_at'] ?? ''))) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
    <?php admin_render_pagination($baseUrl, $page, $result['total'], 25); ?>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
