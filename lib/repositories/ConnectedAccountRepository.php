<?php

declare(strict_types=1);

final class ConnectedAccountRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM connected_accounts WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listByBusinessId(string $businessId, bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM connected_accounts WHERE business_id = :business_id';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY created_at DESC';

        return $this->fetchAll($sql, ['business_id' => $businessId]);
    }

    public function findByPlatform(string $businessId, string $platform): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM connected_accounts WHERE business_id = :business_id AND platform = :platform LIMIT 1',
            ['business_id' => $businessId, 'platform' => $platform]
        );
    }

    public function findActiveByPlatform(string $businessId, string $platform): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM connected_accounts
             WHERE business_id = :business_id AND platform = :platform AND is_active = 1
             ORDER BY created_at DESC
             LIMIT 1',
            ['business_id' => $businessId, 'platform' => $platform]
        );
    }

    /**
     * Facebook Page linked to Instagram messaging for a business (active first).
     */
    public function findLinkedFacebookPageForMessaging(string $businessId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM connected_accounts
             WHERE business_id = :business_id
               AND platform = \'facebook\'
               AND platform_account_id IS NOT NULL
               AND platform_account_id != \'\'
             ORDER BY is_active DESC, created_at DESC
             LIMIT 1',
            ['business_id' => $businessId]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listActiveByBusinessAndPlatform(string $businessId, string $platform): array
    {
        return $this->fetchAll(
            'SELECT * FROM connected_accounts
             WHERE business_id = :business_id AND platform = :platform AND is_active = 1
             ORDER BY created_at DESC',
            ['business_id' => $businessId, 'platform' => $platform]
        );
    }

    /** @return list<string> */
    public function listActivePlatformAccountIds(string $businessId, string $platform): array
    {
        $ids = [];
        foreach ($this->listActiveByBusinessAndPlatform($businessId, $platform) as $row) {
            $id = trim((string) ($row['platform_account_id'] ?? ''));
            if ($id !== '') {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    public function hasAnyForPlatform(string $businessId, string $platform): bool
    {
        $row = $this->fetchOne(
            'SELECT id FROM connected_accounts WHERE business_id = :business_id AND platform = :platform LIMIT 1',
            ['business_id' => $businessId, 'platform' => $platform]
        );

        return $row !== null;
    }

    public function countActiveByBusinessId(string $businessId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM connected_accounts WHERE business_id = :business_id AND is_active = 1',
            ['business_id' => $businessId]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function countActiveByUserId(string $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM connected_accounts ca
             INNER JOIN businesses b ON b.id = ca.business_id
             WHERE b.user_id = :user_id AND ca.is_active = 1',
            ['user_id' => $userId]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function countActiveByBusinessAndPlatform(string $businessId, string $platform): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM connected_accounts
             WHERE business_id = :business_id AND platform = :platform AND is_active = 1',
            ['business_id' => $businessId, 'platform' => $platform]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function findAnyMetaUserTokenForBusiness(string $businessId): ?string
    {
        $row = $this->fetchOne(
            'SELECT refresh_token FROM connected_accounts
             WHERE business_id = :business_id
               AND refresh_token IS NOT NULL
               AND refresh_token != ""
             ORDER BY is_active DESC, created_at DESC
             LIMIT 1',
            ['business_id' => $businessId]
        );

        if ($row === null) {
            return null;
        }

        $token = trim((string) ($row['refresh_token'] ?? ''));

        return $token !== '' ? $token : null;
    }

    public function findByBusinessPlatformAccount(string $businessId, string $platform, string $platformAccountId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM connected_accounts
             WHERE business_id = :business_id AND platform = :platform AND platform_account_id = :platform_account_id
             LIMIT 1',
            [
                'business_id' => $businessId,
                'platform' => $platform,
                'platform_account_id' => $platformAccountId,
            ]
        );
    }

    /**
     * Connect or refresh a channel, respecting per-platform limits on the plan.
     */
    public function connectAccount(string $businessId, string $platform, array $data, ?int $channelsPerPlatform): array
    {
        $platformAccountId = (string) ($data['platform_account_id'] ?? '');

        if ($platformAccountId !== '') {
            $sameAccount = $this->findByBusinessPlatformAccount($businessId, $platform, $platformAccountId);
            if ($sameAccount !== null) {
                $this->update((string) $sameAccount['id'], $data);

                return $this->findById((string) $sameAccount['id']) ?? $sameAccount;
            }
        }

        if ($channelsPerPlatform !== null) {
            $existing = $this->findActiveByPlatform($businessId, $platform)
                ?? $this->findByPlatform($businessId, $platform);
            if ($existing !== null) {
                $this->update((string) $existing['id'], $data);

                return $this->findById((string) $existing['id']) ?? $existing;
            }
        }

        return $this->create($businessId, array_merge(['platform' => $platform], $data));
    }

    public function upsert(string $businessId, string $platform, array $data): array
    {
        return $this->connectAccount($businessId, $platform, $data, 1);
    }

    public function findActiveByPlatformAccountId(string $platform, string $platformAccountId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM connected_accounts
             WHERE platform = :platform AND platform_account_id = :platform_account_id AND is_active = 1
             ORDER BY created_at DESC
             LIMIT 1',
            ['platform' => $platform, 'platform_account_id' => $platformAccountId]
        );
    }

    public function deactivateOtherBusinessAccounts(string $platform, string $platformAccountId, string $keepBusinessId): void
    {
        $this->execute(
            'UPDATE connected_accounts
             SET is_active = 0
             WHERE platform = :platform
               AND platform_account_id = :platform_account_id
               AND business_id <> :business_id
               AND is_active = 1',
            [
                'platform' => $platform,
                'platform_account_id' => $platformAccountId,
                'business_id' => $keepBusinessId,
            ]
        );
    }

    public function create(string $businessId, array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO connected_accounts (id, business_id, platform, platform_account_id, linked_page_id, display_name, profile_picture_url, access_token, refresh_token, token_expires_at, refresh_expires_at, scopes, is_active)
             VALUES (:id, :business_id, :platform, :platform_account_id, :linked_page_id, :display_name, :profile_picture_url, :access_token, :refresh_token, :token_expires_at, :refresh_expires_at, :scopes, :is_active)',
            [
                'id' => $id,
                'business_id' => $businessId,
                'platform' => $data['platform'],
                'platform_account_id' => $data['platform_account_id'] ?? null,
                'linked_page_id' => $data['linked_page_id'] ?? null,
                'display_name' => $data['display_name'] ?? null,
                'profile_picture_url' => $data['profile_picture_url'] ?? null,
                'access_token' => $data['access_token'] ?? null,
                'refresh_token' => $data['refresh_token'] ?? null,
                'token_expires_at' => $data['token_expires_at'] ?? null,
                'refresh_expires_at' => $data['refresh_expires_at'] ?? null,
                'scopes' => $data['scopes'] ?? null,
                'is_active' => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create connected account');
    }

    public function setWabaId(string $id, string $wabaId): void
    {
        $this->execute(
            'UPDATE connected_accounts SET waba_id = :waba_id WHERE id = :id',
            ['id' => $id, 'waba_id' => $wabaId]
        );
    }

    public function update(string $id, array $data): void
    {
        $existing = $this->findById($id);
        if ($existing === null) {
            return;
        }

        $this->execute(
            'UPDATE connected_accounts SET platform_account_id = :platform_account_id, linked_page_id = :linked_page_id, display_name = :display_name, profile_picture_url = :profile_picture_url, access_token = :access_token,
             refresh_token = :refresh_token, token_expires_at = :token_expires_at, refresh_expires_at = :refresh_expires_at, scopes = :scopes, is_active = :is_active WHERE id = :id',
            [
                'id' => $id,
                'platform_account_id' => array_key_exists('platform_account_id', $data)
                    ? $data['platform_account_id']
                    : ($existing['platform_account_id'] ?? null),
                'linked_page_id' => array_key_exists('linked_page_id', $data)
                    ? $data['linked_page_id']
                    : ($existing['linked_page_id'] ?? null),
                'display_name' => array_key_exists('display_name', $data)
                    ? $data['display_name']
                    : ($existing['display_name'] ?? null),
                'profile_picture_url' => array_key_exists('profile_picture_url', $data)
                    ? $data['profile_picture_url']
                    : ($existing['profile_picture_url'] ?? null),
                'access_token' => array_key_exists('access_token', $data)
                    ? $data['access_token']
                    : ($existing['access_token'] ?? null),
                'refresh_token' => array_key_exists('refresh_token', $data)
                    ? $data['refresh_token']
                    : ($existing['refresh_token'] ?? null),
                'token_expires_at' => array_key_exists('token_expires_at', $data)
                    ? $data['token_expires_at']
                    : ($existing['token_expires_at'] ?? null),
                'refresh_expires_at' => array_key_exists('refresh_expires_at', $data)
                    ? $data['refresh_expires_at']
                    : ($existing['refresh_expires_at'] ?? null),
                'scopes' => array_key_exists('scopes', $data)
                    ? $data['scopes']
                    : ($existing['scopes'] ?? null),
                'is_active' => array_key_exists('is_active', $data)
                    ? (int) (bool) $data['is_active']
                    : (int) (bool) ($existing['is_active'] ?? true),
            ]
        );
    }
}
