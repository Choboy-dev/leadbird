<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('settings'));
}

require_csrf();

$userId = (string) (new SessionAuth())->userId();
$ctx = new BusinessContext();
$business = $ctx->resolveActiveBusiness($userId);

if ($business === null) {
    flash_set('error', 'We could not find your active business.');
    redirect(app_route('settings'));
}

$businessId = (string) $business['id'];
$onboarding = new OnboardingService();
$settings = $onboarding->ensureSettings($businessId);
$alertsContext = $onboarding->getAlertsContextForUser($userId);

$data = [
    'ai_name' => (string) ($settings['ai_name'] ?? 'Sales Girl'),
    'business_description' => $settings['business_description'] ?? null,
    'faq' => $settings['faq'] ?? null,
    'delivery_policy' => $settings['delivery_policy'] ?? null,
    'return_policy' => $settings['return_policy'] ?? null,
    'payment_instructions' => $settings['payment_instructions'] ?? null,
    'payment_method' => $settings['payment_method'] ?? null,
    'escalation_phone' => $alertsContext['show_whatsapp_number']
        ? (trim((string) ($_POST['escalation_phone'] ?? '')) ?: null)
        : null,
    'escalation_email' => trim((string) ($_POST['escalation_email'] ?? '')) ?: null,
    'handoff_to_whatsapp' => $alertsContext['show_handoff_to_whatsapp']
        && onboarding_bool_from_post('handoff_to_whatsapp', false),
    'notify_owner_on_hot_lead' => $alertsContext['show_hot_lead_alerts']
        && onboarding_bool_from_post('notify_owner_on_hot_lead', false),
    'notify_owner_on_payment' => onboarding_bool_from_post('notify_owner_on_payment', true),
    'notify_owner_on_complaint' => onboarding_bool_from_post('notify_owner_on_complaint', true),
];

RepositoryFactory::businessSettings()->update($businessId, $data);
flash_set('success', 'Notification preferences saved.');
redirect(app_route('settings'));
