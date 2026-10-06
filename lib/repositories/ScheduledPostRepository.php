<?php

declare(strict_types=1);

final class ScheduledPostRepository extends BaseRepository
{
    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM scheduled_posts WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listByBusinessId(string $businessId, ?string $status = null, ?string $accountId = null, int $limit = 100): array
    {
        $sql = 'SELECT DISTINCT p.* FROM scheduled_posts p';
        $params = ['business_id' => $businessId];

        if ($accountId !== null && $accountId !== '') {
            $sql .= ' INNER JOIN scheduled_post_targets t ON t.scheduled_post_id = p.id AND t.connected_account_id = :account_id';
            $params['account_id'] = $accountId;
        }

        $sql .= ' WHERE p.business_id = :business_id';

        if ($status !== null && $status !== '') {
            $sql .= ' AND p.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY COALESCE(p.scheduled_at, p.created_at) DESC LIMIT ' . max(1, min(200, $limit));

        return $this->fetchAll($sql, $params);
    }

    /** @return list<array<string, mixed>> */
    public function listDueForPublishing(int $limit = 25): array
    {
        return $this->fetchAll(
            'SELECT * FROM scheduled_posts
             WHERE status = :status
               AND scheduled_at IS NOT NULL
               AND scheduled_at <= NOW()
             ORDER BY scheduled_at ASC
             LIMIT ' . max(1, min(50, $limit)),
            ['status' => 'scheduled']
        );
    }

    /** @return array<string, mixed> */
    public function create(string $businessId, array $data): array
    {
        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO scheduled_posts (
                id, business_id, created_by_user_id, caption, hashtags, media_type,
                media_url, media_storage_path, thumbnail_url, status, scheduled_at, ai_assisted
            ) VALUES (
                :id, :business_id, :created_by_user_id, :caption, :hashtags, :media_type,
                :media_url, :media_storage_path, :thumbnail_url, :status, :scheduled_at, :ai_assisted
            )',
            [
                'id' => $id,
                'business_id' => $businessId,
                'created_by_user_id' => $data['created_by_user_id'] ?? null,
                'caption' => $data['caption'],
                'hashtags' => $data['hashtags'] ?? null,
                'media_type' => $data['media_type'] ?? 'image',
                'media_url' => $data['media_url'] ?? null,
                'media_storage_path' => $data['media_storage_path'] ?? null,
                'thumbnail_url' => $data['thumbnail_url'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'ai_assisted' => (int) (bool) ($data['ai_assisted'] ?? false),
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create scheduled post.');
    }

    public function update(string $id, array $data): void
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['caption', 'hashtags', 'media_type', 'media_url', 'media_storage_path', 'thumbnail_url', 'status', 'scheduled_at', 'published_at'] as $key) {
            if (array_key_exists($key, $data)) {
                $fields[] = $key . ' = :' . $key;
                $params[$key] = $data[$key];
            }
        }

        if (array_key_exists('ai_assisted', $data)) {
            $fields[] = 'ai_assisted = :ai_assisted';
            $params['ai_assisted'] = (int) (bool) $data['ai_assisted'];
        }

        if ($fields === []) {
            return;
        }

        $this->execute(
            'UPDATE scheduled_posts SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id',
            $params
        );
    }

    public function delete(string $id): void
    {
        $this->execute('DELETE FROM scheduled_posts WHERE id = :id', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function listMedia(string $scheduledPostId): array
    {
        return $this->fetchAll(
            'SELECT * FROM scheduled_post_media
             WHERE scheduled_post_id = :scheduled_post_id
             ORDER BY sort_order ASC, created_at ASC',
            ['scheduled_post_id' => $scheduledPostId]
        );
    }

    /** @param list<string> $storedPaths */
    public function replaceMedia(string $scheduledPostId, array $storedPaths, SocialMediaImageService $images): void
    {
        $this->execute('DELETE FROM scheduled_post_media WHERE scheduled_post_id = :id', ['id' => $scheduledPostId]);

        foreach (array_values($storedPaths) as $index => $path) {
            $path = trim($path);
            if ($path === '') {
                continue;
            }

            $this->execute(
                'INSERT INTO scheduled_post_media (
                    id, scheduled_post_id, sort_order, media_type, media_url, media_storage_path
                ) VALUES (
                    :id, :scheduled_post_id, :sort_order, :media_type, :media_url, :media_storage_path
                )',
                [
                    'id' => Uuid::v4(),
                    'scheduled_post_id' => $scheduledPostId,
                    'sort_order' => $index,
                    'media_type' => $images->mediaTypeFromStoredPath($path) ?? 'image',
                    'media_url' => $path,
                    'media_storage_path' => $path,
                ]
            );
        }
    }

    /** @return list<array<string, mixed>> */
    public function listTargets(string $scheduledPostId): array
    {
        return $this->fetchAll(
            'SELECT t.*, c.platform_account_id
             FROM scheduled_post_targets t
             INNER JOIN connected_accounts c ON c.id = t.connected_account_id
             WHERE t.scheduled_post_id = :scheduled_post_id
             ORDER BY t.created_at ASC',
            ['scheduled_post_id' => $scheduledPostId]
        );
    }

    /** @param list<string> $connectedAccountIds
     * @param array<string, array{caption?: string, hashtags?: string, post_type?: string, options?: array<string, mixed>}> $platformContent
     * @param array<string, list<string>> $platformMediaPaths
     */
    public function replaceTargets(
        string $scheduledPostId,
        array $connectedAccountIds,
        array $platformContent = [],
        array $platformMediaPaths = [],
        ?SocialMediaImageService $images = null
    ): void {
        $existingTargets = $this->listTargets($scheduledPostId);
        foreach ($existingTargets as $existingTarget) {
            $this->deleteTargetMedia((string) ($existingTarget['id'] ?? ''));
        }

        $this->execute('DELETE FROM scheduled_post_targets WHERE scheduled_post_id = :id', ['id' => $scheduledPostId]);

        foreach ($connectedAccountIds as $accountId) {
            $account = RepositoryFactory::connectedAccounts()->findById($accountId);
            if ($account === null) {
                continue;
            }

            $platform = (string) ($account['platform'] ?? '');
            $override = is_array($platformContent[$platform] ?? null) ? $platformContent[$platform] : [];
            $targetCaption = trim((string) ($override['caption'] ?? ''));
            $targetHashtags = trim((string) ($override['hashtags'] ?? ''));
            $postFormat = trim((string) ($override['post_type'] ?? 'post'));
            if (!in_array($postFormat, ['post', 'story', 'reel'], true)) {
                $postFormat = 'post';
            }

            $options = is_array($override['options'] ?? null) && $override['options'] !== []
                ? json_encode($override['options'], JSON_THROW_ON_ERROR)
                : null;

            $targetId = Uuid::v4();
            $this->execute(
                'INSERT INTO scheduled_post_targets (
                    id, scheduled_post_id, connected_account_id, platform, caption, hashtags, post_format, options, status
                ) VALUES (
                    :id, :scheduled_post_id, :connected_account_id, :platform, :caption, :hashtags, :post_format, :options, :status
                )',
                [
                    'id' => $targetId,
                    'scheduled_post_id' => $scheduledPostId,
                    'connected_account_id' => $accountId,
                    'platform' => $platform,
                    'caption' => $targetCaption !== '' ? $targetCaption : null,
                    'hashtags' => $targetHashtags !== '' ? $targetHashtags : null,
                    'post_format' => $postFormat,
                    'options' => $options,
                    'status' => 'pending',
                ]
            );

            $mediaPaths = is_array($platformMediaPaths[$platform] ?? null) ? $platformMediaPaths[$platform] : [];
            if ($mediaPaths !== [] && $images !== null) {
                $this->replaceTargetMedia($targetId, $mediaPaths, $images);
            }
        }
    }

    /** @return list<array<string, mixed>> */
    public function listTargetMedia(string $targetId): array
    {
        if ($targetId === '') {
            return [];
        }

        return $this->fetchAll(
            'SELECT * FROM scheduled_post_target_media
             WHERE scheduled_post_target_id = :target_id
             ORDER BY sort_order ASC, created_at ASC',
            ['target_id' => $targetId]
        );
    }

    /** @param list<string> $storedPaths */
    public function replaceTargetMedia(string $targetId, array $storedPaths, SocialMediaImageService $images): void
    {
        if ($targetId === '') {
            return;
        }

        $this->deleteTargetMedia($targetId);

        foreach (array_values($storedPaths) as $index => $path) {
            $path = trim($path);
            if ($path === '') {
                continue;
            }

            $this->execute(
                'INSERT INTO scheduled_post_target_media (
                    id, scheduled_post_target_id, sort_order, media_type, media_url, media_storage_path
                ) VALUES (
                    :id, :scheduled_post_target_id, :sort_order, :media_type, :media_url, :media_storage_path
                )',
                [
                    'id' => Uuid::v4(),
                    'scheduled_post_target_id' => $targetId,
                    'sort_order' => $index,
                    'media_type' => $images->mediaTypeFromStoredPath($path) ?? 'image',
                    'media_url' => $path,
                    'media_storage_path' => $path,
                ]
            );
        }
    }

    public function deleteTargetMedia(string $targetId): void
    {
        if ($targetId === '') {
            return;
        }

        $this->execute(
            'DELETE FROM scheduled_post_target_media WHERE scheduled_post_target_id = :target_id',
            ['target_id' => $targetId]
        );
    }

    public function updateTarget(string $targetId, array $data): void
    {
        $fields = [];
        $params = ['id' => $targetId];

        foreach (['status', 'platform_post_id', 'platform_permalink', 'error_message', 'published_at'] as $key) {
            if (array_key_exists($key, $data)) {
                $fields[] = $key . ' = :' . $key;
                $params[$key] = $data[$key];
            }
        }

        if ($fields === []) {
            return;
        }

        $this->execute(
            'UPDATE scheduled_post_targets SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id',
            $params
        );
    }

    /** @return list<array<string, mixed>> */
    public function listPostsWithMediaOlderThan(string $status, int $days): array
    {
        $days = max(1, $days);

        return $this->fetchAll(
            'SELECT DISTINCT p.*
             FROM scheduled_posts p
             LEFT JOIN scheduled_post_media m ON m.scheduled_post_id = p.id
             WHERE p.status = :status
               AND p.updated_at < DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)
               AND (
                    (p.media_url IS NOT NULL AND p.media_url != "")
                    OR m.id IS NOT NULL
               )',
            ['status' => $status]
        );
    }

    /** @return list<string> */
    public function listAllStoredMediaPaths(): array
    {
        $paths = [];

        $postRows = $this->fetchAll(
            'SELECT media_url FROM scheduled_posts WHERE media_url IS NOT NULL AND media_url != ""'
        );
        foreach ($postRows as $row) {
            $path = trim((string) ($row['media_url'] ?? ''));
            if ($path !== '') {
                $paths[] = $path;
            }
        }

        $mediaRows = $this->fetchAll(
            'SELECT media_url FROM scheduled_post_media WHERE media_url IS NOT NULL AND media_url != ""'
        );
        foreach ($mediaRows as $row) {
            $path = trim((string) ($row['media_url'] ?? ''));
            if ($path !== '') {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    /** @return array{scheduled: int, published: int, failed: int, draft: int} */
    public function countByStatus(string $businessId): array
    {
        $rows = $this->fetchAll(
            'SELECT status, COUNT(*) AS cnt FROM scheduled_posts WHERE business_id = :business_id GROUP BY status',
            ['business_id' => $businessId]
        );

        $counts = ['scheduled' => 0, 'published' => 0, 'failed' => 0, 'draft' => 0, 'publishing' => 0];
        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            if (isset($counts[$status])) {
                $counts[$status] = (int) ($row['cnt'] ?? 0);
            }
        }

        return $counts;
    }
}
