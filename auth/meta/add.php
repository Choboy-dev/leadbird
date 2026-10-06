<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$userId = (new SessionAuth())->userId();
if ($userId === null) {
    json_response(['ok' => false, 'error' => 'Please sign in and try again.'], 401);
}

if (!(new SubscriptionService())->hasActiveSubscription((string) $userId)) {
    json_response(['ok' => false, 'error' => 'An active subscription is required.'], 403);
}

require_csrf();

$platform = strtolower(trim((string) ($_POST['platform'] ?? '')));
$businessId = trim((string) ($_POST['business_id'] ?? ''));
$optionKey = trim((string) ($_POST['option_key'] ?? ''));

if ($businessId === '' || $optionKey === '') {
    json_response(['ok' => false, 'error' => 'Missing account details.'], 422);
}

if (!in_array($platform, ['whatsapp', 'instagram', 'facebook'], true)) {
    json_response(['ok' => false, 'error' => 'Unsupported channel.'], 422);
}

if (!(new BusinessContext())->userOwnsBusiness((string) $userId, $businessId)) {
    json_response(['ok' => false, 'error' => 'Business not found.'], 404);
}

$metaConnect = new MetaConnectService();

try {
    $result = $metaConnect->attachAccount((string) $userId, $businessId, $platform, $optionKey);
    json_response([
        'ok' => true,
        'label' => (string) ($result['label'] ?? ''),
        'platform_account_id' => (string) ($result['platform_account_id'] ?? ''),
        'is_connected' => (bool) ($result['is_connected'] ?? true),
        'can_add_more' => (bool) ($result['can_add_more'] ?? false),
    ]);
} catch (Throwable $e) {
    $message = $e->getMessage() !== '' ? $e->getMessage() : 'Could not add this account. Please try again.';
    json_response(['ok' => false, 'error' => $message], 400);
}
