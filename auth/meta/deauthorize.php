<?php

declare(strict_types=1);

/**
 * Meta Deauthorize Callback.
 * Configured in App Dashboard → Facebook Login → Settings → Deauthorize Callback URL.
 *
 * Fires when someone removes Lead Bird from their Facebook account. We drop the
 * stored token and unsubscribe our webhooks so we stop calling the Graph API on
 * their behalf. Conversation history is kept until they ask for deletion.
 */

require_once dirname(__DIR__, 2) . '/includes/bootstrap-automation.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$signedRequest = (string) ($_POST['signed_request'] ?? '');
if ($signedRequest === '') {
    meta_webhook_log('deauthorize rejected: missing signed_request');
    http_response_code(400);
    echo json_encode(['error' => 'Missing signed_request']);
    exit;
}

$payload = meta_parse_signed_request($signedRequest);
if ($payload === null) {
    meta_webhook_log('deauthorize rejected: bad signature');
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signed_request']);
    exit;
}

$metaUserId = trim((string) ($payload['user_id'] ?? ''));
if ($metaUserId === '') {
    meta_webhook_log('deauthorize rejected: no user_id in payload');
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id']);
    exit;
}

$affected = (new MetaDataDeletionService())->deauthorize($metaUserId);
meta_webhook_log('deauthorize handled for user=' . $metaUserId . ' businesses=' . $affected);

echo json_encode(['success' => true]);
