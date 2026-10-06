<?php

declare(strict_types=1);

/**
 * Social media management — compose, schedule, publish, delete posts.
 */
final class SocialMediaService
{
    /** Channels the planner can publish to. */
    public const PUBLISHABLE_PLATFORMS = ['instagram', 'facebook', 'tiktok'];

    private ScheduledPostRepository $posts;
    private SocialMediaImageService $images;
    private MetaPublishService $publisher;
    private TikTokPublishService $tiktokPublisher;

    public function __construct()
    {
        $this->posts = RepositoryFactory::scheduledPosts();
        $this->images = new SocialMediaImageService();
        $this->publisher = new MetaPublishService();
        $this->tiktokPublisher = new TikTokPublishService();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPublishableAccounts(string $businessId): array
    {
        $accounts = RepositoryFactory::connectedAccounts()->listByBusinessId($businessId, true);
        $publishable = [];

        foreach ($accounts as $account) {
            $platform = (string) ($account['platform'] ?? '');
            if (!$this->isPublishablePlatform($platform)) {
                continue;
            }

            $publishable[] = [
                'id' => (string) $account['id'],
                'platform' => $platform,
                'platform_label' => dash_social_feed_label($platform),
                'display_name' => dash_connected_account_name($account),
                'avatar_src' => dash_connected_account_avatar_src($account),
                'account_type' => dash_social_compose_account_subtitle($platform),
                'account_id' => (string) ($account['platform_account_id'] ?? ''),
            ];
        }

        return $publishable;
    }

    /** @return array{scheduled: int, published: int, failed: int, draft: int} */
    public function stats(string $businessId): array
    {
        return $this->posts->countByStatus($businessId);
    }

    /**
     * @param list<string> $accountIds
     * @param list<string> $mediaPaths
     * @return array{ok: bool, post?: array<string, mixed>, error?: string}
     */
    public function createPost(
        string $businessId,
        string $userId,
        string $caption,
        ?string $hashtags,
        array $mediaPaths,
        array $accountIds,
        ?string $scheduledAt,
        bool $publishNow,
        bool $aiAssisted,
        array $platformContent = [],
        array $platformMediaPaths = [],
        ?string $thumbnailPath = null
    ): array {
        $mediaPaths = array_values(array_filter(array_map('trim', $mediaPaths)));
        $platformMediaPaths = $this->normalizePlatformMediaPaths($platformMediaPaths);
        $postMediaPaths = $this->mergePlatformMediaPaths($mediaPaths, $platformMediaPaths);

        $mediaError = $this->validateMediaPaths($postMediaPaths, $platformMediaPaths);
        if ($mediaError !== null) {
            return ['ok' => false, 'error' => $mediaError];
        }

        $caption = trim($caption);
        $platformContent = $this->normalizePlatformContent($platformContent);

        $validAccounts = $this->filterValidAccounts($businessId, $accountIds);
        if ($validAccounts === []) {
            return ['ok' => false, 'error' => $this->selectAccountsMessage()];
        }

        $leavingDraft = $publishNow || ($scheduledAt !== null && trim($scheduledAt) !== '');
        $contentError = $this->validateComposeContent(
            $caption,
            $postMediaPaths,
            $platformContent,
            $validAccounts,
            $platformMediaPaths,
            $leavingDraft
        );
        if ($contentError !== null) {
            return ['ok' => false, 'error' => $contentError];
        }

        $primaryCaption = $this->resolvePrimaryCaption($caption, $platformContent, $validAccounts);
        $primaryHashtags = $this->resolvePrimaryHashtags($hashtags, $platformContent);

        $status = 'draft';
        $scheduledAtValue = null;

        if ($publishNow) {
            $status = 'publishing';
        } elseif ($scheduledAt !== null && trim($scheduledAt) !== '') {
            $scheduledAtValue = $this->parseScheduleTime($scheduledAt);
            if ($scheduledAtValue === null) {
                return ['ok' => false, 'error' => 'Choose a valid date and time in the future.'];
            }
            $status = 'scheduled';
        } else {
            $status = 'draft';
        }

        $post = $this->posts->create($businessId, [
            'created_by_user_id' => $userId,
            'caption' => $primaryCaption !== '' ? $primaryCaption : ' ',
            'hashtags' => $primaryHashtags,
            'media_type' => $this->resolvePostMediaType($postMediaPaths),
            'media_url' => $postMediaPaths[0] ?? null,
            'media_storage_path' => $postMediaPaths[0] ?? null,
            'thumbnail_url' => $thumbnailPath,
            'status' => $status,
            'scheduled_at' => $scheduledAtValue,
            'ai_assisted' => $aiAssisted,
        ]);

        $this->posts->replaceMedia((string) $post['id'], $postMediaPaths, $this->images);

        $this->posts->replaceTargets((string) $post['id'], $validAccounts, $platformContent, $platformMediaPaths, $this->images);

        if ($publishNow) {
            return $this->publishPost((string) $post['id'], $businessId);
        }

        if ($status === 'draft') {
            return ['ok' => true, 'post' => $this->formatPost((string) $post['id'])];
        }

        return ['ok' => true, 'post' => $this->formatPost((string) $post['id'])];
    }

    /**
     * @param list<string> $accountIds
     * @param list<string>|null $mediaPaths null keeps existing media
     * @return array{ok: bool, post?: array<string, mixed>, error?: string}
     */
    public function updateLocalPost(
        string $postId,
        string $businessId,
        string $caption,
        ?string $hashtags,
        ?array $mediaPaths,
        array $accountIds,
        ?string $scheduledAt,
        bool $publishNow,
        array $platformContent = [],
        array $platformMediaPaths = [],
        ?string $thumbnailPath = null
    ): array {
        $post = $this->posts->findById($postId);
        if ($post === null || (string) $post['business_id'] !== $businessId) {
            return ['ok' => false, 'error' => 'Post not found.'];
        }

        $status = (string) ($post['status'] ?? '');
        if (!in_array($status, ['draft', 'scheduled', 'failed'], true)) {
            return ['ok' => false, 'error' => 'Only drafts, scheduled, or failed posts can be edited here.'];
        }

        $caption = trim($caption);
        $platformContent = $this->normalizePlatformContent($platformContent);
        $platformContent = $this->carryOverTargetOptions($postId, $platformContent);
        $platformMediaPaths = $this->normalizePlatformMediaPaths($platformMediaPaths);
        $existingMedia = $this->storedPathsForPost($post);
        $nextMedia = $mediaPaths ?? $existingMedia;
        if ($platformMediaPaths !== []) {
            $nextMedia = $this->mergePlatformMediaPaths($nextMedia, $platformMediaPaths);
        }

        if ($mediaPaths !== null || $platformMediaPaths !== []) {
            $mediaError = $this->validateMediaPaths($nextMedia, $platformMediaPaths);
            if ($mediaError !== null) {
                return ['ok' => false, 'error' => $mediaError];
            }
        }

        $validAccounts = $this->filterValidAccounts($businessId, $accountIds);
        if ($validAccounts === []) {
            return ['ok' => false, 'error' => $this->selectAccountsMessage()];
        }

        $leavingDraft = $publishNow || ($scheduledAt !== null && trim($scheduledAt) !== '');
        $contentError = $this->validateComposeContent(
            $caption,
            $nextMedia,
            $platformContent,
            $validAccounts,
            $platformMediaPaths,
            $leavingDraft
        );
        if ($contentError !== null) {
            return ['ok' => false, 'error' => $contentError];
        }

        $primaryCaption = $this->resolvePrimaryCaption($caption, $platformContent, $validAccounts);
        $primaryHashtags = $this->resolvePrimaryHashtags($hashtags, $platformContent);

        $updates = [
            'caption' => $primaryCaption !== '' ? $primaryCaption : (string) ($post['caption'] ?? ' '),
            'hashtags' => $primaryHashtags,
        ];

        if ($mediaPaths !== null || $platformMediaPaths !== []) {
            foreach ($existingMedia as $oldPath) {
                if (!in_array($oldPath, $nextMedia, true)) {
                    $this->images->deleteStoredPath($oldPath);
                }
            }

            $updates['media_type'] = $this->resolvePostMediaType($nextMedia);
            $updates['media_url'] = $nextMedia[0] ?? null;
            $updates['media_storage_path'] = $nextMedia[0] ?? null;
            $this->posts->replaceMedia($postId, $nextMedia, $this->images);
        }

        if ($thumbnailPath !== null && trim($thumbnailPath) !== '') {
            $this->images->deleteStoredPath((string) ($post['thumbnail_url'] ?? ''));
            $updates['thumbnail_url'] = $thumbnailPath;
        }

        if ($publishNow) {
            $updates['status'] = 'publishing';
            $updates['scheduled_at'] = null;
        } elseif ($scheduledAt !== null && trim($scheduledAt) !== '') {
            $scheduledAtValue = $this->parseScheduleTime($scheduledAt);
            if ($scheduledAtValue === null) {
                return ['ok' => false, 'error' => 'Choose a valid date and time in the future.'];
            }
            $updates['status'] = 'scheduled';
            $updates['scheduled_at'] = $scheduledAtValue;
        } else {
            $updates['status'] = 'draft';
            $updates['scheduled_at'] = null;
        }

        $this->posts->update($postId, $updates);
        $this->posts->replaceTargets($postId, $validAccounts, $platformContent, $platformMediaPaths, $this->images);

        if ($publishNow) {
            return $this->publishPost($postId, $businessId);
        }

        return ['ok' => true, 'post' => $this->formatPost($postId)];
    }

    /**
     * @return array{ok: bool, posts?: list<array<string, mixed>>, error?: string}
     */
    public function listLiveFeed(string $businessId, string $accountId, int $limit = 30): array
    {
        $account = $this->findOwnedAccount($businessId, $accountId);
        if ($account === null) {
            return ['ok' => false, 'error' => 'Account not found.'];
        }

        $platform = (string) ($account['platform'] ?? '');
        $token = (string) ($account['access_token'] ?? '');
        $platformAccountId = (string) ($account['platform_account_id'] ?? '');

        if ($token === '' || $platformAccountId === '') {
            return ['ok' => false, 'error' => 'This account is not connected properly. Reconnect it in Connected Accounts.'];
        }

        $fetchResult = match ($platform) {
            'instagram' => meta_fetch_instagram_media_result($platformAccountId, $token, $limit),
            'facebook' => meta_fetch_facebook_posts_result($platformAccountId, $token, $limit),
            'tiktok' => $this->fetchTikTokFeed($account, $limit),
            default => ['posts' => [], 'error' => null],
        };

        if (!empty($fetchResult['error'])) {
            return ['ok' => false, 'error' => (string) $fetchResult['error']];
        }

        $posts = [];
        foreach ($fetchResult['posts'] as $item) {
            $caption = (string) ($item['caption'] ?? $item['message'] ?? '');
            $posts[] = [
                'source' => 'live',
                'id' => (string) ($item['id'] ?? ''),
                'platform' => $platform,
                'platform_label' => dash_social_feed_label($platform),
                'account_id' => $accountId,
                'display_name' => dash_connected_account_name($account),
                'avatar_src' => (string) ($account['avatar_src'] ?? ''),
                'caption' => $caption,
                'media_type' => (string) ($item['media_type'] ?? 'TEXT'),
                'media_product_type' => $item['media_product_type'] ?? null,
                'media_url' => (string) ($item['image_url'] ?? ''),
                'thumbnail_url' => (string) ($item['thumbnail_url'] ?? ''),
                'permalink' => (string) ($item['permalink'] ?? ''),
                'timestamp' => (string) ($item['timestamp'] ?? ''),
                'status' => 'published',
                'can_edit' => in_array($platform, ['facebook', 'instagram'], true),
                'can_delete' => in_array($platform, ['facebook', 'instagram'], true),
            ];
        }

        return ['ok' => true, 'posts' => $posts, 'synced_platforms' => [$platform]];
    }

    /**
     * TikTok access tokens last about a day, so always go through the connect
     * service — it refreshes transparently before handing back a token.
     *
     * @param array<string, mixed> $account
     * @return array{posts: list<array<string, mixed>>, error: ?string}
     */
    private function fetchTikTokFeed(array $account, int $limit): array
    {
        if (!tiktok_is_enabled()) {
            return ['posts' => [], 'error' => null];
        }

        $token = (new TikTokConnectService())->accessTokenForAccount($account);
        if ($token === '') {
            return ['posts' => [], 'error' => 'Your TikTok connection expired. Reconnect the account to continue.'];
        }

        return tiktok_fetch_videos_result($token, $limit);
    }

    /**
     * @param array<string, mixed> $account
     * @return array{ok: bool, post?: array<string, mixed>, metrics?: list<array<string, mixed>>, error?: string}
     */
    private function fetchTikTokInsights(array $account, string $platformPostId): array
    {
        if (!tiktok_is_enabled()) {
            return ['ok' => false, 'error' => 'Insights are not available for this platform.'];
        }

        $token = (new TikTokConnectService())->accessTokenForAccount($account);
        if ($token === '') {
            return ['ok' => false, 'error' => 'Your TikTok connection expired. Reconnect the account to continue.'];
        }

        return tiktok_fetch_video_insights_result($token, $platformPostId);
    }

    /**
     * @return array{ok: bool, posts?: list<array<string, mixed>>, warnings?: list<string>, error?: string}
     */
    public function listLiveFeedAll(string $businessId, int $limitPerAccount = 30): array
    {
        $accounts = $this->listPublishableAccounts($businessId);
        if ($accounts === []) {
            return ['ok' => true, 'posts' => []];
        }

        $allPosts = [];
        $warnings = [];
        $syncedPlatforms = [];

        foreach ($accounts as $account) {
            $accountId = (string) ($account['id'] ?? '');
            if ($accountId === '') {
                continue;
            }

            $result = $this->listLiveFeed($businessId, $accountId, $limitPerAccount);
            if (($result['ok'] ?? false) === true) {
                foreach ($result['posts'] ?? [] as $post) {
                    $allPosts[] = $post;
                }
                foreach ($result['synced_platforms'] ?? [] as $syncedPlatform) {
                    $syncedPlatforms[] = (string) $syncedPlatform;
                }
                continue;
            }

            $label = (string) ($account['display_name'] ?? $account['platform_label'] ?? 'Account');
            $message = trim((string) ($result['error'] ?? ''));
            if ($message !== '') {
                $warnings[] = $label . ': ' . $message;
            }
        }

        usort($allPosts, static function (array $a, array $b): int {
            return strcmp((string) ($b['timestamp'] ?? ''), (string) ($a['timestamp'] ?? ''));
        });

        return [
            'ok' => true,
            'posts' => $allPosts,
            'warnings' => array_values(array_unique($warnings)),
            'synced_platforms' => array_values(array_unique($syncedPlatforms)),
        ];
    }

    /**
     * @return array{
     *   ok: bool,
     *   metrics?: list<array{name: string, label: string, value: int|float|string|null}>,
     *   overview?: array{views: int|float|null, reach: int|float|null, interactions: int|float|null, follows: int|float|null},
     *   post?: array<string, mixed>,
     *   account?: array<string, mixed>,
     *   error?: string
     * }
     */
    public function getPostInsights(string $businessId, string $accountId, string $platform, string $platformPostId): array
    {
        $account = $this->findOwnedAccount($businessId, $accountId);
        if ($account === null) {
            return ['ok' => false, 'error' => 'Account not found.'];
        }

        $accountPlatform = (string) ($account['platform'] ?? '');
        if ($accountPlatform !== $platform) {
            return ['ok' => false, 'error' => 'Account platform mismatch.'];
        }

        $token = (string) ($account['access_token'] ?? '');
        if ($token === '' || $platformPostId === '') {
            return ['ok' => false, 'error' => 'This post is not ready for insights yet.'];
        }

        // App Review tracks instagram_manage_insights against Facebook User tokens,
        // not Page tokens. Prefer the stored user token for Instagram insights calls.
        $userToken = meta_user_token_from_account($account);
        $insightsToken = ($platform === 'instagram' && $userToken !== '') ? $userToken : $token;

        // TikTok returns the video record and its counts together, so fetch once and
        // use it for both the media details and the metrics below.
        $tiktokInsights = $platform === 'tiktok'
            ? $this->fetchTikTokInsights($account, $platformPostId)
            : null;

        // Load media details first so we know Reel vs Post (thumbnail vs video URL, watch-time metrics).
        // Prefer a token that returns a real playable media_url for Reels.
        $details = match ($platform) {
            'instagram' => meta_fetch_instagram_media_details_for_playback($platformPostId, $token, $userToken),
            'facebook' => meta_fetch_facebook_post_details($platformPostId, $token),
            'tiktok' => $tiktokInsights ?? ['ok' => false, 'error' => 'Unsupported platform.'],
            default => ['ok' => false, 'error' => 'Unsupported platform.'],
        };

        $isReel = $platform === 'instagram'
            && is_array($details['post'] ?? null)
            && !empty($details['post']['is_reel']);

        // App Review api_precheck needs GET /{ig-user-id}/insights with a User token.
        // Media insights alone often do not tick the checklist.
        if ($platform === 'instagram' && $userToken !== '') {
            $igAccountId = (string) ($account['platform_account_id'] ?? '');
            if ($igAccountId !== '') {
                meta_ping_instagram_account_insights($igAccountId, $userToken);
            }
        }

        $result = match ($platform) {
            'instagram' => meta_fetch_instagram_media_insights($platformPostId, $insightsToken, $isReel),
            'facebook' => meta_fetch_facebook_post_insights($platformPostId, $token),
            'tiktok' => ($tiktokInsights['ok'] ?? false)
                ? ['metrics' => $tiktokInsights['metrics'] ?? []]
                : ['metrics' => [], 'error' => (string) ($tiktokInsights['error'] ?? 'Insights are not available for this platform.')],
            default => ['metrics' => [], 'error' => 'Insights are not available for this platform.'],
        };

        // If user-token media insights fail, fall back to the Page token (still useful in-product).
        if (
            $platform === 'instagram'
            && $userToken !== ''
            && $insightsToken === $userToken
            && !empty($result['error'])
        ) {
            $fallback = meta_fetch_instagram_media_insights($platformPostId, $token, $isReel);
            if (empty($fallback['error'])) {
                $result = $fallback;
            }
        }

        if (!empty($result['error'])) {
            return ['ok' => false, 'error' => (string) $result['error']];
        }

        $metrics = $result['metrics'] ?? [];
        $metricMap = [];
        foreach ($metrics as $metric) {
            if (!is_array($metric)) {
                continue;
            }
            $name = (string) ($metric['name'] ?? '');
            if ($name !== '') {
                $metricMap[$name] = $metric['value'] ?? null;
            }
        }

        $overview = match ($platform) {
            'instagram' => [
                'views' => $metricMap['views'] ?? $metricMap['total_views'] ?? null,
                'reach' => $metricMap['reach'] ?? null,
                'interactions' => $metricMap['total_interactions'] ?? null,
                'follows' => $metricMap['follows'] ?? ($isReel ? null : 0),
                'likes' => $metricMap['likes'] ?? $metricMap['total_likes'] ?? null,
                'comments' => $metricMap['comments'] ?? $metricMap['total_comments'] ?? null,
                'shares' => $metricMap['shares'] ?? null,
                'saves' => $metricMap['saved'] ?? null,
                'watch_time_avg_ms' => isset($metricMap['ig_reels_avg_watch_time']) && is_numeric($metricMap['ig_reels_avg_watch_time'])
                    ? (float) $metricMap['ig_reels_avg_watch_time']
                    : null,
                'watch_time_total_ms' => isset($metricMap['ig_reels_video_view_total_time']) && is_numeric($metricMap['ig_reels_video_view_total_time'])
                    ? (float) $metricMap['ig_reels_video_view_total_time']
                    : null,
            ],
            'facebook' => [
                'views' => $metricMap['post_impressions'] ?? null,
                'reach' => $metricMap['post_impressions_unique'] ?? null,
                'interactions' => $metricMap['post_engaged_users'] ?? null,
                'follows' => null,
                'likes' => null,
                'comments' => null,
                'shares' => null,
                'saves' => null,
                'watch_time_avg_ms' => null,
                'watch_time_total_ms' => null,
            ],
            // TikTok reports no reach or follower attribution per video.
            'tiktok' => [
                'views' => $metricMap['view_count'] ?? null,
                'reach' => null,
                'interactions' => (int) ($metricMap['like_count'] ?? 0)
                    + (int) ($metricMap['comment_count'] ?? 0)
                    + (int) ($metricMap['share_count'] ?? 0),
                'follows' => null,
                'likes' => $metricMap['like_count'] ?? null,
                'comments' => $metricMap['comment_count'] ?? null,
                'shares' => $metricMap['share_count'] ?? null,
                'saves' => null,
                'watch_time_avg_ms' => null,
                'watch_time_total_ms' => null,
            ],
            default => [
                'views' => null,
                'reach' => null,
                'interactions' => null,
                'follows' => null,
                'likes' => null,
                'comments' => null,
                'shares' => null,
                'saves' => null,
                'watch_time_avg_ms' => null,
                'watch_time_total_ms' => null,
            ],
        };

        $displayName = dash_connected_account_name($account);
        $username = trim((string) (($details['post']['username'] ?? '') ?: ''));
        if ($username === '' && $platform === 'instagram') {
            $username = ltrim($displayName, '@');
        }

        $post = is_array($details['post'] ?? null) ? $details['post'] : [
            'id' => $platformPostId,
            'caption' => '',
            'media_type' => 'image',
            'media_url' => '',
            'permalink' => '',
            'timestamp' => '',
            'username' => $username,
            'post_format' => 'Post',
        ];
        if ($username !== '') {
            $post['username'] = $username;
        }

        // Same-origin video proxy so Reel <video> can play even when Meta CDN blocks hotlinking.
        $cdnMediaUrl = trim((string) ($post['media_url'] ?? ''));
        $isVideoPost = (string) ($post['media_type'] ?? '') === 'video' || !empty($post['is_reel']);
        if ($platform === 'instagram' && $isVideoPost && $cdnMediaUrl !== '') {
            $post['source_media_url'] = $cdnMediaUrl;
            $post['media_url'] = rtrim((string) app_config()['app_url'], '/')
                . '/social-media/media.php?account_id=' . rawurlencode($accountId)
                . '&platform_post_id=' . rawurlencode($platformPostId);
        }

        // Skip live peer Graph fetches on page load — they were 10+ sequential Meta calls
        // and made insights feel stuck on "Loading…". Comparisons stay empty for now.
        $peerStats = [
            'views_median' => null,
            'views_lower' => null,
            'views_upper' => null,
            'reach_median' => null,
            'interactions_median' => null,
            'sample_size' => 0,
            'views_samples' => [],
        ];
        $comparison = $this->buildInsightComparisons($overview, [
            'views' => null,
            'reach' => null,
            'interactions' => null,
        ], $platform);

        $postViews = isset($overview['views']) && is_numeric($overview['views']) ? (float) $overview['views'] : null;
        $postReach = isset($overview['reach']) && is_numeric($overview['reach']) ? (float) $overview['reach'] : null;
        $audienceViews = $this->viewsVersusReachAudience($postViews, $postReach);

        $demographics = [
            'available' => false,
            'source' => null,
            'women_pct' => null,
            'men_pct' => null,
            'ages' => [],
            'error' => null,
        ];
        if ($platform === 'instagram') {
            $igAccountId = (string) ($account['platform_account_id'] ?? '');
            $demoToken = $userToken !== '' ? $userToken : $token;
            if ($igAccountId !== '' && $demoToken !== '') {
                $demographics = meta_fetch_instagram_audience_demographics($igAccountId, $demoToken);
            }
        }

        $series = $this->buildViewsSeries($postViews, (string) ($post['timestamp'] ?? ''), $platform);

        return [
            'ok' => true,
            'metrics' => $metrics,
            'overview' => $overview,
            'comparison' => $comparison,
            'audience' => [
                'views' => $audienceViews,
                'demographics' => $demographics,
            ],
            'peers' => [
                'views' => $peerStats['views_median'] ?? null,
                'views_lower' => $peerStats['views_lower'] ?? null,
                'views_upper' => $peerStats['views_upper'] ?? null,
                'reach' => $peerStats['reach_median'] ?? null,
                'interactions' => $peerStats['interactions_median'] ?? null,
                'sample_size' => $peerStats['sample_size'] ?? 0,
                'views_samples' => $peerStats['views_samples'] ?? [],
            ],
            'series' => $series,
            'post' => $post,
            'account' => [
                'id' => $accountId,
                'platform' => $platform,
                'platform_label' => dash_social_feed_label($platform),
                'display_name' => $displayName,
                'avatar_src' => dash_connected_account_avatar_src($account),
                'username' => $username !== '' ? $username : ltrim($displayName, '@'),
            ],
            'permissions' => [
                'can_edit' => in_array($platform, ['facebook', 'instagram'], true),
                'can_delete' => in_array($platform, ['facebook', 'instagram'], true),
                'can_duplicate' => true,
                'can_reshare_story' => trim((string) ($post['media_url'] ?? $post['thumbnail_url'] ?? '')) !== '',
                'can_boost' => false,
            ],
        ];
    }

    /**
     * Resolve a temporary Instagram CDN media_url for Reel/video playback.
     *
     * @return array{ok: bool, media_url?: string, error?: string}
     */
    public function getPlayableInstagramMediaUrl(string $businessId, string $accountId, string $platformPostId): array
    {
        $account = $this->findOwnedAccount($businessId, $accountId);
        if ($account === null) {
            return ['ok' => false, 'error' => 'Account not found.'];
        }
        if ((string) ($account['platform'] ?? '') !== 'instagram') {
            return ['ok' => false, 'error' => 'Only Instagram Reels are supported.'];
        }

        $token = (string) ($account['access_token'] ?? '');
        $userToken = meta_user_token_from_account($account);
        if ($token === '' || $platformPostId === '') {
            return ['ok' => false, 'error' => 'This post is not ready for playback.'];
        }

        $details = meta_fetch_instagram_media_details_for_playback($platformPostId, $token, $userToken);
        if (($details['ok'] ?? false) !== true || !is_array($details['post'] ?? null)) {
            return ['ok' => false, 'error' => (string) ($details['error'] ?? 'Video unavailable.')];
        }

        $post = $details['post'];
        $isVideo = (string) ($post['media_type'] ?? '') === 'video' || !empty($post['is_reel']);
        $mediaUrl = trim((string) ($post['media_url'] ?? ''));
        if (!$isVideo || $mediaUrl === '') {
            return ['ok' => false, 'error' => 'No playable video URL from Instagram for this Reel.'];
        }

        return ['ok' => true, 'media_url' => $mediaUrl];
    }

    /**
     * Real post-level Views vs Reach split for the Audience tab.
     * Seen once ≈ unique accounts (reach); seen more than once ≈ repeat views (views − reach).
     *
     * @return array{
     *   seen_once: int|null,
     *   seen_more_than_once: int|null,
     *   available: bool,
     *   source: string|null
     * }
     */
    private function viewsVersusReachAudience(?float $views, ?float $reach): array
    {
        if ($views === null || $reach === null) {
            return [
                'seen_once' => null,
                'seen_more_than_once' => null,
                'available' => false,
                'source' => null,
            ];
        }

        $viewsInt = max(0, (int) round($views));
        $reachInt = max(0, (int) round($reach));

        return [
            'seen_once' => $reachInt,
            'seen_more_than_once' => max(0, $viewsInt - $reachInt),
            'available' => true,
            'source' => 'views_vs_reach',
        ];
    }

    /**
     * @param array<string, mixed> $metricMap
     * @return array{follower: int|null, non_follower: int|null, available: bool}
     */
    private function facebookViewsAudienceFromMetrics(array $metricMap): array
    {
        $total = isset($metricMap['post_impressions']) && is_numeric($metricMap['post_impressions'])
            ? (int) $metricMap['post_impressions']
            : null;
        $fan = isset($metricMap['post_impressions_fan']) && is_numeric($metricMap['post_impressions_fan'])
            ? (int) $metricMap['post_impressions_fan']
            : null;

        if ($total === null || $fan === null) {
            return ['follower' => null, 'non_follower' => null, 'available' => false, 'source' => null];
        }

        return [
            'follower' => max(0, $fan),
            'non_follower' => max(0, $total - $fan),
            'available' => true,
            'source' => 'facebook_fan_impressions',
        ];
    }

    /**
     * Create a Lead Bird draft that copies caption + media from a live Meta post.
     *
     * @return array{ok: bool, post?: array<string, mixed>, redirect?: string, error?: string}
     */
    public function duplicateLivePost(string $businessId, string $userId, string $accountId, string $platformPostId): array
    {
        $prepared = $this->prepareLivePostClone($businessId, $accountId, $platformPostId, false);
        if (($prepared['ok'] ?? false) !== true) {
            return ['ok' => false, 'error' => (string) ($prepared['error'] ?? 'Could not duplicate this post.')];
        }

        $platform = (string) ($prepared['platform'] ?? '');
        $caption = (string) ($prepared['caption'] ?? '');
        $mediaPaths = is_array($prepared['media_paths'] ?? null) ? $prepared['media_paths'] : [];

        $result = $this->createPost(
            $businessId,
            $userId,
            $caption !== '' ? $caption : ' ',
            null,
            $mediaPaths,
            [$accountId],
            null,
            false,
            false,
            [
                $platform => [
                    'caption' => $caption,
                    'hashtags' => '',
                    'post_type' => 'post',
                ],
            ],
            $mediaPaths !== [] ? [$platform => $mediaPaths] : []
        );

        if (($result['ok'] ?? false) !== true) {
            return $result;
        }

        return [
            'ok' => true,
            'post' => $result['post'] ?? null,
            'redirect' => app_route('social-media', ['view' => 'drafts']),
            'message' => 'Draft created. Open Drafts to edit and publish.',
        ];
    }

    /**
     * Publish the live post media as a story on the same account.
     *
     * @return array{ok: bool, post?: array<string, mixed>, redirect?: string, error?: string, message?: string}
     */
    public function reshareLivePostToStory(string $businessId, string $userId, string $accountId, string $platformPostId): array
    {
        $prepared = $this->prepareLivePostClone($businessId, $accountId, $platformPostId, true);
        if (($prepared['ok'] ?? false) !== true) {
            return ['ok' => false, 'error' => (string) ($prepared['error'] ?? 'Could not reshare this post.')];
        }

        $platform = (string) ($prepared['platform'] ?? '');
        $caption = (string) ($prepared['caption'] ?? '');
        $mediaPaths = is_array($prepared['media_paths'] ?? null) ? $prepared['media_paths'] : [];
        if ($mediaPaths === []) {
            return ['ok' => false, 'error' => 'Stories need a photo or video from this post.'];
        }

        $result = $this->createPost(
            $businessId,
            $userId,
            $caption,
            null,
            $mediaPaths,
            [$accountId],
            null,
            true,
            false,
            [
                $platform => [
                    'caption' => $caption,
                    'hashtags' => '',
                    'post_type' => 'story',
                ],
            ],
            [$platform => $mediaPaths]
        );

        if (($result['ok'] ?? false) !== true) {
            return $result;
        }

        return [
            'ok' => true,
            'post' => $result['post'] ?? null,
            'redirect' => app_route('social-media', ['view' => 'content']),
            'message' => 'Story published.',
        ];
    }

    /**
     * @return array{ok: bool, platform?: string, caption?: string, media_paths?: list<string>, error?: string}
     */
    private function prepareLivePostClone(
        string $businessId,
        string $accountId,
        string $platformPostId,
        bool $requireMedia
    ): array {
        $account = $this->findOwnedAccount($businessId, $accountId);
        if ($account === null) {
            return ['ok' => false, 'error' => 'Account not found.'];
        }

        $platform = (string) ($account['platform'] ?? '');
        $token = (string) ($account['access_token'] ?? '');
        if ($token === '' || $platformPostId === '') {
            return ['ok' => false, 'error' => 'This post is not available.'];
        }

        $details = match ($platform) {
            'instagram' => meta_fetch_instagram_media_details($platformPostId, $token),
            'facebook' => meta_fetch_facebook_post_details($platformPostId, $token),
            default => ['ok' => false, 'error' => 'Unsupported platform.'],
        };

        if (($details['ok'] ?? false) !== true || !is_array($details['post'] ?? null)) {
            return ['ok' => false, 'error' => (string) ($details['error'] ?? 'Could not load this post.')];
        }

        $post = $details['post'];
        $caption = trim((string) ($post['caption'] ?? ''));
        $mediaUrl = trim((string) ($post['media_url'] ?? ''));
        $mediaPaths = [];

        if ($mediaUrl !== '') {
            try {
                $mediaPaths[] = $this->images->storeFromRemoteUrl($businessId, $mediaUrl, false);
            } catch (InvalidArgumentException | RuntimeException $e) {
                if ($requireMedia) {
                    return ['ok' => false, 'error' => $e->getMessage()];
                }
            }
        }

        if ($requireMedia && $mediaPaths === []) {
            return ['ok' => false, 'error' => 'This post has no photo or video to reshare.'];
        }

        if ($mediaPaths === [] && $caption === '') {
            return ['ok' => false, 'error' => 'This post has nothing to duplicate.'];
        }

        return [
            'ok' => true,
            'platform' => $platform,
            'caption' => $caption,
            'media_paths' => $mediaPaths,
        ];
    }

    /**
     * @param array<string, int|float|null> $overview
     * @param array<string, float|null> $peerAverages
     * @return array<string, array{tone: string, html: string, available: bool}>
     */
    private function buildInsightComparisons(array $overview, array $peerAverages, string $platform): array
    {
        $network = match ($platform) {
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            default => ucfirst($platform),
        };

        return [
            'views' => $this->formatInsightComparison(
                isset($overview['views']) && is_numeric($overview['views']) ? (float) $overview['views'] : null,
                $peerAverages['views'] ?? null,
                'views',
                $network
            ),
            'reach' => $this->formatInsightComparison(
                isset($overview['reach']) && is_numeric($overview['reach']) ? (float) $overview['reach'] : null,
                $peerAverages['reach'] ?? null,
                'reach',
                $network
            ),
            'interactions' => $this->formatInsightComparison(
                isset($overview['interactions']) && is_numeric($overview['interactions']) ? (float) $overview['interactions'] : null,
                $peerAverages['interactions'] ?? null,
                'interactions',
                $network
            ),
        ];
    }

    /**
     * @return array{tone: string, html: string, available: bool}
     */
    private function formatInsightComparison(?float $value, ?float $typical, string $metricWord, string $network): array
    {
        if ($value === null || $typical === null || $typical < 0) {
            return [
                'tone' => 'neutral',
                'html' => '',
                'available' => false,
            ];
        }

        // Compare against the median of recent live posts on the same account.
        if ($value < $typical) {
            return [
                'tone' => 'less',
                'html' => 'This post received <strong>less ' . $metricWord . '</strong> compared to your recent ' . $network . ' posts.',
                'available' => true,
            ];
        }
        if ($value > $typical) {
            return [
                'tone' => 'more',
                'html' => 'This post received <strong>more ' . $metricWord . '</strong> compared to your recent ' . $network . ' posts.',
                'available' => true,
            ];
        }

        return [
            'tone' => 'similar',
            'html' => 'This post received <strong>similar ' . $metricWord . '</strong> compared to your recent ' . $network . ' posts.',
            'available' => true,
        ];
    }

    /**
     * Views-only chart series. End value is the real Graph lifetime total.
     * Intermediate points are a simple growth shape (Meta does not expose media time-series).
     *
     * @return array<string, mixed>
     */
    private function buildViewsSeries(?float $thisPostViews, string $publishedAt, string $platform = ''): array
    {
        /*
         * TikTok only reports a lifetime view count. Drawing a growth curve from it
         * would be inventing numbers the creator could mistake for real history, so
         * send no series and let the page show the total on its own.
         */
        if ($platform === 'tiktok') {
            return [
                'labels' => [],
                'point_times' => [],
                'this_post' => [],
                'has_typical' => false,
                'this_post_total' => (int) round(max(0.0, $thisPostViews ?? 0.0)),
            ];
        }

        $labels = ['15m', '9h', '1d 6h', '7d'];
        $ageSeconds = [15 * 60, 9 * 3600, (24 + 6) * 3600, 7 * 24 * 3600];
        $publishedTs = strtotime($publishedAt);
        $nowTs = time();
        $pointLabels = [];
        $visibleCount = 0;
        foreach ($ageSeconds as $offset) {
            if ($publishedTs !== false) {
                $at = $publishedTs + $offset;
                $pointLabels[] = gmdate('c', $at);
                if ($at <= $nowTs + 60) {
                    $visibleCount++;
                }
            } else {
                $pointLabels[] = null;
                $visibleCount++;
            }
        }
        if ($visibleCount < 2) {
            $visibleCount = count($labels);
        }
        $visibleCount = min(count($labels), max(2, $visibleCount));

        $thisEnd = $thisPostViews !== null ? max(0.0, $thisPostViews) : 0.0;
        $slice = static function (array $points, int $count): array {
            return array_slice($points, 0, $count);
        };

        return [
            'labels' => $slice($labels, $visibleCount),
            'point_times' => $slice($pointLabels, $visibleCount),
            'this_post' => $slice($this->buildCumulativeSeries($thisEnd), $visibleCount),
            'has_typical' => false,
            'this_post_total' => (int) round($thisEnd),
        ];
    }

    /**
     * @return list<int>
     */
    private function buildCumulativeSeries(float $endValue): array
    {
        $end = max(0.0, $endValue);
        // Front-loaded organic growth shape (matches Meta Business Suite curve character).
        $weights = [0.12, 0.42, 0.78, 1.0];
        $points = [];
        foreach ($weights as $i => $weight) {
            if ($i === count($weights) - 1) {
                $points[] = (int) round($end);
            } else {
                $points[] = (int) round($end * $weight);
            }
        }

        return $points;
    }

    /**
     * @param list<float|int> $values
     */
    private function percentile(array $values, float $percentile): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values, SORT_NUMERIC);
        $count = count($values);
        if ($count === 1) {
            return (float) $values[0];
        }

        $rank = ($percentile / 100) * ($count - 1);
        $low = (int) floor($rank);
        $high = (int) ceil($rank);
        if ($low === $high) {
            return (float) $values[$low];
        }

        $weight = $rank - $low;

        return ((float) $values[$low] * (1 - $weight)) + ((float) $values[$high] * $weight);
    }

    /**
     * @param list<float|int> $values
     */
    private function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values, SORT_NUMERIC);
        $count = count($values);
        $mid = intdiv($count, 2);
        if ($count % 2 === 1) {
            return (float) $values[$mid];
        }

        return (((float) $values[$mid - 1]) + ((float) $values[$mid])) / 2;
    }

    /**
     * @return array{
     *   views_median: float|null,
     *   views_lower: float|null,
     *   views_upper: float|null,
     *   reach_median: float|null,
     *   interactions_median: float|null,
     *   sample_size: int,
     *   views_samples: list<float>
     * }
     */
    private function computePeerInsightStats(
        string $businessId,
        string $accountId,
        string $platform,
        string $platformPostId,
        string $token
    ): array {
        $empty = [
            'views_median' => null,
            'views_lower' => null,
            'views_upper' => null,
            'reach_median' => null,
            'interactions_median' => null,
            'sample_size' => 0,
            'views_samples' => [],
        ];
        $feed = $this->listLiveFeed($businessId, $accountId, 20);
        if (($feed['ok'] ?? false) !== true) {
            return $empty;
        }

        $views = [];
        $reach = [];
        $interactions = [];
        $checked = 0;

        foreach ($feed['posts'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (string) ($item['id'] ?? '');
            if ($id === '' || $id === $platformPostId) {
                continue;
            }

            $peer = match ($platform) {
                'instagram' => meta_fetch_instagram_media_insights($id, $token),
                'facebook' => meta_fetch_facebook_post_insights($id, $token),
                default => ['metrics' => [], 'error' => 'unsupported'],
            };

            if (!empty($peer['error'])) {
                continue;
            }

            $map = [];
            foreach ($peer['metrics'] ?? [] as $metric) {
                if (!is_array($metric)) {
                    continue;
                }
                $name = (string) ($metric['name'] ?? '');
                if ($name !== '' && is_numeric($metric['value'] ?? null)) {
                    $map[$name] = (float) $metric['value'];
                }
            }

            if ($platform === 'instagram') {
                if (isset($map['views']) || isset($map['total_views'])) {
                    $views[] = (float) ($map['views'] ?? $map['total_views']);
                }
                if (isset($map['reach'])) {
                    $reach[] = (float) $map['reach'];
                }
                if (isset($map['total_interactions'])) {
                    $interactions[] = (float) $map['total_interactions'];
                }
            } else {
                if (isset($map['post_impressions'])) {
                    $views[] = (float) $map['post_impressions'];
                }
                if (isset($map['post_impressions_unique'])) {
                    $reach[] = (float) $map['post_impressions_unique'];
                }
                if (isset($map['post_engaged_users'])) {
                    $interactions[] = (float) $map['post_engaged_users'];
                }
            }

            $checked++;
            if ($checked >= 10) {
                break;
            }
        }

        return [
            'views_median' => $this->median($views),
            'views_lower' => $this->percentile($views, 10),
            'views_upper' => $this->percentile($views, 90),
            'reach_median' => $this->median($reach),
            'interactions_median' => $this->median($interactions),
            'sample_size' => $checked,
            'views_samples' => array_values($views),
        ];
    }

    /**
     * @deprecated Kept temporarily unused; peer stats replace averages.
     * @return array{views: float|null, reach: float|null, interactions: float|null, sample_size: int}
     */
    private function computePeerInsightAverages(
        string $businessId,
        string $accountId,
        string $platform,
        string $platformPostId,
        string $token
    ): array {
        $stats = $this->computePeerInsightStats($businessId, $accountId, $platform, $platformPostId, $token);

        return [
            'views' => $stats['views_median'],
            'reach' => $stats['reach_median'],
            'interactions' => $stats['interactions_median'],
            'sample_size' => $stats['sample_size'],
        ];
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public function updateLivePost(string $businessId, string $accountId, string $platformPostId, string $caption): array
    {
        $account = $this->findOwnedAccount($businessId, $accountId);
        if ($account === null) {
            return ['ok' => false, 'error' => 'Account not found.'];
        }

        $result = $this->publisher->updateLivePost($account, $platformPostId, $caption);
        if (($result['ok'] ?? false) === true) {
            try {
                (new BusinessKnowledgeSyncService())->syncSocialSources($businessId);
            } catch (Throwable $e) {
                error_log('[SocialMedia] Knowledge sync after live update: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public function deleteLivePost(string $businessId, string $accountId, string $platformPostId): array
    {
        $account = $this->findOwnedAccount($businessId, $accountId);
        if ($account === null) {
            return ['ok' => false, 'error' => 'Account not found.'];
        }

        $result = $this->publisher->deleteLivePost($account, $platformPostId);
        if (($result['ok'] ?? false) === true) {
            try {
                (new BusinessKnowledgeSyncService())->syncSocialSources($businessId);
            } catch (Throwable $e) {
                error_log('[SocialMedia] Knowledge sync after live delete: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * @return array{ok: bool, post?: array<string, mixed>, error?: string}
     */
    public function publishPost(string $postId, string $businessId): array
    {
        $post = $this->posts->findById($postId);
        if ($post === null || (string) $post['business_id'] !== $businessId) {
            return ['ok' => false, 'error' => 'Post not found.'];
        }

        $business = RepositoryFactory::businesses()->findById($businessId);
        $userId = trim((string) ($business['user_id'] ?? ''));
        $tokenService = new TokenService();
        $publishCost = TokenCosts::SOCIAL_PUBLISH;

        $this->posts->update($postId, ['status' => 'publishing']);
        $targets = $this->posts->listTargets($postId);
        $anySuccess = false;
        $errors = [];

        foreach ($targets as $target) {
            $targetId = (string) ($target['id'] ?? '');
            $platformLabel = dash_social_feed_label((string) ($target['platform'] ?? ''));

            $account = RepositoryFactory::connectedAccounts()->findById((string) $target['connected_account_id']);
            if ($account === null || !(bool) ($account['is_active'] ?? false)) {
                $this->posts->updateTarget($targetId, [
                    'status' => 'failed',
                    'error_message' => 'Account disconnected.',
                ]);
                $errors[] = 'One account is disconnected.';
                continue;
            }

            if ($userId === '' || !$tokenService->canSpend($userId, $publishCost)) {
                $this->posts->updateTarget($targetId, [
                    'status' => 'failed',
                    'error_message' => TokenCosts::depletedMessage(),
                ]);
                $errors[] = $platformLabel . ': ' . TokenCosts::depletedMessage();
                continue;
            }

            if (!$tokenService->spend(
                $userId,
                $publishCost,
                'social_publish',
                'post_target',
                $targetId,
                ['business_id' => $businessId, 'post_id' => $postId, 'platform' => (string) ($target['platform'] ?? '')]
            )) {
                $this->posts->updateTarget($targetId, [
                    'status' => 'failed',
                    'error_message' => TokenCosts::depletedMessage(),
                ]);
                $errors[] = $platformLabel . ': ' . TokenCosts::depletedMessage();
                continue;
            }

            $this->posts->updateTarget($targetId, ['status' => 'publishing']);
            $publishPost = $post;
            $publishPost['media_items'] = $this->mediaItemsForPost($postId, $post);
            $publishPost = $this->applyTargetContentToPost($publishPost, $target);
            $result = (string) ($account['platform'] ?? '') === TikTokConnectService::PLATFORM
                ? $this->tiktokPublisher->publish($publishPost, $account)
                : $this->publisher->publish($publishPost, $account);

            if (($result['ok'] ?? false) === true) {
                $anySuccess = true;
                $this->posts->updateTarget($targetId, [
                    'status' => 'published',
                    'platform_post_id' => $result['platform_post_id'] ?? null,
                    'platform_permalink' => $result['platform_permalink'] ?? null,
                    'published_at' => date('Y-m-d H:i:s'),
                    'error_message' => null,
                ]);
            } else {
                $message = (string) ($result['error'] ?? 'Publish failed.');
                $errors[] = $platformLabel . ': ' . $message;
                $this->posts->updateTarget($targetId, [
                    'status' => 'failed',
                    'error_message' => $message,
                ]);
                if ($userId !== '') {
                    $tokenService->refund(
                        $userId,
                        $publishCost,
                        'social_publish_refund',
                        'post_target',
                        $targetId . ':refund',
                        ['business_id' => $businessId, 'post_id' => $postId, 'platform' => (string) ($target['platform'] ?? '')]
                    );
                }
            }
        }

        $finalStatus = $anySuccess ? 'published' : 'failed';

        $this->posts->update($postId, [
            'status' => $finalStatus,
            'published_at' => $anySuccess ? date('Y-m-d H:i:s') : null,
        ]);

        if ($finalStatus === 'published' && $errors === []) {
            $this->purgeStoredMediaForPost($postId, $post, true);
        }

        if (!$anySuccess) {
            return ['ok' => false, 'error' => implode(' ', array_unique($errors)) ?: 'Publish failed.'];
        }

        try {
            (new BusinessKnowledgeSyncService())->syncSocialSources($businessId);
        } catch (Throwable $e) {
            error_log('[SocialMedia] Knowledge sync after publish: ' . $e->getMessage());
        }

        return [
            'ok' => true,
            'post' => $this->formatPost($postId),
            'warnings' => $errors !== [] ? array_values(array_unique($errors)) : [],
        ];
    }

    public function deletePost(string $postId, string $businessId): array
    {
        $post = $this->posts->findById($postId);
        if ($post === null || (string) $post['business_id'] !== $businessId) {
            return ['ok' => false, 'error' => 'Post not found.'];
        }

        $status = (string) ($post['status'] ?? '');
        if (!in_array($status, ['draft', 'scheduled', 'failed', 'cancelled'], true)) {
            return ['ok' => false, 'error' => 'Published posts cannot be deleted from Lead Bird. Remove them on the social network.'];
        }

        $this->purgeStoredMediaForPost($postId, $post);
        $this->posts->delete($postId);

        return ['ok' => true];
    }

    public function cancelPost(string $postId, string $businessId): array
    {
        $post = $this->posts->findById($postId);
        if ($post === null || (string) $post['business_id'] !== $businessId) {
            return ['ok' => false, 'error' => 'Post not found.'];
        }

        if ((string) ($post['status'] ?? '') !== 'scheduled') {
            return ['ok' => false, 'error' => 'Only scheduled posts can be cancelled.'];
        }

        $this->posts->update($postId, ['status' => 'cancelled']);

        return ['ok' => true];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPosts(string $businessId, ?string $filter = null, ?string $accountId = null): array
    {
        $statusFilter = null;
        if ($filter !== null && $filter !== '' && $filter !== 'all') {
            $statusFilter = $filter;
        }

        $rows = $this->posts->listByBusinessId($businessId, $statusFilter, $accountId, 100);
        $formatted = [];

        foreach ($rows as $row) {
            $post = $this->formatPost((string) $row['id']);
            if ($post === []) {
                continue;
            }

            /*
             * Once every account a post went to is disconnected, we can no longer read
             * or manage it anywhere, so showing it leaves an untouchable ghost behind.
             */
            $targets = $post['targets'] ?? [];
            if ($targets !== [] && !$this->anyTargetAccountConnected($targets)) {
                continue;
            }

            $formatted[] = $post;
        }

        return $formatted;
    }

    /**
     * @param list<array<string, mixed>> $targets
     */
    private function anyTargetAccountConnected(array $targets): bool
    {
        foreach ($targets as $target) {
            if (($target['account_connected'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    public function processDuePosts(int $limit = 20): int
    {
        $due = $this->posts->listDueForPublishing($limit);
        $processed = 0;

        foreach ($due as $row) {
            $businessId = (string) ($row['business_id'] ?? '');
            $postId = (string) ($row['id'] ?? '');
            if ($businessId === '' || $postId === '') {
                continue;
            }

            $this->publishPost($postId, $businessId);
            $processed++;
        }

        return $processed;
    }

    /**
     * Purge media for stale drafts/failed posts and orphaned publish-now temp files.
     *
     * @return array{posts_purged: int, orphan_files_deleted: int}
     */
    public function cleanupStaleMedia(int $draftRetentionDays = 30, int $failedRetentionDays = 7): array
    {
        $postsPurged = 0;

        foreach ($this->posts->listPostsWithMediaOlderThan('draft', $draftRetentionDays) as $post) {
            $postId = (string) ($post['id'] ?? '');
            if ($postId === '') {
                continue;
            }
            $this->purgeStoredMediaForPost($postId, $post);
            $postsPurged++;
        }

        foreach (['failed', 'cancelled'] as $status) {
            foreach ($this->posts->listPostsWithMediaOlderThan($status, $failedRetentionDays) as $post) {
                $postId = (string) ($post['id'] ?? '');
                if ($postId === '') {
                    continue;
                }
                $this->purgeStoredMediaForPost($postId, $post);
                $postsPurged++;
            }
        }

        $orphansDeleted = $this->images->cleanupOrphanedEphemeralFiles($this->posts->listAllStoredMediaPaths());

        return [
            'posts_purged' => $postsPurged,
            'orphan_files_deleted' => $orphansDeleted,
        ];
    }

    /**
     * @param list<string> $accountIds
     * @return list<string>
     */
    private function filterValidAccounts(string $businessId, array $accountIds): array
    {
        $valid = [];
        foreach ($accountIds as $accountId) {
            $account = RepositoryFactory::connectedAccounts()->findById($accountId);
            if ($account === null) {
                continue;
            }
            if ((string) ($account['business_id'] ?? '') !== $businessId) {
                continue;
            }
            if (!(bool) ($account['is_active'] ?? false)) {
                continue;
            }
            $platform = (string) ($account['platform'] ?? '');
            if (!$this->isPublishablePlatform($platform)) {
                continue;
            }
            $valid[] = $accountId;
        }

        return array_values(array_unique($valid));
    }

    /** TikTok only counts as publishable while the integration is switched on. */
    private function isPublishablePlatform(string $platform): bool
    {
        if ($platform === 'tiktok') {
            return tiktok_is_enabled();
        }

        return in_array($platform, ['instagram', 'facebook'], true);
    }

    private function parseScheduleTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false || $timestamp <= time()) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    /** @return array<string, mixed> */
    private function formatPost(string $postId): array
    {
        $post = $this->posts->findById($postId);
        if ($post === null) {
            return [];
        }

        $targets = $this->posts->listTargets($postId);
        $imageService = $this->images;
        $mediaItems = $this->mediaItemsForPost($postId, $post);

        return [
            'source' => 'leadbird',
            'id' => (string) $post['id'],
            'caption' => (string) ($post['caption'] ?? ''),
            'hashtags' => (string) ($post['hashtags'] ?? ''),
            'media_type' => (string) ($post['media_type'] ?? 'text'),
            'media_url' => $imageService->publicUrl((string) ($post['media_url'] ?? '')),
            'thumbnail_url' => $imageService->publicUrl((string) ($post['thumbnail_url'] ?? '')),
            'media_items' => $mediaItems,
            'media_count' => count($mediaItems),
            'status' => (string) ($post['status'] ?? ''),
            'scheduled_at' => (string) ($post['scheduled_at'] ?? ''),
            'published_at' => (string) ($post['published_at'] ?? ''),
            'ai_assisted' => (bool) ($post['ai_assisted'] ?? false),
            'created_at' => (string) ($post['created_at'] ?? ''),
            'can_edit' => in_array((string) ($post['status'] ?? ''), ['draft', 'scheduled', 'failed'], true),
            'can_delete' => in_array((string) ($post['status'] ?? ''), ['draft', 'scheduled', 'failed', 'cancelled'], true),
            'targets' => array_map(function (array $target): array {
                return [
                    'id' => (string) $target['id'],
                    'connected_account_id' => (string) ($target['connected_account_id'] ?? ''),
                    'platform' => (string) ($target['platform'] ?? ''),
                    'platform_label' => dash_social_feed_label((string) ($target['platform'] ?? '')),
                    'post_format' => (string) ($target['post_format'] ?? 'post'),
                    'status' => (string) ($target['status'] ?? ''),
                    'platform_post_id' => (string) ($target['platform_post_id'] ?? ''),
                    'permalink' => (string) ($target['platform_permalink'] ?? ''),
                    'error' => (string) ($target['error_message'] ?? ''),
                    'account_connected' => $this->targetAccountIsConnected($target),
                ];
            }, $targets),
        ];
    }

    /**
     * @param array<string, mixed> $target
     */
    private function targetAccountIsConnected(array $target): bool
    {
        $accountId = (string) ($target['connected_account_id'] ?? '');
        if ($accountId === '') {
            return false;
        }

        $account = RepositoryFactory::connectedAccounts()->findById($accountId);

        return $account !== null && (bool) ($account['is_active'] ?? false);
    }

    /**
     * @param list<string> $mediaPaths
     * @param array<string, list<string>> $platformMediaPaths
     */
    private function validateMediaPaths(array $mediaPaths, array $platformMediaPaths = []): ?string
    {
        // Each channel carries its own attachments, so check the buckets separately —
        // a TikTok video plus an Instagram carousel is not one mixed carousel.
        if ($platformMediaPaths !== []) {
            foreach ($platformMediaPaths as $paths) {
                $error = $this->validateMediaBucket($paths);
                if ($error !== null) {
                    return $error;
                }
            }

            return null;
        }

        return $this->validateMediaBucket($mediaPaths);
    }

    /** @param list<string> $mediaPaths */
    private function validateMediaBucket(array $mediaPaths): ?string
    {
        if (count($mediaPaths) > 10) {
            return 'Carousels support up to 10 photos or videos.';
        }

        if (count($mediaPaths) > 1) {
            foreach ($mediaPaths as $path) {
                if ($this->images->mediaTypeFromStoredPath($path) === 'video') {
                    return 'Carousels use photos only. Add one video, or upload multiple images.';
                }
            }
        }

        return null;
    }

    /** @param list<string> $mediaPaths */
    private function resolvePostMediaType(array $mediaPaths): string
    {
        if ($mediaPaths === []) {
            return 'text';
        }

        if (count($mediaPaths) > 1) {
            return 'carousel';
        }

        return $this->images->mediaTypeFromStoredPath($mediaPaths[0]) ?? 'image';
    }

    /** @return list<string> */
    private function storedPathsForPost(array $post): array
    {
        $rows = $this->posts->listMedia((string) ($post['id'] ?? ''));
        if ($rows !== []) {
            return array_values(array_filter(array_map(
                static fn(array $row): string => trim((string) ($row['media_url'] ?? '')),
                $rows
            )));
        }

        $legacy = trim((string) ($post['media_url'] ?? ''));
        return $legacy !== '' ? [$legacy] : [];
    }

    /**
     * @param array<string, mixed> $post
     * @return list<array{media_type: string, media_url: ?string}>
     */
    private function mediaItemsForPost(string $postId, array $post): array
    {
        $rows = $this->posts->listMedia($postId);
        if ($rows !== []) {
            return array_map(function (array $row): array {
                return [
                    'media_type' => (string) ($row['media_type'] ?? 'image'),
                    'media_url' => $this->images->publicUrl((string) ($row['media_url'] ?? '')),
                ];
            }, $rows);
        }

        $legacy = trim((string) ($post['media_url'] ?? ''));
        if ($legacy === '') {
            return [];
        }

        return [[
            'media_type' => (string) ($post['media_type'] ?? 'image'),
            'media_url' => $this->images->publicUrl($legacy),
        ]];
    }

    /**
     * @param array<string, mixed> $post
     * @param bool $keepThumbnail Keep the poster frame so published cards still show a preview.
     */
    private function purgeStoredMediaForPost(string $postId, array $post, bool $keepThumbnail = false): void
    {
        foreach ($this->storedPathsForPost($post) as $path) {
            $this->images->deleteStoredPath($path);
        }

        $changes = [
            'media_url' => null,
            'media_storage_path' => null,
        ];

        if (!$keepThumbnail) {
            $this->images->deleteStoredPath((string) ($post['thumbnail_url'] ?? ''));
            $changes['thumbnail_url'] = null;
        }

        $this->posts->replaceMedia($postId, [], $this->images);
        $this->posts->update($postId, $changes);
    }

    private function purgeStoredMedia(string $postId, string $storedPath): void
    {
        $post = $this->posts->findById($postId);
        if ($post === null) {
            return;
        }

        $this->purgeStoredMediaForPost($postId, $post);
    }

    /** @return array<string, mixed>|null */
    private function findOwnedAccount(string $businessId, string $accountId): ?array
    {
        $account = RepositoryFactory::connectedAccounts()->findById($accountId);
        if ($account === null) {
            return null;
        }
        if ((string) ($account['business_id'] ?? '') !== $businessId) {
            return null;
        }
        if (!(bool) ($account['is_active'] ?? false)) {
            return null;
        }
        $platform = (string) ($account['platform'] ?? '');
        if (!$this->isPublishablePlatform($platform)) {
            return null;
        }

        return $account;
    }

    /**
     * @return array<string, array{caption: string, hashtags: string, post_type: string}>
     */
    public static function parsePlatformContentFromRequest(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        return (new self())->normalizePlatformContent($raw);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function parsePlatformMediaFromRequest(string $businessId, SocialMediaImageService $images, bool $ephemeral): array
    {
        $service = new self();
        $platformMedia = [];

        foreach (self::PUBLISHABLE_PLATFORMS as $platform) {
            $field = 'media_' . $platform;
            if (!isset($_FILES[$field])) {
                continue;
            }

            try {
                $uploaded = $images->storeMultipleUploads($businessId, $_FILES[$field], $ephemeral);
            } catch (InvalidArgumentException) {
                $uploaded = [];
            }

            if ($uploaded !== []) {
                $platformMedia[$platform] = $uploaded;
            }
        }

        return $service->normalizePlatformMediaPaths($platformMedia);
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, array{caption: string, hashtags: string, post_type: string}>
     */
    private function normalizePlatformContent(array $raw): array
    {
        $normalized = [];

        foreach (self::PUBLISHABLE_PLATFORMS as $platform) {
            if (!isset($raw[$platform]) || !is_array($raw[$platform])) {
                continue;
            }

            $item = $raw[$platform];
            $postType = trim((string) ($item['post_type'] ?? 'post'));
            if (!in_array($postType, ['post', 'story', 'reel'], true)) {
                $postType = 'post';
            }
            if ($platform === 'tiktok') {
                // TikTok only has one publish surface through the Content Posting API.
                $postType = 'post';
            }

            $entry = [
                'caption' => trim((string) ($item['caption'] ?? '')),
                'hashtags' => trim((string) ($item['hashtags'] ?? '')),
                'post_type' => $postType,
            ];

            if ($platform === 'tiktok') {
                $entry['options'] = $this->normalizeTikTokOptions($item);
            }

            $normalized[$platform] = $entry;
        }

        return $normalized;
    }

    /**
     * Interaction and audience settings the creator chose in the compose screen.
     *
     * @param array<string, mixed> $item
     * @return array{privacy_level: string, disable_comment: bool, disable_duet: bool, disable_stitch: bool}
     */
    private function normalizeTikTokOptions(array $item): array
    {
        $raw = is_array($item['options'] ?? null) ? $item['options'] : $item;
        $privacy = strtoupper(trim((string) ($raw['privacy_level'] ?? '')));
        $allowed = ['PUBLIC_TO_EVERYONE', 'MUTUAL_FOLLOW_FRIENDS', 'FOLLOWER_OF_CREATOR', 'SELF_ONLY'];

        return [
            'privacy_level' => in_array($privacy, $allowed, true) ? $privacy : '',
            'disable_comment' => $this->boolFromRequest($raw['disable_comment'] ?? false),
            'disable_duet' => $this->boolFromRequest($raw['disable_duet'] ?? false),
            'disable_stitch' => $this->boolFromRequest($raw['disable_stitch'] ?? false),
        ];
    }

    /**
     * The quick edit form only sends a caption, so keep publish settings the
     * creator already chose (TikTok audience, comments, duet, stitch).
     *
     * @param array<string, array<string, mixed>> $platformContent
     * @return array<string, array<string, mixed>>
     */
    private function carryOverTargetOptions(string $postId, array $platformContent): array
    {
        foreach ($this->posts->listTargets($postId) as $target) {
            $platform = (string) ($target['platform'] ?? '');
            if ($platform === '' || isset($platformContent[$platform]['options'])) {
                continue;
            }

            $decoded = json_decode((string) ($target['options'] ?? ''), true);
            if (!is_array($decoded) || $decoded === []) {
                continue;
            }

            if (!isset($platformContent[$platform])) {
                $platformContent[$platform] = [
                    'caption' => '',
                    'hashtags' => '',
                    'post_type' => (string) ($target['post_format'] ?? 'post'),
                ];
            }
            $platformContent[$platform]['options'] = $decoded;
        }

        return $platformContent;
    }

    private function boolFromRequest(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param list<string> $validAccountIds
     */
    private function validateComposeContent(
        string $caption,
        array $mediaPaths,
        array $platformContent,
        array $validAccountIds,
        array $platformMediaPaths = [],
        bool $requirePublishSettings = false
    ): ?string {
        $platformsNeeded = [];
        foreach ($validAccountIds as $accountId) {
            $account = RepositoryFactory::connectedAccounts()->findById($accountId);
            if ($account === null) {
                continue;
            }
            $platformsNeeded[(string) ($account['platform'] ?? '')] = true;
        }

        foreach (array_keys($platformsNeeded) as $platform) {
            if ($platform === '') {
                continue;
            }

            $platformMedia = is_array($platformMediaPaths[$platform] ?? null) ? $platformMediaPaths[$platform] : [];
            $hasMedia = $platformMedia !== [];
            if (!$hasMedia && $mediaPaths !== [] && count($platformsNeeded) === 1) {
                $hasMedia = true;
                $platformMedia = $mediaPaths;
            }

            $postType = trim((string) ($platformContent[$platform]['post_type'] ?? 'post'));
            if (!in_array($postType, ['post', 'story', 'reel'], true)) {
                $postType = 'post';
            }

            if ($platform === 'tiktok') {
                $tiktokError = $this->validateTikTokContent(
                    $platformMedia,
                    trim((string) ($platformContent['tiktok']['caption'] ?? $caption)),
                    is_array($platformContent['tiktok']['options'] ?? null) ? $platformContent['tiktok']['options'] : [],
                    $requirePublishSettings
                );
                if ($tiktokError !== null) {
                    return $tiktokError;
                }

                continue;
            }

            if ($postType === 'story' || $postType === 'reel') {
                if (!$hasMedia) {
                    return ucfirst($platform) . ' ' . ($postType === 'reel' ? 'reels' : 'stories') . ' need a photo or video.';
                }

                if (count($platformMedia) > 1) {
                    return ucfirst($platform) . ' ' . ($postType === 'reel' ? 'reels' : 'stories') . ' support one photo or video at a time.';
                }

                if ($postType === 'reel') {
                    $path = trim((string) ($platformMedia[0] ?? ''));
                    if ($path !== '' && $this->images->mediaTypeFromStoredPath($path) !== 'video') {
                        return ucfirst($platform) . ' reels require a video file.';
                    }
                }
            }

            $platformCaption = trim((string) ($platformContent[$platform]['caption'] ?? ''));
            if ($hasMedia || $platformCaption !== '') {
                continue;
            }

            if (count($platformsNeeded) === 1 && trim($caption) !== '') {
                continue;
            }

            return 'Write a caption or add media for each selected channel.';
        }

        return null;
    }

    /**
     * TikTok's Content Posting API publishes a single video — photos need a verified
     * URL prefix and a different endpoint, so they are not allowed here.
     *
     * @param list<string> $mediaPaths
     * @param array<string, mixed> $options
     */
    private function validateTikTokContent(
        array $mediaPaths,
        string $caption,
        array $options = [],
        bool $requirePublishSettings = false
    ): ?string {
        if ($mediaPaths === []) {
            return 'TikTok posts need a video. Add a video before publishing.';
        }

        if (count($mediaPaths) > 1) {
            return 'TikTok publishes one video at a time. Remove the extra files.';
        }

        if ($this->images->mediaTypeFromStoredPath($mediaPaths[0]) !== 'video') {
            return 'You can only publish videos to TikTok. Remove the photo and add a video.';
        }

        if (mb_strlen($caption) > 2200) {
            return 'TikTok captions are limited to 2,200 characters.';
        }

        // TikTok will not accept a post without an audience the creator chose.
        if ($requirePublishSettings && trim((string) ($options['privacy_level'] ?? '')) === '') {
            return 'Choose who can watch this video on TikTok.';
        }

        return null;
    }

    private function selectAccountsMessage(): string
    {
        return tiktok_is_enabled()
            ? 'Select at least one Instagram, Facebook, or TikTok account.'
            : 'Select at least one Instagram or Facebook account.';
    }

    /**
     * @param list<string> $validAccountIds
     */
    private function resolvePrimaryCaption(string $caption, array $platformContent, array $validAccountIds): string
    {
        $caption = trim($caption);
        if ($caption !== '') {
            return $caption;
        }

        foreach ($validAccountIds as $accountId) {
            $account = RepositoryFactory::connectedAccounts()->findById($accountId);
            if ($account === null) {
                continue;
            }

            $platform = (string) ($account['platform'] ?? '');
            $platformCaption = trim((string) ($platformContent[$platform]['caption'] ?? ''));
            if ($platformCaption !== '') {
                return $platformCaption;
            }
        }

        return '';
    }

    /**
     * @param array<string, array{caption: string, hashtags: string, post_type: string}> $platformContent
     */
    private function resolvePrimaryHashtags(?string $hashtags, array $platformContent): ?string
    {
        $hashtags = $hashtags !== null ? trim($hashtags) : '';
        if ($hashtags !== '') {
            return $hashtags;
        }

        foreach (self::PUBLISHABLE_PLATFORMS as $platform) {
            $platformHashtags = trim((string) ($platformContent[$platform]['hashtags'] ?? ''));
            if ($platformHashtags !== '') {
                return $platformHashtags;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $target
     * @return array<string, mixed>
     */
    private function applyTargetContentToPost(array $post, array $target): array
    {
        $targetCaption = trim((string) ($target['caption'] ?? ''));
        if ($targetCaption !== '') {
            $post['caption'] = $targetCaption;
        }

        if (array_key_exists('hashtags', $target) && $target['hashtags'] !== null) {
            $targetHashtags = trim((string) $target['hashtags']);
            $post['hashtags'] = $targetHashtags !== '' ? $targetHashtags : null;
        }

        $targetMedia = $this->posts->listTargetMedia((string) ($target['id'] ?? ''));
        if ($targetMedia !== []) {
            $post['media_items'] = array_map(function (array $row): array {
                return [
                    'media_type' => (string) ($row['media_type'] ?? 'image'),
                    'media_url' => $this->images->publicUrl((string) ($row['media_url'] ?? '')),
                ];
            }, $targetMedia);
            $post['media_type'] = count($targetMedia) > 1
                ? 'carousel'
                : ((string) ($targetMedia[0]['media_type'] ?? 'image'));
            $post['media_url'] = $post['media_items'][0]['media_url'] ?? null;
        }

        $postFormat = trim((string) ($target['post_format'] ?? 'post'));
        if (in_array($postFormat, ['post', 'story', 'reel'], true)) {
            $post['post_format'] = $postFormat;
        }

        $rawOptions = trim((string) ($target['options'] ?? ''));
        if ($rawOptions !== '') {
            $decoded = json_decode($rawOptions, true);
            if (is_array($decoded)) {
                $post['platform_options'] = $decoded;
            }
        }

        return $post;
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, list<string>>
     */
    private function normalizePlatformMediaPaths(array $raw): array
    {
        $normalized = [];

        foreach (self::PUBLISHABLE_PLATFORMS as $platform) {
            if (!isset($raw[$platform]) || !is_array($raw[$platform])) {
                continue;
            }

            $paths = array_values(array_filter(array_map(
                static fn(mixed $path): string => trim((string) $path),
                $raw[$platform]
            )));

            if ($paths !== []) {
                $normalized[$platform] = $paths;
            }
        }

        return $normalized;
    }

    /**
     * @param list<string> $mediaPaths
     * @param array<string, list<string>> $platformMediaPaths
     * @return list<string>
     */
    private function mergePlatformMediaPaths(array $mediaPaths, array $platformMediaPaths): array
    {
        $merged = array_values(array_filter(array_map('trim', $mediaPaths)));

        foreach ($platformMediaPaths as $paths) {
            foreach ($paths as $path) {
                $path = trim($path);
                if ($path !== '' && !in_array($path, $merged, true)) {
                    $merged[] = $path;
                }
            }
        }

        return $merged;
    }
}
