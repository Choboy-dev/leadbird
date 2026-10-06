<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'admins';
$pageTitle = 'Admin users — Admin';
$pageHeading = 'Admin users';
$success = flash_get('success');
$error = flash_get('error');
$admins = $adminService->repository()->listAdmins();
$allowlist = AdminAuth::emailAllowlist();
$actionUrl = app_route('admin/action');

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>

<section class="admin-panel">
    <h2 class="admin-panel-title font-heading">Current administrators</h2>
    <?php if ($admins === []): ?>
        <?php admin_render_empty('No admin users yet. Promote a user or add their email to ADMIN_EMAILS in .env.'); ?>
    <?php else: ?>
        <?php admin_render_table_start(['User', 'Role', 'Joined', 'Actions']); ?>
        <?php foreach ($admins as $row): ?>
            <tr>
                <td><a href="<?= e(app_route('admin/user', ['id' => (string) $row['id']])) ?>"><?= e((string) ($row['email'] ?? '')) ?></a></td>
                <td><?= e((string) ($row['role'] ?? AdminAuth::ROLE_USER)) ?></td>
                <td><?= e(admin_format_datetime((string) ($row['created_at'] ?? ''))) ?></td>
                <td>
                    <?php if ((string) ($row['role'] ?? '') !== AdminAuth::ROLE_ADMIN): ?>
                        <form method="post" action="<?= e($actionUrl) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="set_role">
                            <input type="hidden" name="user_id" value="<?= e((string) $row['id']) ?>">
                            <input type="hidden" name="role" value="<?= e(AdminAuth::ROLE_ADMIN) ?>">
                            <input type="hidden" name="redirect" value="<?= e(app_route('admin/admins')) ?>">
                            <button type="submit" class="dash-btn">Promote to admin</button>
                        </form>
                    <?php elseif ((string) $row['id'] !== (string) $adminUserId): ?>
                        <form method="post" action="<?= e($actionUrl) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="set_role">
                            <input type="hidden" name="user_id" value="<?= e((string) $row['id']) ?>">
                            <input type="hidden" name="role" value="<?= e(AdminAuth::ROLE_USER) ?>">
                            <input type="hidden" name="redirect" value="<?= e(app_route('admin/admins')) ?>">
                            <button type="submit" class="dash-btn dash-btn--ghost" data-admin-confirm="Remove admin role from this user?" data-admin-confirm-title="Demote admin?" data-admin-confirm-label="Demote" data-admin-confirm-tone="danger">Demote</button>
                        </form>
                    <?php else: ?>
                        <span class="font-sans" style="font-size:0.8125rem;color:rgb(0 0 0 / 0.55)">You</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php admin_render_table_end(); ?>
    <?php endif; ?>
</section>

<?php if ($allowlist !== []): ?>
<section class="admin-panel">
    <h2 class="admin-panel-title font-heading">Email allowlist (.env)</h2>
    <ul class="admin-detail-list">
        <?php foreach ($allowlist as $email): ?>
            <li class="admin-detail-row"><span><?= e($email) ?></span><span>Allowlist</span></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
