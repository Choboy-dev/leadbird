<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$appUrl = app_config()['app_url'];
$txRef = trim((string) ($_GET['tx_ref'] ?? $_GET['txRef'] ?? ''));
$transactionId = trim((string) ($_GET['transaction_id'] ?? $_GET['transactionId'] ?? ''));

if ($txRef === '' || $transactionId === '') {
    flash_set('error', 'Top-up payment could not be confirmed. Contact support if you were charged.');
    redirect(app_route('billing#tokens'));
}

$result = (new TopupService())->completeFromCallback($txRef, $transactionId);

if (($result['success'] ?? false) === true) {
    flash_set('success', (string) ($result['message'] ?? 'Tokens added to your account.'));
} else {
    flash_set('error', (string) ($result['message'] ?? 'Top-up payment could not be verified.'));
}

redirect(app_route('billing#tokens'));
