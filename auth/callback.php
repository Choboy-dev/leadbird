<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/auth/helpers.php';

$appUrl = app_config()['app_url'];
$authMode = auth_mode_from_session();

if (isset($_GET['error'])) {
    flash_set('error', 'Google sign-in was cancelled or failed.');
    redirect(auth_page_url($authMode));
}

$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

if (!is_string($code) || $code === '' || !is_string($state) || $state === '') {
    flash_set('error', 'Missing authorization data from Google.');
    redirect(auth_page_url($authMode));
}

try {
    $user = (new GoogleAuthService())->handleCallback($code, $state);
    (new SessionAuth())->login($user);
    unset($_SESSION['otp_email'], $_SESSION['auth_mode']);
    redirect(AdminAuth::postLoginRedirectUrl((string) ($user['id'] ?? '')));
} catch (Throwable $e) {
    $message = 'Sign-in failed. Please try again.';
    flash_set('error', $message);
    redirect(auth_page_url($authMode));
}
