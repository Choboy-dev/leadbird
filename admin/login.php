<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/auth/helpers.php';

$sessionAuth = new SessionAuth();

if ($sessionAuth->check()) {
    $userId = (string) $sessionAuth->userId();
    if (AdminAuth::isAdminUserId($userId)) {
        redirect(app_route('admin'));
    }

    flash_set('error', 'This account does not have admin access.');
    $sessionAuth->logout();
}

AdminAuth::setAdminDestination();

$appUrl = app_config()['app_url'];
$authMode = 'signin';
$step = ($_GET['step'] ?? '') === 'verify' ? 'verify' : 'email';
$otpEmail = isset($_SESSION['otp_email']) ? (string) $_SESSION['otp_email'] : '';
$error = flash_get('error');
$success = flash_get('success');
$otpLength = app_config()['otp']['length'];
$authUser = null;
$referral = null;
$referralIsOwnLink = false;
$authAdminPortal = true;
$pageTitle = 'Admin sign in';
$heading = 'Admin sign in';
$leadCopy = 'Sign in with your admin account to open the Lead Bird admin portal.';

auth_store_mode($authMode);

require dirname(__DIR__) . '/includes/auth/page.php';
