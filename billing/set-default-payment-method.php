<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';

$sessionAuth = new SessionAuth();
$userId = $sessionAuth->userId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId === null) {
    redirect(app_route('billing'));
}

require_csrf();

$methodId = trim((string) ($_POST['method_id'] ?? ''));
if ($methodId === '' || !(new PaymentMethodService())->makeDefault($userId, $methodId)) {
    flash_set('error', 'Could not update your default card.');
    redirect(app_route('billing'));
}

flash_set('success', 'Default card updated. Renewals will use this card.');
redirect(app_route('billing'));
