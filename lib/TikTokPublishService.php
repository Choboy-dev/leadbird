<?php

declare(strict_types=1);

require_once __DIR__ . '/TikTokConnectService.php';

/**
 * Publish videos to TikTok with the Content Posting API (Direct Post).
 *
 * TikTok only accepts videos here — photo posts need a verified URL prefix and a
 * different endpoint, so the compose UI keeps TikTok video-only.
 */
final class TikTokPublishService
{
    /** TikTok accepts a whole file in one chunk up to 64 MB. */
    private const SINGLE_CHUNK_MAX_BYTES = 64_000_000;

    private const TITLE_MAX_LENGTH = 2200;

    private const STATUS_POLL_ATTEMPTS = 10;
    private const STATUS_POLL_SLEEP_SECONDS = 3;

    private const PRIVACY_LEVELS = [
        'PUBLIC_TO_EVERYONE',
        'MUTUAL_FOLLOW_FRIENDS',
        'FOLLOWER_OF_CREATOR',
        'SELF_ONLY',
    ];

    private TikTokConnectService $connect;
    private SocialMediaImageService $images;

    public function __construct()
    {
        $this->connect = new TikTokConnectService();
        $this->images = new SocialMediaImageService();
    }

    /**
     * Creator settings that the compose screen must honour before a direct post
     * (privacy options, whether interactions are turned off, max video length).
     *
     * @param array<string, mixed> $account
     * @return array{ok: bool, creator?: array<string, mixed>, error?: string}
     */
    public function creatorInfo(array $account): array
    {
        $token = $this->connect->accessTokenForAccount($account);
        if ($token === '') {
            return ['ok' => false, 'error' => 'Your TikTok connection expired. Reconnect the account to continue.'];
        }

        $response = tiktok_api_post('post/publish/creator_info/query/', $token);
        $error = tiktok_error_message($response);
        if ($error !== '') {
            return ['ok' => false, 'error' => $this->humanize($error)];
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $privacyOptions = [];
        foreach (is_array($data['privacy_level_options'] ?? null) ? $data['privacy_level_options'] : [] as $option) {
            $option = strtoupper(trim((string) $option));
            if (in_array($option, self::PRIVACY_LEVELS, true)) {
                $privacyOptions[] = $option;
            }
        }

        return [
            'ok' => true,
            'creator' => [
                'username' => trim((string) ($data['creator_username'] ?? '')),
                'nickname' => trim((string) ($data['creator_nickname'] ?? '')),
                'avatar_url' => trim((string) ($data['creator_avatar_url'] ?? '')),
                'privacy_level_options' => $privacyOptions,
                'comment_disabled' => (bool) ($data['comment_disabled'] ?? false),
                'duet_disabled' => (bool) ($data['duet_disabled'] ?? false),
                'stitch_disabled' => (bool) ($data['stitch_disabled'] ?? false),
                'max_video_post_duration_sec' => (int) ($data['max_video_post_duration_sec'] ?? 0),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $account
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    public function publish(array $post, array $account): array
    {
        if (!tiktok_is_enabled()) {
            return ['ok' => false, 'error' => 'TikTok publishing is turned off right now.'];
        }

        $items = $this->videoItems($post);
        if ($items === []) {
            return ['ok' => false, 'error' => 'TikTok posts need a video. Add a video before publishing.'];
        }
        if (count($items) > 1) {
            return ['ok' => false, 'error' => 'TikTok publishes one video at a time.'];
        }

        $videoUrl = trim((string) ($items[0]['media_url'] ?? ''));
        if ($videoUrl === '') {
            return ['ok' => false, 'error' => 'TikTok posts need a video. Add a video before publishing.'];
        }

        $token = $this->connect->accessTokenForAccount($account);
        if ($token === '') {
            return ['ok' => false, 'error' => 'Your TikTok connection expired. Reconnect the account to continue.'];
        }

        $options = $this->normalizeOptions(is_array($post['platform_options'] ?? null) ? $post['platform_options'] : []);
        if ($options['privacy_level'] === '') {
            return [
                'ok' => false,
                'error' => 'Open this post and choose who can watch it on TikTok before publishing.',
            ];
        }

        $creator = $this->creatorInfo($account);
        if (($creator['ok'] ?? false) !== true) {
            return ['ok' => false, 'error' => (string) ($creator['error'] ?? 'TikTok rejected this post.')];
        }

        $creatorData = $creator['creator'] ?? [];
        $privacyLevel = $this->resolvePrivacyLevel($options['privacy_level'], $creatorData);
        if ($privacyLevel === '') {
            return [
                'ok' => false,
                'error' => 'TikTok no longer allows the audience you picked for this account. Open the post and choose a new one.',
            ];
        }

        $postInfo = [
            'title' => $this->buildTitle($post),
            'privacy_level' => $privacyLevel,
            'disable_comment' => $options['disable_comment'] || (bool) ($creatorData['comment_disabled'] ?? false),
            'disable_duet' => $options['disable_duet'] || (bool) ($creatorData['duet_disabled'] ?? false),
            'disable_stitch' => $options['disable_stitch'] || (bool) ($creatorData['stitch_disabled'] ?? false),
        ];

        $sourceInfo = $this->buildSourceInfo($videoUrl);
        if ($sourceInfo === null) {
            return ['ok' => false, 'error' => 'We could not read the video file for this post. Upload the video again.'];
        }

        tiktok_log(
            'publish start source=' . $sourceInfo['source']
            . ' bytes=' . $sourceInfo['size']
            . ' mime=' . ($sourceInfo['mime'] !== '' ? $sourceInfo['mime'] : '-')
            . ' privacy=' . $privacyLevel
        );

        $init = tiktok_api_post('post/publish/video/init/', $token, [
            'post_info' => $postInfo,
            'source_info' => $sourceInfo['payload'],
        ]);

        $error = tiktok_error_message($init);
        if ($error !== '') {
            $this->logFailure('init', $error, ['source' => $sourceInfo['source'], 'privacy' => $privacyLevel]);

            return ['ok' => false, 'error' => $this->humanize($error)];
        }

        $publishId = trim((string) ($init['data']['publish_id'] ?? ''));
        if ($publishId === '') {
            return ['ok' => false, 'error' => 'TikTok did not start this upload. Please try again.'];
        }

        if ($sourceInfo['source'] === 'FILE_UPLOAD') {
            $uploadUrl = trim((string) ($init['data']['upload_url'] ?? ''));
            if ($uploadUrl === '') {
                return ['ok' => false, 'error' => 'TikTok did not return an upload address. Please try again.'];
            }

            $uploadError = $this->uploadVideoFile($uploadUrl, $sourceInfo['absolute_path'], $sourceInfo['size'], $sourceInfo['mime']);
            if ($uploadError !== null) {
                return ['ok' => false, 'error' => $uploadError];
            }
        }

        return $this->awaitPublishResult($publishId, $token, $account);
    }

    /**
     * FILE_UPLOAD keeps us off TikTok's verified-domain requirement, so prefer it
     * whenever the video is on disk and small enough for a single chunk.
     *
     * @return array{source: string, payload: array<string, mixed>, absolute_path: string, size: int, mime: string}|null
     */
    private function buildSourceInfo(string $videoUrl): ?array
    {
        $storedPath = (string) ($this->images->storedPathFromPublicUrl($videoUrl) ?? '');
        $absolutePath = $storedPath !== '' ? (string) ($this->images->absolutePathFromUrl($storedPath) ?? '') : '';
        $size = $absolutePath !== '' && is_file($absolutePath) ? (int) filesize($absolutePath) : 0;

        if ($absolutePath !== '' && $size > 0 && $size <= self::SINGLE_CHUNK_MAX_BYTES) {
            return [
                'source' => 'FILE_UPLOAD',
                'payload' => [
                    'source' => 'FILE_UPLOAD',
                    'video_size' => $size,
                    'chunk_size' => $size,
                    'total_chunk_count' => 1,
                ],
                'absolute_path' => $absolutePath,
                'size' => $size,
                'mime' => $this->mimeForPath($absolutePath),
            ];
        }

        if (!str_starts_with($videoUrl, 'https://')) {
            return null;
        }

        return [
            'source' => 'PULL_FROM_URL',
            'payload' => [
                'source' => 'PULL_FROM_URL',
                'video_url' => $videoUrl,
            ],
            'absolute_path' => '',
            'size' => 0,
            'mime' => '',
        ];
    }

    /** Single-chunk PUT of the whole file to the issued upload URL. */
    private function uploadVideoFile(string $uploadUrl, string $absolutePath, int $size, string $mime): ?string
    {
        $handle = fopen($absolutePath, 'rb');
        if ($handle === false) {
            return 'We could not read the video file for this post. Upload the video again.';
        }

        $ch = curl_init($uploadUrl);
        if ($ch === false) {
            fclose($handle);

            return 'Could not reach TikTok to upload the video.';
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_UPLOAD => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_INFILE => $handle,
            CURLOPT_INFILESIZE => $size,
            CURLOPT_HTTPHEADER => [
                'Content-Type: ' . $mime,
                'Content-Length: ' . $size,
                'Content-Range: bytes 0-' . ($size - 1) . '/' . $size,
            ],
            CURLOPT_TIMEOUT => 600,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($handle);

        if ($status >= 200 && $status < 300) {
            return null;
        }

        tiktok_log('video upload failed HTTP ' . $status . ' curl=' . $curlError . ' body=' . mb_substr(is_string($response) ? $response : '', 0, 400));

        return 'TikTok could not accept this video file. Try a shorter MP4 export.';
    }

    /**
     * @param array<string, mixed> $account
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    private function awaitPublishResult(string $publishId, string $token, array $account): array
    {
        $lastStatus = '';

        for ($attempt = 0; $attempt < self::STATUS_POLL_ATTEMPTS; $attempt++) {
            $response = tiktok_api_post('post/publish/status/fetch/', $token, ['publish_id' => $publishId]);
            $error = tiktok_error_message($response);
            if ($error !== '') {
                $this->logFailure('status_fetch', $error, ['publish_id' => $publishId]);

                return ['ok' => false, 'error' => $this->humanize($error)];
            }

            $data = is_array($response['data'] ?? null) ? $response['data'] : [];
            $lastStatus = strtoupper(trim((string) ($data['status'] ?? '')));

            if ($lastStatus === 'PUBLISH_COMPLETE') {
                $postIds = is_array($data['publicaly_available_post_id'] ?? null)
                    ? $data['publicaly_available_post_id']
                    : (is_array($data['publicly_available_post_id'] ?? null) ? $data['publicly_available_post_id'] : []);
                $postId = trim((string) ($postIds[0] ?? ''));

                return [
                    'ok' => true,
                    'platform_post_id' => $postId !== '' ? $postId : $publishId,
                    'platform_permalink' => $this->permalink($account, $postId),
                ];
            }

            if ($lastStatus === 'FAILED') {
                $reason = trim((string) ($data['fail_reason'] ?? ''));
                $this->logFailure('publish_failed', $reason, ['publish_id' => $publishId]);

                return ['ok' => false, 'error' => $this->humanize($reason !== '' ? $reason : 'TikTok could not process this video.')];
            }

            if ($attempt < self::STATUS_POLL_ATTEMPTS - 1) {
                sleep(self::STATUS_POLL_SLEEP_SECONDS);
            }
        }

        // TikTok keeps processing after we stop waiting; treat it as sent so we do not double-post.
        return [
            'ok' => true,
            'platform_post_id' => $publishId,
            'platform_permalink' => $this->permalink($account, ''),
        ];
    }

    /**
     * The humanizer drops TikTok's machine code, which leaves nothing to debug a
     * rejection with. Record the raw text before it is rewritten for the creator.
     *
     * @param array<string, string|int> $context
     */
    private function logFailure(string $stage, string $rawError, array $context = []): void
    {
        $parts = [];
        foreach ($context as $key => $value) {
            $parts[] = $key . '=' . $value;
        }

        tiktok_log(
            'publish ' . $stage . ' rejected: ' . ($rawError !== '' ? $rawError : '(empty)')
            . ($parts !== [] ? ' ' . implode(' ', $parts) : '')
        );
    }

    /**
     * Sandbox and unaudited apps post privately, so surface that plainly.
     */
    private function humanize(string $message): string
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'url_ownership_unverified')) {
            return 'TikTok needs the Lead Bird domain verified before it can pull this video. Verify leadbird.app under URL properties in the TikTok developer portal.';
        }

        if (str_contains($lower, 'unaudited_client_can_only_post_to_private_accounts')) {
            return 'TikTok has not audited this app yet, so it only allows private posts. Set the TikTok account to private in the TikTok app, pick “Only me” as the audience here, then post again. Public posting unlocks after TikTok approves the app.';
        }

        if (str_contains($lower, 'privacy_level_option_mismatch')) {
            return 'TikTok rejected this post because the account privacy settings changed. Open TikTok, then publish again.';
        }

        if (str_contains($lower, 'spam_risk_too_many_posts')) {
            return 'TikTok has hit its daily post limit for this account. Try again tomorrow.';
        }

        if (str_contains($lower, 'spam_risk_user_banned_from_posting')) {
            return 'TikTok has blocked this account from posting.';
        }

        if (str_contains($lower, 'reached_active_user_cap')) {
            return 'TikTok has hit the daily publishing limit for the Lead Bird app. Try again tomorrow.';
        }

        if (str_contains($lower, 'video_pull_failed') || str_contains($lower, 'file_format_check_failed')) {
            return 'TikTok could not read this video. Export it as an MP4 (H.264) and try again.';
        }

        if (str_contains($lower, 'duration_check_failed')) {
            return 'This video is longer than TikTok allows for this account. Trim it and try again.';
        }

        if (str_contains($lower, 'frame_rate_check_failed') || str_contains($lower, 'picture_size_check_failed')) {
            return 'TikTok rejected this video’s resolution or frame rate. Re-export it and try again.';
        }

        return tiktok_humanize_error($message);
    }

    /**
     * @param array<string, mixed> $account
     */
    private function permalink(array $account, string $postId): string
    {
        $handle = ltrim(trim((string) ($account['display_name'] ?? '')), '@');
        if ($handle === '' || str_contains($handle, ' ')) {
            return 'https://www.tiktok.com/';
        }

        if ($postId === '') {
            return 'https://www.tiktok.com/@' . rawurlencode($handle);
        }

        return 'https://www.tiktok.com/@' . rawurlencode($handle) . '/video/' . rawurlencode($postId);
    }

    /**
     * @param array<string, mixed> $post
     * @return list<array{media_type: string, media_url: string}>
     */
    private function videoItems(array $post): array
    {
        $items = [];

        foreach (is_array($post['media_items'] ?? null) ? $post['media_items'] : [] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $url = trim((string) ($item['media_url'] ?? ''));
            if ($url === '') {
                continue;
            }

            $items[] = [
                'media_type' => (string) ($item['media_type'] ?? 'image'),
                'media_url' => $url,
            ];
        }

        if ($items === []) {
            $legacy = trim((string) ($post['media_url'] ?? ''));
            if ($legacy !== '') {
                $items[] = [
                    'media_type' => (string) ($post['media_type'] ?? 'image'),
                    'media_url' => str_starts_with($legacy, 'http')
                        ? $legacy
                        : (string) ($this->images->publicUrl($legacy) ?? ''),
                ];
            }
        }

        return array_values(array_filter(
            $items,
            static fn(array $item): bool => $item['media_type'] === 'video' && $item['media_url'] !== ''
        ));
    }

    /** @param array<string, mixed> $post */
    private function buildTitle(array $post): string
    {
        $caption = trim((string) ($post['caption'] ?? ''));
        $hashtags = trim((string) ($post['hashtags'] ?? ''));
        $title = $hashtags !== '' ? trim($caption . "\n\n" . $hashtags) : $caption;

        return mb_substr($title, 0, self::TITLE_MAX_LENGTH);
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{privacy_level: string, disable_comment: bool, disable_duet: bool, disable_stitch: bool}
     */
    private function normalizeOptions(array $raw): array
    {
        $privacy = strtoupper(trim((string) ($raw['privacy_level'] ?? '')));

        return [
            'privacy_level' => in_array($privacy, self::PRIVACY_LEVELS, true) ? $privacy : '',
            'disable_comment' => (bool) ($raw['disable_comment'] ?? false),
            'disable_duet' => (bool) ($raw['disable_duet'] ?? false),
            'disable_stitch' => (bool) ($raw['disable_stitch'] ?? false),
        ];
    }

    /**
     * TikTok rejects any privacy level it did not offer for this creator.
     *
     * @param array<string, mixed> $creator
     */
    private function resolvePrivacyLevel(string $requested, array $creator): string
    {
        $allowed = is_array($creator['privacy_level_options'] ?? null) ? $creator['privacy_level_options'] : [];
        $allowed = array_values(array_filter(array_map(
            static fn(mixed $value): string => strtoupper(trim((string) $value)),
            $allowed
        )));

        if ($allowed === []) {
            return $requested;
        }

        if ($requested !== '' && in_array($requested, $allowed, true)) {
            return $requested;
        }

        /*
         * The creator's account settings changed since they picked. Only ever fall
         * back to the most private option — never widen the audience for them.
         */
        if (in_array('SELF_ONLY', $allowed, true)) {
            return 'SELF_ONLY';
        }

        return '';
    }

    private function mimeForPath(string $absolutePath): string
    {
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'mov' => 'video/quicktime',
            'webm' => 'video/webm',
            default => 'video/mp4',
        };
    }
}
