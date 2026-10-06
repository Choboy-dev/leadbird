<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (new SessionAuth())->userId();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized.'], JSON_THROW_ON_ERROR);
    exit;
}

$onboarding = new OnboardingService();
$business = $onboarding->getBusinessForUser($userId);
if ($business === null) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Business profile not found.'], JSON_THROW_ON_ERROR);
    exit;
}

$businessId = (string) $business['id'];
$subscription = (new SubscriptionService())->getActiveSubscription($userId);
$planSlug = $subscription !== null ? (string) $subscription['plan_slug'] : 'starter';

if (!PlanAccess::allows($planSlug, 'ai_salesgirl_full')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Upgrade your plan to customize owner alerts.'], JSON_THROW_ON_ERROR);
    exit;
}

$settings = $onboarding->ensureSettings($businessId);
$alertsContext = $onboarding->getAlertsContextForUser($userId);

$angry = isset($_POST['alert_angry_customer']);
$refund = isset($_POST['alert_refund_request']);
$payment = isset($_POST['alert_payment_issue']);
$largeOrder = isset($_POST['alert_large_order']);
$humanHandoff = isset($_POST['alert_human_handoff']);

$data = [
    'ai_name' => (string) ($settings['ai_name'] ?? 'Salesgirl'),
    'business_description' => $settings['business_description'] ?? null,
    'faq' => $settings['faq'] ?? null,
    'delivery_policy' => $settings['delivery_policy'] ?? null,
    'return_policy' => $settings['return_policy'] ?? null,
    'payment_instructions' => $settings['payment_instructions'] ?? null,
    'payment_method' => $settings['payment_method'] ?? null,
    'escalation_phone' => $settings['escalation_phone'] ?? null,
    'escalation_email' => $settings['escalation_email'] ?? null,
    'notify_owner_on_complaint' => $angry || $refund,
    'notify_owner_on_payment' => $payment,
    'notify_owner_on_hot_lead' => $alertsContext['show_hot_lead_alerts'] && $largeOrder,
    'handoff_to_whatsapp' => $alertsContext['show_handoff_to_whatsapp'] && $humanHandoff,
    'ai_tone' => $settings['ai_tone'] ?? null,
    'ai_greeting' => $settings['ai_greeting'] ?? null,
];

try {
    RepositoryFactory::businessSettings()->update($businessId, $data);
} catch (Throwable $e) {
    error_log('[ai-salesgirl/save-alerts] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Something went wrong. Please try again.'], JSON_THROW_ON_ERROR);
    exit;
}

echo json_encode([
    'ok' => true,
    'alerts' => [
        'alert_angry_customer' => $angry,
        'alert_refund_request' => $refund,
        'alert_payment_issue' => $payment,
        'alert_large_order' => $largeOrder,
        'alert_human_handoff' => $humanHandoff,
    ],
], JSON_THROW_ON_ERROR);
