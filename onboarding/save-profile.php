<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_onboarding.php';
require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('onboarding/profile'));
}

$userId = (string) (new SessionAuth())->userId();
$onboarding = new OnboardingService();
$ctx = new BusinessContext();
$appUrl = app_config()['app_url'];

$businessName = trim((string) ($_POST['business_name'] ?? ''));
if ($businessName === '') {
    flash_set('error', 'Business name is required.');
    redirect(app_route('onboarding/profile'));
}

$data = [
    'business_name' => $businessName,
    'owner_name' => trim((string) ($_POST['owner_name'] ?? '')) ?: null,
    'owner_email' => trim((string) ($_POST['owner_email'] ?? '')) ?: null,
    'owner_phone' => trim((string) ($_POST['owner_phone'] ?? '')) ?: null,
];

$settingsData = [
    'ai_name' => trim((string) ($_POST['ai_name'] ?? 'Sales Girl')) ?: 'Sales Girl',
    'business_description' => trim((string) ($_POST['business_description'] ?? '')) ?: null,
];

$business = $ctx->ensureOnboardingBusiness($userId);
$businessId = (string) $business['id'];

RepositoryFactory::businesses()->update($businessId, $data);
$existing = $onboarding->ensureSettings($businessId);
RepositoryFactory::businessSettings()->update($businessId, [
    'ai_name' => $settingsData['ai_name'],
    'business_description' => $settingsData['business_description'],
    'faq' => $existing['faq'] ?? null,
    'delivery_policy' => $existing['delivery_policy'] ?? null,
    'return_policy' => $existing['return_policy'] ?? null,
    'payment_instructions' => $existing['payment_instructions'] ?? null,
    'payment_method' => $existing['payment_method'] ?? null,
    'escalation_phone' => $existing['escalation_phone'] ?? null,
    'escalation_email' => $existing['escalation_email'] ?? null,
    'handoff_to_whatsapp' => (bool) ($existing['handoff_to_whatsapp'] ?? true),
    'notify_owner_on_hot_lead' => (bool) ($existing['notify_owner_on_hot_lead'] ?? true),
    'notify_owner_on_payment' => (bool) ($existing['notify_owner_on_payment'] ?? true),
    'notify_owner_on_complaint' => (bool) ($existing['notify_owner_on_complaint'] ?? true),
]);

(new SubscriptionService())->syncBusinessSubscription($userId);

$onboarding->advanceAfterStep($businessId, OnboardingService::STEP_PROFILE);
redirect(app_route('onboarding/offerings'));
