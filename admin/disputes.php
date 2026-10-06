<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'disputes';
$pageTitle = 'Disputes — Admin';
$pageHeading = 'Wallet disputes';
$success = flash_get('success');
$error = flash_get('error');
$page = admin_page((int) ($_GET['page'] ?? 1));
$status = trim((string) ($_GET['status'] ?? ''));
$result = RepositoryFactory::walletDisputes()->listFiltered($page, 25, $status !== '' ? $status : null);
$baseUrl = app_route('admin/disputes', array_filter(['status' => $status ?: null]));
$actionUrl = app_route('admin/action');

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>
<div class="admin-toolbar">
    <?php admin_render_filter_links([
        ['label' => 'All', 'url' => app_route('admin/disputes'), 'active' => $status === ''],
        ['label' => 'Open', 'url' => app_route('admin/disputes', ['status' => 'open']), 'active' => $status === 'open'],
        ['label' => 'Resolved', 'url' => app_route('admin/disputes', ['status' => 'resolved']), 'active' => $status === 'resolved'],
    ]); ?>
</div>
<?php if ($result['rows'] === []): ?>
    <?php admin_render_empty('No disputes found.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['Entity', 'Reason', 'Status', 'Notes', 'Update']); ?>
    <?php foreach ($result['rows'] as $row): ?>
        <tr>
            <td><?= e((string) ($row['entity_type'] ?? '')) ?> · <?= e((string) ($row['entity_id'] ?? '')) ?></td>
            <td><?= e((string) ($row['reason'] ?? '')) ?></td>
            <td><span class="<?= admin_status_class((string) ($row['status'] ?? '')) ?>"><?= e((string) ($row['status'] ?? '')) ?></span></td>
            <td style="max-width:14rem"><?= e(mb_substr((string) ($row['admin_notes'] ?? ''), 0, 120)) ?></td>
            <td>
                <form method="post" action="<?= e($actionUrl) ?>" class="admin-form-card" style="padding:0.65rem">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_dispute">
                    <input type="hidden" name="dispute_id" value="<?= e((string) $row['id']) ?>">
                    <select name="status">
                        <option value="open">Open</option>
                        <option value="investigating">Investigating</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                    <textarea name="admin_notes" rows="2" placeholder="Admin notes"><?= e((string) ($row['admin_notes'] ?? '')) ?></textarea>
                    <button type="submit" class="dash-btn" style="margin-top:0.5rem">Save</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
    <?php admin_render_pagination($baseUrl, $page, $result['total'], 25); ?>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
