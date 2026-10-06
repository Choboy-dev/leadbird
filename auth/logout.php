<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

(new SessionAuth())->logout();
unset($_SESSION['otp_email']);

$reason = trim((string) ($_GET['reason'] ?? ''));
if ($reason === 'idle') {
    flash_set('success', 'You were signed out after being inactive for your security.');
} else {
    flash_set('success', 'You have been signed out.');
}
redirect(app_route('login'));
