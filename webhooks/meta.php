<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap-automation.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!meta_webhook_verify_challenge()) {
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
    meta_webhook_log('POST rejected: empty body');
    http_response_code(400);
    exit('Empty payload');
}

$hasSignature = isset($_SERVER['HTTP_X_HUB_SIGNATURE_256']) && is_string($_SERVER['HTTP_X_HUB_SIGNATURE_256']);
meta_webhook_log('POST received len=' . strlen($rawBody) . ' has_sig=' . ($hasSignature ? '1' : '0'));

if (!meta_verify_signature($rawBody)) {
    meta_webhook_log('POST rejected: invalid signature len=' . strlen($rawBody));
    http_response_code(401);
    exit('Invalid signature');
}

/** @var array<string, mixed>|null $payload */
$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    meta_webhook_log('POST rejected: invalid JSON');
    http_response_code(400);
    exit('Invalid JSON');
}

$entryCount = is_array($payload['entry'] ?? null) ? count($payload['entry']) : 0;
meta_webhook_log('POST ok object=' . (string) ($payload['object'] ?? '') . ' entries=' . $entryCount);

http_response_code(200);
echo 'EVENT_RECEIVED';

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

if (($payload['object'] ?? '') === 'whatsapp_business_account') {
    whatsapp_process_inbound_payload($payload);

    try {
        $drained = (new AutomationQueue())->processBatch(10);
        if ($drained > 0) {
            meta_webhook_log('whatsapp queue drained count=' . $drained);
        }
    } catch (Throwable $e) {
        error_log('[Meta webhook] WhatsApp queue drain failed: ' . $e->getMessage());
        meta_webhook_log('whatsapp queue drain failed: ' . $e->getMessage());
    }

    exit;
}

$service = new InboundMessageService();
foreach (meta_parse_inbound_events($payload) as $event) {
    $connectedAccount = RepositoryFactory::connectedAccounts()->findActiveByPlatformAccountId(
        $event['platform'],
        $event['page_id']
    );
    $customerProfile = $connectedAccount !== null
        ? meta_fetch_customer_profile($connectedAccount, $event['platform'], $event['sender_id'])
        : ['name' => null, 'phone' => null];

    $context = $service->ingest(
        $event['platform'],
        $event['page_id'],
        $event['sender_id'],
        $event['message_text'],
        $customerProfile['name'] ?? null,
        $customerProfile['phone'] ?? null,
        $event['message_media_url'] ?? null,
        $event['message_media_type'] ?? null,
        $event['referral_context'] ?? null,
        $customerProfile['picture_url'] ?? null
    );

    if ($context !== null) {
        meta_webhook_log(
            'ingested platform=' . $event['platform']
            . ' page=' . $event['page_id']
            . ' sender=' . $event['sender_id']
            . ' business=' . (string) ($context['business_id'] ?? '')
        );
        (new AutomationQueue())->enqueueProcessMessage($context);
    } else {
        meta_webhook_log(
            'ingest skipped platform=' . $event['platform']
            . ' page=' . $event['page_id']
            . ' sender=' . $event['sender_id']
            . ' has_text=' . ($event['message_text'] !== '' ? '1' : '0')
        );
    }
}

try {
    $drained = (new AutomationQueue())->processBatch(10);
    if ($drained > 0) {
        meta_webhook_log('queue drained count=' . $drained);
    }
} catch (Throwable $e) {
    error_log('[Meta webhook] Queue drain failed: ' . $e->getMessage());
    meta_webhook_log('queue drain failed: ' . $e->getMessage());
}
