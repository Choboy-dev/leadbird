<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/auth/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!rate_limit_allow('otp_send_ip', 8, 3600)) {
    json_response(['ok' => false, 'error' => 'Too many sign-in attempts. Please try again later.'], 429);
}

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
    || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

$email = normalize_email((string) ($_POST['email'] ?? ''));
$authMode = auth_normalize_mode((string) ($_POST['auth_mode'] ?? auth_mode_from_session()));

if (!is_valid_email($email)) {
    if ($wantsJson) {
        json_response(['ok' => false, 'error' => 'Please enter a valid email address.'], 422);
    }
    flash_set('error', 'Please enter a valid email address.');
    redirect(auth_page_url($authMode));
}

if (!rate_limit_allow('otp_send_email:' . $email, 5, 3600)) {
    $message = 'Too many codes sent to this email. Please try again later.';
    if ($wantsJson) {
        json_response(['ok' => false, 'error' => $message], 429);
    }
    flash_set('error', $message);
    redirect(auth_page_url($authMode));
}

try {
    (new OtpService())->send($email, $authMode);
    $_SESSION['otp_email'] = $email;
    auth_store_mode($authMode);

    if ($wantsJson) {
        json_response(['ok' => true, 'message' => 'Verification code sent.']);
    }

    flash_set('success', 'We sent a verification code to ' . $email . '.');
    redirect(auth_page_url($authMode, 'verify'));
} catch (Throwable $e) {
    $message = $e->getMessage();
    if (!str_contains(strtolower($message), 'wait')) {
        $message = 'Could not send sign-in code. Please try again.';
    }

    if ($wantsJson) {
        json_response(['ok' => false, 'error' => $message], 400);
    }

    flash_set('error', $message);
    redirect(auth_page_url($authMode));
}
