<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';

$sessionAuth = new SessionAuth();
$userId = $sessionAuth->userId();
$appUrl = app_config()['app_url'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId === null) {
    redirect(app_route('billing'));
}

require_csrf();

try {
    $cancelled = (new SubscriptionService())->cancelActiveSubscription($userId);
} catch (Throwable $e) {
    error_log('[Subscription cancel] ' . $e->getMessage());
    flash_set('error', 'We could not cancel your subscription. Please try again.');
    redirect(app_route('billing'));
}

if (!$cancelled) {
    flash_set('error', 'No active subscription found to cancel.');
    redirect(app_route('billing'));
}

flash_set('success', 'Your subscription has been cancelled. You will not be charged again.');
redirect(app_route('pricing'));
