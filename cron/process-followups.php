<?php

declare(strict_types=1);

/**
 * HTTP cron for proactive follow-ups (Hostinger hPanel → Cron → fetch URL).
 * Schedule: every 10 minutes
 * URL: https://leadbird.app/cron/process-followups?token=YOUR_META_WEBHOOK_VERIFY_TOKEN
 */
require_once dirname(__DIR__) . '/includes/bootstrap-automation.php';

$token = trim((string) ($_GET['token'] ?? ''));
$expected = trim((string) (app_config()['meta']['webhook_verify_token'] ?? ''));

if ($expected === '' || !hash_equals($expected, $token)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

$stats = (new SalesAutomationService())->processDueFollowups(25);

$line = '[' . date('c') . '] followups sent=' . $stats['sent']
    . ' skipped=' . $stats['skipped']
    . ' errors=' . $stats['errors'] . PHP_EOL;
@file_put_contents(dirname(__DIR__) . '/logs/followups.log', $line, FILE_APPEND | LOCK_EX);

try {
    require_once dirname(__DIR__) . '/lib/SubscriptionRenewalRunner.php';
    SubscriptionRenewalRunner::runIfDue();
} catch (Throwable $e) {
    error_log('[Renewals] ' . $e->getMessage());
}

header('Content-Type: text/plain; charset=utf-8');
echo 'sent=' . $stats['sent'] . ' skipped=' . $stats['skipped'] . ' errors=' . $stats['errors'];
