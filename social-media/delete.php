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
$action = is_array($payload) ? trim((string) ($payload['action'] ?? 'delete')) : 'delete';

if ($action === 'delete_live') {
    $accountId = is_array($payload) ? trim((string) ($payload['account_id'] ?? '')) : '';
    $platformPostId = is_array($payload) ? trim((string) ($payload['platform_post_id'] ?? '')) : '';
    if ($accountId === '' || $platformPostId === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Account and post id required.'], JSON_THROW_ON_ERROR);
        exit;
    }
    $result = (new SocialMediaService())->deleteLivePost($ctx['business_id'], $accountId, $platformPostId);
    http_response_code(($result['ok'] ?? false) ? 200 : 422);
    echo json_encode($result, JSON_THROW_ON_ERROR);
    exit;
}

if ($postId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Post id required.'], JSON_THROW_ON_ERROR);
    exit;
}

$service = new SocialMediaService();
$result = $action === 'cancel'
    ? $service->cancelPost($postId, $ctx['business_id'])
    : $service->deletePost($postId, $ctx['business_id']);

http_response_code(($result['ok'] ?? false) ? 200 : 422);
echo json_encode($result, JSON_THROW_ON_ERROR);
