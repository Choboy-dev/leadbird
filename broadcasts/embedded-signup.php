<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';

header('Content-Type: application/json; charset=utf-8');

$respond = static function (array $body, int $status = 200): never {
    http_response_code($status);
    echo json_encode($body, JSON_THROW_ON_ERROR);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respond(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

$userId = (new SessionAuth())->userId();
if ($userId === null) {
    $respond(['ok' => false, 'message' => 'Please sign in again.'], 401);
}

require_csrf();

$subscription = (new SubscriptionService())->getActiveSubscription((string) $userId);
$planSlug = $subscription !== null ? (string) $subscription['plan_slug'] : 'starter';
if (!PlanAccess::allows($planSlug, 'broadcasts')) {
    $respond(['ok' => false, 'message' => 'Bulk WhatsApp Messaging is available on the Vendor plan and above.'], 403);
}

$business = (new BusinessContext())->resolveActiveBusiness((string) $userId);
if ($business === null) {
    $respond(['ok' => false, 'message' => 'No active business found.'], 422);
}

if (!rate_limit_allow('wa_embedded_signup:' . $userId, 10, 3600)) {
    $respond(['ok' => false, 'message' => 'Too many attempts. Please wait a few minutes and try again.'], 429);
}

$raw = file_get_contents('php://input');
$input = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($input)) {
    $respond(['ok' => false, 'message' => 'Invalid request.'], 400);
}

try {
    $result = (new MetaConnectService())->completeEmbeddedSignup(
        (string) $business['id'],
        trim((string) ($input['code'] ?? '')),
        trim((string) ($input['waba_id'] ?? '')),
        trim((string) ($input['phone_number_id'] ?? '')),
        !empty($input['keeps_business_app'])
    );
} catch (Throwable $e) {
    error_log('[EmbeddedSignup] business=' . $business['id'] . ' ' . $e->getMessage());
    $message = $e instanceof RuntimeException && $e->getMessage() !== ''
        ? $e->getMessage()
        : 'We could not connect your number. Please try again.';
    $respond(['ok' => false, 'message' => $message], 422);
}

unset($_SESSION['lb_wa_senders_' . $business['id']]);
flash_set('success', 'Your WhatsApp number ' . $result['phone'] . ' is connected. Hannah can now reply to customers on it, and you can send bulk messages from it.');
$respond(['ok' => true, 'redirect' => app_route('broadcasts/senders')]);
