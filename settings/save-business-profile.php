<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';

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

$businessName = trim((string) ($_POST['business_name'] ?? ''));
if ($businessName === '') {
    flash_set('error', 'Business name is required.');
    redirect(app_route('settings'));
}

$businessId = (string) $business['id'];
$onboarding = new OnboardingService();
$existing = $onboarding->ensureSettings($businessId);

RepositoryFactory::businesses()->update($businessId, [
    'business_name' => $businessName,
]);

RepositoryFactory::businessSettings()->update($businessId, [
    'ai_name' => trim((string) ($_POST['ai_name'] ?? 'Sales Girl')) ?: 'Sales Girl',
    'business_description' => trim((string) ($_POST['business_description'] ?? '')) ?: null,
    'catalog_currency' => money_normalize_currency((string) ($_POST['catalog_currency'] ?? ''))
        ?? money_business_catalog_currency($existing),
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

(new BusinessKnowledgeSyncService())->syncManualSources($businessId);
flash_set('success', 'Business profile saved.');
redirect(app_route('settings'));
