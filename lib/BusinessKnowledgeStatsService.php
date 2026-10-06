<?php

declare(strict_types=1);

/**
 * Dashboard stats for the AI knowledge engine.
 */
final class BusinessKnowledgeStatsService
{
    /**
     * @return array<string, mixed>
     */
    public function getDashboardStats(string $businessId): array
    {
        $counts = RepositoryFactory::businessKnowledge()->countBySourceType($businessId);
        $sync = RepositoryFactory::businessKnowledge()->getSyncState($businessId) ?? [];
        $total = array_sum($counts);
        $embedded = (int) ($sync['embedded_chunk_count'] ?? 0);

        return [
            'total_chunks' => $total,
            'counts' => $counts,
            'social_posts' => (int) ($counts['social_post'] ?? 0),
            'offerings' => (int) ($counts['offering'] ?? 0),
            'faqs' => (int) ($counts['faq'] ?? 0),
            'policies' => (int) ($counts['policy'] ?? 0),
            'profile' => (int) ($counts['profile'] ?? 0),
            'learned_memory' => (int) ($counts['learned_memory'] ?? 0),
            'linked_posts' => RepositoryFactory::knowledgeLinks()->countByBusinessId($businessId),
            'last_manual_sync_at' => $sync['last_manual_sync_at'] ?? null,
            'last_social_sync_at' => $sync['last_social_sync_at'] ?? null,
            'last_embedding_sync_at' => $sync['last_embedding_sync_at'] ?? null,
            'last_learning_sync_at' => $sync['last_learning_sync_at'] ?? null,
            'social_sync_status' => (string) ($sync['social_sync_status'] ?? 'idle'),
            'social_post_count' => (int) ($sync['social_post_count'] ?? 0),
            'embedded_chunk_count' => $embedded,
            'learning_memory_count' => (int) ($sync['learning_memory_count'] ?? 0),
            'linked_post_count' => (int) ($sync['linked_post_count'] ?? 0),
            'semantic_search_ready' => $embedded > 0 && $total > 0,
            'memory_ready' => ((int) ($counts['learned_memory'] ?? 0)) > 0 || RepositoryFactory::knowledgeLinks()->countByBusinessId($businessId) > 0,
            'sample_chunks' => RepositoryFactory::businessKnowledge()->listSampleByBusinessId($businessId, 5),
            'learned_memories' => RepositoryFactory::businessKnowledge()->listLearnedMemory($businessId, 6),
            'post_links' => RepositoryFactory::knowledgeLinks()->listByBusinessId($businessId, 6),
        ];
    }
}
