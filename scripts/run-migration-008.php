<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$db = Database::connection();

try {
    $db->exec('ALTER TABLE businesses ADD INDEX idx_businesses_user_id (user_id)');
    echo "add index ok\n";
} catch (Throwable $e) {
    echo 'add: ' . $e->getMessage() . "\n";
}

try {
    $db->exec('ALTER TABLE businesses DROP INDEX uk_businesses_user_id');
    echo "drop unique ok\n";
} catch (Throwable $e) {
    echo 'drop: ' . $e->getMessage() . "\n";
}
