<?php

declare(strict_types=1);

$ctx = require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.'], JSON_THROW_ON_ERROR);
    exit;
}

$accountId = trim((string) ($_GET['account_id'] ?? ''));
$platform = trim((string) ($_GET['platform'] ?? ''));
$platformPostId = trim((string) ($_GET['platform_post_id'] ?? ''));

if ($accountId === '' || $platform === '' || $platformPostId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Missing account or post details.'], JSON_THROW_ON_ERROR);
    exit;
}

$service = new SocialMediaService();
$result = $service->getPostInsights($ctx['business_id'], $accountId, $platform, $platformPostId);

http_response_code(($result['ok'] ?? false) ? 200 : 422);
echo json_encode($result, JSON_THROW_ON_ERROR);
