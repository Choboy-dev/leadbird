<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/openai-client.php';

/**
 * OpenAI embeddings for semantic knowledge search (v2).
 */
final class KnowledgeEmbeddingService
{
    private const DEFAULT_MODEL = 'text-embedding-3-small';
    private const BATCH_SIZE = 32;

    /** @var array<string, list<float>|null> */
    private static array $textCache = [];

    public function embedBusinessChunks(string $businessId): int
    {
        $budget = new OpenAiBudgetService();
        if ($budget->isExceeded($businessId)) {
            return 0;
        }

        $chunks = RepositoryFactory::businessKnowledge()->listActiveByBusinessId($businessId);
        $pending = [];

        foreach ($chunks as $chunk) {
            if (trim((string) ($chunk['embedding_json'] ?? '')) !== '') {
                continue;
            }

            $text = $this->chunkEmbeddingText($chunk);
            if ($text === '') {
                continue;
            }

            $pending[(string) $chunk['id']] = $text;
        }

        $newlyEmbedded = 0;
        foreach (array_chunk($pending, self::BATCH_SIZE, true) as $batch) {
            if (!$budget->canSpend($businessId, 0.00005 * count($batch))) {
                break;
            }

            $vectors = $this->embedTexts(array_values($batch), $businessId);
            if ($vectors === []) {
                continue;
            }

            $chunkIds = array_keys($batch);
            foreach ($vectors as $index => $vector) {
                $chunkId = $chunkIds[$index] ?? null;
                if ($chunkId === null || $vector === []) {
                    continue;
                }

                RepositoryFactory::businessKnowledge()->updateEmbedding(
                    $chunkId,
                    json_encode($vector, JSON_THROW_ON_ERROR)
                );
                $newlyEmbedded++;
            }
        }

        RepositoryFactory::businessKnowledge()->touchEmbeddingSync(
            $businessId,
            RepositoryFactory::businessKnowledge()->countEmbedded($businessId)
        );

        return $newlyEmbedded;
    }

    /**
     * @return list<float>|null
     */
    public function embedText(string $text, string $businessId): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $cacheKey = $businessId . ':' . hash('sha256', $text);
        if (array_key_exists($cacheKey, self::$textCache)) {
            return self::$textCache[$cacheKey];
        }

        $vectors = $this->embedTexts([$text], $businessId);
        $vector = $vectors[0] ?? null;
        self::$textCache[$cacheKey] = $vector;

        return $vector;
    }

    /**
     * @param list<string> $texts
     * @return list<list<float>>
     */
    private function embedTexts(array $texts, string $businessId): array
    {
        $texts = array_values(array_filter(array_map('trim', $texts), static fn(string $t): bool => $t !== ''));
        if ($texts === []) {
            return [];
        }

        $apiKey = app_config()['openai']['api_key'] ?? '';
        if ($apiKey === '') {
            return [];
        }

        $model = (string) (app_config()['openai']['embedding_model'] ?? self::DEFAULT_MODEL);
        $payload = [
            'model' => $model,
            'input' => count($texts) === 1 ? $texts[0] : $texts,
        ];

        $feature = count($texts) === 1 ? 'embedding' : 'embedding_batch';
        $api = openai_embedding_request($payload, $businessId, $feature, 0.00005 * count($texts));
        if (!($api['ok'] ?? false)) {
            if (!empty($api['budget_exceeded'])) {
                error_log('[KnowledgeEmbedding] Budget exceeded for business ' . $businessId);
            } else {
                error_log('[KnowledgeEmbedding] API error: ' . (string) ($api['error'] ?? 'unknown'));
            }
            return [];
        }

        $decoded = $api['body'] ?? [];
        $rows = $decoded['data'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        usort($rows, static fn(array $a, array $b): int => ((int) ($a['index'] ?? 0)) <=> ((int) ($b['index'] ?? 0)));

        $vectors = [];
        foreach ($rows as $row) {
            $embedding = $row['embedding'] ?? null;
            $vectors[] = is_array($embedding) ? array_map('floatval', $embedding) : [];
        }

        return $vectors;
    }

    /**
     * @param array<string, mixed> $chunk
     */
    private function chunkEmbeddingText(array $chunk): string
    {
        $parts = array_filter([
            (string) ($chunk['title'] ?? ''),
            (string) ($chunk['content'] ?? ''),
            (string) ($chunk['keywords'] ?? ''),
            (string) ($chunk['source_type'] ?? ''),
        ], static fn(string $part): bool => trim($part) !== '');

        $text = trim(implode("\n", $parts));

        return function_exists('mb_strimwidth')
            ? mb_strimwidth($text, 0, 6000, '')
            : substr($text, 0, 6000);
    }
}

function knowledge_cosine_similarity(array $a, array $b): float
{
    if ($a === [] || $b === [] || count($a) !== count($b)) {
        return 0.0;
    }

    $dot = 0.0;
    $normA = 0.0;
    $normB = 0.0;

    foreach ($a as $index => $value) {
        $bv = (float) ($b[$index] ?? 0.0);
        $dot += $value * $bv;
        $normA += $value * $value;
        $normB += $bv * $bv;
    }

    if ($normA <= 0.0 || $normB <= 0.0) {
        return 0.0;
    }

    return $dot / (sqrt($normA) * sqrt($normB));
}
