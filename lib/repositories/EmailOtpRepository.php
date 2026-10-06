<?php

declare(strict_types=1);

final class EmailOtpRepository extends BaseRepository
{
    public function create(string $email, string $otpHash, string $expiresAt): string
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO email_otps (id, email, otp_hash, expires_at) VALUES (:id, :email, :otp_hash, :expires_at)',
            ['id' => $id, 'email' => $email, 'otp_hash' => $otpHash, 'expires_at' => $expiresAt]
        );

        return $id;
    }

    public function findLatestForEmail(string $email): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM email_otps WHERE email = :email ORDER BY created_at DESC LIMIT 1',
            ['email' => $email]
        );
    }

    public function incrementAttempts(string $id, int $attempts): void
    {
        $this->execute('UPDATE email_otps SET attempts = :attempts WHERE id = :id', ['id' => $id, 'attempts' => $attempts]);
    }

    public function markUsed(string $id, int $attempts): void
    {
        $this->execute(
            'UPDATE email_otps SET used_at = NOW(), attempts = :attempts WHERE id = :id',
            ['id' => $id, 'attempts' => $attempts]
        );
    }
}
