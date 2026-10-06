<?php

declare(strict_types=1);

/**
 * Refresh the cached USD→NGN rate from Flutterwave.
 *
 * Cron (every 15 minutes recommended):
 *   0,15,30,45 * * * * php /path/to/ai-salesgirl/scripts/refresh-fx-rate.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$rate = (new FxRateService())->refreshLiveRate();
if ($rate === null) {
    fwrite(STDERR, date('c') . " FX refresh failed — no live quote\n");
    exit(1);
}

echo date('c') . ' FX refreshed: 1 USD = ' . number_format($rate, 2) . " NGN\n";
