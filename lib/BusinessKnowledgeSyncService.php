<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';

/**
 * Builds searchable knowledge chunks from business settings, catalog, and social posts.
 */
final class BusinessKnowledgeSyncService
{
    private const MANUAL_STALE_SECONDS = 3600;
    private const SOCIAL_STALE_SECONDS = 86400;
    private const LINK_STALE_SECONDS = 86400;
    private const LEARNING_STALE_SECONDS = 604800;

    public function syncIfStale(string $businessId): void
    {
        $state = RepositoryFactory::businessKnowledge()->getSyncState($businessId);
        $manualStale = $this->isStale($state['last_manual_sync_at'] ?? null, self::MANUAL_STALE_SECONDS);
        $socialStale = $this->isStale($state['last_social_sync_at'] ?? null, self::SOCIAL_STALE_SECONDS);
        $linkStale = $this->isStale($state['last_social_sync_at'] ?? null, self::LINK_STALE_SECONDS);
        $learningStale = $this->isStale($state['last_learning_sync_at'] ?? null, self::LEARNING_STALE_SECONDS);

        if ($manualStale) {
            $this->syncManualSources($businessId);
        }

        if ($socialStale) {
            $this->syncSocialSources($businessId);
        }

        if ($linkStale) {
            (new KnowledgeLinkService())->autoLinkSocialPosts($businessId);
        }

        if ($learningStale && !(new OpenAiBudgetService())->isExceeded($businessId)) {
            (new ConversationLearningService())->learnFromConversations($businessId);
        }
    }

    /**
     * @return array{manual: int, social: int, links: int, learned: int}
     */
    public function syncAll(string $businessId): array
    {
        return [
            'manual' => $this->syncManualSources($businessId),
            'social' => $this->syncSocialSources($businessId),
            'links' => (new KnowledgeLinkService())->autoLinkSocialPosts($businessId),
            'learned' => (new ConversationLearningService())->learnFromConversations($businessId),
        ];
    }

    public function syncManualSources(string $businessId): int
    {
        $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId);
        if ($settings === null) {
            $settings = RepositoryFactory::businessSettings()->create($businessId);
        }

        $inserted = 0;
        $inserted += $this->syncProfileChunks($businessId, $settings);
        $inserted += $this->syncFaqChunks($businessId, $settings);
        $inserted += $this->syncPolicyChunks($businessId, $settings);
        $inserted += $this->syncOfferingChunks($businessId);

        RepositoryFactory::businessKnowledge()->touchManualSync($businessId);
        (new KnowledgeEmbeddingService())->embedBusinessChunks($businessId);

        return $inserted;
    }

    public function syncSocialSources(string $businessId): int
    {
        try {
            $chunks = [];
            $accounts = RepositoryFactory::connectedAccounts()->listByBusinessId($businessId, true);

            foreach ($accounts as $account) {
                $platform = (string) ($account['platform'] ?? '');
                $token = (string) ($account['access_token'] ?? '');
                $accountId = (string) ($account['platform_account_id'] ?? '');

                if ($token === '' || $accountId === '') {
                    continue;
                }

                if ($platform === 'instagram') {
                    foreach (meta_fetch_instagram_media($accountId, $token, 30) as $post) {
                        $chunks[] = $this->socialChunkFromPost($businessId, 'instagram', $post);
                    }
                } elseif ($platform === 'facebook') {
                    foreach (meta_fetch_facebook_posts($accountId, $token, 30) as $post) {
                        $chunks[] = $this->socialChunkFromPost($businessId, 'facebook', $post);
                    }
                }
            }

            $count = RepositoryFactory::businessKnowledge()->replaceSourceTypeChunks($businessId, 'social_post', $chunks);
            RepositoryFactory::businessKnowledge()->touchSocialSync(
                $businessId,
                $count,
                $count > 0 ? 'ok' : 'empty',
                null
            );
            (new KnowledgeEmbeddingService())->embedBusinessChunks($businessId);

            return $count;
        } catch (Throwable $e) {
            RepositoryFactory::businessKnowledge()->touchSocialSync($businessId, 0, 'error', $e->getMessage());
            error_log('[KnowledgeSync] Social sync failed for ' . $businessId . ': ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function syncProfileChunks(string $businessId, array $settings): int
    {
        $business = RepositoryFactory::businesses()->findById($businessId);
        $description = trim((string) ($settings['business_description'] ?? ''));
        $chunks = [];

        if ($business !== null) {
            $name = trim((string) ($business['business_name'] ?? ''));
            if ($name !== '') {
                $chunks[] = [
                    'source_id' => 'business_name',
                    'title' => 'Business name',
                    'content' => $name,
                    'keywords' => $name,
                ];
            }
        }

        if ($description !== '') {
            $chunks[] = [
                'source_id' => 'business_description',
                'title' => 'About the business',
                'content' => $description,
                'keywords' => $description,
            ];
        }

        return RepositoryFactory::businessKnowledge()->replaceSourceTypeChunks($businessId, 'profile', $chunks);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function syncFaqChunks(string $businessId, array $settings): int
    {
        $chunks = [];
        foreach (faq_entries_parse(isset($settings['faq']) ? (string) $settings['faq'] : null) as $index => $entry) {
            $question = trim((string) ($entry['q'] ?? ''));
            $answer = faq_entry_answer_text($entry);
            if ($question === '' || $answer === '') {
                continue;
            }

            $entryId = trim((string) ($entry['id'] ?? ''));
            $chunks[] = [
                'source_id' => $entryId !== '' ? $entryId : 'faq_' . $index,
                'title' => $question,
                'content' => 'Q: ' . $question . "\nA: " . $answer,
                'keywords' => $question . ' ' . $answer,
            ];
        }

        return RepositoryFactory::businessKnowledge()->replaceSourceTypeChunks($businessId, 'faq', $chunks);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function syncPolicyChunks(string $businessId, array $settings): int
    {
        $policies = [
            'delivery' => trim((string) ($settings['delivery_policy'] ?? '')),
            'returns' => trim((string) ($settings['return_policy'] ?? '')),
            'payment' => trim((string) ($settings['payment_instructions'] ?? '')),
        ];

        $chunks = [];
        foreach ($policies as $key => $content) {
            if ($content === '') {
                continue;
            }

            $title = match ($key) {
                'delivery' => 'Delivery policy',
                'returns' => 'Return and refund policy',
                default => 'Payment instructions',
            };

            $chunks[] = [
                'source_id' => $key,
                'title' => $title,
                'content' => $content,
                'keywords' => $title . ' ' . $content,
            ];
        }

        return RepositoryFactory::businessKnowledge()->replaceSourceTypeChunks($businessId, 'policy', $chunks);
    }

    private function syncOfferingChunks(string $businessId): int
    {
        $chunks = [];

        foreach (RepositoryFactory::offerings()->listByBusinessId($businessId, true) as $offering) {
            $offeringId = (string) ($offering['id'] ?? '');
            $name = trim((string) ($offering['name'] ?? ''));
            if ($offeringId === '' || $name === '') {
                continue;
            }

            $price = OfferingTypeRegistry::formatPrice($offering);
            $description = trim((string) ($offering['description'] ?? ''));
            $type = (string) ($offering['offering_type'] ?? 'offer');
            $billing = (string) ($offering['billing_type'] ?? 'one_time');
            $imageUrl = offering_public_image_url((string) ($offering['image_url'] ?? ''));
            $actionUrl = OfferingTypeRegistry::actionUrl($offering);

            $content = OfferingTypeRegistry::knowledgeContent($offering);

            $chunks[] = [
                'source_id' => $offeringId,
                'title' => $name,
                'content' => $content,
                'keywords' => $name . ' ' . $description . ' ' . $type . ' ' . $price . ' ' . ($actionUrl ?? ''),
                'image_url' => $imageUrl,
                'metadata' => [
                    'offering_id' => $offeringId,
                    'offering_type' => $type,
                    'price' => $offering['price'],
                    'is_free' => !empty($offering['is_free']),
                    'action_url' => $actionUrl,
                    'ai_collect' => OfferingTypeRegistry::aiCollectFields($type),
                    'ai_provide' => OfferingTypeRegistry::aiProvideFields($type),
                ],
            ];
        }

        return RepositoryFactory::businessKnowledge()->replaceSourceTypeChunks($businessId, 'offering', $chunks);
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function socialChunkFromPost(string $businessId, string $platform, array $post): array
    {
        $postId = (string) ($post['id'] ?? '');
        $caption = trim((string) ($post['caption'] ?? $post['message'] ?? ''));
        $imageUrl = trim((string) ($post['image_url'] ?? ''));
        $permalink = trim((string) ($post['permalink'] ?? ''));
        $mediaType = trim((string) ($post['media_type'] ?? 'post'));

        $title = $platform === 'instagram' ? 'Instagram post' : 'Facebook post';
        $contentParts = [$title];
        if ($caption !== '') {
            $contentParts[] = $caption;
        }
        if ($permalink !== '') {
            $contentParts[] = 'Link: ' . $permalink;
        }

        return [
            'source_id' => $platform . ':' . $postId,
            'title' => $caption !== '' ? knowledge_excerpt($caption, 80) : $title,
            'content' => implode("\n", $contentParts),
            'keywords' => $caption . ' ' . $platform . ' ' . $mediaType,
            'image_url' => $imageUrl !== '' ? $imageUrl : null,
            'metadata' => [
                'platform' => $platform,
                'post_id' => $postId,
                'permalink' => $permalink,
                'media_type' => $mediaType,
            ],
        ];
    }

    private function isStale(?string $timestamp, int $maxAgeSeconds): bool
    {
        if ($timestamp === null || trim($timestamp) === '') {
            return true;
        }

        $parsed = strtotime($timestamp);
        if ($parsed === false) {
            return true;
        }

        return (time() - $parsed) >= $maxAgeSeconds;
    }
}
