<?php

declare(strict_types=1);

final class MessageRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM messages WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listByConversationId(string $conversationId, int $limit = 100): array
    {
        return $this->fetchAll(
            'SELECT * FROM messages WHERE conversation_id = :conversation_id ORDER BY created_at ASC LIMIT ' . (int) $limit,
            ['conversation_id' => $conversationId]
        );
    }

    /**
     * Newer messages in a conversation after a created_at cursor (for inbox live sync).
     *
     * @return list<array<string, mixed>>
     */
    public function listByConversationIdAfter(string $conversationId, string $afterCreatedAt, int $limit = 50): array
    {
        $afterCreatedAt = trim($afterCreatedAt);
        if ($afterCreatedAt === '') {
            return [];
        }

        return $this->fetchAll(
            'SELECT * FROM messages
             WHERE conversation_id = :conversation_id AND created_at > :after_created_at
             ORDER BY created_at ASC
             LIMIT ' . max(1, min(100, $limit)),
            [
                'conversation_id' => $conversationId,
                'after_created_at' => $afterCreatedAt,
            ]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRecentForBusinessAfter(string $businessId, string $afterCreatedAt, int $limit = 40): array
    {
        $afterCreatedAt = trim($afterCreatedAt);
        if ($afterCreatedAt === '') {
            return [];
        }

        return $this->fetchAll(
            'SELECT * FROM messages
             WHERE business_id = :business_id AND created_at > :after_created_at
             ORDER BY created_at ASC
             LIMIT ' . max(1, min(100, $limit)),
            [
                'business_id' => $businessId,
                'after_created_at' => $afterCreatedAt,
            ]
        );
    }

    public function countAiRepliesSince(string $businessId, string $since): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM messages
             WHERE business_id = :business_id AND sender_type = :sender_type AND created_at >= :since',
            ['business_id' => $businessId, 'sender_type' => 'ai', 'since' => $since]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function countBySenderType(string $businessId, string $senderType): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM messages WHERE business_id = :business_id AND sender_type = :sender_type',
            ['business_id' => $businessId, 'sender_type' => $senderType]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function countByPlatformAndSender(string $businessId, string $platform, string $senderType): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM messages
             WHERE business_id = :business_id AND platform = :platform AND sender_type = :sender_type',
            ['business_id' => $businessId, 'platform' => $platform, 'sender_type' => $senderType]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public function listRecentByBusinessId(string $businessId, int $limit = 10): array
    {
        return $this->fetchAll(
            'SELECT * FROM messages WHERE business_id = :business_id ORDER BY created_at DESC LIMIT ' . (int) $limit,
            ['business_id' => $businessId]
        );
    }

    public function findLastForCustomer(string $businessId, string $customerId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM messages
             WHERE business_id = :business_id AND customer_id = :customer_id
             ORDER BY created_at DESC LIMIT 1',
            ['business_id' => $businessId, 'customer_id' => $customerId]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listRecentConversationsForLearning(string $businessId, int $limit = 12): array
    {
        return $this->fetchAll(
            'SELECT c.id AS conversation_id, c.last_message AS conversation_updated_at
             FROM conversations c
             INNER JOIN messages m ON m.conversation_id = c.id
             WHERE c.business_id = :business_id
               AND m.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
             GROUP BY c.id, c.last_message
             ORDER BY c.last_message DESC
             LIMIT ' . max(1, min(30, $limit)),
            ['business_id' => $businessId]
        );
    }

    public function create(array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO messages (
                id, business_id, customer_id, conversation_id, platform, sender_type,
                message_text, message_media_url, message_media_type, referral_context, ai_intent
            ) VALUES (
                :id, :business_id, :customer_id, :conversation_id, :platform, :sender_type,
                :message_text, :message_media_url, :message_media_type, :referral_context, :ai_intent
            )',
            [
                'id' => $id,
                'business_id' => $data['business_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'conversation_id' => $data['conversation_id'] ?? null,
                'platform' => $data['platform'],
                'sender_type' => $data['sender_type'],
                'message_text' => $data['message_text'] ?? null,
                'message_media_url' => $data['message_media_url'] ?? null,
                'message_media_type' => $data['message_media_type'] ?? null,
                'referral_context' => $data['referral_context'] ?? null,
                'ai_intent' => $data['ai_intent'] ?? null,
            ]
        );

        if (!empty($data['conversation_id'])) {
            (new ConversationRepository($this->db))->touchLastMessage($data['conversation_id']);
        }

        return $this->findById($id) ?? throw new RuntimeException('Failed to create message');
    }

    public function deleteByCustomerId(string $businessId, string $customerId): void
    {
        $this->execute(
            'DELETE FROM messages WHERE business_id = :business_id AND customer_id = :customer_id',
            ['business_id' => $businessId, 'customer_id' => $customerId]
        );
    }

    /** @param list<string> $conversationIds */
    public function deleteByConversationIds(string $businessId, array $conversationIds): void
    {
        foreach ($conversationIds as $conversationId) {
            $conversationId = trim($conversationId);
            if ($conversationId === '') {
                continue;
            }

            $this->execute(
                'DELETE FROM messages WHERE business_id = :business_id AND conversation_id = :conversation_id',
                ['business_id' => $businessId, 'conversation_id' => $conversationId]
            );
        }
    }
}
