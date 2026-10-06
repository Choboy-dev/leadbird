<?php

declare(strict_types=1);

$ctx = require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.'], JSON_THROW_ON_ERROR);
    exit;
}

$accountId = trim((string) ($_GET['account_id'] ?? ''));
$service = new SocialMediaService();

if ($accountId === '' || strtolower($accountId) === 'all') {
    $result = $service->listLiveFeedAll($ctx['business_id'], 50);
} else {
    $result = $service->listLiveFeed($ctx['business_id'], $accountId, 40);
}

http_response_code(($result['ok'] ?? false) ? 200 : 422);
echo json_encode($result, JSON_THROW_ON_ERROR);
