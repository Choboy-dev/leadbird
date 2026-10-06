<?php

declare(strict_types=1);

$ctx = require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.'], JSON_THROW_ON_ERROR);
    exit;
}

$filter = trim((string) ($_GET['filter'] ?? 'all'));
$accountId = trim((string) ($_GET['account_id'] ?? ''));
$service = new SocialMediaService();

echo json_encode([
    'ok' => true,
    'posts' => $service->listPosts(
        $ctx['business_id'],
        $filter,
        $accountId !== '' ? $accountId : null
    ),
    'stats' => $service->stats($ctx['business_id']),
    'accounts' => $service->listPublishableAccounts($ctx['business_id']),
], JSON_THROW_ON_ERROR);
