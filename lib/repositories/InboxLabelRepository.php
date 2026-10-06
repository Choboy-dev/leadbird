<?php

declare(strict_types=1);

final class InboxLabelRepository extends BaseRepository
{
    public const FAVORITES_SLUG = 'favorites';

    public function findById(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM inbox_labels WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function findByBusinessAndSlug(string $businessId, string $slug): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM inbox_labels WHERE business_id = :business_id AND slug = :slug LIMIT 1',
            ['business_id' => $businessId, 'slug' => $slug]
        );
    }

    /** @return list<array<string, mixed>> */
    public function listByBusinessId(string $businessId): array
    {
        return $this->fetchAll(
            'SELECT * FROM inbox_labels WHERE business_id = :business_id ORDER BY is_system DESC, sort_order ASC, name ASC',
            ['business_id' => $businessId]
        );
    }

    public function ensureFavoritesLabel(string $businessId): array
    {
        $existing = $this->findByBusinessAndSlug($businessId, self::FAVORITES_SLUG);
        if ($existing !== null) {
            return $existing;
        }

        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO inbox_labels (id, business_id, name, slug, is_system, sort_order)
             VALUES (:id, :business_id, :name, :slug, 1, 0)',
            [
                'id' => $id,
                'business_id' => $businessId,
                'name' => 'Favorites',
                'slug' => self::FAVORITES_SLUG,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create Favorites label.');
    }

    public function create(string $businessId, string $name, string $slug): array
    {
        $sortOrder = (int) ($this->fetchOne(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM inbox_labels WHERE business_id = :business_id',
            ['business_id' => $businessId]
        )['next_order'] ?? 1);

        $id = Uuid::v4();
        $this->execute(
            'INSERT INTO inbox_labels (id, business_id, name, slug, is_system, sort_order)
             VALUES (:id, :business_id, :name, :slug, 0, :sort_order)',
            [
                'id' => $id,
                'business_id' => $businessId,
                'name' => $name,
                'slug' => $slug,
                'sort_order' => $sortOrder,
            ]
        );

        return $this->findById($id) ?? throw new RuntimeException('Failed to create label.');
    }

    public function delete(string $labelId, string $businessId): bool
    {
        return $this->execute(
            'DELETE FROM inbox_labels WHERE id = :id AND business_id = :business_id AND is_system = 0',
            ['id' => $labelId, 'business_id' => $businessId]
        );
    }

    public function isAssigned(string $conversationId, string $labelId): bool
    {
        $row = $this->fetchOne(
            'SELECT 1 AS assigned FROM inbox_conversation_labels
             WHERE conversation_id = :conversation_id AND label_id = :label_id LIMIT 1',
            ['conversation_id' => $conversationId, 'label_id' => $labelId]
        );

        return $row !== null;
    }

    public function assign(string $conversationId, string $labelId): void
    {
        if ($this->isAssigned($conversationId, $labelId)) {
            return;
        }

        $this->execute(
            'INSERT INTO inbox_conversation_labels (conversation_id, label_id) VALUES (:conversation_id, :label_id)',
            ['conversation_id' => $conversationId, 'label_id' => $labelId]
        );
    }

    public function unassign(string $conversationId, string $labelId): void
    {
        $this->execute(
            'DELETE FROM inbox_conversation_labels WHERE conversation_id = :conversation_id AND label_id = :label_id',
            ['conversation_id' => $conversationId, 'label_id' => $labelId]
        );
    }

    /** @return list<string> */
    public function listLabelIdsForConversation(string $conversationId): array
    {
        $rows = $this->fetchAll(
            'SELECT label_id FROM inbox_conversation_labels WHERE conversation_id = :conversation_id',
            ['conversation_id' => $conversationId]
        );

        return array_map(static fn (array $row): string => (string) ($row['label_id'] ?? ''), $rows);
    }

    /**
     * @param list<string> $conversationIds
     * @return array<string, list<string>>
     */
    public function mapLabelIdsByConversationIds(array $conversationIds): array
    {
        if ($conversationIds === []) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($conversationIds) as $index => $conversationId) {
            $key = 'conversation_id_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $conversationId;
        }

        $rows = $this->fetchAll(
            'SELECT conversation_id, label_id FROM inbox_conversation_labels
             WHERE conversation_id IN (' . implode(', ', $placeholders) . ')',
            $params
        );

        $map = [];
        foreach ($rows as $row) {
            $conversationId = (string) ($row['conversation_id'] ?? '');
            $labelId = (string) ($row['label_id'] ?? '');
            if ($conversationId === '' || $labelId === '') {
                continue;
            }
            $map[$conversationId][] = $labelId;
        }

        return $map;
    }

    /** @return array<string, int> */
    public function countConversationsByLabelId(string $businessId): array
    {
        $rows = $this->fetchAll(
            'SELECT l.id AS label_id, COUNT(cl.conversation_id) AS chat_count
             FROM inbox_labels l
             LEFT JOIN inbox_conversation_labels cl ON cl.label_id = l.id
             LEFT JOIN conversations c ON c.id = cl.conversation_id
             WHERE l.business_id = :business_id
             GROUP BY l.id',
            ['business_id' => $businessId]
        );

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) ($row['label_id'] ?? '')] = (int) ($row['chat_count'] ?? 0);
        }

        return $counts;
    }
}
