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

$businessId = (string) $business['id'];
$onboarding = new OnboardingService();
$settings = $onboarding->ensureSettings($businessId);

$method = trim((string) ($_POST['payment_method'] ?? PaymentSetupService::METHOD_BANK));
if (!PaymentSetupService::isValidMethod($method)) {
    flash_set('error', 'Enter your bank details for customer payments.');
    redirect(app_route('settings'));
}

$bankCode = trim((string) ($_POST['bank_code'] ?? ''));
$accountNumber = preg_replace('/\D+/', '', (string) ($_POST['account_number'] ?? '')) ?? '';
$accountName = trim((string) ($_POST['account_name'] ?? ''));

if ($bankCode === '' || strlen($accountNumber) !== 10 || $accountName === '') {
    flash_set('error', 'Enter your bank, account number, and account name.');
    redirect(app_route('settings'));
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

RepositoryFactory::businessSettings()->update($businessId, [
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
]);

(new BusinessKnowledgeSyncService())->syncManualSources($businessId);
flash_set('success', 'Payment instructions saved.');
redirect(app_route('settings'));
