<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/auth/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
    || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

$email = normalize_email((string) ($_POST['email'] ?? $_SESSION['otp_email'] ?? ''));
$code = preg_replace('/\D+/', '', (string) ($_POST['code'] ?? '')) ?? '';
$appUrl = app_config()['app_url'];
$authMode = auth_normalize_mode((string) ($_POST['auth_mode'] ?? auth_mode_from_session()));

if (!is_valid_email($email)) {
    if ($wantsJson) {
        json_response(['ok' => false, 'error' => 'Email is required.'], 422);
    }
    flash_set('error', 'Email is required.');
    redirect(auth_page_url($authMode));
}

$expectedLength = app_config()['otp']['length'];
if (strlen($code) !== $expectedLength) {
    if ($wantsJson) {
        json_response(['ok' => false, 'error' => 'Invalid code length.'], 422);
    }
    flash_set('error', 'Please enter the ' . $expectedLength . '-digit code from your email.');
    redirect(auth_page_url($authMode, 'verify'));
}

try {
    $otp = new OtpService();
    if (!$otp->verify($email, $code)) {
        if ($wantsJson) {
            json_response(['ok' => false, 'error' => 'Invalid or expired code.'], 401);
        }
        flash_set('error', 'Invalid or expired code. Request a new one if needed.');
        redirect(auth_page_url($authMode, 'verify'));
    }

    $user = (new UserService())->findOrCreateByEmail($email);
    (new SessionAuth())->login((new UserService())->toSessionUser($user));
    unset($_SESSION['otp_email'], $_SESSION['auth_mode']);

    $redirectUrl = AdminAuth::postLoginRedirectUrl((string) $user['id']);

    if ($wantsJson) {
        json_response(['ok' => true, 'redirect' => $redirectUrl]);
    }

    redirect($redirectUrl);
} catch (Throwable $e) {
    $message = 'Could not verify code. Please try again.';

    if ($wantsJson) {
        json_response(['ok' => false, 'error' => $message], 500);
    }

    flash_set('error', $message);
    redirect(auth_page_url($authMode, 'verify'));
}
