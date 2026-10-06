<?php

declare(strict_types=1);

final class PaymentMethodRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM user_payment_methods WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function findDefaultByUserId(string $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM user_payment_methods WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC LIMIT 1',
            ['user_id' => $userId]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listByUserId(string $userId): array
    {
        return $this->fetchAll(
            'SELECT * FROM user_payment_methods WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC',
            ['user_id' => $userId]
        );
    }

    public function countByUserId(string $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS total FROM user_payment_methods WHERE user_id = :user_id',
            ['user_id' => $userId]
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @param array{
     *     flutterwave_token: string,
     *     card_brand: ?string,
     *     card_last4: string,
     *     card_exp_month: string,
     *     card_exp_year: string,
     *     card_holder: ?string,
     *     is_default?: bool
     * } $data
     */
    public function create(string $userId, array $data): array
    {
        $id = Uuid::v4();
        $isDefault = (bool) ($data['is_default'] ?? true);

        if ($isDefault) {
            $this->clearDefaultForUser($userId);
        }

        $this->execute(
            'INSERT INTO user_payment_methods
             (id, user_id, flutterwave_token, card_brand, card_last4, card_exp_month, card_exp_year, card_holder, is_default)
             VALUES
             (:id, :user_id, :token, :brand, :last4, :exp_month, :exp_year, :holder, :is_default)',
            [
                'id' => $id,
                'user_id' => $userId,
                'token' => $data['flutterwave_token'],
                'brand' => $data['card_brand'],
                'last4' => $data['card_last4'],
                'exp_month' => $data['card_exp_month'],
                'exp_year' => $data['card_exp_year'],
                'holder' => $data['card_holder'],
                'is_default' => $isDefault ? 1 : 0,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to save payment method.');
    }

    public function deleteForUser(string $userId, string $methodId): bool
    {
        $method = $this->findById($methodId);
        if ($method === null || (string) ($method['user_id'] ?? '') !== $userId) {
            return false;
        }

        $wasDefault = (bool) ($method['is_default'] ?? false);
        $this->execute('DELETE FROM user_payment_methods WHERE id = :id', ['id' => $methodId]);

        if ($wasDefault) {
            $next = $this->fetchOne(
                'SELECT id FROM user_payment_methods WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1',
                ['user_id' => $userId]
            );
            if ($next !== null) {
                $this->setDefault((string) $next['id'], $userId);
            }
        }

        return true;
    }

    public function setDefault(string $methodId, string $userId): bool
    {
        $method = $this->findById($methodId);
        if ($method === null || (string) ($method['user_id'] ?? '') !== $userId) {
            return false;
        }

        $this->clearDefaultForUser($userId);
        $this->execute(
            'UPDATE user_payment_methods SET is_default = 1, updated_at = NOW() WHERE id = :id',
            ['id' => $methodId]
        );

        return true;
    }

    public function tokenExistsForUser(string $userId, string $token): bool
    {
        $row = $this->fetchOne(
            'SELECT id FROM user_payment_methods WHERE user_id = :user_id AND flutterwave_token = :token LIMIT 1',
            ['user_id' => $userId, 'token' => $token]
        );

        return $row !== null;
    }

    private function clearDefaultForUser(string $userId): void
    {
        $this->execute(
            'UPDATE user_payment_methods SET is_default = 0, updated_at = NOW() WHERE user_id = :user_id',
            ['user_id' => $userId]
        );
    }
}
