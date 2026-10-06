<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$sessionAuth = new SessionAuth();
$userId = $sessionAuth->userId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized.'], JSON_THROW_ON_ERROR);
    exit;
}

$onboarding = new OnboardingService();
$business = $onboarding->getBusinessForUser($userId);
if ($business === null) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Business profile not found.'], JSON_THROW_ON_ERROR);
    exit;
}

$businessId = (string) $business['id'];
$settings = $onboarding->ensureSettings($businessId);

$aiName = trim((string) ($_POST['ai_name'] ?? ''));
$businessName = trim((string) ($_POST['business_name'] ?? ''));
$aiTone = trim((string) ($_POST['ai_tone'] ?? ''));
$aiGreeting = trim((string) ($_POST['ai_greeting'] ?? ''));

$allowedTones = dash_ai_tone_options((string) ($settings['ai_tone'] ?? ''));
if ($aiTone === '' || !in_array($aiTone, $allowedTones, true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Please choose a tone for your Salesgirl.'], JSON_THROW_ON_ERROR);
    exit;
}

if ($aiName === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Please enter a name for your Salesgirl.'], JSON_THROW_ON_ERROR);
    exit;
}

if ($businessName === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Please enter your business name.'], JSON_THROW_ON_ERROR);
    exit;
}

if ($aiGreeting === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Please enter a greeting message.'], JSON_THROW_ON_ERROR);
    exit;
}

try {
    RepositoryFactory::businesses()->update($businessId, [
        'business_name' => $businessName,
        'owner_name' => $business['owner_name'] ?? null,
        'owner_email' => $business['owner_email'] ?? null,
        'owner_phone' => $business['owner_phone'] ?? null,
    ]);

    RepositoryFactory::businessSettings()->update($businessId, [
        'ai_name' => $aiName,
        'ai_tone' => $aiTone,
        'ai_greeting' => $aiGreeting,
    ]);
} catch (Throwable $e) {
    error_log('[ai-salesgirl/save-profile] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Something went wrong. Please try again.'], JSON_THROW_ON_ERROR);
    exit;
}

echo json_encode([
    'ok' => true,
    'profile' => [
        'ai_name' => $aiName,
        'business_name' => $businessName,
        'ai_tone' => $aiTone,
        'ai_greeting' => $aiGreeting,
    ],
], JSON_THROW_ON_ERROR);
