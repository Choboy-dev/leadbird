<?php

declare(strict_types=1);

/**
 * Run migration 009 (user tokens) and backfill balances for active subscribers.
 *
 * Usage: php scripts/run-migration-009.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$pdo = Database::connection();
$sql = file_get_contents(dirname(__DIR__) . '/database/migrations/009_user_tokens.sql');
if ($sql === false) {
    fwrite(STDERR, "Could not read migration file.\n");
    exit(1);
}

$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql) ?: []));
foreach ($statements as $statement) {
    if ($statement === '') {
        continue;
    }
    $pdo->exec($statement);
    echo "OK: " . substr(str_replace("\n", ' ', $statement), 0, 72) . "...\n";
}

$tokenService = new TokenService();
$rows = $pdo->query(
    "SELECT user_id, plan_slug FROM user_subscriptions WHERE status = 'active'"
)->fetchAll(PDO::FETCH_ASSOC);

$granted = 0;
foreach ($rows as $row) {
    $userId = (string) ($row['user_id'] ?? '');
    $planSlug = (string) ($row['plan_slug'] ?? 'starter');
    if ($userId === '') {
        continue;
    }

    $before = $tokenService->getBalance($userId);
    if ($before > 0) {
        echo "Skip user {$userId} — balance already {$before}\n";
        continue;
    }

    $tokenService->grantMonthlyAllowance($userId, $planSlug, 'subscription_start', 'migration_' . $userId);
    $after = $tokenService->getBalance($userId);
    if ($after > $before) {
        ++$granted;
        echo "Granted tokens to user {$userId} ({$planSlug}): {$before} -> {$after}\n";
    }
}

echo "Migration complete. Backfilled {$granted} active subscriber(s).\n";
