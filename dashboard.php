<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/helpers.php';

$pageTitle = 'Dashboard — ' . $brandName;
$dashboardNav = 'overview';
$dashboardHideTopbar = true;
$extraStylesheets = ['dashboard-overview.css?v=28'];
$success = flash_get('success');

$renewal = is_array($subscription) && !empty($subscription['current_period_end'])
    ? date('M j, Y', strtotime((string) $subscription['current_period_end']))
    : null;
$planName = is_array($plan) ? (string) ($plan['name'] ?? 'Active plan') : 'Active plan';
$planPrice = is_array($plan) ? (int) ($plan['price'] ?? 0) : 0;

$channelPerformance = $dashboardService->getChannelPerformance($businessId);
$recentTransactions = $dashboardService->getRecentTransactions($businessId, 10);

$catalogOfferings = $businessId !== null
    ? RepositoryFactory::offerings()->listByBusinessId($businessId, true)
    : [];
$catalogTotal = count($catalogOfferings);

$connectedChannelCards = [];
if ($businessId !== null) {
    foreach (RepositoryFactory::connectedAccounts()->listByBusinessId($businessId, true) as $account) {
        $platform = (string) ($account['platform'] ?? '');
        if ($platform === '') {
            continue;
        }

        $connectedChannelCards[] = [
            'platform' => $platform,
            'platform_label' => dash_platform_label($platform),
            'display_name' => dash_connected_account_name($account),
            'is_active' => (bool) ($account['is_active'] ?? true),
            'href' => app_route('connected-accounts'),
        ];
    }
}

$platformSlots = [
    'instagram' => 'Instagram',
    'facebook' => 'Facebook Messenger',
    'whatsapp' => 'WhatsApp',
];
$connectedPlatforms = array_column($connectedChannelCards, 'platform');
foreach ($platformSlots as $platform => $label) {
    if (!in_array($platform, $connectedPlatforms, true)) {
        $connectedChannelCards[] = [
            'platform' => $platform,
            'platform_label' => $label,
            'display_name' => '',
            'is_active' => false,
            'href' => app_route('connected-accounts'),
        ];
    }
}

$userName = trim((string) ($user['name'] ?? ''));
if ($userName === '' && is_array($business)) {
    $userName = trim((string) ($business['owner_name'] ?? ''));
}
$userName = dash_first_name($userName);
$pageHeading = $userName !== '' ? 'Hi, ' . $userName : 'Hi, there';

require __DIR__ . '/includes/dashboard/layout-start.php';
require __DIR__ . '/includes/dashboard/overview.php';
require __DIR__ . '/includes/dashboard/layout-end.php';
