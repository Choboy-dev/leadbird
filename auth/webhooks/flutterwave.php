<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$rawBody = file_get_contents('php://input');
if (!is_string($rawBody) || $rawBody === '') {
    http_response_code(400);
    exit('Empty payload');
}

$flutterwave = new FlutterwaveService();
$hash = $_SERVER['HTTP_VERIF_HASH'] ?? null;
if (!$flutterwave->verifyWebhookHash(is_string($hash) ? $hash : null)) {
    http_response_code(401);
    exit('Invalid signature');
}

/** @var array<string, mixed>|null $payload */
$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    http_response_code(400);
    exit('Invalid JSON');
}

try {
    if ((string) ($payload['event'] ?? '') === 'transfer.completed') {
        (new PayoutService())->handleTransferWebhook($payload);
        http_response_code(200);
        echo 'OK';
        exit;
    }

    $handled = (new SubscriptionService())->handleWebhook($payload);
    if (!$handled) {
        // Acknowledge unknown charge events so Flutterwave stops retrying.
        $event = (string) ($payload['event'] ?? '');
        if ($event === 'charge.completed') {
            http_response_code(200);
            echo 'IGNORED';
            exit;
        }
        http_response_code(422);
        exit('Unhandled webhook');
    }

    http_response_code(200);
    echo 'OK';
} catch (Throwable $e) {
    error_log('[Flutterwave webhook] ' . $e->getMessage());
    http_response_code(500);
    exit('Error');
}
