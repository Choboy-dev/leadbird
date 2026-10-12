<?php

declare(strict_types=1);

/**
 * Process queued inbound messages (OpenAI + platform replies).
 *
 * Cron example (every minute):
 *   * * * * * /Applications/XAMPP/xamppfiles/bin/php /path/to/ai-salesgirl/scripts/process-automation-queue.php >> /path/to/logs/automation-queue.log 2>&1
 */
require_once dirname(__DIR__) . '/includes/bootstrap-automation.php';
require_cli();

$limit = 20;
if (PHP_SAPI === 'cli' && isset($argv[1]) && is_numeric($argv[1])) {
    $limit = max(1, min(50, (int) $argv[1]));
}

$processed = (new AutomationQueue())->processBatch($limit);

try {
    (new WhatsAppBroadcastService())->processQueue(5);
} catch (Throwable $e) {
    error_log('[Broadcast] queue: ' . $e->getMessage());
}

if (PHP_SAPI === 'cli') {
    echo '[' . date('c') . '] processed=' . $processed . PHP_EOL;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'processed=' . $processed;
}
