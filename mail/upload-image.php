<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$user = Mail::requireUser();
require_csrf();

$file = $_FILES['image'] ?? null;
if (!is_array($file)
    || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
    || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))
) {
    json_response(['ok' => false, 'error' => 'Choose an image to insert.'], 422);
}

try {
    $url = Mail::storeInlineImage((int) $user['id'], (string) $file['tmp_name'], (string) ($file['type'] ?? ''));
} catch (Throwable $exception) {
    json_response(['ok' => false, 'error' => $exception->getMessage()], 422);
}

json_response(['ok' => true, 'url' => $url]);
