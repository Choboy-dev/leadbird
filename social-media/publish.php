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
$postId = is_array($payload) ? trim((string) ($payload['post_id'] ?? '')) : '';

if ($postId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Post id required.'], JSON_THROW_ON_ERROR);
    exit;
}

$result = (new SocialMediaService())->publishPost($postId, $ctx['business_id']);

http_response_code(($result['ok'] ?? false) ? 200 : 422);
echo json_encode($result, JSON_THROW_ON_ERROR);
