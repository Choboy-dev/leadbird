<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$pdo = Database::connection();
$sql = file_get_contents(dirname(__DIR__) . '/database/migrations/015_scheduled_post_media.sql');
if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "Migration file missing.\n");
    exit(1);
}

foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
    if ($statement === '') {
        continue;
    }

    try {
        $pdo->exec($statement);
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), 'Duplicate column') || str_contains($e->getMessage(), 'already exists')) {
            continue;
        }

        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

echo "Migration 015_scheduled_post_media applied.\n";
