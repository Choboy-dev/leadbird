<?php

declare(strict_types=1);

final class ConversationRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM conversations WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listByBusinessId(string $businessId, ?string $status = null): array
    {
        $sql = 'SELECT * FROM conversations WHERE business_id = :business_id';
        $params = ['business_id' => $businessId];
        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY last_message DESC';

        return $this->fetchAll($sql, $params);
    }

    public function create(string $businessId, array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO conversations (id, business_id, customer_id, platform, status, last_message)
             VALUES (:id, :business_id, :customer_id, :platform, :status, NOW())',
            [
                'id' => $id,
                'business_id' => $businessId,
                'customer_id' => $data['customer_id'] ?? null,
                'platform' => $data['platform'] ?? null,
                'status' => $data['status'] ?? 'open',
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create conversation');
    }

    public function updateStatus(string $id, string $status): void
    {
        $this->execute(
            'UPDATE conversations SET status = :status, last_message = NOW() WHERE id = :id',
            ['id' => $id, 'status' => $status]
        );
    }

    public function touchLastMessage(string $id): void
    {
        $this->execute('UPDATE conversations SET last_message = NOW() WHERE id = :id', ['id' => $id]);
    }

    public function findOpenForCustomer(string $businessId, string $customerId, string $platform): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM conversations
             WHERE business_id = :business_id AND customer_id = :customer_id AND platform = :platform
               AND status IN (:status_open, :status_human)
             ORDER BY last_message DESC LIMIT 1',
            [
                'business_id' => $businessId,
                'customer_id' => $customerId,
                'platform' => $platform,
                'status_open' => 'open',
                'status_human' => 'human',
            ]
        );
    }

    /** @return list<string> */
    public function listIdsByCustomerId(string $businessId, string $customerId): array
    {
        $rows = $this->fetchAll(
            'SELECT id FROM conversations WHERE business_id = :business_id AND customer_id = :customer_id',
            ['business_id' => $businessId, 'customer_id' => $customerId]
        );

        $ids = [];
        foreach ($rows as $row) {
            $id = trim((string) ($row['id'] ?? ''));
            if ($id !== '') {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    public function deleteById(string $id, string $businessId): void
    {
        $this->execute(
            'DELETE FROM conversations WHERE id = :id AND business_id = :business_id',
            ['id' => $id, 'business_id' => $businessId]
        );
    }

    public function deleteByCustomerId(string $businessId, string $customerId): void
    {
        $this->execute(
            'DELETE FROM conversations WHERE business_id = :business_id AND customer_id = :customer_id',
            ['business_id' => $businessId, 'customer_id' => $customerId]
        );
    }

    /**
     * Open chats where the last message is from the customer and they went quiet.
     *
     * @return list<array<string, mixed>>
     */
    public function listQuietForFollowup(string $businessId, int $delayHours, int $limit = 20): array
    {
        $delayHours = max(1, min(168, $delayHours));
        $limit = max(1, min(50, $limit));

        return $this->fetchAll(
            "SELECT c.*,
                    (
                        SELECT m.created_at
                        FROM messages m
                        WHERE m.conversation_id = c.id AND m.sender_type = 'customer'
                        ORDER BY m.created_at DESC
                        LIMIT 1
                    ) AS last_customer_at
             FROM conversations c
             WHERE c.business_id = :business_id
               AND c.status = 'open'
               AND c.last_message <= DATE_SUB(NOW(), INTERVAL {$delayHours} HOUR)
               AND (
                    SELECT m.sender_type
                    FROM messages m
                    WHERE m.conversation_id = c.id
                    ORDER BY m.created_at DESC
                    LIMIT 1
               ) = 'customer'
             ORDER BY c.last_message ASC
             LIMIT {$limit}",
            ['business_id' => $businessId]
        );
    }
}
