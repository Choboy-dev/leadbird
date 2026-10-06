<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$sql = file_get_contents(dirname(__DIR__) . '/database/migrations/036_private_mail.sql');
if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "Migration file missing.\n");
    exit(1);
}

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
        if (str_contains($e->getMessage(), 'already exists')) {
            continue;
        }
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

Mail::ensureStorage();
echo "Migration 036_private_mail applied.\n";
