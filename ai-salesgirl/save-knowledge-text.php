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

try {
    $service = new KnowledgeTrainingService();
    $chunkId = trim((string) ($_POST['knowledge_id'] ?? ''));
    $entry = $service->saveTextKnowledge(
        $businessId,
        trim((string) ($_POST['knowledge_content'] ?? '')),
        $chunkId !== '' ? $chunkId : null
    );
    $entries = $service->listKnowledgeEntries($businessId);

    echo json_encode([
        'ok' => true,
        'message' => 'Knowledge saved.',
        'entry' => $entry,
        'entries' => $entries,
        'count' => count($entries),
        'count_label' => KnowledgeTrainingService::formatEntryCountLabel(count($entries)),
    ], JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log('[ai-salesgirl/save-knowledge-text] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Could not save knowledge. Please try again.'], JSON_THROW_ON_ERROR);
}
