<?php

declare(strict_types=1);

/**
 * Conversation memory and eligibility guards for customer DM replies.
 */
final class ConversationContextService
{
    /**
     * Build a retrieval query from recent chat turns plus the latest message.
     *
     * @param list<array<string, mixed>> $recentMessages
     */
    public static function buildKnowledgeRetrievalQuery(array $recentMessages, string $latestMessage): string
    {
        $parts = [trim($latestMessage)];

        foreach (array_slice($recentMessages, -6) as $message) {
            $text = trim((string) ($message['message_text'] ?? ''));
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return trim(implode("\n", array_filter($parts)));
    }

    /**
     * Short memory block injected into the salesgirl system prompt.
     *
     * @param list<array<string, mixed>> $recentMessages
     */
    public static function memoryBlockForPrompt(array $recentMessages): ?string
    {
        $facts = self::customerFacts($recentMessages);
        $commitments = self::assistantCommitments($recentMessages);

        if ($facts === [] && $commitments === []) {
            return null;
        }

        $lines = [
            'CONVERSATION MEMORY (must follow — overrides conflicting knowledge chunks):',
        ];

        foreach ($facts as $fact) {
            $lines[] = '- ' . $fact;
        }

        foreach ($commitments as $commitment) {
            $lines[] = '- ' . $commitment;
        }

        if (self::joinDeclined($recentMessages) || self::customerMarkedIneligible($recentMessages)) {
            $lines[] = '- Do not send registration links, signup links, payment links, files, PDFs, or join steps. Politely restate why they cannot join and offer to help with other questions.';
        } else {
            $lines[] = '- Stay consistent with what was already said in this thread. Do not contradict yourself.';
        }

        return implode("\n", $lines);
    }

    /**
     * Remove signup-oriented knowledge when the customer was already declined.
     *
     * @param list<array<string, mixed>> $chunks
     * @return list<array<string, mixed>>
     */
    public static function filterKnowledgeChunks(array $chunks, array $recentMessages): array
    {
        if (!self::joinDeclined($recentMessages) && !self::customerMarkedIneligible($recentMessages)) {
            return $chunks;
        }

        $filtered = array_values(array_filter($chunks, static function (array $chunk): bool {
            $haystack = mb_strtolower(
                (string) ($chunk['title'] ?? '') . ' ' . (string) ($chunk['content'] ?? '')
            );

            $isRegistrationChunk = (bool) preg_match(
                '/\b(registration link|register here|join here|sign up|signup link|forms\.gle)\b/',
                $haystack
            );
            $isRestrictionChunk = (bool) preg_match(
                '/\b(women only|women-only|men can\'?t|men cannot|not eligible|cannot join|can\'?t join|not allowed)\b/',
                $haystack
            );

            if ($isRegistrationChunk && !$isRestrictionChunk) {
                return false;
            }

            return true;
        }));

        return $filtered !== [] ? $filtered : $chunks;
    }

    /**
     * Block action URLs and registration links after an eligibility decline.
     *
     * @param array<string, mixed> $ai
     * @return array<string, mixed>
     */
    public static function enforceOnReply(array $ai, array $recentMessages): array
    {
        if (!self::joinDeclined($recentMessages) && !self::customerMarkedIneligible($recentMessages)) {
            return $ai;
        }

        $ai['send_action_url'] = false;
        $ai['action_offering_id'] = null;
        $ai['booking_required'] = false;
        $ai['payment_required'] = false;
        $ai['knowledge_attachment_ids'] = [];
        $ai['knowledge_image_urls'] = [];
        $ai['offer_image_ids'] = [];

        $reply = trim((string) ($ai['customer_reply'] ?? ''));
        if ($reply === '') {
            return $ai;
        }

        $reply = (string) preg_replace(
            '#\s*(?:join|register|sign up|registration)\s*(?:here|link)?\s*:?\s*https?://\S+#iu',
            '',
            $reply
        );
        $reply = (string) preg_replace(
            '#\s*https?://\S*(?:forms\.gle|register|signup|join)[^\s]*#iu',
            '',
            $reply
        );
        // Drop any knowledge-share / file URLs that slipped into a decline reply.
        $reply = (string) preg_replace(
            '#\s*https?://\S*(?:knowledge-share|/storage/uploads/knowledge/)[^\s]*#iu',
            '',
            $reply
        );
        $reply = trim(preg_replace('/\n{3,}/', "\n\n", $reply) ?? $reply);

        if ($reply === '' || self::replyStillOffersJoin($reply)) {
            $reply = self::politeDeclineReply($recentMessages);
        }

        $ai['customer_reply'] = $reply;

        return $ai;
    }

    /**
     * @param list<array<string, mixed>> $recentMessages
     * @return list<string>
     */
    public static function customerFacts(array $recentMessages): array
    {
        $facts = [];

        foreach ($recentMessages as $message) {
            if (($message['sender_type'] ?? '') !== 'customer') {
                continue;
            }

            $text = trim((string) ($message['message_text'] ?? ''));
            if ($text === '') {
                continue;
            }

            if (preg_match('/\b(i am|i\'m|im)\s+(?:a\s+)?(man|male|guy|boy)\b/i', $text) === 1) {
                $facts[] = 'Customer said they are a man.';
            }

            if (preg_match('/\b(i am|i\'m|im)\s+(?:a\s+)?(woman|female|lady|girl)\b/i', $text) === 1) {
                $facts[] = 'Customer said they are a woman.';
            }

            if (preg_match('/\b(under|below)\s+(18|sixteen|17)\b/i', $text) === 1) {
                $facts[] = 'Customer may be under the minimum age.';
            }
        }

        return array_values(array_unique($facts));
    }

    /**
     * @param list<array<string, mixed>> $recentMessages
     * @return list<string>
     */
    public static function assistantCommitments(array $recentMessages): array
    {
        $commitments = [];

        foreach ($recentMessages as $message) {
            $sender = (string) ($message['sender_type'] ?? '');
            if ($sender !== 'ai' && $sender !== 'owner') {
                continue;
            }

            $text = trim((string) ($message['message_text'] ?? ''));
            if ($text === '') {
                continue;
            }

            if (preg_match('/\b(women only|women-only|men can\'?t|men cannot|not for men)\b/i', $text) === 1) {
                $commitments[] = 'You already told this customer the community is for women only and men cannot join.';
            }

            if (preg_match('/\b(can\'?t join|cannot join|not eligible|not able to join|not allowed to join)\b/i', $text) === 1) {
                $commitments[] = 'You already told this customer they cannot join.';
            }
        }

        return array_values(array_unique($commitments));
    }

    /**
     * @param list<array<string, mixed>> $recentMessages
     */
    public static function joinDeclined(array $recentMessages): bool
    {
        return self::assistantCommitments($recentMessages) !== [];
    }

    /**
     * @param list<array<string, mixed>> $recentMessages
     */
    public static function customerMarkedIneligible(array $recentMessages): bool
    {
        $facts = self::customerFacts($recentMessages);
        $commitments = self::assistantCommitments($recentMessages);

        if ($commitments === []) {
            return false;
        }

        foreach ($facts as $fact) {
            if (str_contains($fact, 'man')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{role?: string, content?: string}> $history
     * @return list<array<string, mixed>>
     */
    public static function historyToMessages(array $history): array
    {
        $messages = [];

        foreach ($history as $turn) {
            if (!is_array($turn)) {
                continue;
            }

            $role = trim((string) ($turn['role'] ?? ''));
            $content = trim((string) ($turn['content'] ?? ''));
            if ($content === '' || !in_array($role, ['customer', 'assistant', 'user', 'ai'], true)) {
                continue;
            }

            $senderType = in_array($role, ['assistant', 'ai'], true) ? 'ai' : 'customer';
            $messages[] = [
                'sender_type' => $senderType,
                'message_text' => mb_substr($content, 0, 800),
            ];
        }

        return array_slice($messages, -12);
    }

    private static function replyStillOffersJoin(string $reply): bool
    {
        return preg_match(
            '/\b(here is the (registration|signup) link|you can join (by|using|with)|registration link:|join here)\b/i',
            $reply
        ) === 1;
    }

    /**
     * @param list<array<string, mixed>> $recentMessages
     */
    private static function politeDeclineReply(array $recentMessages): string
    {
        foreach (array_reverse(self::assistantCommitments($recentMessages)) as $commitment) {
            if (str_contains($commitment, 'women only')) {
                return 'I understand you would like to join. This is a women-only community, so I cannot share a registration link for you. I am happy to answer other questions if you have any.';
            }
        }

        return 'I understand you would like to join. Based on what we discussed, I cannot share a registration link for you right now. I am happy to help with other questions if you have any.';
    }
}
