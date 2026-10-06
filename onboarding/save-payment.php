<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('onboarding/payment'));
}

$userId = (string) (new SessionAuth())->userId();
$save = onboarding_resolve_save_request();
$onboarding = $save['onboarding'];
$dashboard = $save['dashboard'];
$business = $save['business'];

if ($business === null) {
    redirect(app_route('onboarding/profile'));
}

$businessId = (string) $business['id'];
$settings = $onboarding->ensureSettings($businessId);
$action = (string) ($_POST['action'] ?? 'continue');

if ($action === 'save_payment_dashboard') {
    $instructions = trim((string) ($_POST['payment_instructions'] ?? ''));
    if ($instructions === '') {
        flash_set('error', 'Enter payment instructions for customers.');
        redirect(app_route('offers', ['tab' => 'payment']));
    }

    $data = [
        'ai_name' => (string) ($settings['ai_name'] ?? 'Sales Girl'),
        'business_description' => $settings['business_description'] ?? null,
        'faq' => $settings['faq'] ?? null,
        'delivery_policy' => $settings['delivery_policy'] ?? null,
        'return_policy' => $settings['return_policy'] ?? null,
        'payment_instructions' => $instructions,
        'payment_method' => PaymentSetupService::METHOD_BANK,
        'escalation_phone' => $settings['escalation_phone'] ?? null,
        'escalation_email' => $settings['escalation_email'] ?? null,
        'handoff_to_whatsapp' => (bool) ($settings['handoff_to_whatsapp'] ?? true),
        'notify_owner_on_hot_lead' => (bool) ($settings['notify_owner_on_hot_lead'] ?? true),
        'notify_owner_on_payment' => (bool) ($settings['notify_owner_on_payment'] ?? true),
        'notify_owner_on_complaint' => (bool) ($settings['notify_owner_on_complaint'] ?? true),
    ];

    RepositoryFactory::businessSettings()->update($businessId, $data);
    (new BusinessKnowledgeSyncService())->syncManualSources($businessId);
    flash_set('success', 'Payment details saved.');
    redirect(app_route('offers', ['tab' => 'payment']));
}

$redirectUrl = $dashboard
    ? app_route('offers', ['tab' => 'payment'])
    : app_route('onboarding/payment');

$method = trim((string) ($_POST['payment_method'] ?? PaymentSetupService::METHOD_BANK));
if (!PaymentSetupService::isValidMethod($method)) {
    flash_set('error', 'Enter your bank details for customer payments.');
    redirect($redirectUrl);
}

$bankCode = trim((string) ($_POST['bank_code'] ?? ''));
$accountNumber = preg_replace('/\D+/', '', (string) ($_POST['account_number'] ?? '')) ?? '';
$accountName = trim((string) ($_POST['account_name'] ?? ''));

if ($bankCode === '' || strlen($accountNumber) !== 10 || $accountName === '') {
    flash_set('error', 'Enter your bank, account number, and account name.');
    redirect($redirectUrl);
}

$bankName = $bankCode;
try {
    foreach ((new FlutterwaveService())->listBanks('NG') as $bank) {
        if ((string) ($bank['code'] ?? '') === $bankCode) {
            $bankName = (string) ($bank['name'] ?? $bankCode);
            break;
        }
    }
} catch (Throwable $e) {
    $bankName = $bankCode;
}

$paymentInstructions = PaymentSetupService::formatBankInstructions($bankName, $accountName, $accountNumber);

$data = [
    'ai_name' => (string) ($settings['ai_name'] ?? 'Sales Girl'),
    'business_description' => $settings['business_description'] ?? null,
    'faq' => $settings['faq'] ?? null,
    'delivery_policy' => $settings['delivery_policy'] ?? null,
    'return_policy' => $settings['return_policy'] ?? null,
    'payment_instructions' => $paymentInstructions,
    'payment_method' => PaymentSetupService::METHOD_BANK,
    'escalation_phone' => $settings['escalation_phone'] ?? null,
    'escalation_email' => $settings['escalation_email'] ?? null,
    'handoff_to_whatsapp' => (bool) ($settings['handoff_to_whatsapp'] ?? true),
    'notify_owner_on_hot_lead' => (bool) ($settings['notify_owner_on_hot_lead'] ?? true),
    'notify_owner_on_payment' => (bool) ($settings['notify_owner_on_payment'] ?? true),
    'notify_owner_on_complaint' => (bool) ($settings['notify_owner_on_complaint'] ?? true),
];

RepositoryFactory::businessSettings()->update($businessId, $data);
(new BusinessKnowledgeSyncService())->syncManualSources($businessId);

if ($action === 'save_payment_dashboard') {
    flash_set('success', 'Payment details saved.');
    redirect(app_route('offers', ['tab' => 'payment']));
}

$onboarding->advanceAfterStep($businessId, OnboardingService::STEP_PAYMENT);
redirect(app_route('onboarding/alerts'));
