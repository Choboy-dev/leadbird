<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/helpers.php';

$pageTitle = 'My Businesses — ' . $brandName;
$dashboardNav = 'businesses';
$pageHeading = 'My Businesses';
$extraStylesheets = ['dashboard-connected-accounts.css?v=26'];

$ctx = new BusinessContext();
$businessRows = $ctx->listWithChannelSummary((string) $userId);
$activeBusinessId = $ctx->getActiveBusinessId();
$canAdd = $ctx->canAddBusiness((string) $userId);
$businessLimit = $ctx->getBusinessLimitForUser((string) $userId);
$success = flash_get('success');
$error = flash_get('error');

$allBusinessesList = [];
$businessCount = count($businessRows);
foreach ($businessRows as $business) {
    $businessId = (string) ($business['id'] ?? '');
    $isComplete = (bool) ($business['is_complete'] ?? false);
    $isActive = $activeBusinessId === $businessId;
    $channels = is_array($business['channels'] ?? null) ? $business['channels'] : [];
    $platforms = [];
    foreach ($channels as $channel) {
        $platform = trim((string) ($channel['platform'] ?? ''));
        if ($platform !== '') {
            $platforms[$platform] = true;
        }
    }
    $platformList = array_keys($platforms);

    if ($isActive) {
        $status = 'active';
        $statusLabel = 'Active';
    } elseif (!$isComplete) {
        $status = 'setup';
        $statusLabel = 'In setup';
    } else {
        $status = 'ready';
        $statusLabel = 'Ready';
    }

    $businessName = trim((string) ($business['business_name'] ?? 'Business'));
    $searchText = strtolower(trim($businessName . ' ' . $statusLabel . ' ' . implode(' ', $platformList)));

    $allBusinessesList[] = [
        'id' => $businessId,
        'name' => $businessName !== '' ? $businessName : 'Business',
        'created_at' => (string) ($business['created_at'] ?? ''),
        'status' => $status,
        'status_label' => $statusLabel,
        'is_complete' => $isComplete,
        'is_active' => $isActive,
        'channel_count' => (int) ($business['channel_count'] ?? count($platformList)),
        'platforms' => $platformList,
        'search_text' => $searchText,
    ];
}

usort($allBusinessesList, static function (array $a, array $b): int {
    return strcmp($b['created_at'], $a['created_at']);
});

$panelTitle = $businessLimit !== null
    ? $businessCount . ' of ' . (int) $businessLimit . ' business' . ($businessLimit === 1 ? '' : 'es') . ' on your plan'
    : $businessCount . ' business' . ($businessCount === 1 ? '' : 'es') . ' on your account';

require __DIR__ . '/includes/dashboard/layout-start.php';
require __DIR__ . '/includes/dashboard/businesses-content.php';
require __DIR__ . '/includes/dashboard/layout-end.php';
