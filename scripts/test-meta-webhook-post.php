<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$secret = (string) (app_config()['meta']['app_secret'] ?? '');
$body = '{"object":"page","entry":[{"id":"110332738511132","time":1718560000,"messaging":[{"sender":{"id":"999888777"},"recipient":{"id":"110332738511132"},"timestamp":1718560000,"message":{"mid":"diag1","text":"pipeline_test"}}]}]}';
$sig = 'sha256=' . hash_hmac('sha256', $body, $secret);

$ch = curl_init('https://leadbird.app/webhooks/meta.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Hub-Signature-256: ' . $sig,
    ],
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "http=$code body=" . (is_string($response) ? trim($response) : '') . "\n";

sleep(2);

$db = Database::connection();
$cnt = (int) $db->query("SELECT COUNT(*) FROM messages WHERE message_text = 'pipeline_test'")->fetchColumn();
echo "pipeline_test_messages=$cnt\n";

$q = (int) $db->query("SELECT COUNT(*) FROM automation_queue WHERE payload LIKE '%pipeline_test%'")->fetchColumn();
echo "pipeline_test_queue=$q\n";
