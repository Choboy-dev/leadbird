<?php

declare(strict_types=1);

$ctx = require __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__) . '/lib/InboxConversationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.'], JSON_THROW_ON_ERROR);
    exit;
}

require_csrf();

$raw = file_get_contents('php://input');
$payload = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid request body.'], JSON_THROW_ON_ERROR);
    exit;
}

$conversationId = trim((string) ($payload['conversation_id'] ?? ''));
if ($conversationId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Conversation id required.'], JSON_THROW_ON_ERROR);
    exit;
}

$result = (new InboxConversationService())->delete($ctx['business_id'], $conversationId);

http_response_code(($result['ok'] ?? false) ? 200 : 422);
echo json_encode($result, JSON_THROW_ON_ERROR);
