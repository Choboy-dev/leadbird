<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'overview';
$pageTitle = 'Admin overview — ' . $brandName;
$pageHeading = 'Overview';
$success = flash_get('success');
$error = flash_get('error');
$overview = $adminService->overview();

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);

admin_render_kpi_cards([
    ['label' => 'Total users', 'value' => (string) $overview['total_users'], 'url' => app_route('admin/users')],
    ['label' => 'Active subscriptions', 'value' => (string) $overview['active_subscriptions'], 'url' => app_route('admin/subscriptions')],
    ['label' => 'Businesses', 'value' => (string) $overview['total_businesses'], 'url' => app_route('admin/businesses')],
    ['label' => 'Est. MRR', 'value' => admin_format_money((float) $overview['mrr_usd']), 'hint' => 'Active subs total (USD)'],
    ['label' => 'Messages today', 'value' => (string) $overview['messages_today']],
    ['label' => 'AI replies today', 'value' => (string) $overview['ai_replies_today']],
    ['label' => 'Failed jobs', 'value' => (string) $overview['failed_jobs'], 'url' => app_route('admin/automation', ['status' => 'failed'])],
    ['label' => 'OpenAI cost (30d)', 'value' => admin_format_openai_usd((float) $overview['openai_cost_30d']), 'url' => app_route('admin/openai-usage')],
]);
?>

<div class="admin-panels">
    <section class="admin-panel">
        <h2 class="admin-panel-title font-heading">Recent signups</h2>
        <?php if ($overview['recent_signups'] === []): ?>
            <?php admin_render_empty('No users yet.'); ?>
        <?php else: ?>
            <?php admin_render_table_start(['User', 'Plan', 'Joined']); ?>
            <?php foreach ($overview['recent_signups'] as $row): ?>
                <tr>
                    <td><a href="<?= e(app_route('admin/user', ['id' => (string) $row['id']])) ?>"><?= e((string) ($row['email'] ?? '')) ?></a></td>
                    <td><?= e((string) ($row['plan_slug'] ?? '—')) ?></td>
                    <td><?= e(admin_format_datetime((string) ($row['created_at'] ?? ''))) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php admin_render_table_end(); ?>
        <?php endif; ?>
    </section>

    <section class="admin-panel">
        <h2 class="admin-panel-title font-heading">Alerts</h2>
        <?php if ($overview['alerts'] === []): ?>
            <?php admin_render_empty('All clear — no urgent alerts.'); ?>
        <?php else: ?>
            <ul class="admin-alerts-list">
                <?php foreach ($overview['alerts'] as $alert): ?>
                    <li class="admin-alerts-item admin-alerts-item--<?= e((string) ($alert['severity'] ?? 'warning')) ?>">
                        <a href="<?= e((string) ($alert['url'] ?? '#')) ?>"><?= e((string) ($alert['message'] ?? '')) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h3 class="admin-panel-title font-heading" style="margin-top:1.25rem">Plan mix</h3>
        <?php if ($overview['plan_distribution'] === []): ?>
            <p class="font-sans" style="color:rgb(0 0 0 / 0.58);font-size:0.875rem">No active subscriptions.</p>
        <?php else: ?>
            <ul class="admin-detail-list">
                <?php foreach ($overview['plan_distribution'] as $planRow): ?>
                    <li class="admin-detail-row">
                        <span><?= e((string) ($planRow['plan_slug'] ?? '')) ?></span>
                        <strong><?= e((string) ($planRow['cnt'] ?? '0')) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<section class="admin-panel">
    <h2 class="admin-panel-title font-heading">Automation queue</h2>
    <div class="admin-detail-grid">
        <?php foreach ($overview['queue_stats'] as $status => $count): ?>
            <div class="admin-kpi-card">
                <p class="admin-kpi-label font-sans"><?= e(ucfirst((string) $status)) ?></p>
                <p class="admin-kpi-value font-heading"><?= e((string) $count) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
