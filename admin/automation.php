<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'automation';
$pageTitle = 'Automation — Admin';
$pageHeading = 'Automation queue';
$success = flash_get('success');
$error = flash_get('error');
$page = admin_page((int) ($_GET['page'] ?? 1));
$status = trim((string) ($_GET['status'] ?? ''));
$result = $adminService->repository()->listAutomationJobs($page, 25, $status !== '' ? $status : null);
$baseUrl = app_route('admin/automation', array_filter(['status' => $status ?: null]));
$actionUrl = app_route('admin/action');
$stats = $adminService->repository()->queueStats();

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);

$kpiCards = [];
foreach ($stats as $label => $value) {
    $kpiCards[] = [
        'label' => ucfirst((string) $label),
        'value' => (string) $value,
        'url' => app_route('admin/automation', ['status' => (string) $label]),
    ];
}
admin_render_kpi_cards($kpiCards);
?>
<div class="admin-toolbar">
    <?php admin_render_filter_links([
        ['label' => 'All', 'url' => app_route('admin/automation'), 'active' => $status === ''],
        ['label' => 'Pending', 'url' => app_route('admin/automation', ['status' => 'pending']), 'active' => $status === 'pending'],
        ['label' => 'Failed', 'url' => app_route('admin/automation', ['status' => 'failed']), 'active' => $status === 'failed'],
    ]); ?>
</div>
<?php if ($result['rows'] === []): ?>
    <?php admin_render_empty('No jobs in this queue state.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['Type', 'Status', 'Attempts', 'Error', 'Created', '']); ?>
    <?php foreach ($result['rows'] as $row): ?>
        <tr>
            <td><?= e((string) ($row['job_type'] ?? '')) ?></td>
            <td><span class="<?= admin_status_class((string) ($row['status'] ?? '')) ?>"><?= e((string) ($row['status'] ?? '')) ?></span></td>
            <td><?= e((string) ($row['attempts'] ?? '0')) ?></td>
            <td style="max-width:18rem"><?= e(mb_substr((string) ($row['last_error'] ?? ''), 0, 120)) ?></td>
            <td><?= e(admin_format_datetime((string) ($row['created_at'] ?? ''))) ?></td>
            <td>
                <?php if ((string) ($row['status'] ?? '') === 'failed'): ?>
                    <form method="post" action="<?= e($actionUrl) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="retry_job">
                        <input type="hidden" name="job_id" value="<?= e((string) $row['id']) ?>">
                        <button type="submit" class="dash-btn">Retry</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
    <?php admin_render_pagination($baseUrl, $page, $result['total'], 25); ?>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
