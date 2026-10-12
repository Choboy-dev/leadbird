<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/bootstrap.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

$pageTitle = 'WhatsApp senders — ' . $brandName;
$dashboardNav = 'wa_senders';
$pageHeading = 'Senders';
$extraStylesheets = ['dashboard-wa-senders.css?v=37'];

if (!dashboard_require_feature('broadcasts') || $businessId === null) {
    require dirname(__DIR__) . '/includes/dashboard/layout-start.php';
    require dirname(__DIR__) . '/includes/dashboard/feature-locked.php';
    require dirname(__DIR__) . '/includes/dashboard/layout-end.php';
    return;
}

$sendersUrl = app_route('broadcasts/senders');
$flashSuccess = flash_get('success');
$flashError = flash_get('error');
$senders = (new WhatsAppBroadcastService())->senders($businessId, ($_GET['refresh'] ?? '') === '1' || $flashSuccess !== null);
$connectUrl = trim((string) (app_config()['meta']['embedded_signup_config_id'] ?? '')) !== ''
    ? app_route('broadcasts/new-sender')
    : dash_platform_setup_url('whatsapp', $businessId, $sendersUrl);
$reconnectUrl = dash_platform_reconnect_url('whatsapp', $businessId, $sendersUrl);

$wabas = [];
foreach ($senders as $sender) {
    if ($sender['waba_id'] !== null && !isset($wabas[$sender['waba_id']])) {
        $wabas[$sender['waba_id']] = $sender;
    }
}

require dirname(__DIR__) . '/includes/dashboard/layout-start.php';
require dirname(__DIR__) . '/includes/dashboard/wa-senders-content.php';
require dirname(__DIR__) . '/includes/dashboard/layout-end.php';
