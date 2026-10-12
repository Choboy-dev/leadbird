<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('refer'));
}

require_csrf();
require dirname(__DIR__) . '/includes/refer/require-partner.php';

if (!rate_limit_allow('partner_save_bank:' . $userId, 10, 3600)) {
    flash_set('error', 'Too many attempts. Please try again later.');
    redirect($referHome);
}

$bankCode = trim((string) ($_POST['bank_code'] ?? ''));
$accountNumber = preg_replace('/\D+/', '', (string) ($_POST['account_number'] ?? '')) ?? '';

if ($bankCode === '' || strlen($accountNumber) !== 10) {
    flash_set('error', 'Enter a valid bank and 10-digit account number.');
    redirect($referHome);
}

$verify = (new PayoutService())->verifyBankAccount($bankCode, $accountNumber);
if (!($verify['ok'] ?? false)) {
    flash_set('error', (string) ($verify['error'] ?? 'Could not verify account.'));
    redirect($referHome);
}

$bankName = $bankCode;
foreach ((new FlutterwaveService())->listBanks('NG') as $bank) {
    if ((string) ($bank['code'] ?? '') === $bankCode) {
        $bankName = (string) ($bank['name'] ?? $bankCode);
        break;
    }
}

RepositoryFactory::affiliates()->update((string) $affiliate['id'], [
    'bank_code' => $bankCode,
    'bank_name' => $bankName,
    'account_number' => $accountNumber,
    'account_name' => (string) ($verify['account_name'] ?? ''),
]);

flash_set('success', 'Bank account verified: ' . (string) ($verify['account_name'] ?? ''));
redirect($referHome);
