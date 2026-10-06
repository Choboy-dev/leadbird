<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'channels';
$pageTitle = 'Channels — Admin';
$pageHeading = 'Connected channels';
$success = flash_get('success');
$error = flash_get('error');
$page = admin_page((int) ($_GET['page'] ?? 1));
$platform = trim((string) ($_GET['platform'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$result = $adminService->repository()->listChannels($page, 25, $platform !== '' ? $platform : null, $search !== '' ? $search : null);
$baseUrl = app_route('admin/channels', array_filter(['platform' => $platform ?: null, 'q' => $search ?: null]));
$actionUrl = app_route('admin/action');

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>
<div class="admin-toolbar">
    <?php admin_toolbar_search('Search business or account…', $search); ?>
    <div class="admin-actions">
        <?php admin_render_filter_links([
            ['label' => 'All', 'url' => app_route('admin/channels', array_filter(['q' => $search ?: null])), 'active' => $platform === ''],
            ['label' => 'Instagram', 'url' => app_route('admin/channels', array_filter(['q' => $search ?: null, 'platform' => 'instagram'])), 'active' => $platform === 'instagram'],
            ['label' => 'Facebook', 'url' => app_route('admin/channels', array_filter(['q' => $search ?: null, 'platform' => 'facebook'])), 'active' => $platform === 'facebook'],
            ['label' => 'WhatsApp', 'url' => app_route('admin/channels', array_filter(['q' => $search ?: null, 'platform' => 'whatsapp'])), 'active' => $platform === 'whatsapp'],
        ]); ?>
    </div>
</div>
<?php if ($result['rows'] === []): ?>
    <?php admin_render_empty('No connected channels found.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['Business', 'Owner', 'Platform', 'Account', 'Connected']); ?>
    <?php foreach ($result['rows'] as $row): ?>
        <tr>
            <td><a href="<?= e(app_route('admin/business', ['id' => (string) $row['business_id']])) ?>"><?= e((string) ($row['business_name'] ?? '')) ?></a></td>
            <td><?= e((string) ($row['owner_email'] ?? '')) ?></td>
            <td><?= e((string) ($row['platform'] ?? '')) ?></td>
            <td><?= e((string) ($row['display_name'] ?? $row['platform_account_id'] ?? '')) ?></td>
            <td>
                <?= e(admin_format_datetime((string) ($row['created_at'] ?? ''))) ?>
                <form method="post" action="<?= e($actionUrl) ?>" style="margin-top:0.35rem">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="disconnect_channel">
                    <input type="hidden" name="account_id" value="<?= e((string) $row['id']) ?>">
                    <button type="submit" class="dash-btn dash-btn--ghost" data-admin-confirm="Disconnect this channel?" data-admin-confirm-title="Disconnect channel?" data-admin-confirm-label="Disconnect" data-admin-confirm-tone="danger">Disconnect</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
    <?php admin_render_pagination($baseUrl, $page, $result['total'], 25); ?>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
