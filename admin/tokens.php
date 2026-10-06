<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'tokens';
$pageTitle = 'Tokens — Admin';
$pageHeading = 'Tokens & AI usage';
$success = flash_get('success');
$error = flash_get('error');
$page = admin_page((int) ($_GET['page'] ?? 1));
$search = trim((string) ($_GET['q'] ?? ''));
$result = $adminService->repository()->listTokenBalances($page, 25, $search !== '' ? $search : null);
$baseUrl = app_route('admin/tokens', array_filter(['q' => $search ?: null]));

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>
<div class="admin-toolbar"><?php admin_toolbar_search('Search user email…', $search); ?></div>
<?php if ($result['rows'] === []): ?>
    <?php admin_render_empty('No token balances found.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['User', 'Plan', 'Balance', 'Updated']); ?>
    <?php foreach ($result['rows'] as $row): ?>
        <tr>
            <td><a href="<?= e(app_route('admin/user', ['id' => (string) $row['user_id']])) ?>"><?= e((string) ($row['email'] ?? '')) ?></a></td>
            <td><?= e((string) ($row['plan_slug'] ?? '—')) ?></td>
            <td><?= e((string) ($row['balance'] ?? '0')) ?></td>
            <td><?= e(admin_format_datetime((string) ($row['updated_at'] ?? ''))) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
    <?php admin_render_pagination($baseUrl, $page, $result['total'], 25); ?>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
