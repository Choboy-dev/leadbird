<?php

declare(strict_types=1);

/**
 * One-shot App Review helper: fire GET /{ig-user-id}/insights with stored user tokens.
 * Run on production: php scripts/ping-instagram-insights-appreview.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();
require_once dirname(__DIR__) . '/includes/meta.php';

$pdo = Database::connection();
$rows = $pdo->query(
    "SELECT id, business_id, platform_account_id, access_token, refresh_token, display_name
     FROM connected_accounts
     WHERE platform = 'instagram' AND is_active = 1
     ORDER BY created_at DESC
     LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);

if ($rows === []) {
    fwrite(STDERR, "No active Instagram accounts found.\n");
    exit(1);
}

$okCount = 0;
foreach ($rows as $row) {
    $igId = trim((string) ($row['platform_account_id'] ?? ''));
    $userToken = meta_user_token_from_account($row);
    $label = trim((string) ($row['display_name'] ?? $igId));

    echo "Account {$label} ({$igId})\n";
    if ($userToken === '') {
        echo "  SKIP: no Facebook user token in refresh_token — reconnect Instagram.\n";
        continue;
    }

    $ping = meta_ping_instagram_account_insights($igId, $userToken);
    if (($ping['ok'] ?? false) === true) {
        $okCount++;
        echo '  OK metric=' . (string) ($ping['metric'] ?? '') . "\n";
    } else {
        echo '  FAIL: ' . (string) ($ping['error'] ?? 'unknown') . "\n";
    }
}

echo "\nSuccessful App Review pings: {$okCount}\n";
exit($okCount > 0 ? 0 : 2);
