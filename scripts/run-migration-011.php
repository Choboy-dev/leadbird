<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$pdo = Database::connection();
$sql = file_get_contents(dirname(__DIR__) . '/database/migrations/011_business_knowledge.sql');
if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "Migration file missing.\n");
    exit(1);
}

try {
    $pdo->exec($sql);
    echo "Migration 011_business_knowledge applied.\n";
} catch (Throwable $e) {
    if (str_contains($e->getMessage(), 'already exists')) {
        echo "Migration 011_business_knowledge already applied.\n";
        exit(0);
    }

    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
