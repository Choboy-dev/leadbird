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

$conversationId = trim((string) ($payload['conversation_id'] ?? ''));
$text = trim((string) ($payload['message_text'] ?? ''));
$pauseAi = !array_key_exists('pause_ai', $payload) || (bool) $payload['pause_ai'];

if ($conversationId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Conversation id required.'], JSON_THROW_ON_ERROR);
    exit;
}

$result = (new InboxReplyService())->sendReply($ctx['business_id'], $conversationId, $text, $pauseAi);

http_response_code(($result['ok'] ?? false) ? 200 : 422);
echo json_encode($result, JSON_THROW_ON_ERROR);
