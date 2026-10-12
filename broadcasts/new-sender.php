<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/bootstrap.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

$dashboardNav = 'wa_senders';
$pageHeading = 'Senders';
$pageTitle = 'New sender — ' . $brandName;
$extraStylesheets = ['dashboard-wa-senders.css?v=37'];
$extraScripts = ['dashboard-wa-new-sender.js?v=3'];

if (!dashboard_require_feature('broadcasts') || $businessId === null) {
    require dirname(__DIR__) . '/includes/dashboard/layout-start.php';
    require dirname(__DIR__) . '/includes/dashboard/feature-locked.php';
    require dirname(__DIR__) . '/includes/dashboard/layout-end.php';
    return;
}

$meta = app_config()['meta'];
$sendersUrl = app_route('broadcasts/senders');
$signupReady = trim((string) ($meta['app_id'] ?? '')) !== '' && trim((string) ($meta['embedded_signup_config_id'] ?? '')) !== '';
$signup = [
    'app_id' => (string) ($meta['app_id'] ?? ''),
    'config_id' => (string) ($meta['embedded_signup_config_id'] ?? ''),
    'graph_version' => meta_graph_version(),
    'endpoint' => app_route('broadcasts/embedded-signup'),
];

require dirname(__DIR__) . '/includes/dashboard/layout-start.php';
require dirname(__DIR__) . '/includes/dashboard/wa-new-sender-content.php';
require dirname(__DIR__) . '/includes/dashboard/layout-end.php';
