<?php

declare(strict_types=1);

final class OfferingRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM offerings WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listByBusinessId(string $businessId, bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM offerings WHERE business_id = :business_id';
        if ($activeOnly) {
            $sql .= ' AND active = 1';
        }
        $sql .= ' ORDER BY created_at DESC';

        return $this->fetchAll($sql, ['business_id' => $businessId]);
    }

    public function create(string $businessId, array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO offerings (
                id, business_id, offering_type, name, description, image_url, price, is_free, action_url, type_config,
                currency, billing_type, stock_quantity, capacity, active
            ) VALUES (
                :id, :business_id, :offering_type, :name, :description, :image_url, :price, :is_free, :action_url, :type_config,
                :currency, :billing_type, :stock_quantity, :capacity, :active
            )',
            [
                'id' => $id,
                'business_id' => $businessId,
                'offering_type' => $data['offering_type'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'image_url' => $data['image_url'] ?? null,
                'price' => $data['price'] ?? null,
                'is_free' => isset($data['is_free']) ? (int) (bool) $data['is_free'] : 0,
                'action_url' => $data['action_url'] ?? null,
                'type_config' => $data['type_config'] ?? null,
                'currency' => $data['currency'] ?? 'NGN',
                'billing_type' => $data['billing_type'] ?? 'one_time',
                'stock_quantity' => $data['stock_quantity'] ?? null,
                'capacity' => $data['capacity'] ?? null,
                'active' => isset($data['active']) ? (int) (bool) $data['active'] : 1,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create offering');
    }

    public function update(string $id, array $data): void
    {
        $this->execute(
            'UPDATE offerings SET offering_type = :offering_type, name = :name, description = :description,
             image_url = :image_url, price = :price, is_free = :is_free, action_url = :action_url, type_config = :type_config,
             currency = :currency, billing_type = :billing_type,
             stock_quantity = :stock_quantity, capacity = :capacity, active = :active WHERE id = :id',
            [
                'id' => $id,
                'offering_type' => $data['offering_type'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'image_url' => $data['image_url'] ?? null,
                'price' => $data['price'] ?? null,
                'is_free' => isset($data['is_free']) ? (int) (bool) $data['is_free'] : 0,
                'action_url' => $data['action_url'] ?? null,
                'type_config' => $data['type_config'] ?? null,
                'currency' => $data['currency'] ?? 'NGN',
                'billing_type' => $data['billing_type'] ?? 'one_time',
                'stock_quantity' => $data['stock_quantity'] ?? null,
                'capacity' => $data['capacity'] ?? null,
                'active' => (int) (bool) ($data['active'] ?? true),
            ]
        );
    }

    public function delete(string $id): void
    {
        $this->execute('DELETE FROM offerings WHERE id = :id', ['id' => $id]);
    }
}
