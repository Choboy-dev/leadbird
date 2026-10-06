<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/helpers.php';

$pageTitle = 'Post insights — ' . $brandName;
$dashboardNav = 'social_media';
$pageHeading = 'Post insights';
$dashboardNavCollapsed = true;
$dashboardHideTopbar = true;
$plannerView = 'content';
$dashboardNavContext = ['planner_view' => $plannerView];
$extraStylesheets = ['dashboard-social-media-insights.css?v=15'];

$accountId = trim((string) ($_GET['account_id'] ?? ''));
$platform = trim((string) ($_GET['platform'] ?? ''));
$platformPostId = trim((string) ($_GET['platform_post_id'] ?? ''));

if (!dashboard_require_feature('social_media')) {
    require __DIR__ . '/includes/dashboard/layout-start.php';
    require __DIR__ . '/includes/dashboard/feature-locked.php';
    require __DIR__ . '/includes/dashboard/layout-end.php';
    return;
}

$backUrl = app_route('social-media', ['view' => 'content']);

require __DIR__ . '/includes/dashboard/layout-start.php';
require __DIR__ . '/includes/dashboard/social-media-insights-content.php';
require __DIR__ . '/includes/dashboard/layout-end.php';
