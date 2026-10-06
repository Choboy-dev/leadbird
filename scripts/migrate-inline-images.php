<?php

declare(strict_types=1);

/**
 * Rehost data: images that were saved into signatures and drafts before inline
 * images were uploaded to public storage. Email clients drop data: URIs, so
 * these would never render in a recipient's inbox.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = Database::connection();
$converted = 0;

foreach ($pdo->query('SELECT id, user_id, body_html FROM mail_signatures')->fetchAll() as $row) {
    $html = (string) $row['body_html'];
    $rehosted = Mail::materializeInlineImages($html, (int) $row['user_id']);
    if ($rehosted === $html) {
        continue;
    }
    $statement = $pdo->prepare('UPDATE mail_signatures SET body_html = ? WHERE id = ?');
    $statement->execute([$rehosted, (int) $row['id']]);
    $converted++;
    echo 'signature #' . (int) $row['id'] . ' rehosted' . PHP_EOL;
}

foreach ($pdo->query('SELECT id, user_id, body_html FROM mail_messages WHERE folder = "drafts"')->fetchAll() as $row) {
    $html = (string) $row['body_html'];
    $rehosted = Mail::materializeInlineImages($html, (int) $row['user_id']);
    if ($rehosted === $html) {
        continue;
    }
    $statement = $pdo->prepare('UPDATE mail_messages SET body_html = ? WHERE id = ?');
    $statement->execute([$rehosted, (int) $row['id']]);
    $converted++;
    echo 'draft #' . (int) $row['id'] . ' rehosted' . PHP_EOL;
}

echo 'rehosted rows: ' . $converted . PHP_EOL;
