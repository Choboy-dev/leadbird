<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$pdo = Database::connection();
$sql = file_get_contents(dirname(__DIR__) . '/database/migrations/010_offering_images.sql');
if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "Migration file missing.\n");
    exit(1);
}

try {
    $pdo->exec($sql);
    echo "Migration 010_offering_images applied.\n";
} catch (Throwable $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "Migration 010_offering_images already applied.\n";
        exit(0);
    }

    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
