<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/auth/helpers.php';

PlanSelection::captureFromRequest();
ReferralSelection::captureFromRequest();

$mode = auth_normalize_mode((string) ($_GET['mode'] ?? 'signin'));
auth_store_mode($mode);

try {
    $auth = new GoogleAuthService();
    redirect($auth->beginLogin());
} catch (Throwable $e) {
    $message = 'Google sign-in is not configured yet.';
    flash_set('error', $message);
    redirect(auth_page_url($mode));
}
