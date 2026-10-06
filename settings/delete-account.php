<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';

$sessionAuth = new SessionAuth();
$userId = $sessionAuth->userId();
$appUrl = app_config()['app_url'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId === null) {
    redirect(app_route('settings'));
}

require_csrf();

$confirmation = trim((string) ($_POST['confirmation'] ?? ''));
if ($confirmation !== 'Delete') {
    flash_set('error', 'Type Delete exactly to confirm account deletion.');
    redirect(app_route('settings'));
}

try {
    (new UserService())->deleteAccount($userId);
} catch (Throwable $e) {
    flash_set('error', 'We could not delete your account. Please try again or contact support.');
    redirect(app_route('settings'));
}

$sessionAuth->logout();
unset($_SESSION['otp_email']);

flash_set('success', 'Your account has been permanently deleted.');
redirect(app_route('login'));
