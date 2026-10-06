<?php

declare(strict_types=1);

/**
 * Quick checks for knowledge attachment relevance guards.
 *
 * php scripts/test-attachment-relevance.php
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$cases = [
    ['Can you please share the registration link with me?', false],
    ['Please send me the signup link', false],
    ['Can I see a resume sample?', true],
    ['Send me the CV PDF', true],
    ['Share your brochure please', true],
    ['How much is the gospel music plan?', false],
];

$failed = 0;
foreach ($cases as [$message, $expectFile]) {
    $got = knowledge_message_requests_file($message);
    $ok = $got === $expectFile;
    echo ($ok ? 'OK  ' : 'FAIL') . ' file_request=' . ($got ? '1' : '0')
        . ' expect=' . ($expectFile ? '1' : '0')
        . ' | ' . $message . "\n";
    if (!$ok) {
        $failed++;
    }
}

$pdo = Database::connection();
$row = $pdo->query(
    "SELECT id FROM businesses WHERE id = '5d076afe-27b6-450f-abde-30e204e27ab6' LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
$businessId = is_array($row) ? (string) $row['id'] : '';

if ($businessId !== '') {
    $msg = 'Can you please share the registration link with me?';
    $ids = knowledge_infer_attachment_ids($msg, [], $businessId);
    echo 'infer(registration link) => ' . json_encode($ids) . (count($ids) === 0 ? " OK\n" : " FAIL\n");
    if (count($ids) !== 0) {
        $failed++;
    }

    $ids2 = knowledge_infer_attachment_ids('Can I see a resume sample?', [], $businessId);
    echo 'infer(resume sample) => ' . json_encode($ids2) . (count($ids2) > 0 ? " OK\n" : " WARN (no CV doc?)\n");
}

exit($failed > 0 ? 1 : 0);
