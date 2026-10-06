<?php

declare(strict_types=1);

final class UserRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
    }

    public function findByGoogleId(string $googleId): ?array
    {
        return $this->fetchOne('SELECT * FROM users WHERE google_id = :google_id LIMIT 1', ['google_id' => $googleId]);
    }

    public function create(string $email, ?string $name = null, ?string $googleId = null, bool $verified = false): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO users (id, email, name, google_id, email_verified_at) VALUES (:id, :email, :name, :google_id, :email_verified_at)',
            [
                'id' => $id,
                'email' => $email,
                'name' => $name,
                'google_id' => $googleId,
                'email_verified_at' => $verified ? date('Y-m-d H:i:s') : null,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create user');
    }

    public function updateGoogleLink(string $userId, string $googleId, ?string $name = null): void
    {
        $this->execute(
            'UPDATE users SET google_id = :google_id, name = COALESCE(:name, name), email_verified_at = COALESCE(email_verified_at, NOW()), updated_at = NOW() WHERE id = :id',
            ['id' => $userId, 'google_id' => $googleId, 'name' => $name]
        );
    }

    public function markEmailVerified(string $userId): void
    {
        $this->execute(
            'UPDATE users SET email_verified_at = NOW(), updated_at = NOW() WHERE id = :id',
            ['id' => $userId]
        );
    }

    public function setReferredBy(string $userId, string $affiliateId): void
    {
        $this->execute(
            'UPDATE users SET referred_by_affiliate_id = :affiliate_id, updated_at = NOW()
             WHERE id = :id AND referred_by_affiliate_id IS NULL',
            ['id' => $userId, 'affiliate_id' => $affiliateId]
        );
    }

    public function deleteById(string $userId): void
    {
        $this->execute('DELETE FROM users WHERE id = :id', ['id' => $userId]);
    }

    public function setRole(string $userId, string $role): void
    {
        $role = $role === AdminAuth::ROLE_ADMIN ? AdminAuth::ROLE_ADMIN : AdminAuth::ROLE_USER;
        $this->execute(
            'UPDATE users SET role = :role, updated_at = NOW() WHERE id = :id',
            ['id' => $userId, 'role' => $role]
        );
    }

    public function setSuspended(string $userId, bool $suspended): void
    {
        $this->execute(
            'UPDATE users SET is_suspended = :suspended, suspended_at = CASE WHEN :suspended2 = 1 THEN NOW() ELSE NULL END, updated_at = NOW() WHERE id = :id',
            [
                'id' => $userId,
                'suspended' => $suspended ? 1 : 0,
                'suspended2' => $suspended ? 1 : 0,
            ]
        );
    }
}
