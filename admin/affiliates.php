<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'affiliates';
$pageTitle = 'Affiliates — Admin';
$pageHeading = 'Affiliates & payouts';
$success = flash_get('success');
$error = flash_get('error');
$page = admin_page((int) ($_GET['page'] ?? 1));
$search = trim((string) ($_GET['q'] ?? ''));
$result = $adminService->repository()->listAffiliates($page, 25, $search !== '' ? $search : null);
$baseUrl = app_route('admin/affiliates', array_filter(['q' => $search ?: null]));
$readyCommissions = RepositoryFactory::affiliateCommissions()->listReadyForRelease();

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>
<?php if ($readyCommissions !== []): ?>
    <div class="admin-panel">
        <h2 class="admin-panel-title font-heading">Commissions ready for release</h2>
        <p class="font-sans" style="font-size:0.875rem;color:rgb(0 0 0 / 0.58)"><?= count($readyCommissions) ?> commission(s) past hold period.</p>
    </div>
<?php endif; ?>
<div class="admin-toolbar"><?php admin_toolbar_search('Search code, name, or email…', $search); ?></div>
<?php if ($result['rows'] === []): ?>
    <?php admin_render_empty('No affiliates found.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['Affiliate', 'Code', 'Status', 'Commissions', 'Paid total', 'Joined']); ?>
    <?php foreach ($result['rows'] as $row): ?>
        <tr>
            <td><?= e((string) ($row['name'] ?? '')) ?><br><span style="font-size:0.8125rem;color:rgb(0 0 0 / 0.55)"><?= e((string) ($row['email'] ?? '')) ?></span></td>
            <td><?= e((string) ($row['code'] ?? '')) ?></td>
            <td><span class="<?= admin_status_class((string) ($row['status'] ?? '')) ?>"><?= e((string) ($row['status'] ?? '')) ?></span></td>
            <td><?= e((string) ($row['commission_count'] ?? '0')) ?></td>
            <td><?= e(admin_format_money((float) ($row['paid_total'] ?? 0))) ?></td>
            <td><?= e(admin_format_datetime((string) ($row['created_at'] ?? ''))) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
    <?php admin_render_pagination($baseUrl, $page, $result['total'], 25); ?>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
