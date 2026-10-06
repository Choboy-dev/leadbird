<?php

declare(strict_types=1);

/**
 * Re-sort inbound mail that the old body-keyword categoriser filed in the wrong
 * tab. Headers are not stored, so this uses the sender and subject only.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = Database::connection();
$rows = $pdo->query('SELECT id, from_email, subject, category FROM mail_messages WHERE direction = "inbound"')->fetchAll();
$statement = $pdo->prepare('UPDATE mail_messages SET category = ? WHERE id = ?');
$moved = 0;

foreach ($rows as $row) {
    $category = Mail::categorizeInbound((string) $row['from_email'], (string) $row['subject']);
    if ($category === (string) $row['category']) {
        continue;
    }
    $statement->execute([$category, (int) $row['id']]);
    $moved++;
    echo '#' . (int) $row['id'] . ' ' . (string) $row['from_email'] . ': ' . (string) $row['category'] . ' -> ' . $category . PHP_EOL;
}

echo 'checked ' . count($rows) . ' inbound messages, moved ' . $moved . PHP_EOL;
