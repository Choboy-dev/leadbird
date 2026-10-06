<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap-automation.php';
require_once __DIR__ . '/lib/KnowledgeTrainingService.php';
require_once __DIR__ . '/lib/KnowledgeAssetService.php';

$chunkId = trim((string) ($_GET['id'] ?? ''));
$sig = trim((string) ($_GET['sig'] ?? ''));

if ($chunkId === '' || $sig === '') {
    http_response_code(404);
    exit('Not found.');
}

$file = KnowledgeAssetService::resolveSharedFile($chunkId, $sig);
if ($file === null) {
    http_response_code(404);
    exit('Not found.');
}

header('Content-Type: ' . $file['mime']);
header('Content-Disposition: inline; filename="' . str_replace('"', '', $file['file_name']) . '"');
header('Content-Length: ' . (string) filesize($file['path']));
header('Cache-Control: public, max-age=86400');
readfile($file['path']);
