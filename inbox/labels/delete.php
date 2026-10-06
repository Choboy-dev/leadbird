<?php

declare(strict_types=1);

$ctx = require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.'], JSON_THROW_ON_ERROR);
    exit;
}

$raw = file_get_contents('php://input');
$payload = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid request body.'], JSON_THROW_ON_ERROR);
    exit;
}

$labelId = trim((string) ($payload['label_id'] ?? ''));
if ($labelId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Label id required.'], JSON_THROW_ON_ERROR);
    exit;
}

$result = (new InboxLabelService())->deleteLabel($ctx['business_id'], $labelId);

http_response_code(($result['ok'] ?? false) ? 200 : 422);
echo json_encode($result, JSON_THROW_ON_ERROR);
