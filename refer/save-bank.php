<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('refer'));
}

require_csrf();

if (!PlanAccess::allows($planSlug, 'refer_earn')) {
    flash_set('error', 'Not allowed.');
    redirect(app_route('dashboard'));
}

$affiliate = (new AffiliateService())->ensureForUser((string) $userId, $user ?? []);
$bankCode = trim((string) ($_POST['bank_code'] ?? ''));
$accountNumber = preg_replace('/\D+/', '', (string) ($_POST['account_number'] ?? '')) ?? '';
$phone = trim((string) ($_POST['phone'] ?? ''));

if ($bankCode === '' || strlen($accountNumber) !== 10) {
    flash_set('error', 'Enter a valid bank and 10-digit account number.');
    redirect(app_route('refer'));
}

$verify = (new PayoutService())->verifyBankAccount($bankCode, $accountNumber);
if (!($verify['ok'] ?? false)) {
    flash_set('error', (string) ($verify['error'] ?? 'Could not verify account.'));
    redirect(app_route('refer'));
}

$banks = (new FlutterwaveService())->listBanks('NG');
$bankName = $bankCode;
foreach ($banks as $bank) {
    if ((string) ($bank['code'] ?? '') === $bankCode) {
        $bankName = (string) ($bank['name'] ?? $bankCode);
        break;
    }
}

$fields = [
    'bank_code' => $bankCode,
    'bank_name' => $bankName,
    'account_number' => $accountNumber,
    'account_name' => (string) ($verify['account_name'] ?? ''),
];
if ($phone !== '') {
    $fields['phone'] = $phone;
}

RepositoryFactory::affiliates()->update((string) $affiliate['id'], $fields);
$fresh = RepositoryFactory::affiliates()->findById((string) $affiliate['id']) ?? $affiliate;
(new WalletService())->ensureAffiliateWallet($fresh);

flash_set('success', 'Bank account verified: ' . (string) ($verify['account_name'] ?? ''));
redirect(app_route('refer'));
