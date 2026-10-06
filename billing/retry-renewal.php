<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';

$sessionAuth = new SessionAuth();
$userId = $sessionAuth->userId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId === null) {
    redirect(app_route('billing'));
}

require_csrf();

if (!rate_limit_allow('retry_renewal', 5, 3600)) {
    flash_set('error', 'Too many attempts. Please wait a little and try again.');
    redirect(app_route('billing'));
}

$renewed = (new SubscriptionService())->retryPastDueRenewal($userId);

if ($renewed === null) {
    flash_set('success', 'Your plan is already up to date.');
} elseif ($renewed) {
    flash_set('success', 'Payment successful. Your plan has been renewed.');
} else {
    flash_set('error', 'The payment did not go through. Check with your bank or add a different card.');
}

redirect(app_route('billing'));
