<?php

declare(strict_types=1);

$ctx = require __DIR__ . '/_bootstrap.php';

/**
 * TikTok requires apps to show the creator's current audience and interaction
 * settings before a direct post, so the compose screen loads them from here.
 */
$accountId = trim((string) ($_GET['account_id'] ?? ''));
if ($accountId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Missing TikTok account.'], JSON_THROW_ON_ERROR);
    exit;
}

if (!tiktok_is_enabled()) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'TikTok publishing is turned off right now.'], JSON_THROW_ON_ERROR);
    exit;
}

$account = RepositoryFactory::connectedAccounts()->findById($accountId);
if (
    $account === null
    || (string) ($account['business_id'] ?? '') !== $ctx['business_id']
    || (string) ($account['platform'] ?? '') !== TikTokConnectService::PLATFORM
    || !(bool) ($account['is_active'] ?? false)
) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'TikTok account not found.'], JSON_THROW_ON_ERROR);
    exit;
}

$result = (new TikTokPublishService())->creatorInfo($account);

if (($result['ok'] ?? false) !== true) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => (string) ($result['error'] ?? 'Could not load TikTok settings.'),
    ], JSON_THROW_ON_ERROR);
    exit;
}

echo json_encode([
    'ok' => true,
    'account_id' => $accountId,
    'creator' => $result['creator'] ?? [],
], JSON_THROW_ON_ERROR);
