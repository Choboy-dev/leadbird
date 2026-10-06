<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok'=>false,'error'=>'method_not_allowed'], 405);
$raw = file_get_contents('php://input') ?: '';
$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!(new ResendClient())->verifyWebhook($raw, is_array($headers) ? $headers : [])) {
    json_response(['ok'=>false,'error'=>'invalid_signature'], 401);
}
$event = json_decode($raw, true);
if (!is_array($event)) json_response(['ok'=>false,'error'=>'invalid_json'], 400);
try {
    $result = Mail::processInboundWebhook($event);
    json_response($result, ($result['ok'] ?? false) ? 200 : 422);
} catch (Throwable $e) {
    error_log('Resend mail webhook: ' . $e->getMessage());
    json_response(['ok'=>false,'error'=>'processing_failed'], 500);
}
