<?php

declare(strict_types=1);

$ctx = require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.'], JSON_THROW_ON_ERROR);
    exit;
}

$accountId = trim((string) ($_GET['account_id'] ?? ''));
$platformPostId = trim((string) ($_GET['platform_post_id'] ?? ''));

if ($accountId === '' || $platformPostId === '') {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Missing account or post details.'], JSON_THROW_ON_ERROR);
    exit;
}

$service = new SocialMediaService();
$result = $service->getPlayableInstagramMediaUrl($ctx['business_id'], $accountId, $platformPostId);

if (($result['ok'] ?? false) !== true) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => (string) ($result['error'] ?? 'Video unavailable.')], JSON_THROW_ON_ERROR);
    exit;
}

$mediaUrl = (string) ($result['media_url'] ?? '');
if ($mediaUrl === '') {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Video unavailable.'], JSON_THROW_ON_ERROR);
    exit;
}

$ch = curl_init($mediaUrl);
if ($ch === false) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Could not load video from Instagram.'], JSON_THROW_ON_ERROR);
    exit;
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 90,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_USERAGENT => 'LeadBird/1.0',
    CURLOPT_HTTPHEADER => [
        'Accept: video/*,*/*',
    ],
]);

$body = curl_exec($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = trim((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
curl_close($ch);

if (!is_string($body) || $body === '' || $status < 200 || $status >= 300) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Could not load video from Instagram.'], JSON_THROW_ON_ERROR);
    exit;
}

if (
    $contentType === ''
    || stripos($contentType, 'text/') === 0
    || stripos($contentType, 'application/json') === 0
) {
    $contentType = 'video/mp4';
}

header('Content-Type: ' . $contentType);
header('Content-Length: ' . (string) strlen($body));
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');
echo $body;
