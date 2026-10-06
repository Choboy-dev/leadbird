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
$details = meta_fetch_instagram_media_details_for_playback($mediaId, $token, $user);
$post = is_array($details['post'] ?? null) ? $details['post'] : [];

echo 'ok=' . ((($details['ok'] ?? false) === true) ? '1' : '0') . "\n";
echo 'type=' . (string) ($post['media_type'] ?? '') . "\n";
echo 'reel=' . (!empty($post['is_reel']) ? '1' : '0') . "\n";
echo 'media_len=' . strlen((string) ($post['media_url'] ?? '')) . "\n";
echo 'thumb_len=' . strlen((string) ($post['thumbnail_url'] ?? '')) . "\n";
$host = parse_url((string) ($post['media_url'] ?? ''), PHP_URL_HOST);
echo 'media_host=' . (is_string($host) ? $host : '') . "\n";
