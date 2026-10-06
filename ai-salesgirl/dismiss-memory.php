<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$sessionAuth = new SessionAuth();
$userId = $sessionAuth->userId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized.'], JSON_THROW_ON_ERROR);
    exit;
}

$businessCtx = new BusinessContext();
$business = $businessCtx->resolveActiveBusiness($userId);
if ($business === null) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'No active business found.'], JSON_THROW_ON_ERROR);
    exit;
}

$chunkId = trim((string) ($_POST['chunk_id'] ?? ''));
if ($chunkId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Missing memory id.'], JSON_THROW_ON_ERROR);
    exit;
}

$businessId = (string) $business['id'];

try {
    $removed = RepositoryFactory::businessKnowledge()->deactivateChunk($chunkId, $businessId);
    if (!$removed) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Memory not found.'], JSON_THROW_ON_ERROR);
        exit;
    }

    echo json_encode(['ok' => true, 'message' => 'Memory removed.'], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log('[ai-salesgirl/dismiss-memory] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Could not remove memory. Please try again.'], JSON_THROW_ON_ERROR);
}
