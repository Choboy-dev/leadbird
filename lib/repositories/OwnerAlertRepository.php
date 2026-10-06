<?php

declare(strict_types=1);

final class OwnerAlertRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM owner_alerts WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listByBusinessId(string $businessId, ?string $status = null): array
    {
        $sql = 'SELECT * FROM owner_alerts WHERE business_id = :business_id';
        $params = ['business_id' => $businessId];
        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY created_at DESC';

        return $this->fetchAll($sql, $params);
    }

    public function create(string $businessId, array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO owner_alerts (id, business_id, customer_id, conversation_id, platform, alert_reason, alert_message, status)
             VALUES (:id, :business_id, :customer_id, :conversation_id, :platform, :alert_reason, :alert_message, :status)',
            [
                'id' => $id,
                'business_id' => $businessId,
                'customer_id' => $data['customer_id'] ?? null,
                'conversation_id' => $data['conversation_id'] ?? null,
                'platform' => $data['platform'] ?? null,
                'alert_reason' => $data['alert_reason'] ?? null,
                'alert_message' => $data['alert_message'] ?? null,
                'status' => $data['status'] ?? 'unread',
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create owner alert');
    }

    public function updateStatus(string $id, string $status): void
    {
        $this->execute('UPDATE owner_alerts SET status = :status WHERE id = :id', ['id' => $id, 'status' => $status]);
    }

    /** @param list<string> $conversationIds */
    public function deleteForCustomerAndConversations(string $businessId, ?string $customerId, array $conversationIds): void
    {
        if ($customerId !== null && $customerId !== '') {
            $this->execute(
                'DELETE FROM owner_alerts WHERE business_id = :business_id AND customer_id = :customer_id',
                ['business_id' => $businessId, 'customer_id' => $customerId]
            );
        }

        foreach ($conversationIds as $conversationId) {
            $conversationId = trim($conversationId);
            if ($conversationId === '') {
                continue;
            }

            $this->execute(
                'DELETE FROM owner_alerts WHERE business_id = :business_id AND conversation_id = :conversation_id',
                ['business_id' => $businessId, 'conversation_id' => $conversationId]
            );
        }
    }
}
