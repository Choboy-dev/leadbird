<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$pdo = Database::connection();
$count = (int) $pdo->query('SELECT COUNT(*) FROM user_token_balances')->fetchColumn();
echo "Token balance rows: {$count}\n";

$rows = $pdo->query(
    'SELECT u.email, b.balance, s.plan_slug
     FROM user_token_balances b
     JOIN users u ON u.id = b.user_id
     LEFT JOIN user_subscriptions s ON s.user_id = b.user_id AND s.status = \'active\''
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    echo ($row['email'] ?? '?') . ' | ' . ($row['balance'] ?? '0') . ' tokens | ' . ($row['plan_slug'] ?? 'n/a') . "\n";
}
