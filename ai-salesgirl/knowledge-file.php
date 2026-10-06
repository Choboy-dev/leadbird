<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';

$userId = (new SessionAuth())->userId();
if ($userId === null) {
    http_response_code(401);
    exit('Unauthorized.');
}

$business = (new BusinessContext())->resolveActiveBusiness($userId);
if ($business === null) {
    http_response_code(404);
    exit('Not found.');
}

$chunkId = trim((string) ($_GET['chunk_id'] ?? ''));
if ($chunkId === '') {
    http_response_code(404);
    exit('Not found.');
}

$file = (new KnowledgeTrainingService())->resolveDocumentFile((string) $business['id'], $chunkId);
if ($file === null) {
    http_response_code(404);
    exit('Not found.');
}

header('Content-Type: ' . $file['mime']);
header('Content-Disposition: inline; filename="' . str_replace('"', '', $file['file_name']) . '"');
header('Cache-Control: private, max-age=3600');
readfile($file['path']);
