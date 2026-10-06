<?php

declare(strict_types=1);

/**
 * Nightly knowledge sync for all businesses (cron).
 *
 * Example:
 *   0 3 * * * php /path/to/ai-salesgirl/scripts/nightly-knowledge-sync.php >> /path/to/logs/knowledge-sync.log 2>&1
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$sync = new BusinessKnowledgeSyncService();
$total = 0;

foreach (Database::connection()->query('SELECT id FROM businesses')->fetchAll(PDO::FETCH_ASSOC) as $business) {
    $businessId = (string) ($business['id'] ?? '');
    if ($businessId === '') {
        continue;
    }

    $result = $sync->syncAll($businessId);
    echo '[' . date('c') . '] business=' . $businessId
        . ' manual=' . $result['manual']
        . ' social=' . $result['social'] . PHP_EOL;
    $total++;
}

echo '[' . date('c') . '] synced_businesses=' . $total . PHP_EOL;
