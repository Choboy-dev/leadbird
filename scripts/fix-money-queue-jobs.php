<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap-automation.php';
require_cli();

$pdo = Database::connection();

// Re-queue jobs that failed because money helpers were missing from the automation bootstrap.
$updated = $pdo->exec(
    "UPDATE automation_queue
     SET status = 'pending',
         available_at = NOW(),
         last_error = NULL
     WHERE status IN ('failed', 'pending', 'processing')
       AND last_error LIKE '%money_format_catalog%'"
);

echo "Requeued money_format_catalog jobs: {$updated}\n";

$processed = (new AutomationQueue())->processBatch(20);
echo "Processed batch: {$processed}\n";

$q = $pdo->query(
    "SELECT id, status, LEFT(COALESCE(last_error, ''), 120) AS err, created_at, processed_at
     FROM automation_queue
     ORDER BY created_at DESC
     LIMIT 6"
);
foreach ($q as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
}
