<?php

declare(strict_types=1);

final class CustomerRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM customers WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listByBusinessId(string $businessId): array
    {
        return $this->fetchAll(
            'SELECT * FROM customers WHERE business_id = :business_id ORDER BY created_at DESC',
            ['business_id' => $businessId]
        );
    }

    public function findByPlatformUser(string $businessId, string $platform, string $platformUserId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM customers WHERE business_id = :business_id AND platform = :platform AND platform_user_id = :platform_user_id LIMIT 1',
            ['business_id' => $businessId, 'platform' => $platform, 'platform_user_id' => $platformUserId]
        );
    }

    public function create(string $businessId, array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO customers (id, business_id, customer_name, phone, email, platform, platform_user_id, profile_picture_url)
             VALUES (:id, :business_id, :customer_name, :phone, :email, :platform, :platform_user_id, :profile_picture_url)',
            [
                'id' => $id,
                'business_id' => $businessId,
                'customer_name' => $data['customer_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'platform' => $data['platform'] ?? null,
                'platform_user_id' => $data['platform_user_id'] ?? null,
                'profile_picture_url' => $data['profile_picture_url'] ?? null,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create customer');
    }

    public function updateProfilePicture(string $id, ?string $url): void
    {
        $this->execute(
            'UPDATE customers SET profile_picture_url = :profile_picture_url WHERE id = :id',
            ['id' => $id, 'profile_picture_url' => $url !== null && trim($url) !== '' ? trim($url) : null]
        );
    }

    public function update(string $id, array $data): void
    {
        $this->execute(
            'UPDATE customers SET customer_name = :customer_name, phone = :phone, email = :email,
             platform = :platform, platform_user_id = :platform_user_id,
             profile_picture_url = :profile_picture_url WHERE id = :id',
            [
                'id' => $id,
                'customer_name' => $data['customer_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'platform' => $data['platform'] ?? null,
                'platform_user_id' => $data['platform_user_id'] ?? null,
                'profile_picture_url' => $data['profile_picture_url'] ?? null,
            ]
        );
    }

    public function deleteById(string $id, string $businessId): void
    {
        $this->execute(
            'DELETE FROM customers WHERE id = :id AND business_id = :business_id',
            ['id' => $id, 'business_id' => $businessId]
        );
    }
}
