<?php

declare(strict_types=1);

final class UserService
{
    public function findOrCreateByEmail(string $email, ?string $name = null): array
    {
        $email = normalize_email($email);
        $users = RepositoryFactory::users();
        $existing = $users->findByEmail($email);

        if ($existing !== null) {
            if ($existing['email_verified_at'] === null) {
                $users->markEmailVerified($existing['id']);
                $existing = $users->findById($existing['id']) ?? $existing;
            }
            return $existing;
        }

        $user = $users->create($email, $name, null, true);
        (new AffiliateService())->bindUserReferral((string) $user['id']);

        return $user;
    }

    public function findOrCreateByGoogle(string $googleId, string $email, ?string $name = null): array
    {
        $email = normalize_email($email);
        $users = RepositoryFactory::users();

        $byGoogle = $users->findByGoogleId($googleId);
        if ($byGoogle !== null) {
            return $byGoogle;
        }

        $byEmail = $users->findByEmail($email);
        if ($byEmail !== null) {
            $users->updateGoogleLink($byEmail['id'], $googleId, $name);
            return $users->findById($byEmail['id']) ?? $byEmail;
        }

        $user = $users->create($email, $name, $googleId, true);
        (new AffiliateService())->bindUserReferral((string) $user['id']);

        return $user;
    }

    public function toSessionUser(array $user): array
    {
        return [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'] ?? null,
        ];
    }

    public function deleteAccount(string $userId): void
    {
        $user = RepositoryFactory::users()->findById($userId);
        if ($user === null) {
            throw new RuntimeException('User not found.');
        }

        RepositoryFactory::users()->deleteById($userId);
    }
}
