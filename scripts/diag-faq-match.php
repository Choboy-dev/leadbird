<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap-automation.php';

$needle = $argv[1] ?? 'How do I find the church?';
$seen = [];

foreach (RepositoryFactory::businessKnowledge()->listRecentDocuments(25) as $row) {
    $seen[(string) ($row['business_id'] ?? '')] = (string) ($row['business_name'] ?? '');
}

if ($seen === []) {
    fwrite(STDERR, "No businesses with knowledge documents found\n");
    exit(1);
}

foreach ($seen as $id => $name) {
    if ($id === '') {
        continue;
    }

    echo "Business: {$name} ({$id})\n";

    (new BusinessKnowledgeSyncService())->syncManualSources($id);

    $faqs = RepositoryFactory::businessKnowledge()->listBySourceType($id, 'faq', 20);
    echo 'FAQ chunks: ' . count($faqs) . "\n";
    foreach ($faqs as $faq) {
        echo '  Q: ' . ($faq['title'] ?? '') . "\n";
    }

    $match = knowledge_find_best_faq_match($id, $needle);
    if ($match !== null) {
        echo 'Match score: ' . ($match['score'] ?? 0) . "\n";
        echo 'Answer: ' . ($match['answer'] ?? '') . "\n";
    } else {
        echo "No FAQ match found for: {$needle}\n";
    }

    echo "\n";
}
