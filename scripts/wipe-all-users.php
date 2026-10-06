<?php

declare(strict_types=1);

/**
 * Remove every signed-up user and all related app data.
 *
 * Usage:
 *   php scripts/wipe-all-users.php           # preview only
 *   php scripts/wipe-all-users.php --execute
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$execute = in_array('--execute', $argv ?? [], true);
$pdo = Database::connection();

function wipe_count(PDO $pdo, string $sql): int
{
    return (int) $pdo->query($sql)->fetchColumn();
}

$tables = [
    'users' => 'SELECT COUNT(*) FROM users',
    'businesses' => 'SELECT COUNT(*) FROM businesses',
    'user_subscriptions' => 'SELECT COUNT(*) FROM user_subscriptions',
    'user_payment_methods' => 'SELECT COUNT(*) FROM user_payment_methods',
    'user_billing_profiles' => 'SELECT COUNT(*) FROM user_billing_profiles',
    'user_token_balances' => 'SELECT COUNT(*) FROM user_token_balances',
    'connected_accounts' => 'SELECT COUNT(*) FROM connected_accounts',
    'messages' => 'SELECT COUNT(*) FROM messages',
    'customers' => 'SELECT COUNT(*) FROM customers',
    'automation_queue' => 'SELECT COUNT(*) FROM automation_queue',
    'email_otps' => 'SELECT COUNT(*) FROM email_otps',
    'oauth_states' => 'SELECT COUNT(*) FROM oauth_states',
];

echo "=== Lead Bird user wipe " . ($execute ? '(EXECUTING)' : '(preview)') . " ===\n\n";

$users = $pdo->query('SELECT id, email, name, created_at FROM users ORDER BY created_at ASC')->fetchAll(PDO::FETCH_ASSOC);
echo 'Users (' . count($users) . "):\n";
foreach ($users as $user) {
    echo '  - ' . ($user['email'] ?? '?') . ' (' . ($user['id'] ?? '') . ")\n";
}
echo "\n";

echo "Row counts:\n";
foreach ($tables as $label => $sql) {
    echo sprintf("  %-24s %d\n", $label . ':', wipe_count($pdo, $sql));
}
echo "\n";

if (!$execute) {
    echo "No changes made. Re-run with --execute to delete everything above.\n";
    exit(0);
}

$flutterwave = new FlutterwaveService();
$cancelled = 0;
$cancelErrors = 0;

foreach ($pdo->query(
    "SELECT id, user_id, flutterwave_subscription_id, status FROM user_subscriptions
     WHERE flutterwave_subscription_id IS NOT NULL AND flutterwave_subscription_id != ''"
)->fetchAll(PDO::FETCH_ASSOC) as $sub) {
    $subId = trim((string) ($sub['flutterwave_subscription_id'] ?? ''));
    if ($subId === '') {
        continue;
    }
    try {
        $flutterwave->cancelSubscription($subId);
        ++$cancelled;
        echo "Cancelled Flutterwave subscription {$subId}\n";
    } catch (Throwable $e) {
        ++$cancelErrors;
        echo "Flutterwave cancel failed for {$subId}: " . $e->getMessage() . "\n";
    }
}

$pdo->exec('DELETE FROM automation_queue');
$pdo->exec('DELETE FROM email_otps');
$pdo->exec('DELETE FROM oauth_states');
$deletedUsers = $pdo->exec('DELETE FROM users');

echo "\nDeleted {$deletedUsers} user(s).\n";
echo "Cleared automation_queue, email_otps, oauth_states.\n";
echo "Flutterwave cancellations: {$cancelled} ok, {$cancelErrors} failed.\n\n";

echo "Remaining counts:\n";
foreach ($tables as $label => $sql) {
    echo sprintf("  %-24s %d\n", $label . ':', wipe_count($pdo, $sql));
}

echo "\nDone. Database is ready for fresh signups.\n";
