<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

$pageId = $argv[1] ?? '106516258734254';
$rows = Database::connection()->query(
    "SELECT * FROM connected_accounts WHERE platform = 'facebook' AND platform_account_id = " . Database::connection()->quote($pageId) . " AND is_active = 1"
)->fetchAll(PDO::FETCH_ASSOC);

if ($rows === []) {
    fwrite(STDOUT, "No active facebook account for page {$pageId}\n");
    exit(0);
}

foreach ($rows as $account) {
    $name = dash_connected_account_name($account);
    fwrite(STDOUT, $account['id'] . ' => ' . $name . PHP_EOL);
}
