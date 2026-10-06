<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$db = Database::connection();
$meta = app_config()['meta'];

echo "meta_app_id=" . (($meta['app_id'] ?? '') !== '' ? 'set' : 'missing') . "\n";
echo "meta_app_secret=" . (($meta['app_secret'] ?? '') !== '' ? 'set' : 'missing') . "\n";
echo "meta_webhook_token=" . (($meta['webhook_verify_token'] ?? '') !== '' ? 'set' : 'missing') . "\n";

echo "=== duplicate facebook page ids ===\n";
foreach ($db->query(
    "SELECT platform_account_id, COUNT(*) AS cnt, GROUP_CONCAT(business_id) AS businesses
     FROM connected_accounts
     WHERE platform = 'facebook' AND is_active = 1 AND platform_account_id IS NOT NULL
     GROUP BY platform_account_id
     HAVING cnt > 1"
) as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
}

echo "=== metro facebook account ===\n";
$stmt = $db->prepare(
    "SELECT ca.id, ca.business_id, ca.platform_account_id, ca.is_active, ca.created_at,
            LENGTH(ca.access_token) AS token_len, b.business_name
     FROM connected_accounts ca
     JOIN businesses b ON b.id = ca.business_id
     WHERE b.business_name LIKE :name AND ca.platform = 'facebook'"
);
$stmt->execute(['name' => '%Metro%']);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
}

echo "=== messages last hour ===\n";
$row = $db->query(
    "SELECT COUNT(*) AS cnt FROM messages WHERE created_at >= NOW() - INTERVAL 2 HOUR"
)->fetch(PDO::FETCH_ASSOC);
echo ($row['cnt'] ?? 0) . "\n";

echo "=== queue last hour ===\n";
$row = $db->query(
    "SELECT COUNT(*) AS cnt FROM automation_queue WHERE created_at >= NOW() - INTERVAL 2 HOUR"
)->fetch(PDO::FETCH_ASSOC);
echo ($row['cnt'] ?? 0) . "\n";

echo "=== cron log tail ===\n";
$log = dirname(__DIR__) . '/logs/automation-queue.log';
if (is_readable($log)) {
    $lines = file($log, FILE_IGNORE_NEW_LINES);
    foreach (array_slice($lines ?: [], -5) as $line) {
        echo $line . "\n";
    }
} else {
    echo "(missing)\n";
}
