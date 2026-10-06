<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';
require_once dirname(__DIR__) . '/lib/DashboardAssistantService.php';

header('Content-Type: application/json; charset=utf-8');

$sessionAuth = new SessionAuth();
$userId = $sessionAuth->userId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized.'], JSON_THROW_ON_ERROR);
    exit;
}

require_csrf();

if (!rate_limit_allow('dashboard_assistant', 60, 3600)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Too many assistant messages. Please wait and try again.'], JSON_THROW_ON_ERROR);
    exit;
}

$raw = file_get_contents('php://input');
$payload = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid request.'], JSON_THROW_ON_ERROR);
    exit;
}

$businessCtx = new BusinessContext();
$business = $businessCtx->resolveActiveBusiness((string) $userId);
if ($business === null) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'No active business found.'], JSON_THROW_ON_ERROR);
    exit;
}

$message = trim((string) ($payload['message'] ?? ''));
$history = is_array($payload['history'] ?? null) ? $payload['history'] : [];

$result = (new DashboardAssistantService((string) $userId, $business))->chat($history, $message);

http_response_code($result['ok'] ? 200 : 422);
echo json_encode($result, JSON_THROW_ON_ERROR);
