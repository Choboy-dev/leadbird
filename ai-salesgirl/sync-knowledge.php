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

$businessCtx = new BusinessContext();
$business = $businessCtx->resolveActiveBusiness($userId);
if ($business === null) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'No active business found.'], JSON_THROW_ON_ERROR);
    exit;
}

$businessId = (string) $business['id'];

$tokenService = new TokenService();
$knowledgeCost = TokenCosts::KNOWLEDGE_AI_REFRESH;
if (!$tokenService->canSpend((string) $userId, $knowledgeCost)) {
    http_response_code(402);
    echo json_encode(['ok' => false, 'message' => TokenCosts::insufficientMessage($knowledgeCost)], JSON_THROW_ON_ERROR);
    exit;
}

try {
    $sync = new BusinessKnowledgeSyncService();
    $result = $sync->syncAll($businessId);
    $stats = (new BusinessKnowledgeStatsService())->getDashboardStats($businessId);

    $tokenService->spend(
        (string) $userId,
        $knowledgeCost,
        'knowledge_ai_refresh',
        'knowledge_sync',
        Uuid::v4(),
        ['business_id' => $businessId]
    );

    echo json_encode([
        'ok' => true,
        'message' => 'Knowledge updated.',
        'sync' => $result,
        'stats' => [
            'total_chunks' => $stats['total_chunks'],
            'social_posts' => $stats['social_posts'],
            'offerings' => $stats['offerings'],
            'faqs' => $stats['faqs'],
            'learned_memory' => $stats['learned_memory'],
            'linked_posts' => $stats['linked_posts'],
            'semantic_search_ready' => $stats['semantic_search_ready'],
            'memory_ready' => $stats['memory_ready'],
            'last_manual_sync_at' => knowledge_format_sync_time($stats['last_manual_sync_at']),
            'last_social_sync_at' => knowledge_format_sync_time($stats['last_social_sync_at']),
            'last_learning_sync_at' => knowledge_format_sync_time($stats['last_learning_sync_at']),
        ],
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log('[ai-salesgirl/sync-knowledge] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Could not refresh knowledge. Please try again.'], JSON_THROW_ON_ERROR);
}
