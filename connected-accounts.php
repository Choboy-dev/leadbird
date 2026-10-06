<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/helpers.php';

$pageTitle = 'Social Accounts — ' . $brandName;
$dashboardNav = 'connected_accounts';
$pageHeading = 'Social Accounts';
$extraStylesheets = ['dashboard-connected-accounts.css?v=26'];

$connected = [];
$connectedByPlatform = [];
if ($businessId !== null) {
    foreach (RepositoryFactory::connectedAccounts()->listByBusinessId($businessId, true) as $account) {
        $platform = (string) ($account['platform'] ?? '');
        if ($platform === '') {
            continue;
        }
        $connectedByPlatform[$platform][] = $account;
        if (!isset($connected[$platform])) {
            $connected[$platform] = $account;
        }
    }
}

$platforms = [
    'instagram' => [
        'title' => 'Instagram',
        'connect' => 'Connect Instagram',
    ],
    'facebook' => [
        'title' => 'Facebook Messenger',
        'connect' => 'Connect Facebook Page',
    ],
    'whatsapp' => [
        'title' => 'WhatsApp',
        'connect' => 'Connect WhatsApp',
    ],
];

$tiktokEnabled = tiktok_is_enabled();
if ($tiktokEnabled) {
    $platforms['tiktok'] = [
        'title' => 'TikTok',
        'connect' => 'Connect TikTok',
    ];
}

$planName = is_array($plan) ? (string) ($plan['name'] ?? 'Your plan') : 'Your plan';
$planAllowance = PlanLimits::planAllowanceLabel($planSlug);
$planUsage = PlanLimits::usageSummaryForUser((string) $userId, $businessId);
$success = flash_get('success');
$error = flash_get('error');

$businessName = trim((string) ($business['business_name'] ?? ''));
if ($businessName === '') {
    $businessName = 'your business';
}

$allAccounts = [];
$connectedCount = 0;
foreach ($connectedByPlatform as $platform => $platformAccounts) {
    foreach ($platformAccounts as $account) {
        $connectedCount++;
        $allAccounts[] = [
            'id' => (string) ($account['id'] ?? ''),
            'platform' => $platform,
            'display_name' => dash_connected_account_name($account),
            'avatar_src' => dash_connected_account_avatar_src($account),
            'platform_label' => dash_platform_label($platform),
            'created_at' => (string) ($account['created_at'] ?? ''),
            'is_active' => (int) ($account['is_active'] ?? 1) === 1,
        ];
    }
}

usort($allAccounts, static function (array $a, array $b): int {
    return strcmp($b['created_at'], $a['created_at']);
});

$connectPlatforms = [
    'facebook' => [
        'label' => 'Facebook',
        'description' => 'Facebook Page',
    ],
    'instagram' => [
        'label' => 'Instagram Business',
        'description' => 'Instagram DMs',
    ],
    'whatsapp' => [
        'label' => 'WhatsApp Business',
        'description' => 'WhatsApp messages',
    ],
];

if ($tiktokEnabled) {
    $connectPlatforms['tiktok'] = [
        'label' => 'TikTok',
        'description' => 'TikTok videos',
    ];
}

$platformCanConnect = [];
foreach (array_keys($connectPlatforms) as $platformKey) {
    $platformCanConnect[$platformKey] = PlanLimits::canConnectPlatform((string) $userId, (string) $businessId, $platformKey);
}

$guideUrl = app_route('guides/connect-meta-channels', ['return_to' => app_route('connected-accounts')]);

require __DIR__ . '/includes/dashboard/layout-start.php';
require __DIR__ . '/includes/dashboard/connected-accounts-content.php';
require __DIR__ . '/includes/dashboard/layout-end.php';
