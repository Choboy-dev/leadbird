<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$user = Mail::requireUser();
$attachment = Mail::getAttachment((int) $user['id'], (int) ($_GET['id'] ?? 0));
if ($attachment === null) { http_response_code(404); exit('Attachment not found.'); }
$root = realpath(dirname(__DIR__) . '/storage/mail');
$path = realpath(dirname(__DIR__) . '/' . ltrim((string) $attachment['storage_path'], '/'));
if ($root === false || $path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
    http_response_code(404); exit('File missing.');
}
$filename = preg_replace('/[\r\n"]+/', '_', basename((string) $attachment['filename'])) ?: 'attachment';
header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . (string) ($attachment['content_type'] ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header("Content-Disposition: attachment; filename=\"{$filename}\"; filename*=UTF-8''" . rawurlencode($filename));
header('Cache-Control: private, no-store');
readfile($path);
exit;
