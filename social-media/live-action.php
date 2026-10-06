<?php

declare(strict_types=1);

$ctx = require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.'], JSON_THROW_ON_ERROR);
    exit;
}

$raw = file_get_contents('php://input');
$payload = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    $payload = $_POST;
}

$action = trim((string) ($payload['action'] ?? ''));
$accountId = trim((string) ($payload['account_id'] ?? ''));
$platformPostId = trim((string) ($payload['platform_post_id'] ?? ''));

if ($accountId === '' || $platformPostId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Account and post id required.'], JSON_THROW_ON_ERROR);
    exit;
}

$service = new SocialMediaService();

$result = match ($action) {
    'duplicate' => $service->duplicateLivePost($ctx['business_id'], $ctx['user_id'], $accountId, $platformPostId),
    'reshare_story' => $service->reshareLivePostToStory($ctx['business_id'], $ctx['user_id'], $accountId, $platformPostId),
    default => ['ok' => false, 'error' => 'Unknown action.'],
};

http_response_code(($result['ok'] ?? false) ? 200 : 422);
echo json_encode($result, JSON_THROW_ON_ERROR);
