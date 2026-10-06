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

$methodId = trim((string) ($_POST['method_id'] ?? ''));
if ($methodId === '') {
    flash_set('error', 'Payment method not found.');
    redirect(app_route('billing'));
}

$removed = (new PaymentMethodService())->removeCard($userId, $methodId);
if (!$removed) {
    flash_set('error', 'Could not remove that payment method.');
    redirect(app_route('billing'));
}

flash_set('success', 'Payment method removed.');
redirect(app_route('billing'));
