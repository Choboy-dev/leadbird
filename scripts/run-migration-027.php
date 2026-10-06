<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$pdo = Database::connection();
$sql = file_get_contents(dirname(__DIR__) . '/database/migrations/027_customer_profile_picture.sql');
if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "Migration file missing.\n");
    exit(1);
}

foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
    if ($statement === '') {
        continue;
    }

    $lines = preg_split("/\R/", $statement) ?: [];
    $cleanLines = [];
    foreach ($lines as $line) {
        $trim = ltrim($line);
        if ($trim === '' || str_starts_with($trim, '--')) {
            continue;
        }
        $cleanLines[] = $line;
    }
    $statement = trim(implode("\n", $cleanLines));
    if ($statement === '') {
        continue;
    }

    try {
        $pdo->exec($statement);
        echo "OK: " . substr(preg_replace('/\s+/', ' ', $statement) ?? $statement, 0, 80) . "…\n";
    } catch (Throwable $e) {
        if (
            str_contains($e->getMessage(), 'Duplicate column')
            || str_contains($e->getMessage(), 'already exists')
            || str_contains($e->getMessage(), 'Duplicate key')
        ) {
            echo "Skip: " . $e->getMessage() . "\n";
            continue;
        }

        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

echo "Migration 027_customer_profile_picture applied.\n";
