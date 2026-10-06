<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'openai';
$pageTitle = 'OpenAI usage — Admin';
$pageHeading = 'OpenAI usage';
$success = flash_get('success');
$error = flash_get('error');
$days = max(7, min(90, (int) ($_GET['days'] ?? 30)));

$repo = $adminService->repository();
$platformCost = $repo->openAiPlatformCost($days);
$platformTokens = $repo->openAiPlatformTokens($days);
$platformEvents = $repo->openAiPlatformEvents($days);
$byBusiness = $repo->openAiUsageByBusiness($days, 50);
$budget = (float) (app_config()['openai']['platform_monthly_budget_usd'] ?? 10);
$budgetUsedPct = $budget > 0 ? round(($platformCost / $budget) * 100, 1) : 0.0;

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);

admin_render_kpi_cards([
    ['label' => 'Cost (' . $days . 'd)', 'value' => admin_format_openai_usd($platformCost)],
    ['label' => 'Tokens', 'value' => number_format($platformTokens)],
    ['label' => 'API calls', 'value' => number_format($platformEvents)],
    [
        'label' => 'Monthly budget',
        'value' => '$' . number_format($budget, 0),
        'hint' => $budget > 0 ? $budgetUsedPct . '% used' : '',
    ],
]);
?>
<div class="admin-toolbar">
    <?php admin_render_filter_links([
        ['label' => '7 days', 'url' => app_route('admin/openai-usage', ['days' => 7]), 'active' => $days === 7],
        ['label' => '30 days', 'url' => app_route('admin/openai-usage', ['days' => 30]), 'active' => $days === 30],
        ['label' => '90 days', 'url' => app_route('admin/openai-usage', ['days' => 90]), 'active' => $days === 90],
    ]); ?>
</div>

<?php if ($byBusiness === []): ?>
    <?php admin_render_empty('No OpenAI usage in this period.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['Business', 'API calls', 'Tokens', 'Cost']); ?>
    <?php foreach ($byBusiness as $row): ?>
        <tr>
            <td><a href="<?= e(app_route('admin/business', ['id' => (string) $row['business_id']])) ?>"><?= e((string) ($row['business_name'] ?? '')) ?></a></td>
            <td><?= e(number_format((int) ($row['event_count'] ?? 0))) ?></td>
            <td><?= e(number_format((int) ($row['total_tokens'] ?? 0))) ?></td>
            <td><?= e(admin_format_openai_usd((float) ($row['total_cost'] ?? 0))) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
