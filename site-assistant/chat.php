<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/config/plans.php';
require_once dirname(__DIR__) . '/lib/LbPublicGuideService.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_THROW_ON_ERROR);
    exit;
}

if (!rate_limit_allow('site_assistant', 30, 3600)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Too many questions right now. Please try again later.'], JSON_THROW_ON_ERROR);
    exit;
}

$raw = file_get_contents('php://input');
$payload = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid request.'], JSON_THROW_ON_ERROR);
    exit;
}

$message = trim((string) ($payload['message'] ?? ''));
$history = is_array($payload['history'] ?? null) ? $payload['history'] : [];

$result = (new SiteAssistantService())->chat($history, $message);

http_response_code($result['ok'] ? 200 : 422);
echo json_encode($result, JSON_THROW_ON_ERROR);
