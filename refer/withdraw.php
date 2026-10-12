<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('refer'));
}

require_csrf();
require dirname(__DIR__) . '/includes/refer/require-partner.php';

if (!rate_limit_allow('partner_withdraw:' . $userId, 5, 3600)) {
    flash_set('error', 'Too many withdrawal attempts. Please try again later.');
    redirect($referHome);
}

$result = (new PayoutService())->requestAffiliateWithdrawal($affiliate);

if (!($result['ok'] ?? false)) {
    flash_set('error', (string) ($result['error'] ?? 'Withdrawal failed.'));
    redirect($referHome);
}

flash_set(
    'success',
    'Withdrawal of ' . money_format((float) ($result['amount_usd'] ?? 0))
        . ' requested. We will check it and send it to your bank. You can follow its status in your reward history.'
);
redirect($referHome);
