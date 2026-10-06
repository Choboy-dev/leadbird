<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/helpers.php';

$pageTitle = 'Planner — ' . $brandName;
$dashboardNav = 'social_media';
$pageHeading = 'Planner';
$dashboardNavCollapsed = true;
$dashboardHideTopbar = true;
$plannerView = (string) ($_GET['view'] ?? 'calendar');
if (!in_array($plannerView, ['calendar', 'drafts', 'content'], true)) {
    $plannerView = 'calendar';
}
$dashboardNavContext = ['planner_view' => $plannerView];
$extraStylesheets = ['dashboard-social-media.css?v=110'];

if (!dashboard_require_feature('social_media')) {
    require __DIR__ . '/includes/dashboard/layout-start.php';
    require __DIR__ . '/includes/dashboard/feature-locked.php';
    require __DIR__ . '/includes/dashboard/layout-end.php';
    return;
}

$socialService = new SocialMediaService();
$accounts = $socialService->listPublishableAccounts((string) $businessId);
$postStats = $socialService->stats((string) $businessId);
$initialPosts = $socialService->listPosts((string) $businessId);

require __DIR__ . '/includes/dashboard/layout-start.php';
require __DIR__ . '/includes/dashboard/social-media-content.php';
require __DIR__ . '/includes/dashboard/layout-end.php';
