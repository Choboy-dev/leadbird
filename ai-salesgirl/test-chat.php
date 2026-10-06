<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/bootstrap.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';
require_once dirname(__DIR__) . '/includes/openai.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Invalid request.'], 405);
}

require_csrf();

$userId = (new SessionAuth())->userId();
if ($userId === null || $businessId === null || !is_array($business)) {
    json_response(['ok' => false, 'error' => 'Unauthorized.'], 401);
}

$raw = file_get_contents('php://input');
$payload = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    json_response(['ok' => false, 'error' => 'Invalid request.'], 400);
}

$message = trim((string) ($payload['message'] ?? ''));
if ($message === '') {
    json_response(['ok' => false, 'error' => 'Type a message first.'], 422);
}

if (mb_strlen($message) > 800) {
    json_response(['ok' => false, 'error' => 'That message is too long. Please shorten it.'], 422);
}

$tokenService = new TokenService();
if (!$tokenService->canSpend((string) $userId, TokenCosts::AI_TEST_CHAT)) {
    json_response(['ok' => false, 'error' => TokenCosts::depletedMessage()], 402);
}

require_once dirname(__DIR__) . '/lib/BusinessKnowledgeRetrievalService.php';
require_once dirname(__DIR__) . '/lib/ConversationContextService.php';

$history = is_array($payload['history'] ?? null) ? $payload['history'] : [];
$recentMessages = ConversationContextService::historyToMessages($history);

$testSettings = is_array($settings)
    ? $settings
    : (new OnboardingService())->ensureSettings($businessId);

try {
    $offerings = RepositoryFactory::offerings()->listByBusinessId($businessId, true);
    $retrievalQuery = ConversationContextService::buildKnowledgeRetrievalQuery($recentMessages, $message);
    $knowledgeChunks = (new BusinessKnowledgeRetrievalService())->retrieve($businessId, $retrievalQuery);
    $ai = openai_generate_sales_reply(
        $business,
        $testSettings,
        $offerings,
        $recentMessages,
        ['message_text' => $message],
        $knowledgeChunks,
        null,
        null,
        (string) $planSlug
    );

    $reply = trim((string) ($ai['customer_reply'] ?? ''));
    if ($reply === '') {
        json_response(['ok' => false, 'error' => 'Could not generate a reply. Check that your OpenAI API key is configured.'], 422);
    }

    $tokenService->spend(
        (string) $userId,
        TokenCosts::AI_TEST_CHAT,
        'ai_test_chat',
        'test_chat',
        Uuid::v4(),
        ['business_id' => $businessId]
    );

    $aiName = trim((string) ($testSettings['ai_name'] ?? 'Salesgirl'));
    if ($aiName === '') {
        $aiName = 'Salesgirl';
    }

    json_response([
        'ok' => true,
        'message' => $message,
        'reply' => $reply,
        'reply_html' => chat_format_message_html($reply),
        'ai_name' => $aiName,
    ]);
} catch (Throwable $e) {
    error_log('[ai-salesgirl test-chat] ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Something went wrong generating a test reply. Please try again.'], 500);
}
