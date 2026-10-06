<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$userId = (new SessionAuth())->userId();
if ($userId === null) {
    redirect(app_route('login'));
}

if (!(new SubscriptionService())->hasActiveSubscription((string) $userId)) {
    redirect(app_route('pricing'));
}

$tiktok = new TikTokConnectService();
$businessId = trim((string) ($_GET['business_id'] ?? ''));
$connectMode = strtolower(trim((string) ($_GET['mode'] ?? 'connect')));
$returnTo = safe_return_url(
    trim((string) ($_GET['return_to'] ?? '')),
    $tiktok->defaultReturnTo((string) $userId)
);

try {
    redirect($tiktok->beginConnect(
        (string) $userId,
        $returnTo,
        $businessId !== '' ? $businessId : null,
        $connectMode
    ));
} catch (Throwable $e) {
    $message = $e->getMessage();
    flash_set('error', $message !== '' ? $message : 'Could not start the TikTok connection. Please try again.');
    redirect($returnTo);
}
