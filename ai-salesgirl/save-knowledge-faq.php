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
$action = trim((string) ($_POST['action'] ?? 'save'));
$service = new KnowledgeTrainingService();

try {
    if ($action === 'delete') {
        $entryId = trim((string) ($_POST['entry_id'] ?? ''));
        if ($entryId === '') {
            throw new InvalidArgumentException('Invalid request.');
        }
        $faqs = $service->deleteFaqEntry($businessId, $entryId);
        echo json_encode([
            'ok' => true,
            'message' => 'Question removed.',
            'faqs' => $faqs,
            'count' => count($faqs),
            'count_label' => KnowledgeTrainingService::formatFaqCountLabel(count($faqs)),
        ], JSON_THROW_ON_ERROR);
        exit;
    }

    $question = trim((string) ($_POST['faq_question'] ?? ''));
    $answerType = trim((string) ($_POST['answer_type'] ?? 'text'));
    $entryId = trim((string) ($_POST['entry_id'] ?? ''));
    $payloadRaw = (string) ($_POST['payload_json'] ?? '{}');
    $payload = json_decode($payloadRaw, true);
    if (!is_array($payload)) {
        throw new InvalidArgumentException('Invalid answer data.');
    }

    $saved = $service->saveFaqEntry(
        $businessId,
        $question,
        $answerType,
        $payload,
        $entryId !== '' ? $entryId : null
    );
    $faqs = $service->listFaqEntries($businessId);

    echo json_encode([
        'ok' => true,
        'message' => 'Question saved.',
        'faq' => $saved,
        'faqs' => $faqs,
        'count' => count($faqs),
        'count_label' => KnowledgeTrainingService::formatFaqCountLabel(count($faqs)),
    ], JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log('[ai-salesgirl/save-knowledge-faq] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Could not save that question. Please try again.'], JSON_THROW_ON_ERROR);
}
