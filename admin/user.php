<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$userId = trim((string) ($_GET['id'] ?? ''));
$detail = $userId !== '' ? $adminService->repository()->getUserDetail($userId) : null;
if ($detail === null) {
    flash_set('error', 'User not found.');
    redirect(app_route('admin/users'));
}

$user = $detail['user'];
$adminNav = 'users';
$pageTitle = 'User — Admin';
$pageHeading = (string) ($user['email'] ?? 'User');
$success = flash_get('success');
$error = flash_get('error');
$actionUrl = app_route('admin/action');
$plans = PlanService::list();

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>

<div class="admin-detail-grid">
    <section class="admin-panel">
        <h2 class="admin-panel-title font-heading">Profile</h2>
        <dl class="admin-detail-list">
            <div class="admin-detail-row"><dt>Email</dt><dd><?= e((string) ($user['email'] ?? '')) ?></dd></div>
            <div class="admin-detail-row"><dt>Name</dt><dd><?= e((string) ($user['name'] ?? '—')) ?></dd></div>
            <div class="admin-detail-row"><dt>Role</dt><dd><?= e((string) ($user['role'] ?? AdminAuth::ROLE_USER)) ?></dd></div>
            <div class="admin-detail-row"><dt>Joined</dt><dd><?= e(admin_format_datetime((string) ($user['created_at'] ?? ''))) ?></dd></div>
            <div class="admin-detail-row"><dt>Token balance</dt><dd><?= e((string) ($detail['token_balance']['balance'] ?? '0')) ?></dd></div>
        </dl>
    </section>

    <section class="admin-panel">
        <h2 class="admin-panel-title font-heading">Admin actions</h2>
        <div class="admin-form-card">
            <form method="post" action="<?= e($actionUrl) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="grant_tokens">
                <input type="hidden" name="user_id" value="<?= e($userId) ?>">
                <input type="hidden" name="redirect" value="<?= e(app_route('admin/user', ['id' => $userId])) ?>">
                <div class="admin-form-row">
                    <label for="grant-amount">Grant tokens</label>
                    <input id="grant-amount" type="number" name="amount" min="1" step="1" value="100" required>
                </div>
                <div class="admin-form-row">
                    <label for="grant-note">Note</label>
                    <input id="grant-note" type="text" name="note" value="Support credit">
                </div>
                <button type="submit" class="dash-btn">Grant tokens</button>
            </form>
        </div>

        <div class="admin-actions" style="margin-top:0.85rem">
            <form method="post" action="<?= e($actionUrl) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="suspend_user">
                <input type="hidden" name="user_id" value="<?= e($userId) ?>">
                <input type="hidden" name="suspend" value="<?= !empty($user['is_suspended']) ? '0' : '1' ?>">
                <input type="hidden" name="redirect" value="<?= e(app_route('admin/user', ['id' => $userId])) ?>">
                <button
                    type="submit"
                    class="dash-btn dash-btn--ghost"
                    data-admin-confirm="<?= !empty($user['is_suspended']) ? 'Reactivate this user?' : 'Suspend this user? They will lose dashboard access.' ?>"
                    data-admin-confirm-title="<?= !empty($user['is_suspended']) ? 'Reactivate user?' : 'Suspend user?' ?>"
                    data-admin-confirm-label="<?= !empty($user['is_suspended']) ? 'Reactivate' : 'Suspend' ?>"
                    data-admin-confirm-tone="danger"
                ><?= !empty($user['is_suspended']) ? 'Reactivate user' : 'Suspend user' ?></button>
            </form>

            <form method="post" action="<?= e($actionUrl) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="impersonate">
                <input type="hidden" name="user_id" value="<?= e($userId) ?>">
                <button
                    type="submit"
                    class="dash-btn dash-btn--ghost"
                    data-admin-confirm="You will view the app as this user. Every action is audited."
                    data-admin-confirm-title="Impersonate user?"
                    data-admin-confirm-label="Impersonate"
                >Impersonate</button>
            </form>
        </div>

        <div class="admin-form-card" style="margin-top:0.85rem">
            <form method="post" action="<?= e($actionUrl) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="override_subscription">
                <input type="hidden" name="user_id" value="<?= e($userId) ?>">
                <input type="hidden" name="redirect" value="<?= e(app_route('admin/user', ['id' => $userId])) ?>">
                <div class="admin-form-row">
                    <label for="plan-slug">Comp / override plan</label>
                    <select id="plan-slug" name="plan_slug" class="admin-select">
                        <?php foreach ($plans as $plan): ?>
                            <option value="<?= e((string) $plan['slug']) ?>"><?= e((string) $plan['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-row">
                    <label for="plan-days">Days</label>
                    <input id="plan-days" type="number" name="days" min="1" value="30">
                </div>
                <button type="submit" class="dash-btn">Apply override</button>
            </form>
        </div>
    </section>
</div>

<section class="admin-panel">
    <h2 class="admin-panel-title font-heading">Businesses</h2>
    <?php if ($detail['businesses'] === []): ?>
        <p class="font-sans" style="color:rgb(0 0 0 / 0.58)">No businesses.</p>
    <?php else: ?>
        <?php admin_render_table_start(['Name', 'Plan', 'Onboarding', '']); ?>
        <?php foreach ($detail['businesses'] as $business): ?>
            <tr>
                <td><a href="<?= e(app_route('admin/business', ['id' => (string) $business['id']])) ?>"><?= e((string) ($business['business_name'] ?? '')) ?></a></td>
                <td><?= e((string) ($business['subscription_plan'] ?? '—')) ?></td>
                <td><?= !empty($business['onboarding_completed_at']) ? 'Complete' : 'In progress' ?></td>
                <td><?= e(admin_format_datetime((string) ($business['created_at'] ?? ''))) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php admin_render_table_end(); ?>
    <?php endif; ?>
</section>

<section class="admin-panel">
    <h2 class="admin-panel-title font-heading">Subscriptions</h2>
    <?php admin_render_table_start(['Plan', 'Status', 'Period end', '']); ?>
    <?php foreach ($detail['subscriptions'] as $sub): ?>
        <tr>
            <td><?= e((string) ($sub['plan_slug'] ?? '')) ?></td>
            <td><span class="<?= admin_status_class((string) ($sub['status'] ?? '')) ?>"><?= e((string) ($sub['status'] ?? '')) ?></span></td>
            <td><?= e(admin_format_datetime((string) ($sub['current_period_end'] ?? ''))) ?></td>
            <td>
                <?php if ((string) ($sub['status'] ?? '') === 'active'): ?>
                    <form method="post" action="<?= e($actionUrl) ?>" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="cancel_subscription">
                        <input type="hidden" name="subscription_id" value="<?= e((string) $sub['id']) ?>">
                        <button type="submit" class="dash-btn dash-btn--ghost" data-admin-confirm="Cancel this subscription?" data-admin-confirm-title="Cancel subscription?" data-admin-confirm-label="Cancel" data-admin-confirm-tone="danger">Cancel</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
</section>

<section class="admin-panel">
    <h2 class="admin-panel-title font-heading">Token ledger</h2>
    <?php admin_render_table_start(['Delta', 'Balance', 'Reason', 'When']); ?>
    <?php foreach ($detail['token_ledger'] as $entry): ?>
        <tr>
            <td><?= e((string) ($entry['delta'] ?? '')) ?></td>
            <td><?= e((string) ($entry['balance_after'] ?? '')) ?></td>
            <td><?= e((string) ($entry['reason'] ?? '')) ?></td>
            <td><?= e(admin_format_datetime((string) ($entry['created_at'] ?? ''))) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
</section>

<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
