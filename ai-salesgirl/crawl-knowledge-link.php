<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (new SessionAuth())->userId();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized.'], JSON_THROW_ON_ERROR);
    exit;
}

$business = (new BusinessContext())->resolveActiveBusiness($userId);
if ($business === null) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'No active business found.'], JSON_THROW_ON_ERROR);
    exit;
}

$businessId = (string) $business['id'];
$url = trim((string) ($_POST['url'] ?? ''));

try {
    $result = (new KnowledgeTrainingService())->discoverLinkPages($url);
    echo json_encode([
        'ok' => true,
        'message' => 'Pages discovered.',
        'url' => $result['url'],
        'pages' => $result['pages'],
        'max_selectable' => WebsiteCrawlService::MAX_SELECTABLE,
    ], JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log('[ai-salesgirl/crawl-knowledge-link] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR);
}
