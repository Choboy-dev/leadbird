<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/auth/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$email = normalize_email((string) ($_POST['email'] ?? $_SESSION['otp_email'] ?? ''));
$authMode = auth_normalize_mode((string) ($_POST['auth_mode'] ?? auth_mode_from_session()));

if (!is_valid_email($email)) {
    flash_set('error', 'Please enter a valid email address.');
    redirect(auth_page_url($authMode, 'verify'));
}

try {
    (new OtpService())->send($email, $authMode);
    $_SESSION['otp_email'] = $email;
    auth_store_mode($authMode);
    flash_set('success', 'We sent a new verification code to ' . $email . '.');
} catch (Throwable $e) {
    $message = $e->getMessage();
    if (!str_contains(strtolower($message), 'wait')) {
        $message = 'Could not resend your code. Please try again.';
    }
    flash_set('error', $message);
}

redirect(auth_page_url($authMode, 'verify'));
