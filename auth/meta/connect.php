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

$platform = strtolower(trim((string) ($_GET['platform'] ?? '')));
$returnTo = trim((string) ($_GET['return_to'] ?? ''));
$businessId = trim((string) ($_GET['business_id'] ?? ''));
$connectMode = strtolower(trim((string) ($_GET['mode'] ?? 'connect')));
$forceOAuth = ($_GET['refresh'] ?? '') === '1';
$metaConnect = new MetaConnectService();
$returnTo = safe_return_url($returnTo, $metaConnect->defaultReturnTo((string) $userId));

try {
    $url = $metaConnect->beginConnect(
        (string) $userId,
        $platform,
        $returnTo,
        $businessId !== '' ? $businessId : null,
        $connectMode,
        $forceOAuth
    );
    redirect($url);
} catch (Throwable $e) {
    flash_set('error', $e->getMessage() !== '' ? $e->getMessage() : 'Could not start Meta connection. Check your app configuration and try again.');
    redirect($returnTo);
}
