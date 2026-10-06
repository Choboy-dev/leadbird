<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';

$userId = (new SessionAuth())->userId();
if ($userId === null) {
    redirect(app_route('login'));
}

try {
    $result = (new PaymentMethodService())->completeCardVerification(
        $userId,
        trim((string) ($_GET['tx_ref'] ?? '')),
        trim((string) ($_GET['transaction_id'] ?? '')),
        trim((string) ($_GET['status'] ?? ''))
    );
} catch (Throwable $e) {
    error_log('[billing/card-callback] ' . $e->getMessage());
    $result = ['ok' => false, 'message' => 'We could not confirm your card. Please try again.'];
}

flash_set($result['ok'] ? 'success' : 'error', $result['message']);
redirect(app_route('billing'));
