<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';

$userId = (new SessionAuth())->userId();
$appUrl = app_config()['app_url'];

if ($userId === null) {
    redirect(app_route('login'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('billing'));
}

require_csrf();

if (!rate_limit_allow('billing_topup', 20, 3600)) {
    flash_set('error', 'Too many top-up attempts. Please wait and try again.');
    redirect(app_route('billing#tokens'));
}

$packageSlug = trim((string) ($_POST['package'] ?? ''));
if ($packageSlug === '') {
    flash_set('error', 'Choose a top-up package to continue.');
    redirect(app_route('billing#tokens'));
}

try {
    $checkout = (new TopupService())->initiateCheckout($userId, $packageSlug);
    redirect((string) $checkout['link']);
} catch (Throwable $e) {
    flash_set('error', $e->getMessage());
    redirect(app_route('billing#tokens'));
}
