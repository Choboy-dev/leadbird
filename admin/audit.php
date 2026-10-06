<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'audit';
$pageTitle = 'Audit log — Admin';
$pageHeading = 'Audit log';
$success = flash_get('success');
$error = flash_get('error');
$page = admin_page((int) ($_GET['page'] ?? 1));
$search = trim((string) ($_GET['q'] ?? ''));
$actionFilter = trim((string) ($_GET['action'] ?? ''));
$result = RepositoryFactory::adminAuditLog()->listFiltered(
    $page,
    25,
    $actionFilter !== '' ? $actionFilter : null,
    null,
    $search !== '' ? $search : null
);
$baseUrl = app_route('admin/audit', array_filter(['q' => $search ?: null, 'action' => $actionFilter ?: null]));

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>
<div class="admin-toolbar"><?php admin_toolbar_search('Search action, email, or target…', $search); ?></div>
<?php if ($result['rows'] === []): ?>
    <?php admin_render_empty('No audit events yet.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['When', 'Admin', 'Action', 'Target', 'Details']); ?>
    <?php foreach ($result['rows'] as $row): ?>
        <tr>
            <td><?= e(admin_format_datetime((string) ($row['created_at'] ?? ''))) ?></td>
            <td><?= e((string) ($row['admin_email'] ?? '')) ?></td>
            <td><?= e((string) ($row['action'] ?? '')) ?></td>
            <td><?= e(trim((string) ($row['target_type'] ?? '') . ' ' . (string) ($row['target_id'] ?? ''))) ?></td>
            <td style="max-width:16rem;font-size:0.8125rem"><?= e(mb_substr((string) ($row['details'] ?? ''), 0, 160)) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
    <?php admin_render_pagination($baseUrl, $page, $result['total'], 25); ?>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
