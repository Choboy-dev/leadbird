<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';
require_once dirname(__DIR__) . '/includes/bootstrap-automation.php';
require_once dirname(__DIR__) . '/lib/InboxReplyService.php';
require_once dirname(__DIR__) . '/lib/InboxLabelService.php';

header('Content-Type: application/json; charset=utf-8');

$sessionAuth = new SessionAuth();
$userId = $sessionAuth->userId();

if ($userId === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized.'], JSON_THROW_ON_ERROR);
    exit;
}

$businessCtx = new BusinessContext();
$business = $businessCtx->resolveActiveBusiness((string) $userId);

if ($business === null) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'No active business found.'], JSON_THROW_ON_ERROR);
    exit;
}

return [
    'user_id' => (string) $userId,
    'business_id' => (string) $business['id'],
    'business' => $business,
];
