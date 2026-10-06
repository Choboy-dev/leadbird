<?php

declare(strict_types=1);

final class BusinessMetaConnectionRepository extends BaseRepository
{
    public function findByBusinessId(string $businessId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM business_meta_connections WHERE business_id = :business_id LIMIT 1',
            ['business_id' => $businessId]
        );
    }

    public function upsert(string $businessId, array $data): array
    {
        $existing = $this->findByBusinessId($businessId);
        if ($existing !== null) {
            $this->execute(
                'UPDATE business_meta_connections SET
                    meta_user_id = COALESCE(:meta_user_id, meta_user_id),
                    access_token = :access_token,
                    updated_at = NOW()
                 WHERE business_id = :business_id',
                [
                    'business_id' => $businessId,
                    'meta_user_id' => $data['meta_user_id'] ?? null,
                    'access_token' => $data['access_token'],
                ]
            );

            return $this->findByBusinessId($businessId) ?? throw new RuntimeException('Failed to update Meta connection');
        }

        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO business_meta_connections (id, business_id, meta_user_id, access_token)
             VALUES (:id, :business_id, :meta_user_id, :access_token)',
            [
                'id' => $id,
                'business_id' => $businessId,
                'meta_user_id' => $data['meta_user_id'] ?? null,
                'access_token' => $data['access_token'],
            ]
        );

        return $this->findByBusinessId($businessId) ?? throw new RuntimeException('Failed to create Meta connection');
    }

    public function deleteByBusinessId(string $businessId): void
    {
        $this->execute(
            'DELETE FROM business_meta_connections WHERE business_id = :business_id',
            ['business_id' => $businessId]
        );
    }
}
