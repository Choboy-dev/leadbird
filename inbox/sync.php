<?php

declare(strict_types=1);

$ctx = require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.'], JSON_THROW_ON_ERROR);
    exit;
}

$businessId = (string) $ctx['business_id'];
$conversationId = trim((string) ($_GET['conversation_id'] ?? ''));
$after = trim((string) ($_GET['after'] ?? ''));

if ($after === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Missing after cursor.'], JSON_THROW_ON_ERROR);
    exit;
}

$messagesRepo = RepositoryFactory::messages();
$conversationsRepo = RepositoryFactory::conversations();

$threadMessages = [];
$conversationStatus = null;

if ($conversationId !== '') {
    $conversation = $conversationsRepo->findById($conversationId);
    if ($conversation === null || (string) ($conversation['business_id'] ?? '') !== $businessId) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Conversation not found.'], JSON_THROW_ON_ERROR);
        exit;
    }

    $conversationStatus = (string) ($conversation['status'] ?? 'open');
    $rows = $messagesRepo->listByConversationIdAfter($conversationId, $after, 80);
    foreach ($rows as $row) {
        $threadMessages[] = [
            'id' => (string) ($row['id'] ?? ''),
            'conversation_id' => (string) ($row['conversation_id'] ?? $conversationId),
            'sender_type' => (string) ($row['sender_type'] ?? 'customer'),
            'message_text' => (string) ($row['message_text'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }
}

$recent = $messagesRepo->listRecentForBusinessAfter($businessId, $after, 60);
$threads = [];
$seen = [];
foreach (array_reverse($recent) as $row) {
    $cid = (string) ($row['conversation_id'] ?? '');
    if ($cid === '' || isset($seen[$cid])) {
        continue;
    }
    $seen[$cid] = true;
    $text = trim((string) ($row['message_text'] ?? ''));
    $preview = preg_replace('/\s+/', ' ', $text) ?? $text;
    if (function_exists('mb_strimwidth')) {
        $preview = mb_strimwidth($preview, 0, 56, '...');
    } elseif (strlen($preview) > 56) {
        $preview = substr($preview, 0, 53) . '...';
    }

    $threads[] = [
        'conversation_id' => $cid,
        'sender_type' => (string) ($row['sender_type'] ?? ''),
        'preview' => $preview,
        'created_at' => (string) ($row['created_at'] ?? ''),
    ];
}

echo json_encode([
    'ok' => true,
    'server_time' => date('Y-m-d H:i:s'),
    'conversation_status' => $conversationStatus,
    'messages' => $threadMessages,
    'threads' => $threads,
], JSON_THROW_ON_ERROR);
