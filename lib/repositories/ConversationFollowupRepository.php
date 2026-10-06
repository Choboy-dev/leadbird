<?php

declare(strict_types=1);

final class ConversationFollowupRepository extends BaseRepository
{
    public function findByConversationId(string $conversationId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM conversation_followups WHERE conversation_id = :conversation_id LIMIT 1',
            ['conversation_id' => $conversationId]
        );
    }

    public function upsertActive(string $businessId, string $conversationId, ?string $customerId, ?string $platform): array
    {
        $existing = $this->findByConversationId($conversationId);
        if ($existing !== null) {
            $this->execute(
                'UPDATE conversation_followups SET
                    customer_id = :customer_id,
                    platform = :platform,
                    status = :status,
                    updated_at = NOW()
                 WHERE id = :id',
                [
                    'id' => $existing['id'],
                    'customer_id' => $customerId,
                    'platform' => $platform,
                    'status' => 'active',
                ]
            );

            return $this->findByConversationId($conversationId) ?? $existing;
        }

        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO conversation_followups (
                id, business_id, conversation_id, customer_id, platform, attempt_count, status
            ) VALUES (
                :id, :business_id, :conversation_id, :customer_id, :platform, 0, :status
            )',
            [
                'id' => $id,
                'business_id' => $businessId,
                'conversation_id' => $conversationId,
                'customer_id' => $customerId,
                'platform' => $platform,
                'status' => 'active',
            ]
        );

        return $this->fetchOne('SELECT * FROM conversation_followups WHERE id = :id', ['id' => $id])
            ?? throw new RuntimeException('Failed to create conversation followup');
    }

    public function recordAttempt(string $id, int $attemptCount): void
    {
        $this->execute(
            'UPDATE conversation_followups SET
                attempt_count = :attempt_count,
                last_followup_at = NOW(),
                updated_at = NOW()
             WHERE id = :id',
            ['id' => $id, 'attempt_count' => $attemptCount]
        );
    }

    public function markStatus(string $conversationId, string $status): void
    {
        $this->execute(
            'UPDATE conversation_followups SET status = :status, updated_at = NOW()
             WHERE conversation_id = :conversation_id',
            ['conversation_id' => $conversationId, 'status' => $status]
        );
    }

    public function resetOnCustomerReply(string $conversationId): void
    {
        $this->execute(
            'UPDATE conversation_followups SET
                attempt_count = 0,
                last_followup_at = NULL,
                status = :status,
                updated_at = NOW()
             WHERE conversation_id = :conversation_id',
            ['conversation_id' => $conversationId, 'status' => 'active']
        );
    }
}
