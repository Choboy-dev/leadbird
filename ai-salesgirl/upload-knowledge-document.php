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
$file = $_FILES['document'] ?? null;
if (!is_array($file)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Choose a file to upload.'], JSON_THROW_ON_ERROR);
    exit;
}

try {
    $service = new KnowledgeTrainingService();
    $document = $service->importDocument(
        $businessId,
        $file,
        trim((string) ($_POST['usage_instructions'] ?? ''))
    );
    $documents = $service->listDocuments($businessId);

    echo json_encode([
        'ok' => true,
        'message' => 'File saved.',
        'document' => $document,
        'documents' => $documents,
        'count' => count($documents),
        'count_label' => KnowledgeTrainingService::formatFileCountLabel(count($documents)),
    ], JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log('[ai-salesgirl/upload-knowledge-document] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Could not save that file. Please try again.'], JSON_THROW_ON_ERROR);
}
