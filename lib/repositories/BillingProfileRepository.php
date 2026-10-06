<?php

declare(strict_types=1);

final class BillingProfileRepository extends BaseRepository
{
    public function findByUserId(string $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM user_billing_profiles WHERE user_id = :user_id LIMIT 1',
            ['user_id' => $userId]
        );
    }

    /**
     * @param array{
     *     address_line1: string,
     *     city: string,
     *     state: string,
     *     country: string,
     *     postal_code: string,
     *     phone: string
     * } $data
     */
    public function upsert(string $userId, array $data): array
    {
        $existing = $this->findByUserId($userId);
        if ($existing !== null) {
            $this->execute(
                'UPDATE user_billing_profiles SET
                    address_line1 = :address_line1,
                    city = :city,
                    state = :state,
                    country = :country,
                    postal_code = :postal_code,
                    phone = :phone,
                    updated_at = NOW()
                 WHERE user_id = :user_id',
                [
                    'user_id' => $userId,
                    'address_line1' => $data['address_line1'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'country' => $data['country'],
                    'postal_code' => $data['postal_code'],
                    'phone' => $data['phone'],
                ]
            );

            return $this->findByUserId($userId) ?? throw new RuntimeException('Failed to update billing profile.');
        }

        $this->execute(
            'INSERT INTO user_billing_profiles
             (user_id, address_line1, city, state, country, postal_code, phone)
             VALUES
             (:user_id, :address_line1, :city, :state, :country, :postal_code, :phone)',
            [
                'user_id' => $userId,
                'address_line1' => $data['address_line1'],
                'city' => $data['city'],
                'state' => $data['state'],
                'country' => $data['country'],
                'postal_code' => $data['postal_code'],
                'phone' => $data['phone'],
            ]
        );

        return $this->findByUserId($userId) ?? throw new RuntimeException('Failed to create billing profile.');
    }
}
