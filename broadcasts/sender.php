<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/bootstrap.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

$dashboardNav = 'wa_senders';
$pageHeading = 'Senders';
$pageTitle = 'Edit sender — ' . $brandName;
$extraStylesheets = ['dashboard-wa-senders.css?v=37'];
$extraScripts = ['dashboard-wa-sender.js?v=1'];

if (!dashboard_require_feature('broadcasts') || $businessId === null) {
    require dirname(__DIR__) . '/includes/dashboard/layout-start.php';
    require dirname(__DIR__) . '/includes/dashboard/feature-locked.php';
    require dirname(__DIR__) . '/includes/dashboard/layout-end.php';
    return;
}

$sendersUrl = app_route('broadcasts/senders');
$service = new WhatsAppBroadcastService();
$accountId = trim((string) ($_GET['id'] ?? ''));
$account = $service->senderAccount($businessId, $accountId);
if ($account === null) {
    flash_set('error', 'That sender was not found.');
    redirect($sendersUrl);
}

$senderUrl = app_route('broadcasts/sender', ['id' => $accountId]);
$error = null;
$posted = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (!rate_limit_allow('wa_sender_profile:' . $userId, 20, 3600)) {
        $error = 'Too many changes in a short time. Please wait a few minutes.';
    } else {
        $photo = is_array($_FILES['photo'] ?? null) ? $_FILES['photo'] : null;
        $result = $service->updateSenderProfile($account, $_POST, $photo);
        if ($result['ok']) {
            unset($_SESSION['lb_wa_senders_' . $businessId]);
            flash_set('success', 'Sender profile updated. WhatsApp may take a few minutes to show it.');
            redirect($senderUrl);
        }
        $error = $result['error'] ?? 'Could not save your changes.';
    }
    $posted = $_POST;
}

$sender = null;
foreach ([false, true] as $fresh) {
    foreach ($service->senders($businessId, $fresh) as $row) {
        if ($row['account_id'] === $accountId) {
            $sender = $row;
            break 2;
        }
    }
}
$profileResult = $sender !== null && $sender['status'] !== 'expired' ? $service->senderProfile($account) : ['ok' => false, 'profile' => []];
$profile = $profileResult['profile'];
$values = [
    'about' => (string) ($posted['about'] ?? $profile['about'] ?? ''),
    'description' => (string) ($posted['description'] ?? $profile['description'] ?? ''),
    'address' => (string) ($posted['address'] ?? $profile['address'] ?? ''),
    'email' => (string) ($posted['email'] ?? $profile['email'] ?? ''),
    'vertical' => strtoupper((string) ($posted['vertical'] ?? $profile['vertical'] ?? '')),
    'website_1' => (string) ($posted['website_1'] ?? $profile['websites'][0] ?? ''),
    'website_2' => (string) ($posted['website_2'] ?? $profile['websites'][1] ?? ''),
];
$photoUrl = trim((string) ($profile['profile_picture_url'] ?? ''));
$canEdit = $sender !== null && $sender['status'] !== 'expired';
$reconnectUrl = dash_platform_reconnect_url('whatsapp', $businessId, $senderUrl);
$success = flash_get('success');

$pageTitle = 'WhatsApp sender ' . ($sender['phone'] ?? '') . ' — ' . $brandName;

require dirname(__DIR__) . '/includes/dashboard/layout-start.php';
require dirname(__DIR__) . '/includes/dashboard/wa-sender-content.php';
require dirname(__DIR__) . '/includes/dashboard/layout-end.php';
