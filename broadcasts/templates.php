<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/bootstrap.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

$pageTitle = 'WhatsApp templates — ' . $brandName;
$dashboardNav = 'wa_templates';
$pageHeading = 'Templates';
$extraStylesheets = ['dashboard-wa-senders.css?v=37'];
$extraScripts = ['dashboard-wa-templates.js?v=6'];

if (!dashboard_require_feature('broadcasts') || $businessId === null) {
    $extraScripts = [];
    require dirname(__DIR__) . '/includes/dashboard/layout-start.php';
    require dirname(__DIR__) . '/includes/dashboard/feature-locked.php';
    require dirname(__DIR__) . '/includes/dashboard/layout-end.php';
    return;
}

$preview = ($_GET['preview'] ?? '') === '1';
$previewQuery = $preview ? ['preview' => '1'] : [];
$templatesUrl = app_route('broadcasts/templates', $previewQuery);
$sendersUrl = app_route('broadcasts/senders');
$service = new WhatsAppBroadcastService();
$accounts = $preview ? [] : RepositoryFactory::connectedAccounts()->listActiveByBusinessAndPlatform($businessId, 'whatsapp');
$hasNumber = $preview || $accounts !== [];
$business = RepositoryFactory::businesses()->findById($businessId);

$wabaId = null;
foreach ($accounts as $account) {
    $wabaId = $service->resolveWabaId($account);
    if ($wabaId !== null) {
        break;
    }
}

$syncError = null;
if ($accounts !== []) {
    $cacheKey = 'lb_wa_templates_synced_' . $businessId;
    $lastSync = (int) ($_SESSION[$cacheKey] ?? 0);
    if (($_GET['refresh'] ?? '') === '1' || $lastSync < time() - 300) {
        $sync = $service->syncTemplates($businessId);
        if ($sync['ok']) {
            $_SESSION[$cacheKey] = time();
        } else {
            $syncError = $sync['error'] ?? null;
        }
    }
}

$templates = $preview ? [] : RepositoryFactory::broadcasts()->listTemplates($businessId);
$flashSuccess = flash_get('success');
$flashError = flash_get('error');
$createUrl = $hasNumber ? app_route('broadcasts/template', $previewQuery) : null;

$managerUrl = $wabaId !== null
    ? 'https://business.facebook.com/wa/manage/message-templates/?waba_id=' . rawurlencode($wabaId)
    : null;

require dirname(__DIR__) . '/includes/dashboard/layout-start.php';
require dirname(__DIR__) . '/includes/dashboard/wa-templates-content.php';
require dirname(__DIR__) . '/includes/dashboard/layout-end.php';
