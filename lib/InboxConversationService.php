<?php

declare(strict_types=1);

require_once __DIR__ . '/CustomerConversationPurgeService.php';

final class InboxConversationService
{
    /**
     * @return array{ok: bool, message?: string, conversation_ids?: list<string>, customer_id?: ?string}
     */
    public function delete(string $businessId, string $conversationId): array
    {
        $conversationId = trim($conversationId);
        if ($conversationId === '') {
            return ['ok' => false, 'message' => 'Conversation id required.'];
        }

        try {
            $result = (new CustomerConversationPurgeService())->purgeFromConversation($businessId, $conversationId);
        } catch (RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            error_log('[InboxConversationService] delete failed for ' . $conversationId . ': ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Could not delete chat. Please try again.'];
        }

        return [
            'ok' => true,
            'conversation_ids' => $result['conversation_ids'],
            'customer_id' => $result['customer_id'],
        ];
    }
}
