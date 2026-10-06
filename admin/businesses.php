<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'businesses';
$pageTitle = 'Businesses — Admin';
$pageHeading = 'Businesses';
$success = flash_get('success');
$error = flash_get('error');
$page = admin_page((int) ($_GET['page'] ?? 1));
$search = trim((string) ($_GET['q'] ?? ''));
$result = $adminService->repository()->listBusinesses($page, 25, $search !== '' ? $search : null);
$baseUrl = app_route('admin/businesses', array_filter(['q' => $search ?: null]));

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>
<div class="admin-toolbar"><?php admin_toolbar_search('Search businesses or owner email…', $search); ?></div>
<?php if ($result['rows'] === []): ?>
    <?php admin_render_empty('No businesses found.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['Business', 'Owner', 'Plan', 'Channels', 'Customers', 'Updated']); ?>
    <?php foreach ($result['rows'] as $row): ?>
        <tr>
            <td><a href="<?= e(app_route('admin/business', ['id' => (string) $row['id']])) ?>"><?= e((string) ($row['business_name'] ?? '')) ?></a></td>
            <td><?= e((string) ($row['owner_email'] ?? '')) ?></td>
            <td><?= e((string) ($row['subscription_plan'] ?? '—')) ?></td>
            <td><?= e((string) ($row['channel_count'] ?? '0')) ?></td>
            <td><?= e((string) ($row['customer_count'] ?? '0')) ?></td>
            <td><?= e(admin_format_datetime((string) ($row['updated_at'] ?? ''))) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
    <?php admin_render_pagination($baseUrl, $page, $result['total'], 25); ?>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
