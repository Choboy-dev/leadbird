<?php

declare(strict_types=1);

$ctx = require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.'], JSON_THROW_ON_ERROR);
    exit;
}

if (!rate_limit_allow('social_ai_generate', 40, 3600)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'message' => 'Too many AI requests. Please wait and try again.'], JSON_THROW_ON_ERROR);
    exit;
}

require_csrf();

$raw = file_get_contents('php://input');
$payload = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid request.'], JSON_THROW_ON_ERROR);
    exit;
}

$type = trim((string) ($payload['type'] ?? 'caption'));
$prompt = trim((string) ($payload['prompt'] ?? ''));
$caption = trim((string) ($payload['caption'] ?? ''));
$tone = trim((string) ($payload['tone'] ?? ''));

$ai = new SocialMediaAiService();

if ($type === 'hashtags') {
    if ($caption === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Write a caption first.'], JSON_THROW_ON_ERROR);
        exit;
    }
    $result = $ai->generateHashtags($ctx['business_id'], $caption);
} else {
    if ($prompt === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Describe what you want to post about.'], JSON_THROW_ON_ERROR);
        exit;
    }
    $result = $ai->generateCaption($ctx['business_id'], $prompt, $tone !== '' ? $tone : null);
}

http_response_code(($result['ok'] ?? false) ? 200 : 422);
echo json_encode($result, JSON_THROW_ON_ERROR);
