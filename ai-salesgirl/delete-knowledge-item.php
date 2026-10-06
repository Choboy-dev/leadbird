<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (new SessionAuth())->userId();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized.'], JSON_THROW_ON_ERROR);
    exit;
}

$business = (new BusinessContext())->resolveActiveBusiness($userId);
if ($business === null) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'No active business found.'], JSON_THROW_ON_ERROR);
    exit;
}

$businessId = (string) $business['id'];
$type = trim((string) ($_POST['type'] ?? ''));
$chunkId = trim((string) ($_POST['chunk_id'] ?? ''));
$service = new KnowledgeTrainingService();

if ($chunkId === '' || !in_array($type, ['link', 'document', 'knowledge'], true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid request.'], JSON_THROW_ON_ERROR);
    exit;
}

try {
    if ($type === 'link') {
        $service->deleteLink($businessId, $chunkId);
    } elseif ($type === 'document') {
        $service->deleteDocument($businessId, $chunkId);
    } else {
        $service->deleteKnowledgeEntry($businessId, $chunkId);
    }

    $payload = ['ok' => true, 'message' => 'Removed from knowledge base.'];
    if ($type === 'knowledge') {
        $entries = $service->listKnowledgeEntries($businessId);
        $payload['entries'] = $entries;
        $payload['count'] = count($entries);
        $payload['count_label'] = KnowledgeTrainingService::formatEntryCountLabel(count($entries));
    } elseif ($type === 'document') {
        $documents = $service->listDocuments($businessId);
        $payload['documents'] = $documents;
        $payload['count'] = count($documents);
        $payload['count_label'] = KnowledgeTrainingService::formatFileCountLabel(count($documents));
    } elseif ($type === 'link') {
        $links = $service->listLinks($businessId);
        $subpageCount = array_sum(array_map(static fn (array $row): int => (int) ($row['subpage_count'] ?? 0), $links));
        $payload['links'] = $links;
        $payload['count'] = count($links);
        $payload['subpage_count'] = $subpageCount;
        $payload['count_label'] = KnowledgeTrainingService::formatLinkCountLabel(count($links), $subpageCount);
    }

    echo json_encode($payload, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log('[ai-salesgirl/delete-knowledge-item] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Could not remove that item.'], JSON_THROW_ON_ERROR);
}
