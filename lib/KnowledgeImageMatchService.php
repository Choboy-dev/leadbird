<?php

declare(strict_types=1);

final class KnowledgeImageMatchService
{
    /** @var array<string, list<array<string, mixed>>> */
    private static array $matchCache = [];

    /**
     * @param list<array<string, mixed>> $chunks
     * @return list<array<string, mixed>>
     */
    public function matchChunks(string $imageUrl, array $chunks, int $limit = 3, string $businessId = ''): array
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '' || $chunks === []) {
            return [];
        }

        $cacheKey = hash('sha256', $businessId . '|' . $imageUrl . '|' . $limit);
        if (isset(self::$matchCache[$cacheKey])) {
            return self::$matchCache[$cacheKey];
        }

        $candidates = [];
        foreach ($chunks as $chunk) {
            $image = trim((string) ($chunk['image_url'] ?? ''));
            if ($image === '') {
                continue;
            }

            $candidates[] = $chunk;
            if (count($candidates) >= 12) {
                break;
            }
        }

        if ($candidates === []) {
            return [];
        }

        $apiKey = app_config()['openai']['api_key'] ?? '';
        if ($apiKey === '' || (new OpenAiBudgetService())->isExceeded($businessId)) {
            return [];
        }

        $model = (string) (app_config()['openai']['vision_model'] ?? 'gpt-4o-mini');
        $lines = [];
        foreach ($candidates as $index => $chunk) {
            $lines[] = ($index + 1) . '. id=' . ($chunk['id'] ?? '')
                . ' | ' . ($chunk['title'] ?? 'Item')
                . ' | ' . knowledge_excerpt((string) ($chunk['content'] ?? ''), 100);
        }

        $payload = [
            'model' => $model,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You match a customer photo to business catalog or social post items. Return JSON: {"matches":["chunk-id",...]} with up to ' . $limit . ' best matching ids from the list, or empty array if none fit.',
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "Customer sent this photo. Which items match?\n" . implode("\n", $lines),
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $imageUrl,
                                'detail' => 'low',
                            ],
                        ],
                    ],
                ],
            ],
            'temperature' => 0.1,
            'max_tokens' => 120,
        ];

        $api = openai_chat_completion($payload, $businessId, 'vision_match', 0.001);
        if (!($api['ok'] ?? false)) {
            error_log('[KnowledgeImageMatch] API error: ' . (string) ($api['error'] ?? 'unknown'));
            return [];
        }

        $decoded = $api['body'] ?? [];
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || $content === '') {
            return [];
        }

        $result = json_decode($content, true);
        if (!is_array($result)) {
            return [];
        }

        $lookup = [];
        foreach ($candidates as $chunk) {
            $lookup[(string) ($chunk['id'] ?? '')] = $chunk;
        }

        $matched = [];
        foreach ($result['matches'] ?? [] as $id) {
            $id = trim((string) $id);
            if ($id !== '' && isset($lookup[$id])) {
                $chunk = $lookup[$id];
                $chunk['_score'] = 10.0;
                $matched[] = $chunk;
            }
        }

        $matched = array_slice($matched, 0, $limit);
        self::$matchCache[$cacheKey] = $matched;

        return $matched;
    }
}
