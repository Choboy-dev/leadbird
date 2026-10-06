<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$pdo = Database::connection();
$cid = $argv[1] ?? 'a2436a99-c7a9-40e6-913c-907fd1f84a80';

$c = $pdo->prepare('SELECT id, status, platform, business_id, created_at FROM conversations WHERE id = ?');
$c->execute([$cid]);
$conv = $c->fetch(PDO::FETCH_ASSOC);
echo "CONV: " . json_encode($conv, JSON_UNESCAPED_SLASHES) . "\n";

$m = $pdo->prepare(
    'SELECT id, sender_type, LEFT(message_text, 100) AS text, created_at
     FROM messages WHERE conversation_id = ? ORDER BY created_at DESC LIMIT 10'
);
$m->execute([$cid]);
foreach ($m->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo 'MSG: ' . json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
}

$q = $pdo->query(
    "SELECT id, job_type, status, LEFT(COALESCE(last_error, ''), 160) AS err, created_at, processed_at
     FROM automation_queue ORDER BY created_at DESC LIMIT 12"
);
foreach ($q as $row) {
    echo 'Q: ' . json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
}

if (is_array($conv)) {
    $bizId = (string) ($conv['business_id'] ?? '');
    $b = $pdo->prepare('SELECT id, user_id, subscription_plan, subscription_status FROM businesses WHERE id = ?');
    $b->execute([$bizId]);
    $biz = $b->fetch(PDO::FETCH_ASSOC);
    echo 'BIZ: ' . json_encode($biz, JSON_UNESCAPED_SLASHES) . "\n";
    if (is_array($biz)) {
        $uid = (string) ($biz['user_id'] ?? '');
        $t = $pdo->prepare('SELECT balance FROM user_token_balances WHERE user_id = ?');
        $t->execute([$uid]);
        echo 'TOKENS: ' . json_encode($t->fetch(PDO::FETCH_ASSOC), JSON_UNESCAPED_SLASHES) . "\n";
        $s = $pdo->prepare("SELECT plan_slug, status, expires_at FROM user_subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
        $s->execute([$uid]);
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
            echo 'SUB: ' . json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
        }
    }
}
