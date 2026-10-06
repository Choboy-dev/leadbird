<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();
require_once dirname(__DIR__) . '/includes/meta.php';

$db = Database::connection();

echo "=== page webhook subscription (Graph API) ===\n";
$stmt = $db->prepare(
    "SELECT ca.platform_account_id, ca.access_token, b.business_name
     FROM connected_accounts ca
     JOIN businesses b ON b.id = ca.business_id
     WHERE ca.platform = 'facebook' AND ca.platform_account_id = '110332738511132' AND ca.is_active = 1
     ORDER BY ca.created_at DESC"
);
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($accounts as $acc) {
    $pageId = (string) $acc['platform_account_id'];
    $token = (string) $acc['access_token'];
    $name = (string) $acc['business_name'];
    $subscribed = meta_graph_get($pageId . '/subscribed_apps', $token);
    echo $name . ' => ' . json_encode($subscribed, JSON_UNESCAPED_SLASHES) . "\n";
}

echo "=== process queue now ===\n";
$processed = (new AutomationQueue())->processBatch(5);
echo "processed=$processed\n";

echo "=== recent queue ===\n";
foreach ($db->query('SELECT status, last_error, created_at, processed_at FROM automation_queue ORDER BY created_at DESC LIMIT 5') as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
}

echo "=== recent messages ===\n";
foreach ($db->query('SELECT sender_type, message_text, created_at FROM messages ORDER BY created_at DESC LIMIT 6') as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
}
