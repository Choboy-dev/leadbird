<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('onboarding/alerts'));
}

$save = onboarding_resolve_save_request();
$userId = $save['user_id'];
$onboarding = $save['onboarding'];
$appUrl = $save['app_url'];
$dashboard = $save['dashboard'];
$business = $save['business'];
if ($business === null) {
    redirect(app_route('onboarding/profile'));
}

$businessId = (string) $business['id'];
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

if ($dashboard) {
    flash_set('success', 'Alert settings saved.');
    redirect(app_route('dashboard'));
}

$onboarding->completeOnboarding($businessId);
flash_set('success', 'Welcome to Lead Bird! Your business is ready.');
redirect(app_route('dashboard'));
