<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();
require_once dirname(__DIR__) . '/includes/meta.php';

$mediaId = $argv[1] ?? '17949030708081178';
$pdo = Database::connection();
$row = $pdo->query(
    "SELECT * FROM connected_accounts WHERE platform = 'instagram' AND is_active = 1 ORDER BY created_at DESC LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if (!is_array($row)) {
    fwrite(STDERR, "No IG account\n");
    exit(1);
}

$token = (string) ($row['access_token'] ?? '');
$user = meta_user_token_from_account($row);

$fieldSets = [
    'id,media_type,media_product_type,media_url,thumbnail_url,permalink',
    'id,media_type,media_product_type,media_url,thumbnail_url,permalink,is_shared_to_feed,owner',
    'id,media_type,media_url,thumbnail_url,children{media_url,media_type,thumbnail_url}',
];

foreach ([['page', $token], ['user', $user]] as [$label, $tok]) {
    if ($tok === '') {
        echo "=== {$label} token missing ===\n";
        continue;
    }
    echo "=== {$label} token ===\n";
    foreach ($fieldSets as $fields) {
        $data = meta_graph_get(rawurlencode($mediaId), $tok, ['fields' => $fields]);
        $err = $data['error']['message'] ?? null;
        if ($err) {
            echo "fields={$fields}\n  ERROR: {$err}\n";
            continue;
        }
        echo "fields={$fields}\n";
        echo '  media_type=' . ($data['media_type'] ?? '') . ' product=' . ($data['media_product_type'] ?? '') . "\n";
        echo '  media_url_len=' . strlen((string) ($data['media_url'] ?? '')) . "\n";
        echo '  thumb_len=' . strlen((string) ($data['thumbnail_url'] ?? '')) . "\n";
        echo '  permalink=' . ($data['permalink'] ?? '') . "\n";
        if (isset($data['children'])) {
            echo '  children=' . json_encode($data['children'], JSON_UNESCAPED_SLASHES) . "\n";
        }
    }
}
