<?php

declare(strict_types=1);

/**
 * Charge due subscriptions using saved payment methods.
 *
 * Cron example (daily at 6am):
 *   0 6 * * * php /path/to/ai-salesgirl/scripts/renew-subscriptions.php >> /path/to/logs/renewals.log 2>&1
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$result = (new SubscriptionService())->renewDueSubscriptions();

echo '[' . date('c') . '] processed=' . $result['processed']
    . ' renewed=' . $result['renewed']
    . ' failed=' . $result['failed']
    . ' expired=' . $result['expired'] . PHP_EOL;
