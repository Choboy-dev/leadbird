<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

$userId = (new SessionAuth())->userId();
if ($userId === null) {
    http_response_code(401);
    exit;
}

$business = (new BusinessContext())->resolveActiveBusiness($userId);
if ($business === null) {
    http_response_code(404);
    exit;
}

$businessId = (string) $business['id'];
$file = basename((string) ($_GET['file'] ?? ''));
if ($file === '' || !preg_match('/^faq_[a-z0-9]+\.[a-z0-9]+$/i', $file)) {
    http_response_code(404);
    exit;
}

$path = dirname(__DIR__) . '/storage/uploads/knowledge/' . $businessId . '/faq/' . $file;
if (!is_readable($path)) {
    http_response_code(404);
    exit;
}

$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mime = match ($extension) {
    'jpg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    'gif' => 'image/gif',
    'pdf' => 'application/pdf',
    default => 'application/octet-stream',
};

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, max-age=3600');
readfile($path);
