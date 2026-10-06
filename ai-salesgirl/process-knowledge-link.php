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
$chunkId = trim((string) ($_POST['chunk_id'] ?? ''));

if ($chunkId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid request.'], JSON_THROW_ON_ERROR);
    exit;
}

$service = new KnowledgeTrainingService();

try {
    $link = $service->processLinkTraining($businessId, $chunkId);
    $links = $service->listLinks($businessId);
    $subpageCount = array_sum(array_map(static fn (array $row): int => (int) ($row['subpage_count'] ?? 0), $links));

    echo json_encode([
        'ok' => true,
        'message' => 'Link added to your knowledge base.',
        'link' => $link,
        'links' => $links,
        'count' => count($links),
        'subpage_count' => $subpageCount,
        'count_label' => KnowledgeTrainingService::formatLinkCountLabel(count($links), $subpageCount),
    ], JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log('[ai-salesgirl/process-knowledge-link] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR);
}
