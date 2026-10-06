<?php

declare(strict_types=1);

final class ProductRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM products WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listByBusinessId(string $businessId, bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM products WHERE business_id = :business_id';
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
            'INSERT INTO products (id, business_id, product_name, description, price, currency, stock_quantity, active)
             VALUES (:id, :business_id, :product_name, :description, :price, :currency, :stock_quantity, :active)',
            [
                'id' => $id,
                'business_id' => $businessId,
                'product_name' => $data['product_name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'] ?? null,
                'currency' => $data['currency'] ?? 'NGN',
                'stock_quantity' => $data['stock_quantity'] ?? null,
                'active' => isset($data['active']) ? (int) (bool) $data['active'] : 1,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create product');
    }

    public function update(string $id, array $data): void
    {
        $this->execute(
            'UPDATE products SET product_name = :product_name, description = :description, price = :price,
             currency = :currency, stock_quantity = :stock_quantity, active = :active WHERE id = :id',
            [
                'id' => $id,
                'product_name' => $data['product_name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'] ?? null,
                'currency' => $data['currency'] ?? 'NGN',
                'stock_quantity' => $data['stock_quantity'] ?? null,
                'active' => (int) (bool) ($data['active'] ?? true),
            ]
        );
    }

    public function delete(string $id): void
    {
        $this->execute('DELETE FROM products WHERE id = :id', ['id' => $id]);
    }
}
