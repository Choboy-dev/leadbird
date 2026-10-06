<?php

declare(strict_types=1);

/**
 * Verify the Meta deauthorize + data deletion callbacks the way Meta does:
 * build a real signed_request with the app secret and POST it.
 *
 * Usage: php scripts/test-meta-deletion-callback.php [meta_user_id]
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$secret = (string) (app_config()['meta']['app_secret'] ?? '');
if ($secret === '') {
    fwrite(STDERR, "META_APP_SECRET is not set.\n");
    exit(1);
}

$metaUserId = $argv[1] ?? 'test-user-does-not-exist';

function base64_url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function build_signed_request(string $secret, string $userId): string
{
    $payload = base64_url_encode((string) json_encode([
        'algorithm' => 'HMAC-SHA256',
        'issued_at' => time(),
        'user_id' => $userId,
    ]));
    $signature = base64_url_encode(hash_hmac('sha256', $payload, $secret, true));

    return $signature . '.' . $payload;
}

function post_form(string $url, array $fields): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_TIMEOUT => 30,
    ]);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $code, 'body' => $body];
}

$base = rtrim(app_config()['app_url'], '/');
$signed = build_signed_request($secret, $metaUserId);

foreach (['data-deletion', 'deauthorize'] as $endpoint) {
    $url = $base . '/auth/meta/' . $endpoint;

    $bad = post_form($url, ['signed_request' => 'garbage.payload']);
    echo $endpoint . " — invalid signature: HTTP {$bad['code']} {$bad['body']}\n";

    $good = post_form($url, ['signed_request' => $signed]);
    echo $endpoint . " — valid signature:   HTTP {$good['code']} {$good['body']}\n\n";
}
