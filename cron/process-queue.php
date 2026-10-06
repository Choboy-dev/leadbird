<?php

declare(strict_types=1);

/**
 * HTTP cron endpoint for shared hosting (Hostinger hPanel → Cron → fetch URL).
 * Schedule: every minute
 * URL: https://leadbird.app/cron/process-queue?token=YOUR_META_WEBHOOK_VERIFY_TOKEN
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

$processed = (new AutomationQueue())->processBatch(20);

$line = '[' . date('c') . '] processed=' . $processed . PHP_EOL;
@file_put_contents(dirname(__DIR__) . '/logs/automation-queue.log', $line, FILE_APPEND | LOCK_EX);

header('Content-Type: text/plain; charset=utf-8');
echo 'processed=' . $processed;
