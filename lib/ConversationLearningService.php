<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/openai-client.php';

/**
 * Extracts durable business memory from successful chat conversations.
 */
final class ConversationLearningService
{
    private const MAX_CONVERSATIONS = 6;

    public function learnFromConversations(string $businessId): int
    {
        $conversations = RepositoryFactory::messages()->listRecentConversationsForLearning($businessId, self::MAX_CONVERSATIONS);
        if ($conversations === []) {
            RepositoryFactory::businessKnowledge()->touchLearningSync(
                $businessId,
                RepositoryFactory::businessKnowledge()->countBySourceType($businessId)['learned_memory'] ?? 0
            );

            return 0;
        }

        $prepared = [];
        $seen = [];

        foreach ($conversations as $row) {
            $conversationId = (string) ($row['conversation_id'] ?? '');
            if ($conversationId === '' || isset($seen[$conversationId])) {
                continue;
            }
            $seen[$conversationId] = true;

            $messages = RepositoryFactory::messages()->listByConversationId($conversationId, 20);
            if ($messages === []) {
                continue;
            }

            $transcript = $this->buildTranscript($messages);
            if ($transcript === []) {
                continue;
            }

            $prepared[] = [
                'conversation_id' => $conversationId,
                'transcript' => $transcript,
            ];
        }

        if ($prepared === []) {
            RepositoryFactory::businessKnowledge()->touchLearningSync(
                $businessId,
                RepositoryFactory::businessKnowledge()->countBySourceType($businessId)['learned_memory'] ?? 0
            );

            return 0;
        }

        $extractedByConversation = $this->extractLearningsBatch($businessId, $prepared);
        $chunks = [];

        foreach ($prepared as $row) {
            $conversationId = $row['conversation_id'];
            $learnings = $extractedByConversation[$conversationId] ?? $this->fallbackLearnings($row['transcript']);

            foreach ($learnings as $learning) {
                $content = trim((string) ($learning['content'] ?? ''));
                $title = trim((string) ($learning['title'] ?? 'Learned from chat'));
                if ($content === '') {
                    continue;
                }

                $sourceId = substr(hash('sha256', $conversationId . $title . $content), 0, 32);
                $chunks[] = [
                    'source_id' => $sourceId,
                    'title' => $title,
                    'content' => $content,
                    'keywords' => $title . ' ' . $content . ' learned_memory chat',
                    'metadata' => [
                        'conversation_id' => $conversationId,
                    ],
                ];
            }
        }

        $count = RepositoryFactory::businessKnowledge()->replaceSourceTypeChunks($businessId, 'learned_memory', $chunks);
        (new KnowledgeEmbeddingService())->embedBusinessChunks($businessId);
        RepositoryFactory::businessKnowledge()->touchLearningSync($businessId, $count);

        return $count;
    }

    /**
     * @param list<array<string, mixed>> $messages
     * @return list<string>
     */
    private function buildTranscript(array $messages): array
    {
        $transcript = [];

        foreach ($messages as $message) {
            $role = ($message['sender_type'] ?? '') === 'customer' ? 'Customer' : 'Salesgirl';
            $text = trim((string) ($message['message_text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $transcript[] = $role . ': ' . knowledge_excerpt($text, 220);
        }

        return $transcript;
    }

    /**
     * One API call for all conversations instead of one per chat.
     *
     * @param list<array{conversation_id: string, transcript: list<string>}> $prepared
     * @return array<string, list<array{title: string, content: string}>>
     */
    private function extractLearningsBatch(string $businessId, array $prepared): array
    {
        $apiKey = app_config()['openai']['api_key'] ?? '';
        if ($apiKey === '' || (new OpenAiBudgetService())->isExceeded($businessId)) {
            return [];
        }

        $blocks = [];
        foreach ($prepared as $index => $row) {
            $blocks[] = 'Chat ' . ($index + 1)
                . ' (id=' . $row['conversation_id'] . "):\n"
                . implode("\n", array_slice($row['transcript'], 0, 12));
        }

        $payload = [
            'model' => app_config()['openai']['model'] ?? 'gpt-4o-mini',
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Extract reusable business facts from successful sales chats. Return JSON: {"conversations":[{"conversation_id":"uuid","learnings":[{"title":"short label","content":"fact"}]}]}. Max 2 learnings per chat. No personal data. Do not extract registration or signup links when the assistant declined someone for eligibility reasons (gender, age, membership rules). Prefer durable policies and product facts.',
                ],
                [
                    'role' => 'user',
                    'content' => implode("\n\n---\n\n", $blocks),
                ],
            ],
            'temperature' => 0.2,
            'max_tokens' => 700,
        ];

        $api = openai_chat_completion($payload, $businessId, 'conversation_learning', 0.004);
        if (!($api['ok'] ?? false)) {
            return [];
        }

        $decoded = $api['body'] ?? [];
        $contentJson = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($contentJson)) {
            return [];
        }

        $result = json_decode($contentJson, true);
        if (!is_array($result) || !is_array($result['conversations'] ?? null)) {
            return [];
        }

        $byConversation = [];
        foreach ($result['conversations'] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $conversationId = trim((string) ($row['conversation_id'] ?? ''));
            if ($conversationId === '' || !is_array($row['learnings'] ?? null)) {
                continue;
            }

            $learnings = [];
            foreach ($row['learnings'] as $learning) {
                if (!is_array($learning)) {
                    continue;
                }
                $learnings[] = [
                    'title' => (string) ($learning['title'] ?? 'Learned from chat'),
                    'content' => (string) ($learning['content'] ?? ''),
                ];
            }

            if ($learnings !== []) {
                $byConversation[$conversationId] = $learnings;
            }
        }

        return $byConversation;
    }

    /**
     * @param list<string> $transcript
     * @return list<array{title: string, content: string}>
     */
    private function fallbackLearnings(array $transcript): array
    {
        $customerLines = array_values(array_filter($transcript, static fn(?string $line): bool => is_string($line) && str_starts_with($line, 'Customer:')));
        if ($customerLines === []) {
            return [];
        }

        $question = trim(substr($customerLines[0], strlen('Customer:')));
        if ($question === '') {
            return [];
        }

        return [[
            'title' => 'Common customer question',
            'content' => 'Customers often ask: ' . knowledge_excerpt($question, 160),
        ]];
    }
}
