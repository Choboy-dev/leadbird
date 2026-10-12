<?php

declare(strict_types=1);

/**
 * Send the next batch of every WhatsApp broadcast that is still sending.
 * Also runs from process-automation-queue.php and cron/process-queue.php, so no extra cron is required.
 */
require_once dirname(__DIR__) . '/includes/bootstrap-automation.php';
require_cli();

$sent = (new WhatsAppBroadcastService())->processQueue(10);

echo '[' . date('c') . '] broadcast_sent=' . $sent . PHP_EOL;
