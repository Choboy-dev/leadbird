<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$sessionAuth = new SessionAuth();
$appUrl = app_config()['app_url'];

if (!$sessionAuth->check()) {
    redirect(app_route('login'));
}

$userId = $sessionAuth->userId();
$txRef = trim((string) ($_GET['tx_ref'] ?? ''));
$transactionId = trim((string) ($_GET['transaction_id'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

if ($txRef === '') {
    flash_set('error', 'Missing payment reference.');
    redirect(app_route('pricing'));
}

$pending = RepositoryFactory::subscriptions()->findByTxRef($txRef);
if ($pending !== null && (string) ($pending['user_id'] ?? '') !== (string) $userId) {
    flash_set('error', 'This payment does not belong to your account.');
    redirect(app_route('billing'));
}

try {
    $result = (new SubscriptionService())->completeCheckout($txRef, $transactionId !== '' ? $transactionId : null, $status !== '' ? $status : null);

    if (!$result['ok']) {
        if (isset($result['message'])) {
            flash_set('error', $result['message']);
        }
        redirect($result['redirect']);
    }

    flash_set('success', (string) ($result['message'] ?? 'Payment successful! Let\'s set up your business.'));
    redirect($result['redirect']);
} catch (Throwable $e) {
    $message = 'We could not confirm your payment. Please contact support if you were charged.';
    flash_set('error', $message);
    redirect(app_route('pricing'));
}
