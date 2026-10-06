<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$sessionAuth = new SessionAuth();
$userId = $sessionAuth->userId();

if ($userId === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized.'], JSON_THROW_ON_ERROR);
    exit;
}

$subscription = (new SubscriptionService())->getActiveSubscription((string) $userId);
$planSlug = $subscription !== null ? (string) $subscription['plan_slug'] : 'starter';

if (!PlanAccess::allows($planSlug, 'social_media')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Social Media Management is available on the Vendor plan and above.'], JSON_THROW_ON_ERROR);
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
    'plan_slug' => $planSlug,
];
