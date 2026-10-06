<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'reports';
$pageTitle = 'Reports — Admin';
$pageHeading = 'Reports & exports';
$success = flash_get('success');
$error = flash_get('error');
$export = trim((string) ($_GET['export'] ?? ''));

if ($export !== '') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leadbird-' . $export . '-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    if ($out === false) {
        exit;
    }

    switch ($export) {
        case 'users':
            fputcsv($out, ['id', 'email', 'name', 'role', 'is_suspended', 'created_at']);
            $page = 1;
            do {
                $batch = $adminService->repository()->listUsers($page, 100);
                foreach ($batch['rows'] as $row) {
                    fputcsv($out, [
                        $row['id'] ?? '',
                        $row['email'] ?? '',
                        $row['name'] ?? '',
                        $row['role'] ?? '',
                        $row['is_suspended'] ?? 0,
                        $row['created_at'] ?? '',
                    ]);
                }
                $page++;
            } while ($page <= (int) ceil($batch['total'] / 100));
            break;

        case 'subscriptions':
            fputcsv($out, ['id', 'user_id', 'user_email', 'plan_slug', 'status', 'amount', 'currency', 'current_period_end']);
            $page = 1;
            do {
                $batch = $adminService->repository()->listSubscriptions($page, 100);
                foreach ($batch['rows'] as $row) {
                    fputcsv($out, [
                        $row['id'] ?? '',
                        $row['user_id'] ?? '',
                        $row['user_email'] ?? '',
                        $row['plan_slug'] ?? '',
                        $row['status'] ?? '',
                        $row['amount'] ?? '',
                        $row['currency'] ?? '',
                        $row['current_period_end'] ?? '',
                    ]);
                }
                $page++;
            } while ($page <= (int) ceil($batch['total'] / 100));
            break;

        case 'audit':
            fputcsv($out, ['id', 'admin_email', 'action', 'target_type', 'target_id', 'created_at']);
            $page = 1;
            do {
                $batch = RepositoryFactory::adminAuditLog()->listFiltered($page, 100);
                foreach ($batch['rows'] as $row) {
                    fputcsv($out, [
                        $row['id'] ?? '',
                        $row['admin_email'] ?? '',
                        $row['action'] ?? '',
                        $row['target_type'] ?? '',
                        $row['target_id'] ?? '',
                        $row['created_at'] ?? '',
                    ]);
                }
                $page++;
            } while ($page <= (int) ceil($batch['total'] / 100));
            break;

        default:
            fputcsv($out, ['error'], ['Unknown export type']);
    }

    fclose($out);
    exit;
}

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>

<section class="admin-panel">
    <h2 class="admin-panel-title font-heading">CSV exports</h2>
    <p class="font-sans" style="font-size:0.875rem;color:rgb(0 0 0 / 0.58);margin:0 0 1rem">Download operational reports for accounting and support.</p>
    <div class="admin-actions">
        <a class="dash-btn" href="<?= e(app_route('admin/reports', ['export' => 'users'])) ?>">Export users</a>
        <a class="dash-btn" href="<?= e(app_route('admin/reports', ['export' => 'subscriptions'])) ?>">Export subscriptions</a>
        <a class="dash-btn" href="<?= e(app_route('admin/reports', ['export' => 'audit'])) ?>">Export audit log</a>
    </div>
</section>

<section class="admin-panel">
    <h2 class="admin-panel-title font-heading">Payment intents (recent)</h2>
    <?php
    $intents = $adminService->repository()->listPaymentIntents(30);
    if ($intents === []) {
        admin_render_empty('No payment intents yet.');
    } else {
        admin_render_table_start(['Business', 'Amount', 'Status', 'Created']);
        foreach ($intents as $row) {
            echo '<tr>';
            echo '<td>' . e((string) ($row['business_name'] ?? '')) . '</td>';
            echo '<td>' . e(admin_format_money((float) ($row['amount'] ?? 0), (string) ($row['currency'] ?? 'USD'))) . '</td>';
            echo '<td><span class="' . admin_status_class((string) ($row['status'] ?? '')) . '">' . e((string) ($row['status'] ?? '')) . '</span></td>';
            echo '<td>' . e(admin_format_datetime((string) ($row['created_at'] ?? ''))) . '</td>';
            echo '</tr>';
        }
        admin_render_table_end();
    }
    ?>
</section>

<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
