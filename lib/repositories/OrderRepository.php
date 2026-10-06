<?php

declare(strict_types=1);

final class OrderRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM orders WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listByBusinessId(string $businessId, ?string $status = null): array
    {
        $sql = 'SELECT * FROM orders WHERE business_id = :business_id';
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
            'INSERT INTO orders (id, business_id, customer_id, conversation_id, status, total_amount, currency, delivery_location, customer_phone)
             VALUES (:id, :business_id, :customer_id, :conversation_id, :status, :total_amount, :currency, :delivery_location, :customer_phone)',
            [
                'id' => $id,
                'business_id' => $businessId,
                'customer_id' => $data['customer_id'] ?? null,
                'conversation_id' => $data['conversation_id'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'total_amount' => $data['total_amount'] ?? null,
                'currency' => $data['currency'] ?? 'NGN',
                'delivery_location' => $data['delivery_location'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create order');
    }

    public function updateStatus(string $id, string $status): void
    {
        $this->execute('UPDATE orders SET status = :status WHERE id = :id', ['id' => $id, 'status' => $status]);
    }

    public function updateTotal(string $id, string $totalAmount): void
    {
        $this->execute('UPDATE orders SET total_amount = :total_amount WHERE id = :id', [
            'id' => $id,
            'total_amount' => $totalAmount,
        ]);
    }

    public function deleteForCustomer(string $businessId, string $customerId): void
    {
        $this->execute(
            'DELETE FROM orders WHERE business_id = :business_id AND customer_id = :customer_id',
            ['business_id' => $businessId, 'customer_id' => $customerId]
        );
    }
}
