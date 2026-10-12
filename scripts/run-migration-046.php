<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$sql = file_get_contents(dirname(__DIR__) . '/database/migrations/046_whatsapp_template_library.sql');
if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "Migration file missing.\n");
    exit(1);
}

/** Re-running the migration must be safe, so treat "already applied" errors as success. */
$alreadyApplied = ['already exists', 'Duplicate column name', 'Duplicate key name'];

$pdo = Database::connection();
foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
    $lines = preg_split("/\R/", $statement) ?: [];
    $statement = trim(implode("\n", array_filter($lines, static fn(string $line): bool => !str_starts_with(ltrim($line), '--'))));
    if ($statement === '') {
        continue;
    }
    try {
        $pdo->exec($statement);
    } catch (Throwable $e) {
        foreach ($alreadyApplied as $needle) {
            if (str_contains($e->getMessage(), $needle)) {
                continue 2;
            }
        }
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

echo "Migration 046_whatsapp_template_library applied.\n";
