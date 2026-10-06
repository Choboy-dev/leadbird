<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$db = Database::connection();

// Keep Metro Football UK active; deactivate other businesses on the same page.
$metroBusinessId = (string) $db->query(
    "SELECT id FROM businesses WHERE business_name LIKE '%Metro%' LIMIT 1"
)->fetchColumn();

if ($metroBusinessId === '') {
    echo "metro business not found\n";
    exit(1);
}

$repo = RepositoryFactory::connectedAccounts();
$repo->deactivateOtherBusinessAccounts('facebook', '110332738511132', $metroBusinessId);

echo "deactivated duplicates for metro business=$metroBusinessId\n";

foreach ($db->query(
    "SELECT b.business_name, ca.is_active, ca.created_at
     FROM connected_accounts ca
     JOIN businesses b ON b.id = ca.business_id
     WHERE ca.platform = 'facebook' AND ca.platform_account_id = '110332738511132'"
) as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
}
