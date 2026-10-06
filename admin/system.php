<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'system';
$pageTitle = 'System health — Admin';
$pageHeading = 'System health';
$success = flash_get('success');
$error = flash_get('error');
$checks = admin_system_health();
$queueStats = $adminService->repository()->queueStats();

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>

<section class="admin-panel">
    <h2 class="admin-panel-title font-heading">Environment checks</h2>
    <?php admin_render_table_start(['Service', 'Status', 'Detail']); ?>
    <?php foreach ($checks as $check): ?>
        <tr>
            <td><?= e((string) ($check['label'] ?? '')) ?></td>
            <td><span class="<?= admin_status_class((string) (($check['status'] ?? '') === 'ok' ? 'active' : (($check['status'] ?? '') === 'error' ? 'failed' : 'pending'))) ?>"><?= e((string) ($check['status'] ?? '')) ?></span></td>
            <td><?= e((string) ($check['detail'] ?? '')) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
</section>

<section class="admin-panel">
    <h2 class="admin-panel-title font-heading">Automation queue snapshot</h2>
    <div class="admin-detail-grid">
        <?php foreach ($queueStats as $status => $count): ?>
            <div class="admin-kpi-card">
                <p class="admin-kpi-label font-sans"><?= e(ucfirst((string) $status)) ?></p>
                <p class="admin-kpi-value font-heading"><?= e((string) $count) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="admin-panel">
    <h2 class="admin-panel-title font-heading">Cron scripts</h2>
    <ul class="admin-detail-list">
        <li class="admin-detail-row"><span>process-automation-queue.php</span><span>Every minute</span></li>
        <li class="admin-detail-row"><span>renew-subscriptions.php</span><span>Daily</span></li>
        <li class="admin-detail-row"><span>refresh-fx-rate.php</span><span>Periodic</span></li>
        <li class="admin-detail-row"><span>release-affiliate-holds.php</span><span>Daily</span></li>
        <li class="admin-detail-row"><span>process-scheduled-posts.php</span><span>Every minute</span></li>
        <li class="admin-detail-row"><span>process-followups.php</span><span>Every 10 minutes</span></li>
    </ul>
</section>

<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
