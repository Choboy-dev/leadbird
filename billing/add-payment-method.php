<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';

header('Content-Type: application/json; charset=utf-8');

$sessionAuth = new SessionAuth();
$user = $sessionAuth->user();
$userId = $sessionAuth->userId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId === null || $user === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized.'], JSON_THROW_ON_ERROR);
    exit;
}

require_csrf();

if (!rate_limit_allow('add_payment_method', 15, 3600)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'message' => 'Too many card attempts. Please wait and try again.'], JSON_THROW_ON_ERROR);
    exit;
}

$billing = [
    'address_line1' => trim((string) ($_POST['address_line1'] ?? '')),
    'city' => trim((string) ($_POST['city'] ?? '')),
    'state' => trim((string) ($_POST['state'] ?? '')),
    'country' => trim((string) ($_POST['country'] ?? 'NG')),
    'postal_code' => trim((string) ($_POST['postal_code'] ?? '')),
    'phone' => trim((string) ($_POST['phone'] ?? '')),
];

try {
    $result = (new PaymentMethodService())->beginCardVerification($userId, $user, $billing);
} catch (Throwable $e) {
    error_log('[billing/add-payment-method] Card checkout failed for user ' . $userId . ': ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Something went wrong. Please try again.'], JSON_THROW_ON_ERROR);
    exit;
}

if (!($result['ok'] ?? false)) {
    http_response_code(422);
}

echo json_encode($result, JSON_THROW_ON_ERROR);
