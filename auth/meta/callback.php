<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$userId = (new SessionAuth())->userId();
$metaConnect = new MetaConnectService();
$returnTo = $metaConnect->resolveReturnTo(
    trim((string) ($_GET['state'] ?? '')) ?: null,
    $userId !== null ? (string) $userId : null
);

$error = (string) ($_GET['error_description'] ?? $_GET['error'] ?? '');
if ($error !== '') {
    $lower = strtolower($error);
    if (str_contains($lower, 'invalid scope') || str_contains($lower, 'pages_manage_posts')) {
        flash_set(
            'error',
            'Facebook blocked a publish permission that is not set up on the Lead Bird app yet. '
            . 'You can still connect for Messenger replies. Try again now, or contact support if it keeps failing.'
        );
    } else {
        flash_set('error', 'Meta authorization was cancelled or denied.');
    }
    redirect($returnTo);
}

$code = (string) ($_GET['code'] ?? '');
$state = (string) ($_GET['state'] ?? '');
if ($code === '' || $state === '') {
    flash_set('error', 'Meta did not finish the connection. Please try again.');
    redirect($returnTo);
}

try {
    (new MetaConnectService())->handleCallback($code, $state);
} catch (Throwable $e) {
    error_log('[Meta callback] ' . $e->getMessage());
    flash_set('error', 'Meta connection failed. Please try again.');
    redirect($returnTo);
}
