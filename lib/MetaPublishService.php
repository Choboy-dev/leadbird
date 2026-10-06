<?php

declare(strict_types=1);

/**
 * Publish posts to Instagram and Facebook via Meta Graph API.
 */
final class MetaPublishService
{
    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $account
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    public function publish(array $post, array $account): array
    {
        $platform = (string) ($account['platform'] ?? '');

        return match ($platform) {
            'instagram' => $this->publishInstagram($post, $account),
            'facebook' => $this->publishFacebook($post, $account),
            default => ['ok' => false, 'error' => 'This channel does not support feed posts. Choose Instagram or Facebook.'],
        };
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $account
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    private function publishInstagram(array $post, array $account): array
    {
        $igId = (string) ($account['platform_account_id'] ?? '');
        $token = (string) ($account['access_token'] ?? '');
        $items = $this->resolveMediaItems($post);
        $caption = $this->buildCaption($post);
        $format = $this->resolvePostFormat($post);

        if ($igId === '' || $token === '') {
            return ['ok' => false, 'error' => 'Instagram account is not connected properly.'];
        }

        if ($items === []) {
            return ['ok' => false, 'error' => 'Instagram posts need a photo or video. Add media before publishing.'];
        }

        if ($format === 'story') {
            if (count($items) > 1) {
                return ['ok' => false, 'error' => 'Instagram stories support one photo or video at a time.'];
            }

            return $this->publishInstagramStory($igId, $token, $items[0], $caption);
        }

        if ($format === 'reel') {
            if (count($items) > 1) {
                return ['ok' => false, 'error' => 'Instagram reels support one video at a time.'];
            }

            $item = $items[0];
            if ((string) ($item['media_type'] ?? 'image') !== 'video') {
                return ['ok' => false, 'error' => 'Instagram reels require a video file.'];
            }

            $videoUrl = (string) ($item['media_url'] ?? '');
            if ($videoUrl === '') {
                return ['ok' => false, 'error' => 'Instagram reels need a video. Add media before publishing.'];
            }

            return $this->publishInstagramReel($igId, $token, $videoUrl, $caption);
        }

        if (count($items) >= 2) {
            return $this->publishInstagramCarousel($igId, $token, $items, $caption);
        }

        $item = $items[0];
        $mediaUrl = (string) ($item['media_url'] ?? '');
        $mediaType = (string) ($item['media_type'] ?? 'image');

        if ($mediaUrl === '') {
            return ['ok' => false, 'error' => 'Instagram feed posts need a photo or video. Add media before publishing.'];
        }

        if ($mediaType === 'video') {
            return $this->publishInstagramVideo($igId, $token, $mediaUrl, $caption);
        }

        return $this->publishInstagramImage($igId, $token, $mediaUrl, $caption);
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $account
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    private function publishFacebook(array $post, array $account): array
    {
        $pageId = (string) ($account['platform_account_id'] ?? '');
        $token = (string) ($account['access_token'] ?? '');
        $items = $this->resolveMediaItems($post);
        $caption = $this->buildCaption($post);
        $format = $this->resolvePostFormat($post);

        if ($pageId === '' || $token === '') {
            return ['ok' => false, 'error' => 'Facebook Page is not connected properly.'];
        }

        if ($format === 'story') {
            if ($items === []) {
                return ['ok' => false, 'error' => 'Facebook stories need a photo or video. Add media before publishing.'];
            }

            if (count($items) > 1) {
                return ['ok' => false, 'error' => 'Facebook stories support one photo or video at a time.'];
            }

            return $this->publishFacebookStory($pageId, $token, $items[0], $caption);
        }

        if ($format === 'reel') {
            if ($items === []) {
                return ['ok' => false, 'error' => 'Facebook reels need a video. Add media before publishing.'];
            }

            if (count($items) > 1) {
                return ['ok' => false, 'error' => 'Facebook reels support one video at a time.'];
            }

            $item = $items[0];
            if ((string) ($item['media_type'] ?? 'image') !== 'video') {
                return ['ok' => false, 'error' => 'Facebook reels require a video file.'];
            }

            $videoUrl = (string) ($item['media_url'] ?? '');
            if ($videoUrl === '') {
                return ['ok' => false, 'error' => 'Facebook reels need a video. Add media before publishing.'];
            }

            return $this->publishFacebookReel($pageId, $token, $videoUrl, $caption);
        }

        if (count($items) >= 2) {
            return $this->publishFacebookCarousel($pageId, $token, $items, $caption);
        }

        $mediaService = new SocialMediaImageService();
        $mediaUrl = $items !== [] ? (string) ($items[0]['media_url'] ?? '') : $mediaService->publicUrl((string) ($post['media_url'] ?? ''));
        $mediaType = $items !== [] ? (string) ($items[0]['media_type'] ?? 'image') : (string) ($post['media_type'] ?? 'image');

        if ($mediaUrl !== null && $mediaUrl !== '' && $mediaType === 'video') {
            $result = meta_graph_post_json($pageId . '/videos', $token, [
                'file_url' => $mediaUrl,
                'description' => $caption,
            ]);
        } elseif ($mediaUrl !== null && $mediaUrl !== '') {
            $result = meta_graph_post_json($pageId . '/photos', $token, [
                'url' => $mediaUrl,
                'caption' => $caption,
            ]);
        } else {
            if (trim($caption) === '') {
                return ['ok' => false, 'error' => 'Write a caption before publishing.'];
            }

            $result = meta_graph_post_json($pageId . '/feed', $token, [
                'message' => $caption,
            ]);
        }

        $postId = (string) ($result['id'] ?? $result['post_id'] ?? '');
        if ($postId === '') {
            $raw = (string) ($result['error']['message'] ?? 'Facebook publish failed. Reconnect with publish permissions.');

            return [
                'ok' => false,
                'error' => meta_humanize_publish_error($raw, 'facebook'),
            ];
        }

        $permalinkData = meta_graph_get($postId, $token, ['fields' => 'permalink_url']);
        $permalink = (string) ($permalinkData['permalink_url'] ?? '');

        return [
            'ok' => true,
            'platform_post_id' => $postId,
            'platform_permalink' => $permalink !== '' ? $permalink : null,
        ];
    }

    /**
     * @param array<string, mixed> $post
     */
    private function buildCaption(array $post): string
    {
        $caption = trim((string) ($post['caption'] ?? ''));
        $hashtags = trim((string) ($post['hashtags'] ?? ''));

        if ($hashtags === '') {
            return $caption;
        }

        if ($caption === '') {
            return $hashtags;
        }

        return $caption . "\n\n" . $hashtags;
    }

    /**
     * @param array<string, mixed> $post
     */
    private function resolvePostFormat(array $post): string
    {
        $format = trim((string) ($post['post_format'] ?? 'post'));

        return in_array($format, ['post', 'story', 'reel'], true) ? $format : 'post';
    }

    /**
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    private function publishInstagramImage(string $igId, string $token, string $imageUrl, string $caption): array
    {
        return $this->publishInstagramContainer($igId, $token, [
            'image_url' => $imageUrl,
            'caption' => $caption,
        ], false);
    }

    /**
     * @param array{media_type?: string, media_url?: ?string} $item
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    private function publishInstagramStory(string $igId, string $token, array $item, string $caption = ''): array
    {
        $mediaUrl = $this->prepareStoryMediaUrl($item, $caption);
        $mediaType = (string) ($item['media_type'] ?? 'image');

        if ($mediaUrl === '') {
            return ['ok' => false, 'error' => 'Instagram stories need a photo or video.'];
        }

        $params = ['media_type' => 'STORIES'];
        if ($mediaType === 'video') {
            $params['video_url'] = $mediaUrl;
        } else {
            $params['image_url'] = $mediaUrl;
        }

        return $this->publishInstagramContainer($igId, $token, $params, $mediaType === 'video');
    }

    /**
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    private function publishInstagramReel(string $igId, string $token, string $videoUrl, string $caption): array
    {
        return $this->publishInstagramContainer($igId, $token, [
            'media_type' => 'REELS',
            'video_url' => $videoUrl,
            'caption' => $caption,
        ], true);
    }

    /**
     * @param array<string, mixed> $containerParams
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    private function publishInstagramContainer(string $igId, string $token, array $containerParams, bool $longVideoWait): array
    {
        $container = meta_graph_post_json($igId . '/media', $token, $containerParams);

        $creationId = (string) ($container['id'] ?? '');
        if ($creationId === '') {
            return [
                'ok' => false,
                'error' => (string) ($container['error']['message'] ?? 'Could not prepare Instagram media. Reconnect with publish permissions.'),
            ];
        }

        $ready = $this->waitForInstagramContainer(
            $creationId,
            $token,
            'Instagram could not process this media.',
            $longVideoWait ? 12 : 5
        );
        if (($ready['ok'] ?? false) !== true) {
            return $ready;
        }

        $published = meta_graph_post_json($igId . '/media_publish', $token, [
            'creation_id' => $creationId,
        ]);

        $mediaId = (string) ($published['id'] ?? '');
        if ($mediaId === '') {
            return [
                'ok' => false,
                'error' => (string) ($published['error']['message'] ?? 'Instagram publish failed. Check your account permissions.'),
            ];
        }

        $permalinkData = meta_graph_get($mediaId, $token, ['fields' => 'permalink']);
        $permalink = (string) ($permalinkData['permalink'] ?? '');

        return [
            'ok' => true,
            'platform_post_id' => $mediaId,
            'platform_permalink' => $permalink !== '' ? $permalink : null,
        ];
    }

    /**
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    private function publishInstagramVideo(string $igId, string $token, string $videoUrl, string $caption): array
    {
        return $this->publishInstagramContainer($igId, $token, [
            'media_type' => 'VIDEO',
            'video_url' => $videoUrl,
            'caption' => $caption,
        ], true);
    }

    /**
     * @param array{media_type?: string, media_url?: ?string} $item
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    private function publishFacebookStory(string $pageId, string $token, array $item, string $caption = ''): array
    {
        $mediaUrl = $this->prepareStoryMediaUrl($item, $caption);
        $mediaType = (string) ($item['media_type'] ?? 'image');

        if ($mediaUrl === '') {
            return ['ok' => false, 'error' => 'Facebook stories need a photo or video.'];
        }

        if ($mediaType === 'video') {
            return $this->publishFacebookHostedVideo($pageId, $token, 'video_stories', $mediaUrl);
        }

        $upload = meta_graph_post_json($pageId . '/photos', $token, [
            'url' => $mediaUrl,
            'published' => false,
        ]);

        $photoId = (string) ($upload['id'] ?? '');
        if ($photoId === '') {
            $raw = (string) ($upload['error']['message'] ?? 'Could not upload the story photo to Facebook.');

            return [
                'ok' => false,
                'error' => meta_humanize_publish_error($raw, 'facebook'),
            ];
        }

        $result = meta_graph_post_json($pageId . '/photo_stories', $token, [
            'photo_id' => $photoId,
        ]);

        $postId = (string) ($result['id'] ?? $result['post_id'] ?? $photoId);
        if (isset($result['error']) && ($result['success'] ?? false) !== true && $postId === $photoId) {
            $raw = (string) ($result['error']['message'] ?? 'Facebook story publish failed.');

            return [
                'ok' => false,
                'error' => meta_humanize_publish_error($raw, 'facebook'),
            ];
        }

        return [
            'ok' => true,
            'platform_post_id' => $postId,
            'platform_permalink' => null,
        ];
    }

    /**
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    private function publishFacebookReel(string $pageId, string $token, string $videoUrl, string $caption): array
    {
        return $this->publishFacebookHostedVideo($pageId, $token, 'video_reels', $videoUrl, $caption);
    }

    /**
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    private function publishFacebookHostedVideo(
        string $pageId,
        string $token,
        string $endpoint,
        string $videoUrl,
        string $description = ''
    ): array {
        $start = meta_graph_post_json($pageId . '/' . $endpoint, $token, [
            'upload_phase' => 'start',
        ]);

        $videoId = (string) ($start['video_id'] ?? '');
        if ($videoId === '') {
            $raw = (string) ($start['error']['message'] ?? 'Could not start Facebook video upload.');

            return [
                'ok' => false,
                'error' => meta_humanize_publish_error($raw, 'facebook'),
            ];
        }

        $upload = meta_rupload_video_from_url($videoId, $token, $videoUrl);
        if (($upload['success'] ?? false) !== true) {
            $raw = (string) ($upload['error']['message'] ?? 'Facebook could not upload this video.');

            return [
                'ok' => false,
                'error' => meta_humanize_publish_error($raw, 'facebook'),
            ];
        }

        $finishParams = [
            'upload_phase' => 'finish',
            'video_id' => $videoId,
            'video_state' => 'PUBLISHED',
        ];

        if (trim($description) !== '' && $endpoint === 'video_reels') {
            $finishParams['description'] = trim($description);
        }

        $finish = meta_graph_post_query($pageId . '/' . $endpoint, $token, $finishParams);
        if (($finish['success'] ?? false) !== true) {
            $raw = (string) ($finish['error']['message'] ?? 'Facebook video publish failed.');

            return [
                'ok' => false,
                'error' => meta_humanize_publish_error($raw, 'facebook'),
            ];
        }

        $permalinkData = meta_graph_get($videoId, $token, ['fields' => 'permalink_url']);
        $permalink = (string) ($permalinkData['permalink_url'] ?? '');

        return [
            'ok' => true,
            'platform_post_id' => $videoId,
            'platform_permalink' => $permalink !== '' ? $permalink : null,
        ];
    }

    /**
     * @param list<array{media_type?: string, media_url?: ?string}> $items
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    private function publishInstagramCarousel(string $igId, string $token, array $items, string $caption): array
    {
        $childIds = [];

        foreach ($items as $item) {
            $imageUrl = trim((string) ($item['media_url'] ?? ''));
            if ($imageUrl === '') {
                continue;
            }

            $container = meta_graph_post_json($igId . '/media', $token, [
                'image_url' => $imageUrl,
                'is_carousel_item' => true,
            ]);

            $childId = (string) ($container['id'] ?? '');
            if ($childId === '') {
                return [
                    'ok' => false,
                    'error' => (string) ($container['error']['message'] ?? 'Could not prepare one of the carousel photos.'),
                ];
            }

            $ready = $this->waitForInstagramContainer($childId, $token, 'Instagram could not process one of the carousel photos.');
            if (($ready['ok'] ?? false) !== true) {
                return $ready;
            }

            $childIds[] = $childId;
        }

        if (count($childIds) < 2) {
            return ['ok' => false, 'error' => 'Add at least two photos for a carousel post.'];
        }

        $carousel = meta_graph_post_json($igId . '/media', $token, [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $childIds),
            'caption' => $caption,
        ]);

        $creationId = (string) ($carousel['id'] ?? '');
        if ($creationId === '') {
            return [
                'ok' => false,
                'error' => (string) ($carousel['error']['message'] ?? 'Could not prepare Instagram carousel.'),
            ];
        }

        $ready = $this->waitForInstagramContainer($creationId, $token, 'Instagram could not process this carousel.');
        if (($ready['ok'] ?? false) !== true) {
            return $ready;
        }

        $published = meta_graph_post_json($igId . '/media_publish', $token, [
            'creation_id' => $creationId,
        ]);

        $mediaId = (string) ($published['id'] ?? '');
        if ($mediaId === '') {
            return [
                'ok' => false,
                'error' => (string) ($published['error']['message'] ?? 'Instagram carousel publish failed.'),
            ];
        }

        $permalinkData = meta_graph_get($mediaId, $token, ['fields' => 'permalink']);
        $permalink = (string) ($permalinkData['permalink'] ?? '');

        return [
            'ok' => true,
            'platform_post_id' => $mediaId,
            'platform_permalink' => $permalink !== '' ? $permalink : null,
        ];
    }

    /**
     * @param list<array{media_type?: string, media_url?: ?string}> $items
     * @return array{ok: bool, platform_post_id?: string, platform_permalink?: string, error?: string}
     */
    private function publishFacebookCarousel(string $pageId, string $token, array $items, string $caption): array
    {
        $attached = [];

        foreach ($items as $item) {
            $imageUrl = trim((string) ($item['media_url'] ?? ''));
            if ($imageUrl === '') {
                continue;
            }

            $upload = meta_graph_post_json($pageId . '/photos', $token, [
                'url' => $imageUrl,
                'published' => false,
            ]);

            $photoId = (string) ($upload['id'] ?? '');
            if ($photoId === '') {
                $raw = (string) ($upload['error']['message'] ?? 'Could not upload one of the carousel photos to Facebook.');

                return [
                    'ok' => false,
                    'error' => meta_humanize_publish_error($raw, 'facebook'),
                ];
            }

            $attached[] = ['media_fbid' => $photoId];
        }

        if (count($attached) < 2) {
            return ['ok' => false, 'error' => 'Add at least two photos for a carousel post.'];
        }

        $result = meta_graph_post_json($pageId . '/feed', $token, [
            'message' => $caption,
            'attached_media' => $attached,
        ]);

        $postId = (string) ($result['id'] ?? '');
        if ($postId === '') {
            $raw = (string) ($result['error']['message'] ?? 'Facebook carousel publish failed.');

            return [
                'ok' => false,
                'error' => meta_humanize_publish_error($raw, 'facebook'),
            ];
        }

        $permalinkData = meta_graph_get($postId, $token, ['fields' => 'permalink_url']);
        $permalink = (string) ($permalinkData['permalink_url'] ?? '');

        return [
            'ok' => true,
            'platform_post_id' => $postId,
            'platform_permalink' => $permalink !== '' ? $permalink : null,
        ];
    }

    /**
     * @param array{media_type?: string, media_url?: ?string} $item
     */
    private function prepareStoryMediaUrl(array $item, string $caption): string
    {
        $mediaUrl = trim((string) ($item['media_url'] ?? ''));
        $caption = trim($caption);
        if ($mediaUrl === '' || $caption === '' || (string) ($item['media_type'] ?? 'image') === 'video') {
            return $mediaUrl;
        }

        $images = new SocialMediaImageService();
        $overlaid = $images->applyStoryCaptionToPublicUrl($mediaUrl, $caption);

        return $overlaid ?? $mediaUrl;
    }

    /**
     * @param array<string, mixed> $post
     * @return list<array{media_type: string, media_url: ?string}>
     */
    private function resolveMediaItems(array $post): array
    {
        $items = $post['media_items'] ?? null;
        if (is_array($items) && $items !== []) {
            return array_values(array_filter(array_map(static function ($item): ?array {
                if (!is_array($item)) {
                    return null;
                }

                $url = trim((string) ($item['media_url'] ?? ''));
                if ($url === '') {
                    return null;
                }

                return [
                    'media_type' => (string) ($item['media_type'] ?? 'image'),
                    'media_url' => $url,
                ];
            }, $items)));
        }

        $mediaService = new SocialMediaImageService();
        $mediaUrl = $mediaService->publicUrl((string) ($post['media_url'] ?? ''));
        if ($mediaUrl === null || $mediaUrl === '') {
            return [];
        }

        return [[
            'media_type' => (string) ($post['media_type'] ?? 'image'),
            'media_url' => $mediaUrl,
        ]];
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    private function waitForInstagramContainer(
        string $creationId,
        string $token,
        string $errorMessage,
        int $maxAttempts = 8
    ): array {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            sleep($attempt < 2 ? ($maxAttempts > 8 ? 3 : 2) : ($maxAttempts > 8 ? 5 : 3));
            $status = meta_graph_get($creationId, $token, ['fields' => 'status_code']);
            $code = (string) ($status['status_code'] ?? '');
            if ($code === 'FINISHED' || $code === '') {
                return ['ok' => true];
            }
            if ($code === 'ERROR') {
                return ['ok' => false, 'error' => $errorMessage];
            }
        }

        return ['ok' => true];
    }

    /**
     * Update caption on a live Facebook Page post (feed only — not Messenger).
     *
     * @param array<string, mixed> $account
     * @return array{ok: bool, error?: string}
     */
    public function updateLivePost(array $account, string $platformPostId, string $caption): array
    {
        $platform = (string) ($account['platform'] ?? '');
        $token = (string) ($account['access_token'] ?? '');
        $caption = trim($caption);

        if ($platformPostId === '' || $token === '') {
            return ['ok' => false, 'error' => 'Post or account is missing.'];
        }

        if ($caption === '') {
            return ['ok' => false, 'error' => 'Write a caption before saving.'];
        }

        if ($platform === 'facebook') {
            $result = meta_graph_post_json($platformPostId, $token, ['message' => $caption]);

            if (isset($result['error'])) {
                return ['ok' => false, 'error' => (string) ($result['error']['message'] ?? 'Could not update this Page post.')];
            }

            return ['ok' => true];
        }

        if ($platform === 'instagram') {
            return [
                'ok' => false,
                'error' => 'Instagram does not allow editing captions after publish. Delete the post and create a new one.',
            ];
        }

        return ['ok' => false, 'error' => 'This channel does not support feed post updates.'];
    }

    /**
     * Delete a live post from Facebook Page wall or Instagram feed.
     *
     * @param array<string, mixed> $account
     * @return array{ok: bool, error?: string}
     */
    public function deleteLivePost(array $account, string $platformPostId): array
    {
        $platform = (string) ($account['platform'] ?? '');
        $token = (string) ($account['access_token'] ?? '');

        if ($platformPostId === '' || $token === '') {
            return ['ok' => false, 'error' => 'Post or account is missing.'];
        }

        if (!in_array($platform, ['instagram', 'facebook'], true)) {
            return ['ok' => false, 'error' => 'This channel does not support deleting feed posts here.'];
        }

        $result = meta_graph_delete($platformPostId, $token);

        if (isset($result['error']) && ($result['success'] ?? false) !== true) {
            return ['ok' => false, 'error' => (string) ($result['error']['message'] ?? 'Could not delete this post on the social network.')];
        }

        return ['ok' => true];
    }
}
