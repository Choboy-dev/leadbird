<?php

declare(strict_types=1);

final class OrderItemRepository extends BaseRepository
{
    /** @return list<array<string, mixed>> */
    public function listByOrderId(string $orderId): array
    {
        return $this->fetchAll(
            'SELECT * FROM order_items WHERE order_id = :order_id ORDER BY created_at ASC',
            ['order_id' => $orderId]
        );
    }

    public function create(string $orderId, array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO order_items (id, order_id, product_id, offering_id, quantity, unit_price)
             VALUES (:id, :order_id, :product_id, :offering_id, :quantity, :unit_price)',
            [
                'id' => $id,
                'order_id' => $orderId,
                'product_id' => $data['product_id'] ?? null,
                'offering_id' => $data['offering_id'] ?? null,
                'quantity' => $data['quantity'] ?? 1,
                'unit_price' => $data['unit_price'] ?? null,
            ]
        );

        return $this->fetchOne('SELECT * FROM order_items WHERE id = :id', ['id' => $id])
            ?? throw new RuntimeException('Failed to create order item');
    }
}
