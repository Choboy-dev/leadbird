<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';
require_once dirname(__DIR__) . '/includes/meta.php';

$userId = (new SessionAuth())->userId();
$business = $userId !== null ? (new BusinessContext())->getActiveBusiness($userId) : null;
$customerId = trim((string) ($_GET['customer_id'] ?? ''));
$customer = $customerId !== '' ? RepositoryFactory::customers()->findById($customerId) : null;

if ($business === null || $customer === null || (string) ($customer['business_id'] ?? '') !== (string) ($business['id'] ?? '')) {
    http_response_code(404);
    exit;
}

$pictureUrl = dash_customer_picture_url($customer);
if ($pictureUrl === '') {
    http_response_code(404);
    exit;
}

$ch = curl_init($pictureUrl);
if ($ch === false) {
    http_response_code(502);
    exit;
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_USERAGENT => 'LeadBird/1.0',
]);

$body = curl_exec($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = trim((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
curl_close($ch);

if (!is_string($body) || $body === '' || $status < 200 || $status >= 300) {
    http_response_code(404);
    exit;
}

if ($contentType === '' || !str_starts_with(strtolower($contentType), 'image/')) {
    $contentType = 'image/jpeg';
}

header('Content-Type: ' . $contentType);
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
echo $body;
