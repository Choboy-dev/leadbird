<?php

declare(strict_types=1);

/**
 * Send proactive follow-ups for quiet Shop-plan conversations.
 *
 * Cron: every 5–15 minutes
 *   php /path/to/scripts/process-followups.php
 */
require_once dirname(__DIR__) . '/includes/bootstrap-automation.php';
require_cli();

$stats = (new SalesAutomationService())->processDueFollowups(25);

$line = '[' . date('c') . '] followups sent=' . $stats['sent']
    . ' skipped=' . $stats['skipped']
    . ' errors=' . $stats['errors'] . PHP_EOL;

@file_put_contents(dirname(__DIR__) . '/logs/followups.log', $line, FILE_APPEND | LOCK_EX);

if (PHP_SAPI === 'cli') {
    echo $line;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $line;
}
