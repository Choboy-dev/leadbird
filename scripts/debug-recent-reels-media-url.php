<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();
require_once dirname(__DIR__) . '/includes/meta.php';

$pdo = Database::connection();
$row = $pdo->query(
    "SELECT * FROM connected_accounts WHERE platform = 'instagram' AND is_active = 1 ORDER BY created_at DESC LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if (!is_array($row)) {
    fwrite(STDERR, "No IG account\n");
    exit(1);
}

$igId = (string) ($row['platform_account_id'] ?? '');
$token = (string) ($row['access_token'] ?? '');
$user = meta_user_token_from_account($row);
$tok = $user !== '' ? $user : $token;

$data = meta_graph_get(rawurlencode($igId) . '/media', $tok, [
    'fields' => 'id,media_type,media_product_type,media_url,thumbnail_url,timestamp,permalink,caption',
    'limit' => 12,
]);

if (isset($data['error'])) {
    echo "ERROR: " . ($data['error']['message'] ?? 'unknown') . "\n";
    exit(1);
}

foreach ($data['data'] ?? [] as $item) {
    if (!is_array($item)) {
        continue;
    }
    $product = strtoupper((string) ($item['media_product_type'] ?? ''));
    $type = strtoupper((string) ($item['media_type'] ?? ''));
    $isVideo = $type === 'VIDEO' || $product === 'REELS';
    if (!$isVideo) {
        continue;
    }
    echo ($item['id'] ?? '') . ' | ' . $product . '/' . $type
        . ' | media_len=' . strlen((string) ($item['media_url'] ?? ''))
        . ' | ' . substr((string) ($item['timestamp'] ?? ''), 0, 10)
        . ' | ' . substr(preg_replace('/\s+/', ' ', (string) ($item['caption'] ?? '')), 0, 40)
        . "\n";
}

// Also check if Lead Bird stored this platform post.
$target = $pdo->prepare(
    "SELECT sp.id, sp.media_type, sp.media_url, sp.media_storage_path, spt.platform_post_id, spt.status
     FROM scheduled_post_targets spt
     JOIN scheduled_posts sp ON sp.id = spt.scheduled_post_id
     WHERE spt.platform_post_id = :pid
     LIMIT 5"
);
$target->execute(['pid' => '17949030708081178']);
$local = $target->fetchAll(PDO::FETCH_ASSOC);
echo "\nLocal scheduled rows for target reel: " . count($local) . "\n";
foreach ($local as $r) {
    echo json_encode($r, JSON_UNESCAPED_SLASHES) . "\n";
}
