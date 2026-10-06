<?php

declare(strict_types=1);

/**
 * Runs subscription renewals at most once per day from a frequent cron (e.g. the follow-ups
 * cron), so renewals keep working without a dedicated hPanel cron entry.
 */
final class SubscriptionRenewalRunner
{
    /** 07:00 UTC is 08:00 in Lagos: charges and emails land in the morning. */
    private const RUN_AFTER_HOUR_UTC = 7;

    /**
     * @return array{processed: int, renewed: int, failed: int, expired: int}|null null when today's run already happened
     */
    public static function runIfDue(): ?array
    {
        if ((int) gmdate('G') < self::RUN_AFTER_HOUR_UTC) {
            return null;
        }

        $logDir = dirname(__DIR__) . '/logs';
        $handle = @fopen($logDir . '/renewals.state', 'c+');
        if ($handle === false) {
            error_log('[Renewals] could not open state file');
            return null;
        }

        try {
            if (!flock($handle, LOCK_EX | LOCK_NB)) {
                return null;
            }

            $today = gmdate('Y-m-d');
            if (trim((string) stream_get_contents($handle)) === $today) {
                return null;
            }

            // Mark the day as done before charging so a crash mid-run can never charge twice in one day.
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $today);
            fflush($handle);

            self::loadDependencies();
            $result = (new SubscriptionService())->renewDueSubscriptions();

            @file_put_contents(
                $logDir . '/renewals.log',
                '[' . date('c') . '] processed=' . $result['processed']
                    . ' renewed=' . $result['renewed']
                    . ' failed=' . $result['failed']
                    . ' expired=' . $result['expired'] . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** The automation bootstrap does not load the billing classes renewals need. */
    private static function loadDependencies(): void
    {
        $lib = __DIR__;
        require_once $lib . '/ResendClient.php';
        require_once $lib . '/FxRateService.php';
        require_once $lib . '/CheckoutPricing.php';
        require_once $lib . '/FlutterwaveService.php';
        require_once $lib . '/repositories/PaymentMethodRepository.php';
        require_once $lib . '/PaymentMethodService.php';
        require_once $lib . '/repositories/BillingProfileRepository.php';
        require_once $lib . '/BillingProfileService.php';
        require_once $lib . '/RenewalNotice.php';
    }
}
