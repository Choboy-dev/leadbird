<?php

declare(strict_types=1);

/**
 * Refresh business knowledge for one or all businesses.
 *
 * Usage:
 *   php scripts/sync-business-knowledge.php
 *   php scripts/sync-business-knowledge.php <business-id>
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$businessId = isset($argv[1]) ? trim((string) $argv[1]) : '';
$sync = new BusinessKnowledgeSyncService();

if ($businessId !== '') {
    $result = $sync->syncAll($businessId);
    echo 'business=' . $businessId . ' manual=' . $result['manual'] . ' social=' . $result['social'] . PHP_EOL;
    exit(0);
}

$total = 0;
$rows = Database::connection()->query('SELECT id FROM businesses')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $business) {
    $id = (string) ($business['id'] ?? '');
    if ($id === '') {
        continue;
    }

    $result = $sync->syncAll($id);
    echo 'business=' . $id . ' manual=' . $result['manual'] . ' social=' . $result['social'] . PHP_EOL;
    $total++;
}

echo 'synced_businesses=' . $total . PHP_EOL;
