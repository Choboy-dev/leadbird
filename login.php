<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth/helpers.php';

ReferralSelection::captureFromRequest();

$referralCode = strtoupper(trim((string) ($_GET['referral_code'] ?? '')));
if ($referralCode !== '') {
    redirect(app_route('signup', ReferralSelection::trackingQueryForCode($referralCode)));
}

// Sign-in is not signup — clear stale referral cookie/session (e.g. after testing your own /r/ link).
ReferralSelection::clearForSignIn();

PlanSelection::captureFromRequest();

auth_clear_admin_destination();

$sessionAuth = new SessionAuth();
$referral = null;

if ($sessionAuth->check() && $referral === null) {
    redirect(app_route('app'));
}

$appUrl = app_config()['app_url'];
$authMode = 'signin';
$step = ($_GET['step'] ?? '') === 'verify' ? 'verify' : 'email';
$otpEmail = isset($_SESSION['otp_email']) ? (string) $_SESSION['otp_email'] : '';
$error = flash_get('error');
$success = flash_get('success');
$otpLength = app_config()['otp']['length'];
$authUser = $sessionAuth->check() ? $sessionAuth->user() : null;
$referralIsOwnLink = false;

auth_store_mode($authMode);

require __DIR__ . '/includes/auth/page.php';
