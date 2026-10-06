<?php

declare(strict_types=1);

/**
 * Meta Data Deletion Callback.
 * Configured in App Dashboard → Settings → Basic → Data Deletion Callback URL.
 *
 * Meta POSTs a signed_request. We must respond with JSON containing a status
 * URL and a confirmation code the person can use to check progress.
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
    meta_webhook_log('data-deletion rejected: missing signed_request');
    http_response_code(400);
    echo json_encode(['error' => 'Missing signed_request']);
    exit;
}

$payload = meta_parse_signed_request($signedRequest);
if ($payload === null) {
    meta_webhook_log('data-deletion rejected: bad signature');
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signed_request']);
    exit;
}

$metaUserId = trim((string) ($payload['user_id'] ?? ''));
if ($metaUserId === '') {
    meta_webhook_log('data-deletion rejected: no user_id in payload');
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id']);
    exit;
}

$result = (new MetaDataDeletionService())->requestDeletion($metaUserId);
meta_webhook_log('data-deletion accepted for user=' . $metaUserId . ' code=' . $result['code']);

echo json_encode([
    'url' => $result['status_url'],
    'confirmation_code' => $result['code'],
]);
