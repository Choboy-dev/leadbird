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

$canNegotiate = PlanAccess::allows($planSlug, 'ai_negotiation');
$canFollowup = PlanAccess::allows($planSlug, 'proactive_followups');
$canReceipt = PlanAccess::allows($planSlug, 'payment_receipts');

if (!$canNegotiate && !$canFollowup && !$canReceipt) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Upgrade your plan to edit selling automation.'], JSON_THROW_ON_ERROR);
    exit;
}

$settings = $onboarding->ensureSettings($businessId);
$data = [];

if ($canNegotiate) {
    $data['negotiation_enabled'] = isset($_POST['negotiation_enabled']);
    $data['max_discount_percent'] = (float) ($_POST['max_discount_percent'] ?? ($settings['max_discount_percent'] ?? 10));
    $data['negotiation_rules'] = trim((string) ($_POST['negotiation_rules'] ?? ''));
    $data['upsell_enabled'] = isset($_POST['upsell_enabled']);
    $data['upsell_notes'] = trim((string) ($_POST['upsell_notes'] ?? ''));
}

if ($canFollowup) {
    $data['followup_enabled'] = isset($_POST['followup_enabled']);
    $data['followup_delay_hours'] = (int) ($_POST['followup_delay_hours'] ?? ($settings['followup_delay_hours'] ?? 6));
    $data['followup_max_attempts'] = (int) ($_POST['followup_max_attempts'] ?? ($settings['followup_max_attempts'] ?? 2));
    $data['followup_interval_hours'] = (int) ($_POST['followup_interval_hours'] ?? ($settings['followup_interval_hours'] ?? 12));
    $data['followup_notes'] = trim((string) ($_POST['followup_notes'] ?? ''));
}

if ($canReceipt) {
    $data['receipt_enabled'] = isset($_POST['receipt_enabled']);
    $data['receipt_message'] = trim((string) ($_POST['receipt_message'] ?? ''));
}

try {
    RepositoryFactory::businessSettings()->update($businessId, $data);
    $fresh = RepositoryFactory::businessSettings()->findByBusinessId($businessId) ?? $settings;
} catch (Throwable $e) {
    error_log('[ai-salesgirl/save-selling] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Something went wrong. Please try again.'], JSON_THROW_ON_ERROR);
    exit;
}

echo json_encode([
    'ok' => true,
    'selling' => [
        'negotiation_enabled' => (bool) ($fresh['negotiation_enabled'] ?? true),
        'max_discount_percent' => (float) ($fresh['max_discount_percent'] ?? 10),
        'negotiation_rules' => (string) ($fresh['negotiation_rules'] ?? ''),
        'upsell_enabled' => (bool) ($fresh['upsell_enabled'] ?? true),
        'upsell_notes' => (string) ($fresh['upsell_notes'] ?? ''),
        'followup_enabled' => (bool) ($fresh['followup_enabled'] ?? true),
        'followup_delay_hours' => (int) ($fresh['followup_delay_hours'] ?? 6),
        'followup_max_attempts' => (int) ($fresh['followup_max_attempts'] ?? 2),
        'followup_interval_hours' => (int) ($fresh['followup_interval_hours'] ?? 12),
        'followup_notes' => (string) ($fresh['followup_notes'] ?? ''),
        'receipt_enabled' => (bool) ($fresh['receipt_enabled'] ?? true),
        'receipt_message' => (string) ($fresh['receipt_message'] ?? ''),
    ],
], JSON_THROW_ON_ERROR);
