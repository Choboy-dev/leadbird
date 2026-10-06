<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$pdo = Database::connection();
$sql = file_get_contents(dirname(__DIR__) . '/database/migrations/012_knowledge_v2.sql');
if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "Migration file missing.\n");
    exit(1);
}

$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if ($statement === '') {
        continue;
    }

    try {
        $pdo->exec($statement);
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            continue;
        }

        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

echo "Migration 012_knowledge_v2 applied.\n";
