<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$userId = (new SessionAuth())->userId();
$tiktok = new TikTokConnectService();
$state = trim((string) ($_GET['state'] ?? ''));
$returnTo = $tiktok->resolveReturnTo(
    $state !== '' ? $state : null,
    $userId !== null ? (string) $userId : null
);

$error = trim((string) ($_GET['error_description'] ?? $_GET['error'] ?? ''));
if ($error !== '') {
    flash_set('error', tiktok_humanize_error($error));
    redirect($returnTo);
}

$code = trim((string) ($_GET['code'] ?? ''));
if ($code === '' || $state === '') {
    flash_set('error', 'TikTok did not finish the connection. Please try again.');
    redirect($returnTo);
}

try {
    $tiktok->handleCallback($code, $state);
} catch (Throwable $e) {
    error_log('[TikTok callback] ' . $e->getMessage());
    flash_set('error', tiktok_humanize_error($e->getMessage()));
    redirect($returnTo);
}
