<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

$pageId = $argv[1] ?? '106516258734254';
$rows = Database::connection()->query(
    "SELECT * FROM connected_accounts WHERE platform = 'facebook' AND platform_account_id = " . Database::connection()->quote($pageId) . " AND is_active = 1"
)->fetchAll(PDO::FETCH_ASSOC);

if ($rows === []) {
    fwrite(STDOUT, "No account found\n");
    exit(0);
}

$account = $rows[0];
$businessId = (string) ($account['business_id'] ?? '');
fwrite(STDOUT, "business_id={$businessId}\n");
fwrite(STDOUT, "has access_token=" . (trim((string) ($account['access_token'] ?? '')) !== '' ? 'yes' : 'no') . "\n");
fwrite(STDOUT, "has refresh_token=" . (trim((string) ($account['refresh_token'] ?? '')) !== '' ? 'yes' : 'no') . "\n");

$meta = RepositoryFactory::businessMetaConnections()->findByBusinessId($businessId);
fwrite(STDOUT, "business meta token=" . (is_array($meta) && trim((string) ($meta['access_token'] ?? '')) !== '' ? 'yes' : 'no') . "\n");

foreach (dash_meta_lookup_tokens($account) as $i => $token) {
    fwrite(STDOUT, "\nToken #{$i}\n");
    $page = meta_graph_get($pageId, $token, ['fields' => 'name']);
    fwrite(STDOUT, 'direct: ' . json_encode($page) . "\n");

    $accounts = meta_graph_get('me/accounts', $token, ['fields' => 'id,name', 'limit' => 5]);
    fwrite(STDOUT, 'me/accounts sample: ' . json_encode($accounts) . "\n");

    $found = dash_find_facebook_page_name_in_accounts($pageId, $token);
    fwrite(STDOUT, "scan found: {$found}\n");
}

fwrite(STDOUT, "\nFinal: " . dash_connected_account_name($account) . "\n");
