<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth/helpers.php';

ReferralSelection::captureFromRequest();
PlanSelection::captureFromRequest();

$sessionAuth = new SessionAuth();
$referral = ReferralSelection::pendingReferral();

if ($sessionAuth->check() && $referral === null) {
    redirect(app_route('app'));
}

$appUrl = app_config()['app_url'];
$authMode = 'signup';
$step = ($_GET['step'] ?? '') === 'verify' ? 'verify' : 'email';
$otpEmail = isset($_SESSION['otp_email']) ? (string) $_SESSION['otp_email'] : '';
$error = flash_get('error');
$success = flash_get('success');
$otpLength = app_config()['otp']['length'];
$authUser = $sessionAuth->check() ? $sessionAuth->user() : null;
$referralIsOwnLink = $referral !== null && ReferralSelection::isOwnLink($referral, $authUser);

auth_store_mode($authMode);

require __DIR__ . '/includes/auth/page.php';
