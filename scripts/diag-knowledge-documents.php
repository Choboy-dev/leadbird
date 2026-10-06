<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap-automation.php';
require_once dirname(__DIR__) . '/lib/KnowledgeAssetService.php';

foreach (RepositoryFactory::businessKnowledge()->listRecentDocuments(5) as $row) {
    $businessId = (string) ($row['business_id'] ?? '');
    $chunkId = (string) ($row['id'] ?? '');
    $meta = knowledge_chunk_metadata($row);
    $stored = (string) ($meta['stored_name'] ?? '');
    $path = dirname(__DIR__) . '/storage/uploads/knowledge/' . $businessId . '/' . $stored;
    $url = KnowledgeAssetService::shareUrl($businessId, $chunkId);
    $ids = knowledge_infer_attachment_ids('Can I see a resume sample?', [], $businessId);

    echo ($row['business_name'] ?? '') . ' | ' . ($row['title'] ?? '') . PHP_EOL;
    echo '  chunk=' . $chunkId . ' file_exists=' . (is_file($path) ? 'yes' : 'no') . PHP_EOL;
    echo '  url=' . $url . PHP_EOL;
    echo '  infer=' . json_encode($ids) . PHP_EOL;
}
