<?php

declare(strict_types=1);

final class HandoffRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM handoffs WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listByBusinessId(string $businessId): array
    {
        return $this->fetchAll(
            'SELECT * FROM handoffs WHERE business_id = :business_id ORDER BY created_at DESC',
            ['business_id' => $businessId]
        );
    }

    public function create(string $businessId, array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO handoffs (id, business_id, customer_id, from_platform, to_platform, handoff_reason, handoff_link)
             VALUES (:id, :business_id, :customer_id, :from_platform, :to_platform, :handoff_reason, :handoff_link)',
            [
                'id' => $id,
                'business_id' => $businessId,
                'customer_id' => $data['customer_id'] ?? null,
                'from_platform' => $data['from_platform'] ?? null,
                'to_platform' => $data['to_platform'] ?? 'whatsapp',
                'handoff_reason' => $data['handoff_reason'] ?? null,
                'handoff_link' => $data['handoff_link'] ?? null,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create handoff');
    }

    public function deleteForCustomer(string $businessId, string $customerId): void
    {
        $this->execute(
            'DELETE FROM handoffs WHERE business_id = :business_id AND customer_id = :customer_id',
            ['business_id' => $businessId, 'customer_id' => $customerId]
        );
    }
}
