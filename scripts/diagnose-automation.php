<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$db = Database::connection();

echo "=== connected_accounts count ===\n";
$row = $db->query('SELECT COUNT(*) AS cnt FROM connected_accounts')->fetch(PDO::FETCH_ASSOC);
echo ($row['cnt'] ?? 0) . "\n";

echo "=== connected_accounts rows ===\n";
foreach ($db->query('SELECT * FROM connected_accounts LIMIT 10') as $account) {
    unset($account['access_token'], $account['refresh_token']);
    echo json_encode($account, JSON_UNESCAPED_SLASHES) . "\n";
}

echo "=== user_subscriptions (recent) ===\n";
foreach ($db->query('SELECT user_id, plan_slug, status, current_period_end FROM user_subscriptions ORDER BY created_at DESC LIMIT 8') as $sub) {
    echo json_encode($sub, JSON_UNESCAPED_SLASHES) . "\n";
}

echo "=== Metro Football UK business ===\n";
$stmt = $db->prepare('SELECT b.id, b.business_name, b.subscription_status, u.email FROM businesses b JOIN users u ON u.id = b.user_id WHERE b.business_name LIKE :name');
$stmt->execute(['name' => '%Metro%']);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $biz) {
    echo json_encode($biz, JSON_UNESCAPED_SLASHES) . "\n";
    $sub = (new SubscriptionService())->getActiveSubscription(
        (string) $db->query('SELECT user_id FROM businesses WHERE id = ' . $db->quote($biz['id']))->fetchColumn()
    );
    echo 'user_sub_active=' . ($sub !== null ? 'yes' : 'no') . "\n";
}

echo "=== automation_queue ===\n";
foreach ($db->query('SELECT status, last_error, created_at FROM automation_queue ORDER BY created_at DESC LIMIT 5') as $job) {
    echo json_encode($job, JSON_UNESCAPED_SLASHES) . "\n";
}

echo "=== messages count ===\n";
$row = $db->query('SELECT COUNT(*) AS cnt FROM messages')->fetch(PDO::FETCH_ASSOC);
echo ($row['cnt'] ?? 0) . "\n";

echo 'openai=' . (app_config()['openai']['api_key'] !== '' ? 'set' : 'missing') . "\n";
