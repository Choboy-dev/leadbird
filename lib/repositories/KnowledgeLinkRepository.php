<?php

declare(strict_types=1);

final class KnowledgeLinkRepository extends BaseRepository
{
    public function deleteByBusinessId(string $businessId): void
    {
        $this->execute('DELETE FROM business_knowledge_links WHERE business_id = :business_id', [
            'business_id' => $businessId,
        ]);
    }

    public function upsertLink(
        string $businessId,
        string $socialChunkId,
        string $offeringId,
        float $matchScore,
        string $linkSource = 'auto'
    ): void {
        $existing = $this->fetchOne(
            'SELECT id FROM business_knowledge_links
             WHERE social_chunk_id = :social_chunk_id AND offering_id = :offering_id LIMIT 1',
            ['social_chunk_id' => $socialChunkId, 'offering_id' => $offeringId]
        );

        if ($existing !== null) {
            $this->execute(
                'UPDATE business_knowledge_links
                 SET match_score = :match_score, link_source = :link_source
                 WHERE id = :id',
                [
                    'id' => $existing['id'],
                    'match_score' => $matchScore,
                    'link_source' => $linkSource,
                ]
            );

            return;
        }

        $this->execute(
            'INSERT INTO business_knowledge_links (
                id, business_id, social_chunk_id, offering_id, match_score, link_source
            ) VALUES (
                :id, :business_id, :social_chunk_id, :offering_id, :match_score, :link_source
            )',
            [
                'id' => Uuid::v4(),
                'business_id' => $businessId,
                'social_chunk_id' => $socialChunkId,
                'offering_id' => $offeringId,
                'match_score' => $matchScore,
                'link_source' => $linkSource,
            ]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listByBusinessId(string $businessId, int $limit = 20): array
    {
        return $this->fetchAll(
            'SELECT l.*, c.title AS post_title, c.content AS post_content, c.image_url AS post_image_url,
                    o.name AS offering_name, o.price AS offering_price, o.image_url AS offering_image_url
             FROM business_knowledge_links l
             INNER JOIN business_knowledge_chunks c ON c.id = l.social_chunk_id
             INNER JOIN offerings o ON o.id = l.offering_id
             WHERE l.business_id = :business_id
             ORDER BY l.match_score DESC, l.created_at DESC
             LIMIT ' . max(1, min(50, $limit)),
            ['business_id' => $businessId]
        );
    }

    public function countByBusinessId(string $businessId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM business_knowledge_links WHERE business_id = :business_id',
            ['business_id' => $businessId]
        );

        return (int) ($row['cnt'] ?? 0);
    }
}
