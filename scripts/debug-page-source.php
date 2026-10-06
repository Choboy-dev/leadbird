<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$businessId = '5d076afe-27b6-450f-abde-30e204e27ab6';
$pageId = '106516258734254';
$token = (new MetaConnectService())->resolveBusinessMetaToken($businessId);
if ($token === null) {
    fwrite(STDOUT, "no token\n");
    exit(1);
}

$after = null;
for ($i = 0; $i < 20; $i++) {
    $q = ['fields' => 'id,name', 'limit' => 100];
    if ($after !== null) {
        $q['after'] = $after;
    }
    $data = meta_graph_get('me/accounts', $token, $q);
    foreach ($data['data'] ?? [] as $row) {
        if ((string) ($row['id'] ?? '') === $pageId) {
            fwrite(STDOUT, 'FOUND in me/accounts: ' . ($row['name'] ?? '') . PHP_EOL);
            exit(0);
        }
    }
    $after = trim((string) ($data['paging']['cursors']['after'] ?? ''));
    if ($after === '') {
        break;
    }
}
fwrite(STDOUT, "Not in me/accounts after paging\n");

$biz = meta_graph_get('me/businesses', $token, ['fields' => 'id,name', 'limit' => 25]);
foreach ($biz['data'] ?? [] as $b) {
    if (!is_array($b)) {
        continue;
    }
    $bid = (string) ($b['id'] ?? '');
    foreach (['owned_pages', 'client_pages'] as $edge) {
        $pages = meta_graph_get($bid . '/' . $edge, $token, ['fields' => 'id,name', 'limit' => 100]);
        foreach ($pages['data'] ?? [] as $p) {
            if ((string) ($p['id'] ?? '') === $pageId) {
                fwrite(STDOUT, 'FOUND in ' . $edge . ': ' . ($p['name'] ?? '') . PHP_EOL);
                exit(0);
            }
        }
    }
}
fwrite(STDOUT, "Not in business portfolio either\n");
