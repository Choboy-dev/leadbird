<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap-automation.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!whatsapp_webhook_verify_challenge()) {
        http_response_code(403);
        exit('Forbidden');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$rawBody = file_get_contents('php://input');
if (!is_string($rawBody) || $rawBody === '') {
    meta_webhook_log('whatsapp POST rejected: empty body');
    http_response_code(400);
    exit('Empty payload');
}

$hasSignature = isset($_SERVER['HTTP_X_HUB_SIGNATURE_256']) && is_string($_SERVER['HTTP_X_HUB_SIGNATURE_256']);
meta_webhook_log('whatsapp POST received len=' . strlen($rawBody) . ' has_sig=' . ($hasSignature ? '1' : '0'));

if (!whatsapp_verify_signature($rawBody)) {
    meta_webhook_log('whatsapp POST rejected: invalid signature len=' . strlen($rawBody));
    http_response_code(401);
    exit('Invalid signature');
}

/** @var array<string, mixed>|null $payload */
$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    meta_webhook_log('whatsapp POST rejected: invalid JSON');
    http_response_code(400);
    exit('Invalid JSON');
}

$entryCount = is_array($payload['entry'] ?? null) ? count($payload['entry']) : 0;
meta_webhook_log('whatsapp POST ok entries=' . $entryCount);

http_response_code(200);
echo 'EVENT_RECEIVED';

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

whatsapp_process_inbound_payload($payload);

try {
    $drained = (new AutomationQueue())->processBatch(10);
    if ($drained > 0) {
        meta_webhook_log('whatsapp queue drained count=' . $drained);
    }
} catch (Throwable $e) {
    error_log('[WhatsApp webhook] Queue drain failed: ' . $e->getMessage());
    meta_webhook_log('whatsapp queue drain failed: ' . $e->getMessage());
}
