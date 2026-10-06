<?php

declare(strict_types=1);

final class BusinessKnowledgeRepository extends BaseRepository
{
    /** @return list<array<string, mixed>> */
    public function listBySourceType(string $businessId, string $sourceType, int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT * FROM business_knowledge_chunks
             WHERE business_id = :business_id AND source_type = :source_type AND active = 1
             ORDER BY updated_at DESC
             LIMIT ' . max(1, min(100, $limit)),
            ['business_id' => $businessId, 'source_type' => $sourceType]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listRecentDocuments(int $limit = 10): array
    {
        return $this->fetchAll(
            'SELECT c.*, b.business_name
             FROM business_knowledge_chunks c
             INNER JOIN businesses b ON b.id = c.business_id
             WHERE c.source_type = :source_type AND c.active = 1
             ORDER BY c.updated_at DESC
             LIMIT ' . max(1, min(20, $limit)),
            ['source_type' => 'document']
        );
    }

    /** @return list<array<string, mixed>> */
    public function listLearnedMemory(string $businessId, int $limit = 10): array
    {
        return $this->listBySourceType($businessId, 'learned_memory', $limit);
    }

    public function deactivateChunk(string $chunkId, string $businessId): bool
    {
        return $this->execute(
            'UPDATE business_knowledge_chunks SET active = 0, updated_at = NOW()
             WHERE id = :id AND business_id = :business_id AND source_type = \'learned_memory\'',
            ['id' => $chunkId, 'business_id' => $businessId]
        );
    }

    public function touchLinkSync(string $businessId, int $linkedCount): void
    {
        $this->execute(
            'INSERT INTO business_knowledge_sync (business_id, linked_post_count)
             VALUES (:business_id, :linked_post_count)
             ON DUPLICATE KEY UPDATE
                linked_post_count = VALUES(linked_post_count),
                updated_at = NOW()',
            ['business_id' => $businessId, 'linked_post_count' => $linkedCount]
        );
    }

    public function touchLearningSync(string $businessId, int $memoryCount): void
    {
        $this->execute(
            'INSERT INTO business_knowledge_sync (business_id, last_learning_sync_at, learning_memory_count)
             VALUES (:business_id, NOW(), :learning_memory_count)
             ON DUPLICATE KEY UPDATE
                last_learning_sync_at = NOW(),
                learning_memory_count = VALUES(learning_memory_count),
                updated_at = NOW()',
            ['business_id' => $businessId, 'learning_memory_count' => $memoryCount]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listSampleByBusinessId(string $businessId, int $limit = 5): array
    {
        return $this->fetchAll(
            'SELECT id, source_type, title, content, image_url, updated_at
             FROM business_knowledge_chunks
             WHERE business_id = :business_id AND active = 1
             ORDER BY updated_at DESC
             LIMIT ' . max(1, min(20, $limit)),
            ['business_id' => $businessId]
        );
    }

    /**
     * @return array<string, int>
     */
    public function countBySourceType(string $businessId): array
    {
        $rows = $this->fetchAll(
            'SELECT source_type, COUNT(*) AS cnt
             FROM business_knowledge_chunks
             WHERE business_id = :business_id AND active = 1
             GROUP BY source_type',
            ['business_id' => $businessId]
        );

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) ($row['source_type'] ?? '')] = (int) ($row['cnt'] ?? 0);
        }

        return $counts;
    }

    public function countEmbedded(string $businessId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM business_knowledge_chunks
             WHERE business_id = :business_id AND active = 1
               AND embedding_json IS NOT NULL AND embedding_json != ""',
            ['business_id' => $businessId]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function updateEmbedding(string $chunkId, string $embeddingJson): void
    {
        $this->execute(
            'UPDATE business_knowledge_chunks SET embedding_json = :embedding_json, updated_at = NOW() WHERE id = :id',
            ['id' => $chunkId, 'embedding_json' => $embeddingJson]
        );
    }

    public function touchEmbeddingSync(string $businessId, int $embeddedCount): void
    {
        $this->execute(
            'INSERT INTO business_knowledge_sync (business_id, last_embedding_sync_at, embedded_chunk_count)
             VALUES (:business_id, NOW(), :embedded_chunk_count)
             ON DUPLICATE KEY UPDATE
                last_embedding_sync_at = NOW(),
                embedded_chunk_count = VALUES(embedded_chunk_count),
                updated_at = NOW()',
            [
                'business_id' => $businessId,
                'embedded_chunk_count' => $embeddedCount,
            ]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listActiveByBusinessId(string $businessId): array
    {
        return $this->fetchAll(
            'SELECT * FROM business_knowledge_chunks
             WHERE business_id = :business_id AND active = 1
             ORDER BY updated_at DESC',
            ['business_id' => $businessId]
        );
    }

    public function deleteBySourceType(string $businessId, string $sourceType): void
    {
        $this->execute(
            'DELETE FROM business_knowledge_chunks
             WHERE business_id = :business_id AND source_type = :source_type',
            ['business_id' => $businessId, 'source_type' => $sourceType]
        );
    }

    public function deleteBySource(string $businessId, string $sourceType, string $sourceId): void
    {
        $this->execute(
            'DELETE FROM business_knowledge_chunks
             WHERE business_id = :business_id AND source_type = :source_type AND source_id = :source_id',
            [
                'business_id' => $businessId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]
        );
    }

    /** @param list<string> $conversationIds */
    public function deleteLearnedMemoryForConversations(string $businessId, array $conversationIds): void
    {
        if ($conversationIds === []) {
            return;
        }

        $lookup = array_fill_keys($conversationIds, true);
        foreach ($this->listBySourceType($businessId, 'learned_memory', 500) as $chunk) {
            $metadataRaw = trim((string) ($chunk['metadata'] ?? ''));
            if ($metadataRaw === '') {
                continue;
            }

            $metadata = json_decode($metadataRaw, true);
            if (!is_array($metadata)) {
                continue;
            }

            $conversationId = trim((string) ($metadata['conversation_id'] ?? ''));
            if ($conversationId === '' || !isset($lookup[$conversationId])) {
                continue;
            }

            $this->deleteById((string) ($chunk['id'] ?? ''), $businessId);
        }
    }

    public function findById(string $chunkId, string $businessId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM business_knowledge_chunks WHERE id = :id AND business_id = :business_id LIMIT 1',
            ['id' => $chunkId, 'business_id' => $businessId]
        );
    }

    public function findActiveDocumentById(string $chunkId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM business_knowledge_chunks
             WHERE id = :id AND source_type = \'document\' AND active = 1
             LIMIT 1',
            ['id' => $chunkId]
        );
    }

    public function findBySource(string $businessId, string $sourceType, string $sourceId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM business_knowledge_chunks
             WHERE business_id = :business_id AND source_type = :source_type AND source_id = :source_id AND active = 1
             LIMIT 1',
            [
                'business_id' => $businessId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]
        );
    }

    public function deleteById(string $chunkId, string $businessId): bool
    {
        return $this->execute(
            'DELETE FROM business_knowledge_chunks WHERE id = :id AND business_id = :business_id',
            ['id' => $chunkId, 'business_id' => $businessId]
        );
    }

    /**
     * @param array<string, mixed> $chunk
     */
    public function updateChunkById(string $chunkId, string $businessId, array $chunk): void
    {
        $content = trim((string) ($chunk['content'] ?? ''));
        if ($content === '') {
            throw new InvalidArgumentException('Knowledge content cannot be empty.');
        }

        $this->execute(
            'UPDATE business_knowledge_chunks
             SET source_id = :source_id, title = :title, content = :content, keywords = :keywords,
                 image_url = :image_url, metadata = :metadata, embedding_json = NULL, active = 1, updated_at = NOW()
             WHERE id = :id AND business_id = :business_id',
            [
                'id' => $chunkId,
                'business_id' => $businessId,
                'source_id' => (string) ($chunk['source_id'] ?? ''),
                'title' => $chunk['title'] ?? null,
                'content' => $content,
                'keywords' => $chunk['keywords'] ?? null,
                'image_url' => $chunk['image_url'] ?? null,
                'metadata' => isset($chunk['metadata']) ? json_encode($chunk['metadata'], JSON_THROW_ON_ERROR) : null,
            ]
        );
    }

    /**
     * Insert or replace a single knowledge chunk (used for links, documents, custom notes).
     *
     * @param array<string, mixed> $chunk
     */
    public function upsertChunk(string $businessId, string $sourceType, array $chunk): string
    {
        $sourceId = trim((string) ($chunk['source_id'] ?? ''));
        if ($sourceId === '') {
            $sourceId = Uuid::v4();
        }

        $content = trim((string) ($chunk['content'] ?? ''));
        if ($content === '') {
            throw new InvalidArgumentException('Knowledge content cannot be empty.');
        }

        $existing = $this->fetchOne(
            'SELECT id, embedding_json, content FROM business_knowledge_chunks
             WHERE business_id = :business_id AND source_type = :source_type AND source_id = :source_id
             LIMIT 1',
            [
                'business_id' => $businessId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]
        );

        $embeddingJson = null;
        if (is_array($existing)) {
            $oldHash = hash('sha256', trim((string) ($existing['content'] ?? '')));
            $newHash = hash('sha256', $content);
            if ($oldHash === $newHash) {
                $embeddingJson = trim((string) ($existing['embedding_json'] ?? '')) ?: null;
            }
            $chunkId = (string) $existing['id'];
            $this->execute(
                'UPDATE business_knowledge_chunks
                 SET title = :title, content = :content, keywords = :keywords,
                     image_url = :image_url, metadata = :metadata, embedding_json = :embedding_json,
                     active = 1, updated_at = NOW()
                 WHERE id = :id AND business_id = :business_id',
                [
                    'id' => $chunkId,
                    'business_id' => $businessId,
                    'title' => $chunk['title'] ?? null,
                    'content' => $content,
                    'keywords' => $chunk['keywords'] ?? null,
                    'image_url' => $chunk['image_url'] ?? null,
                    'metadata' => isset($chunk['metadata']) ? json_encode($chunk['metadata'], JSON_THROW_ON_ERROR) : null,
                    'embedding_json' => $embeddingJson,
                ]
            );

            return $chunkId;
        }

        $chunkId = Uuid::v4();
        $this->execute(
            'INSERT INTO business_knowledge_chunks (
                id, business_id, source_type, source_id, title, content, keywords, image_url, metadata, embedding_json, active
            ) VALUES (
                :id, :business_id, :source_type, :source_id, :title, :content, :keywords, :image_url, :metadata, :embedding_json, 1
            )',
            [
                'id' => $chunkId,
                'business_id' => $businessId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'title' => $chunk['title'] ?? null,
                'content' => $content,
                'keywords' => $chunk['keywords'] ?? null,
                'image_url' => $chunk['image_url'] ?? null,
                'metadata' => isset($chunk['metadata']) ? json_encode($chunk['metadata'], JSON_THROW_ON_ERROR) : null,
                'embedding_json' => null,
            ]
        );

        return $chunkId;
    }

    /**
     * @param list<array<string, mixed>> $chunks
     */
    public function replaceSourceTypeChunks(string $businessId, string $sourceType, array $chunks): int
    {
        $existingBySource = [];
        foreach ($this->listBySourceType($businessId, $sourceType, 200) as $row) {
            $sourceId = trim((string) ($row['source_id'] ?? ''));
            if ($sourceId === '') {
                continue;
            }
            $existingBySource[$sourceId] = [
                'content_hash' => hash('sha256', trim((string) ($row['content'] ?? ''))),
                'embedding_json' => trim((string) ($row['embedding_json'] ?? '')),
            ];
        }

        $this->db->beginTransaction();

        try {
            $this->deleteBySourceType($businessId, $sourceType);
            $inserted = 0;

            foreach ($chunks as $chunk) {
                $content = trim((string) ($chunk['content'] ?? ''));
                if ($content === '') {
                    continue;
                }

                $sourceId = trim((string) ($chunk['source_id'] ?? ''));
                $contentHash = hash('sha256', $content);
                $embeddingJson = null;
                if ($sourceId !== ''
                    && isset($existingBySource[$sourceId])
                    && $existingBySource[$sourceId]['content_hash'] === $contentHash
                    && $existingBySource[$sourceId]['embedding_json'] !== '') {
                    $embeddingJson = $existingBySource[$sourceId]['embedding_json'];
                }

                $id = Uuid::v4();
                $this->execute(
                    'INSERT INTO business_knowledge_chunks (
                        id, business_id, source_type, source_id, title, content, keywords, image_url, metadata, embedding_json, active
                    ) VALUES (
                        :id, :business_id, :source_type, :source_id, :title, :content, :keywords, :image_url, :metadata, :embedding_json, 1
                    )',
                    [
                        'id' => $id,
                        'business_id' => $businessId,
                        'source_type' => $sourceType,
                        'source_id' => $chunk['source_id'] ?? null,
                        'title' => $chunk['title'] ?? null,
                        'content' => $content,
                        'keywords' => $chunk['keywords'] ?? null,
                        'image_url' => $chunk['image_url'] ?? null,
                        'metadata' => isset($chunk['metadata']) ? json_encode($chunk['metadata'], JSON_THROW_ON_ERROR) : null,
                        'embedding_json' => $embeddingJson,
                    ]
                );
                $inserted++;
            }

            $this->db->commit();

            return $inserted;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getSyncState(string $businessId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM business_knowledge_sync WHERE business_id = :business_id LIMIT 1',
            ['business_id' => $businessId]
        );
    }

    public function touchManualSync(string $businessId): void
    {
        $this->execute(
            'INSERT INTO business_knowledge_sync (business_id, last_manual_sync_at, social_sync_status)
             VALUES (:business_id, NOW(), "idle")
             ON DUPLICATE KEY UPDATE last_manual_sync_at = NOW(), updated_at = NOW()',
            ['business_id' => $businessId]
        );
    }

    public function touchSocialSync(string $businessId, int $postCount, string $status, ?string $error = null): void
    {
        $this->execute(
            'INSERT INTO business_knowledge_sync (
                business_id, last_social_sync_at, social_sync_status, social_post_count, last_error
            ) VALUES (
                :business_id, NOW(), :status, :post_count, :last_error
            )
            ON DUPLICATE KEY UPDATE
                last_social_sync_at = NOW(),
                social_sync_status = VALUES(social_sync_status),
                social_post_count = VALUES(social_post_count),
                last_error = VALUES(last_error),
                updated_at = NOW()',
            [
                'business_id' => $businessId,
                'status' => $status,
                'post_count' => $postCount,
                'last_error' => $error,
            ]
        );
    }
}
