<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$businessId = trim((string) ($_GET['id'] ?? ''));
$detail = $businessId !== '' ? $adminService->repository()->getBusinessDetail($businessId) : null;
if ($detail === null) {
    flash_set('error', 'Business not found.');
    redirect(app_route('admin/businesses'));
}

$business = $detail['business'];
$owner = $detail['owner'];
$adminNav = 'businesses';
$pageTitle = 'Business — Admin';
$pageHeading = (string) ($business['business_name'] ?? 'Business');
$success = flash_get('success');
$error = flash_get('error');
$actionUrl = app_route('admin/action');

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>

<div class="admin-detail-grid">
    <section class="admin-panel">
        <h2 class="admin-panel-title font-heading">Summary</h2>
        <dl class="admin-detail-list">
            <div class="admin-detail-row"><dt>Owner</dt><dd><a href="<?= e(app_route('admin/user', ['id' => (string) ($owner['id'] ?? '')])) ?>"><?= e((string) ($owner['email'] ?? '')) ?></a></dd></div>
            <div class="admin-detail-row"><dt>Plan</dt><dd><?= e((string) ($business['subscription_plan'] ?? '—')) ?></dd></div>
            <div class="admin-detail-row"><dt>Status</dt><dd><?= e((string) ($business['subscription_status'] ?? '—')) ?></dd></div>
            <div class="admin-detail-row"><dt>Onboarding</dt><dd><?= !empty($business['onboarding_completed_at']) ? 'Complete' : 'In progress' ?></dd></div>
            <div class="admin-detail-row"><dt>Offers</dt><dd><?= e((string) $detail['offerings_count']) ?></dd></div>
            <div class="admin-detail-row"><dt>Conversations</dt><dd><?= e((string) $detail['conversations_count']) ?></dd></div>
        </dl>
    </section>

    <section class="admin-panel">
        <h2 class="admin-panel-title font-heading">Connected channels</h2>
        <?php if ($detail['channels'] === []): ?>
            <p class="font-sans" style="color:rgb(0 0 0 / 0.58)">No channels connected.</p>
        <?php else: ?>
            <?php admin_render_table_start(['Platform', 'Account', 'Status', '']); ?>
            <?php foreach ($detail['channels'] as $channel): ?>
                <tr>
                    <td><?= e((string) ($channel['platform'] ?? '')) ?></td>
                    <td><?= e((string) ($channel['display_name'] ?? $channel['platform_account_id'] ?? '')) ?></td>
                    <td><?= !empty($channel['is_active']) ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <?php if (!empty($channel['is_active'])): ?>
                            <form method="post" action="<?= e($actionUrl) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="disconnect_channel">
                                <input type="hidden" name="account_id" value="<?= e((string) $channel['id']) ?>">
                                <button type="submit" class="dash-btn dash-btn--ghost" data-admin-confirm="Disconnect this channel?" data-admin-confirm-title="Disconnect channel?" data-admin-confirm-label="Disconnect" data-admin-confirm-tone="danger">Disconnect</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php admin_render_table_end(); ?>
        <?php endif; ?>
    </section>
</div>

<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
