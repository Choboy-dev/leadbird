<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'feature_flags';
$pageTitle = 'Config — Admin';
$pageHeading = 'Config & feature flags';
$success = flash_get('success');
$error = flash_get('error');
$flags = $adminService->repository()->listFeatureFlags();
$plans = PlanService::list();
$actionUrl = app_route('admin/action');

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>

<div class="admin-detail-grid">
    <section class="admin-panel">
        <h2 class="admin-panel-title font-heading">Feature flags</h2>
        <?php foreach ($flags as $flag): ?>
            <?php
            $key = (string) ($flag['flag_key'] ?? '');
            $raw = json_decode((string) ($flag['flag_value'] ?? 'null'), true);
            $display = is_bool($raw) ? ($raw ? 'true' : 'false') : (string) $raw;
            ?>
            <form method="post" action="<?= e($actionUrl) ?>" class="admin-form-card" style="margin-bottom:0.75rem">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_feature_flag">
                <input type="hidden" name="flag_key" value="<?= e($key) ?>">
                <p class="font-heading" style="margin:0 0 0.35rem"><?= e($key) ?></p>
                <p class="font-sans" style="font-size:0.8125rem;color:rgb(0 0 0 / 0.58);margin:0 0 0.65rem"><?= e((string) ($flag['description'] ?? '')) ?></p>
                <select name="flag_value">
                    <option value="true" <?= $display === 'true' || $display === '1' ? 'selected' : '' ?>>Enabled</option>
                    <option value="false" <?= $display === 'false' || $display === '0' ? 'selected' : '' ?>>Disabled</option>
                </select>
                <button type="submit" class="dash-btn" style="margin-top:0.65rem">Save</button>
            </form>
        <?php endforeach; ?>
    </section>

    <section class="admin-panel">
        <h2 class="admin-panel-title font-heading">Plan catalog (read-only)</h2>
        <?php admin_render_table_start(['Plan', 'Monthly USD', 'Businesses', 'Tokens/mo']); ?>
        <?php foreach ($plans as $plan): ?>
            <tr>
                <td><?= e((string) ($plan['name'] ?? '')) ?></td>
                <td>$<?= e(number_format((float) ($plan['price'] ?? 0), 0)) ?></td>
                <td><?= e((string) ($plan['business_limit'] ?? '∞')) ?></td>
                <td><?= e((string) ($plan['monthly_tokens'] ?? '∞')) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php admin_render_table_end(); ?>
        <p class="font-sans" style="font-size:0.8125rem;color:rgb(0 0 0 / 0.58);margin:0.85rem 0 0">Plan prices are defined in <code>config/plans.php</code>. Change via code review.</p>
    </section>
</div>

<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
