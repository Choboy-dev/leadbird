<?php

declare(strict_types=1);

/** @var list<string> */
const KNOWLEDGE_STOP_WORDS = [
    'a', 'an', 'and', 'are', 'as', 'at', 'be', 'but', 'by', 'can', 'do', 'for', 'from', 'have',
    'how', 'i', 'if', 'in', 'is', 'it', 'me', 'my', 'of', 'on', 'or', 'please', 'that', 'the',
    'this', 'to', 'what', 'when', 'where', 'who', 'will', 'with', 'you', 'your',
];

/**
 * Hybrid semantic + keyword retrieval for business knowledge (v2).
 */
final class BusinessKnowledgeRetrievalService
{
    private const DEFAULT_LIMIT = 6;

    /**
     * @param list<array<string, mixed>> $forcedChunks
     * @return list<array<string, mixed>>
     */
    public function retrieve(
        string $businessId,
        string $query,
        int $limit = self::DEFAULT_LIMIT,
        ?string $customerImageUrl = null,
        ?string $referralContext = null,
        array $forcedChunks = []
    ): array {
        if ($referralContext !== null && trim($referralContext) !== '') {
            $query = trim($query . "\n" . $referralContext);
        }

        // Vision matching below handles customer photos — skip the extra describe call.

        $query = trim($query);
        if ($query === '' && $forcedChunks === []) {
            return [];
        }

        $chunks = RepositoryFactory::businessKnowledge()->listActiveByBusinessId($businessId);
        if ($chunks === [] && $forcedChunks === []) {
            return [];
        }

        $chunks = $this->enrichChunksWithLinks($chunks, $businessId);

        $tokens = knowledge_tokenize($query);
        $visualQuery = knowledge_has_visual_intent($query) || ($customerImageUrl !== null && trim($customerImageUrl) !== '');

        // Pass 1: keyword-only (no embedding API call).
        $keywordScored = [];
        foreach ($chunks as $chunk) {
            $keywordScore = $this->scoreChunkKeywords($chunk, $tokens, $query, $visualQuery);
            if ($keywordScore <= 0) {
                continue;
            }
            $chunk['_score'] = $keywordScore;
            $chunk['_keyword_score'] = $keywordScore;
            $keywordScored[] = $chunk;
        }

        usort($keywordScored, static function (array $a, array $b): int {
            return ($b['_score'] <=> $a['_score']) ?: strcmp((string) ($a['updated_at'] ?? ''), (string) ($b['updated_at'] ?? ''));
        });

        $topKeywordScore = (float) ($keywordScored[0]['_keyword_score'] ?? 0);
        $needsSemantic = $query !== ''
            && ($topKeywordScore < 4.0 || count($keywordScored) < 2);

        $queryEmbedding = null;
        if ($needsSemantic) {
            $queryEmbedding = (new KnowledgeEmbeddingService())->embedText($query, $businessId);
        }

        $scored = $keywordScored;
        if ($queryEmbedding !== null) {
            $scored = [];
            foreach ($chunks as $chunk) {
                $keywordScore = $this->scoreChunkKeywords($chunk, $tokens, $query, $visualQuery);
                $semanticScore = $this->scoreChunkSemantic($chunk, $queryEmbedding);
                $score = ($semanticScore * 0.65) + ($keywordScore * 0.35);
                if ($score <= 0) {
                    continue;
                }
                $chunk['_score'] = $score;
                $scored[] = $chunk;
            }
            usort($scored, static function (array $a, array $b): int {
                return ($b['_score'] <=> $a['_score']) ?: strcmp((string) ($a['updated_at'] ?? ''), (string) ($b['updated_at'] ?? ''));
            });
        }

        $selected = array_slice($scored, 0, $limit);
        $this->ensurePolicyCoverage($selected, $chunks, $query);
        $this->ensureFaqCoverage($selected, $query, $businessId, $limit);
        $this->ensureDocumentCoverage($selected, $chunks, $query, $businessId, $limit);
        $this->mergeForcedChunks($selected, $forcedChunks, $limit);

        if ($customerImageUrl !== null && trim($customerImageUrl) !== '') {
            $topSelectedScore = (float) ($selected[0]['_score'] ?? 0);
            $needsVision = $topSelectedScore < 6.0;
            if ($needsVision) {
                $imageMatches = (new KnowledgeImageMatchService())->matchChunks($customerImageUrl, $chunks, 3, $businessId);
                $this->mergeForcedChunks($selected, $imageMatches, $limit);
            }
        }

        return array_values(array_slice($selected, 0, $limit));
    }

    /**
     * @param list<string> $tokens
     * @param array<string, mixed> $chunk
     */
    private function scoreChunkKeywords(array $chunk, array $tokens, string $query, bool $visualQuery): float
    {
        $haystack = strtolower(
            (string) ($chunk['title'] ?? '') . ' '
            . (string) ($chunk['content'] ?? '') . ' '
            . (string) ($chunk['keywords'] ?? '')
        );

        $score = 0.0;
        foreach ($tokens as $token) {
            if (str_contains($haystack, $token)) {
                $score += strlen($token) >= 5 ? 2.0 : 1.0;
            }
        }

        $sourceType = (string) ($chunk['source_type'] ?? '');
        if ($sourceType === 'offering') {
            $score += 0.75;
        }
        if ($sourceType === 'learned_memory') {
            $score += 1.25;
        }

        if ($sourceType === 'document') {
            $score += 1.0;
            if (preg_match('/\b(resume|cv|sample|template|brochure|catalog|menu|photo|picture|image|video|file|document|pdf|show me|see a|send me)\b/i', $query)) {
                $score += 3.5;
            }
            $meta = knowledge_chunk_metadata($chunk);
            $usage = mb_strtolower(trim((string) ($meta['usage_instructions'] ?? '')));
            if ($usage !== '' && preg_match('/\b(show|send|share|when someone asks|if someone asks|resume|cv|sample)\b/i', $usage)) {
                $score += 2.5;
            }
        }

        if ($sourceType === 'social_post') {
            $score += 0.5;
        }

        if ($sourceType === 'web_link') {
            $score += 1.0;
            foreach (knowledge_extract_chunk_urls($chunk) as $pageUrl) {
                if (str_contains(strtolower($query), strtolower(parse_url($pageUrl, PHP_URL_HOST) ?: ''))) {
                    $score += 0.5;
                }
            }
        }

        if ($visualQuery && !empty($chunk['image_url'])) {
            $score += 3.0;
        }

        if ($this->matchesPolicyIntent($query, 'delivery') && $sourceType === 'policy' && str_contains($haystack, 'delivery')) {
            $score += 4.0;
        }
        if ($this->matchesPolicyIntent($query, 'payment') && $sourceType === 'policy' && str_contains($haystack, 'payment')) {
            $score += 4.0;
        }
        if ($this->matchesPolicyIntent($query, 'return') && $sourceType === 'policy' && str_contains($haystack, 'return')) {
            $score += 4.0;
        }

        if ($sourceType === 'faq') {
            $title = knowledge_normalize_question((string) ($chunk['title'] ?? ''));
            $queryNorm = knowledge_normalize_question($query);
            if ($title !== '' && $queryNorm !== '') {
                if ($title === $queryNorm) {
                    $score += 50.0;
                } else {
                    $similarity = 0.0;
                    similar_text($title, $queryNorm, $similarity);
                    if ($similarity >= 75.0) {
                        $score += 35.0;
                    } elseif ($similarity >= 55.0) {
                        $score += 15.0;
                    }

                    foreach (knowledge_tokenize($query) as $token) {
                        if (strlen($token) >= 4 && str_contains($title, $token)) {
                            $score += 3.0;
                        }
                    }
                }
            }

            if (str_contains(strtolower($query), '?')) {
                $score += 0.5;
            }
        }

        if (str_contains(strtolower($query), 'post') && $sourceType === 'social_post') {
            $score += 2.0;
        }
        if (str_contains(strtolower($query), 'comment') && $sourceType === 'social_post') {
            $score += 2.5;
        }

        return $score;
    }

    /**
     * @param list<array<string, mixed>> $selected
     * @param list<array<string, mixed>> $allChunks
     */
    private function ensureDocumentCoverage(array &$selected, array $allChunks, string $query, string $businessId, int $limit): void
    {
        if (!knowledge_message_requests_file($query)) {
            return;
        }

        $attachmentIds = knowledge_infer_attachment_ids($query, $selected, $businessId);
        if ($attachmentIds === []) {
            $attachmentIds = knowledge_infer_attachment_ids($query, $allChunks, $businessId);
        }

        if ($attachmentIds === []) {
            return;
        }

        $selectedIds = [];
        foreach ($selected as $chunk) {
            $selectedIds[(string) ($chunk['id'] ?? '')] = true;
        }

        $lookup = [];
        foreach ($allChunks as $chunk) {
            $lookup[(string) ($chunk['id'] ?? '')] = $chunk;
        }

        foreach (RepositoryFactory::businessKnowledge()->listBySourceType($businessId, 'document', 25) as $document) {
            $lookup[(string) ($document['id'] ?? '')] = $document;
        }

        foreach ($attachmentIds as $chunkId) {
            if ($chunkId === '' || isset($selectedIds[$chunkId])) {
                continue;
            }

            $chunk = $lookup[$chunkId] ?? null;
            if (!is_array($chunk)) {
                continue;
            }

            $chunk['_score'] = 99.0;
            array_unshift($selected, $chunk);
            $selectedIds[$chunkId] = true;
        }

        $selected = array_slice($selected, 0, $limit);
    }

    /**
     * @param array<string, mixed> $chunk
     * @param list<float>|null $queryEmbedding
     */
    private function scoreChunkSemantic(array $chunk, ?array $queryEmbedding): float
    {
        if ($queryEmbedding === null) {
            return 0.0;
        }

        $stored = json_decode((string) ($chunk['embedding_json'] ?? ''), true);
        if (!is_array($stored) || $stored === []) {
            return 0.0;
        }

        $similarity = knowledge_cosine_similarity($queryEmbedding, array_map('floatval', $stored));

        return max(0.0, $similarity * 10.0);
    }

    /**
     * @param list<array<string, mixed>> $selected
     * @param list<array<string, mixed>> $forced
     */
    private function mergeForcedChunks(array &$selected, array $forced, int $limit): void
    {
        if ($forced === []) {
            return;
        }

        $selectedIds = [];
        foreach ($selected as $chunk) {
            $selectedIds[(string) ($chunk['id'] ?? '')] = true;
        }

        foreach ($forced as $chunk) {
            $chunkId = (string) ($chunk['id'] ?? '');
            if ($chunkId === '' || isset($selectedIds[$chunkId])) {
                continue;
            }

            $chunk['_score'] = (float) ($chunk['_score'] ?? 20.0);
            array_unshift($selected, $chunk);
            $selectedIds[$chunkId] = true;
        }

        $selected = array_slice($selected, 0, $limit);
    }

    /**
     * @param list<array<string, mixed>> $chunks
     * @return list<array<string, mixed>>
     */
    private function enrichChunksWithLinks(array $chunks, string $businessId): array
    {
        $links = RepositoryFactory::knowledgeLinks()->listByBusinessId($businessId, 100);
        if ($links === []) {
            return $chunks;
        }

        $byChunk = [];
        foreach ($links as $link) {
            $chunkId = (string) ($link['social_chunk_id'] ?? '');
            $name = trim((string) ($link['offering_name'] ?? ''));
            if ($chunkId === '' || $name === '') {
                continue;
            }
            $byChunk[$chunkId][] = $name;
        }

        foreach ($chunks as &$chunk) {
            $chunkId = (string) ($chunk['id'] ?? '');
            $names = $byChunk[$chunkId] ?? [];
            if ($names === []) {
                continue;
            }
            $chunk['keywords'] = trim((string) ($chunk['keywords'] ?? '') . ' linked products: ' . implode(', ', $names));
        }
        unset($chunk);

        return $chunks;
    }

    /**
     * @param list<array<string, mixed>> $selected
     */
    private function ensureFaqCoverage(array &$selected, string $query, string $businessId, int $limit): void
    {
        $match = knowledge_find_best_faq_match($businessId, $query);
        if ($match === null) {
            return;
        }

        $chunk = $match['chunk'] ?? null;
        if (!is_array($chunk)) {
            return;
        }

        $chunkId = (string) ($chunk['id'] ?? '');
        if ($chunkId === '') {
            return;
        }

        foreach ($selected as $existing) {
            if ((string) ($existing['id'] ?? '') === $chunkId) {
                return;
            }
        }

        $chunk['_score'] = 99.0;
        array_unshift($selected, $chunk);
        $selected = array_slice($selected, 0, $limit);
    }

    /**
     * @param list<array<string, mixed>> $selected
     * @param list<array<string, mixed>> $allChunks
     */
    private function ensurePolicyCoverage(array &$selected, array $allChunks, string $query): void
    {
        $needed = [];
        if ($this->matchesPolicyIntent($query, 'delivery')) {
            $needed[] = 'delivery';
        }
        if ($this->matchesPolicyIntent($query, 'payment')) {
            $needed[] = 'payment';
        }
        if ($this->matchesPolicyIntent($query, 'return')) {
            $needed[] = 'returns';
        }

        if ($needed === []) {
            return;
        }

        $selectedIds = [];
        foreach ($selected as $chunk) {
            $selectedIds[(string) ($chunk['id'] ?? '')] = true;
        }

        foreach ($allChunks as $chunk) {
            if ((string) ($chunk['source_type'] ?? '') !== 'policy') {
                continue;
            }

            $sourceId = (string) ($chunk['source_id'] ?? '');
            if (!in_array($sourceId, $needed, true)) {
                continue;
            }

            $chunkId = (string) ($chunk['id'] ?? '');
            if ($chunkId === '' || isset($selectedIds[$chunkId])) {
                continue;
            }

            $chunk['_score'] = 99.0;
            $selected[] = $chunk;
            $selectedIds[$chunkId] = true;
        }
    }

    private function matchesPolicyIntent(string $query, string $intent): bool
    {
        $query = strtolower($query);

        return match ($intent) {
            'delivery' => (bool) preg_match('/\b(deliver|delivery|ship|shipping|dispatch|location|address)\b/', $query),
            'payment' => (bool) preg_match('/\b(pay|payment|transfer|bank|account|price|cost|how much)\b/', $query),
            'return' => (bool) preg_match('/\b(return|refund|cancel|exchange)\b/', $query),
            default => false,
        };
    }
}

function knowledge_tokenize(string $text): array
{
    $text = strtolower($text);
    $parts = preg_split('/[^a-z0-9]+/', $text) ?: [];
    $tokens = [];

    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '' || strlen($part) < 2 || in_array($part, KNOWLEDGE_STOP_WORDS, true)) {
            continue;
        }
        $tokens[$part] = true;
    }

    return array_keys($tokens);
}

function knowledge_has_visual_intent(string $query): bool
{
    return (bool) preg_match(
        '/\b(photo|picture|pic|image|show me|see it|look like|post|reel|video|catalog|this one|that one|commented|comment)\b/i',
        $query
    );
}

function knowledge_excerpt(string $text, int $limit = 120): string
{
    $normalized = trim(preg_replace('/\s+/', ' ', $text));
    if ($normalized === '') {
        return '';
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($normalized, 0, $limit, '...');
    }

    return strlen($normalized) > $limit
        ? substr($normalized, 0, $limit - 3) . '...'
        : $normalized;
}

/**
 * @param list<array<string, mixed>> $chunks
 */
function knowledge_format_for_prompt(array $chunks): string
{
    if ($chunks === []) {
        return '- No extra knowledge matched this message yet.';
    }

    $lines = [];
    foreach ($chunks as $chunk) {
        $label = trim((string) ($chunk['title'] ?? 'Knowledge'));
        $sourceType = (string) ($chunk['source_type'] ?? 'info');
        $content = trim((string) ($chunk['content'] ?? ''));
        $imageNote = !empty($chunk['image_url']) ? ' [has photo]' : '';

        if ($sourceType === 'web_link') {
            $pageUrls = knowledge_extract_chunk_urls($chunk);
            $urlNote = $pageUrls !== [] ? ' | page URLs: ' . implode(', ', array_slice($pageUrls, 0, 6)) : '';
            $lines[] = '- [web_link] ' . $label . $urlNote . ': ' . knowledge_excerpt($content, 520);
            continue;
        }

        if ($sourceType === 'document') {
            $meta = knowledge_chunk_metadata($chunk);
            $chunkId = trim((string) ($chunk['id'] ?? ''));
            $fileName = trim((string) ($meta['file_name'] ?? ''));
            $usage = trim((string) ($meta['usage_instructions'] ?? ''));
            $fileNote = $chunkId !== '' && trim((string) ($meta['stored_name'] ?? '')) !== ''
                ? ' [has file, id:' . $chunkId . ', file:' . ($fileName !== '' ? $fileName : 'attachment') . ']'
                : '';
            $usageNote = $usage !== '' ? ' | Usage: ' . knowledge_excerpt($usage, 160) : '';
            $lines[] = '- [document] ' . $label . $fileNote . $usageNote . ': ' . knowledge_excerpt($content, 220);
            continue;
        }

        if ($sourceType === 'faq') {
            $answer = knowledge_extract_faq_answer($chunk);
            $lines[] = '- [faq — trained Q&A, use answer when question matches] Q: ' . $label
                . ' | A: ' . knowledge_excerpt($answer !== '' ? $answer : $content, 220);
            continue;
        }

        $lines[] = '- [' . $sourceType . '] ' . $label . $imageNote . ': ' . knowledge_excerpt($content, 180);
    }

    return implode("\n", $lines);
}

/**
 * @param array<string, mixed> $chunk
 * @return list<string>
 */
function knowledge_extract_chunk_urls(array $chunk): array
{
    $urls = [];
    $metadata = $chunk['metadata'] ?? null;
    if (is_string($metadata) && $metadata !== '') {
        $decoded = json_decode($metadata, true);
        $metadata = is_array($decoded) ? $decoded : null;
    }

    if (is_array($metadata)) {
        $rootUrl = trim((string) ($metadata['url'] ?? ''));
        if ($rootUrl !== '') {
            $urls[$rootUrl] = true;
        }

        $subpages = $metadata['subpages'] ?? null;
        if (is_array($subpages)) {
            foreach ($subpages as $subpage) {
                if (!is_array($subpage)) {
                    continue;
                }
                $pageUrl = trim((string) ($subpage['url'] ?? ''));
                if ($pageUrl !== '') {
                    $urls[$pageUrl] = true;
                }
            }
        }
    }

    $content = (string) ($chunk['content'] ?? '');
    if (preg_match_all('#^URL:\s*(https?://\S+)#mi', $content, $matches)) {
        foreach ($matches[1] as $match) {
            $urls[trim($match)] = true;
        }
    }

    return array_values(array_keys($urls));
}

/**
 * @param list<array<string, mixed>> $chunks
 * @return list<string>
 */
function knowledge_collect_image_urls(array $chunks): array
{
    $urls = [];
    foreach ($chunks as $chunk) {
        $url = trim((string) ($chunk['image_url'] ?? ''));
        if ($url !== '') {
            $urls[$url] = true;
        }
    }

    return array_keys($urls);
}

/**
 * @param array<string, mixed> $chunk
 * @return array<string, mixed>
 */
function knowledge_chunk_metadata(array $chunk): array
{
    $metadata = $chunk['metadata'] ?? null;
    if (is_string($metadata) && $metadata !== '') {
        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : [];
    }

    return is_array($metadata) ? $metadata : [];
}

/**
 * @param list<array<string, mixed>> $chunks
 * @return array<string, array{chunk_id: string, url: string, mime: string, file_name: string, delivery: string}>
 */
function knowledge_collect_attachment_catalog(array $chunks, string $businessId): array
{
    require_once dirname(__DIR__) . '/lib/KnowledgeAssetService.php';

    $catalog = [];
    foreach ($chunks as $chunk) {
        $asset = KnowledgeAssetService::attachmentFromChunk($chunk, $businessId);
        if ($asset === null) {
            continue;
        }

        $catalog[$asset['chunk_id']] = $asset;
    }

    return $catalog;
}

function knowledge_message_requests_file(string $message): bool
{
    $message = trim($message);
    if ($message === '') {
        return false;
    }

    // "Share the registration/signup link" is a URL request, not a document send.
    $asksForLink = (bool) preg_match(
        '/\b(link|url|website|webpage|form)\b/i',
        $message
    );
    $mentionsFileType = (bool) preg_match(
        '/\b(pdf|document|file|brochure|catalog|menu|resume|cv|curriculum\s+vitae|template|photo|picture|image|video|sample|example)\b/i',
        $message
    );
    if ($asksForLink && !$mentionsFileType) {
        return false;
    }

    if ($mentionsFileType) {
        return true;
    }

    return (bool) preg_match(
        '/\b(see|show|send|share|give|get|receive)\b.{0,48}\b(sample|example|copy|brochure|catalog|resume|cv|pdf|file|document|template|photo|picture|image|video)\b/i',
        $message
    );
}

/**
 * True when the assistant reply is declining / refusing the customer's request.
 */
function knowledge_reply_is_refusal(string $reply): bool
{
    $reply = trim($reply);
    if ($reply === '') {
        return false;
    }

    return (bool) preg_match(
        '/\b('
        . 'cannot join|can\'?t join|not eligible|not allowed|men can\'?t|men cannot|'
        . 'women only|women-only|sorry[,.]?\s+but|unable to (help with|join|register)|'
        . 'do not (qualify|meet)|doesn\'?t allow|not for (men|women|you)'
        . ')\b/i',
        $reply
    );
}

/**
 * @param list<array<string, mixed>> $chunks
 * @return list<array<string, mixed>>
 */
function knowledge_merge_document_chunks(string $businessId, array $chunks): array
{
    $byId = [];
    foreach ($chunks as $chunk) {
        $id = (string) ($chunk['id'] ?? '');
        if ($id !== '') {
            $byId[$id] = $chunk;
        }
    }

    foreach (RepositoryFactory::businessKnowledge()->listBySourceType($businessId, 'document', 25) as $document) {
        $id = (string) ($document['id'] ?? '');
        if ($id !== '') {
            $byId[$id] = $document;
        }
    }

    return array_values($byId);
}

/**
 * @param list<string> $attachmentIds
 * @return array<string, array{chunk_id: string, url: string, mime: string, file_name: string, delivery: string}>
 */
function knowledge_attachment_assets_for_ids(string $businessId, array $attachmentIds): array
{
    require_once dirname(__DIR__) . '/lib/KnowledgeAssetService.php';

    $assets = [];
    foreach ($attachmentIds as $chunkId) {
        $chunkId = trim((string) $chunkId);
        if ($chunkId === '') {
            continue;
        }

        $row = RepositoryFactory::businessKnowledge()->findById($chunkId, $businessId);
        if ($row === null) {
            continue;
        }

        $asset = KnowledgeAssetService::attachmentFromChunk($row, $businessId);
        if ($asset !== null) {
            $assets[$chunkId] = $asset;
        }
    }

    return $assets;
}

/**
 * @param array<string, array{chunk_id: string, url: string, mime: string, file_name: string, delivery: string}> $assets
 */
function knowledge_polish_attachment_reply(string $reply, array $assets): string
{
    if ($assets === []) {
        return trim($reply);
    }

    // Never rewrite a refusal into "sending the file now".
    if (knowledge_reply_is_refusal($reply)) {
        return trim($reply);
    }

    if (preg_match('/\b(don\'?t have|do not have|can\'?t provide|cannot provide|not available|can\'?t accept|unable to (send|share|provide))\b/i', $reply)) {
        $reply = 'Sure. I am sending the file now.';
    }

    foreach ($assets as $asset) {
        $url = trim((string) ($asset['url'] ?? ''));
        if ($url !== '' && !str_contains($reply, $url)) {
            $reply = trim($reply) . "\n\n" . $url;
        }
    }

    return trim($reply);
}

/**
 * Score how well a document chunk matches a customer message for attachment delivery.
 *
 * @param array<string, mixed> $chunk
 */
function knowledge_attachment_relevance_score(string $message, array $chunk): float
{
    $normalized = mb_strtolower(trim($message));
    if ($normalized === '' || (string) ($chunk['source_type'] ?? '') !== 'document') {
        return 0.0;
    }

    $meta = knowledge_chunk_metadata($chunk);
    if (trim((string) ($meta['stored_name'] ?? '')) === '') {
        return 0.0;
    }

    $title = mb_strtolower(trim((string) ($chunk['title'] ?? '')));
    $fileName = mb_strtolower(trim((string) ($meta['file_name'] ?? '')));
    $usage = mb_strtolower(trim((string) ($meta['usage_instructions'] ?? '')));
    $content = mb_strtolower(trim((string) ($chunk['content'] ?? '')));
    $keywords = mb_strtolower(trim((string) ($chunk['keywords'] ?? '')));
    $haystack = trim($title . ' ' . $fileName . ' ' . $usage . ' ' . $content . ' ' . $keywords);
    if ($haystack === '') {
        return 0.0;
    }

    $score = 0.0;

    // Strong topical cues: both sides mention the same document type.
    $typePairs = [
        ['resume', 'cv', 'curriculum'],
        ['brochure', 'flyer'],
        ['catalog', 'catalogue', 'price list'],
        ['menu'],
        ['template'],
        ['invoice', 'receipt'],
        ['guide', 'handbook', 'manual'],
    ];
    foreach ($typePairs as $group) {
        $inMessage = false;
        $inDoc = false;
        foreach ($group as $term) {
            if (str_contains($normalized, $term)) {
                $inMessage = true;
            }
            if (str_contains($haystack, $term)) {
                $inDoc = true;
            }
        }
        if ($inMessage && $inDoc) {
            $score += 8.0;
        } elseif ($inMessage && !$inDoc) {
            // Customer asked for a specific type this document is not.
            $score -= 6.0;
        }
    }

    // Content-word overlap (ignore generic verbs like share/send/please).
    $stop = [
        'please' => true, 'share' => true, 'send' => true, 'show' => true, 'give' => true,
        'with' => true, 'from' => true, 'have' => true, 'this' => true, 'that' => true,
        'your' => true, 'you' => true, 'can' => true, 'could' => true, 'would' => true,
        'link' => true, 'file' => true, 'document' => true, 'sample' => true, 'example' => true,
        'copy' => true, 'want' => true, 'need' => true, 'just' => true, 'also' => true,
        'here' => true, 'there' => true, 'about' => true, 'into' => true, 'them' => true,
    ];
    $contentTokens = [];
    foreach (knowledge_tokenize($message) as $token) {
        if (strlen($token) < 4 || isset($stop[$token])) {
            continue;
        }
        $contentTokens[$token] = true;
    }

    $titleHits = 0;
    $fileHits = 0;
    $bodyHits = 0;
    foreach (array_keys($contentTokens) as $token) {
        if ($title !== '' && str_contains($title, $token)) {
            $titleHits++;
            $score += 3.5;
        }
        if ($fileName !== '' && str_contains($fileName, $token)) {
            $fileHits++;
            $score += 3.0;
        }
        if (str_contains($content . ' ' . $keywords, $token)) {
            $bodyHits++;
            $score += 1.25;
        }
        if ($usage !== '' && str_contains($usage, $token)) {
            $score += 1.5;
        }
    }

    // Usage instructions only help after some topical signal exists.
    if (($titleHits + $fileHits + $bodyHits) > 0 && $usage !== ''
        && preg_match('/\b(show|send|share|when someone asks|if someone asks)\b/i', $usage)
    ) {
        $score += 2.0;
    }

    // Exact phrase from title/filename in the customer message.
    foreach ([$title, preg_replace('/\.[a-z0-9]+$/', '', $fileName) ?? $fileName] as $label) {
        $label = trim((string) $label);
        if (strlen($label) >= 5 && str_contains($normalized, $label)) {
            $score += 10.0;
        }
    }

    return $score;
}

/**
 * Keep only attachment ids that are topically relevant to the customer message.
 *
 * @param list<string> $attachmentIds
 * @param list<array<string, mixed>> $chunks
 * @return list<string>
 */
function knowledge_filter_relevant_attachment_ids(
    string $message,
    array $attachmentIds,
    array $chunks = [],
    string $businessId = '',
    float $minScore = 5.0
): array {
    if ($attachmentIds === [] || !knowledge_message_requests_file($message)) {
        return [];
    }

    $lookup = [];
    foreach ($chunks as $chunk) {
        $id = (string) ($chunk['id'] ?? '');
        if ($id !== '') {
            $lookup[$id] = $chunk;
        }
    }
    if ($businessId !== '') {
        foreach (RepositoryFactory::businessKnowledge()->listBySourceType($businessId, 'document', 25) as $document) {
            $id = (string) ($document['id'] ?? '');
            if ($id !== '') {
                $lookup[$id] = $document;
            }
        }
    }

    $kept = [];
    foreach ($attachmentIds as $chunkId) {
        $chunkId = trim((string) $chunkId);
        if ($chunkId === '' || !isset($lookup[$chunkId])) {
            continue;
        }
        if (knowledge_attachment_relevance_score($message, $lookup[$chunkId]) >= $minScore) {
            $kept[] = $chunkId;
        }
    }

    return array_values(array_unique($kept));
}

/**
 * @param list<array<string, mixed>> $chunks
 * @return list<string>
 */
function knowledge_infer_attachment_ids(string $message, array $chunks, string $businessId = ''): array
{
    if (!knowledge_message_requests_file($message)) {
        return [];
    }

    if ($businessId !== '') {
        $chunks = knowledge_merge_document_chunks($businessId, $chunks);
    }

    $bestId = '';
    $bestScore = 0.0;

    foreach ($chunks as $chunk) {
        $score = knowledge_attachment_relevance_score($message, $chunk);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestId = (string) ($chunk['id'] ?? '');
        }
    }

    // Require clear topical overlap — never attach "the only PDF we have".
    return $bestId !== '' && $bestScore >= 5.0 ? [$bestId] : [];
}

function knowledge_format_referral_context(?string $referralJson): ?string
{
    if ($referralJson === null || trim($referralJson) === '') {
        return null;
    }

    $data = json_decode($referralJson, true);
    if (!is_array($data)) {
        return trim($referralJson);
    }

    $parts = [];
    foreach (['source', 'type', 'ref', 'ad_id', 'post_id'] as $key) {
        if (!empty($data[$key])) {
            $parts[] = $key . ': ' . (string) $data[$key];
        }
    }

    $ads = $data['ads_context_data'] ?? null;
    if (is_array($ads)) {
        foreach (['ad_title', 'post_id', 'photo_url', 'video_url'] as $key) {
            if (!empty($ads[$key])) {
                $parts[] = $key . ': ' . (string) $ads[$key];
            }
        }
    }

    return $parts !== [] ? 'Customer came from: ' . implode(' | ', $parts) : null;
}

function knowledge_format_sync_time(?string $timestamp): string
{
    if ($timestamp === null || trim($timestamp) === '') {
        return 'Never';
    }

    $parsed = strtotime($timestamp);
    if ($parsed === false) {
        return 'Never';
    }

    $delta = time() - $parsed;
    if ($delta < 60) {
        return 'Just now';
    }
    if ($delta < 3600) {
        return (string) max(1, (int) floor($delta / 60)) . 'm ago';
    }
    if ($delta < 86400) {
        return (string) max(1, (int) floor($delta / 3600)) . 'h ago';
    }

    return date('M j, g:ia', $parsed);
}

function knowledge_source_label(string $sourceType): string
{
    return match ($sourceType) {
        'social_post' => 'Social post',
        'learned_memory' => 'Learned from chats',
        'offering' => 'Catalog item',
        'faq' => 'FAQ',
        'policy' => 'Policy',
        'profile' => 'Business info',
        default => ucfirst(str_replace('_', ' ', $sourceType)),
    };
}

function knowledge_normalize_question(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text) ?? $text;
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;

    return trim($text);
}

function knowledge_score_faq_question_match(string $message, string $question): float
{
    $messageNorm = knowledge_normalize_question($message);
    $questionNorm = knowledge_normalize_question($question);
    if ($messageNorm === '' || $questionNorm === '') {
        return 0.0;
    }

    if ($messageNorm === $questionNorm) {
        return 100.0;
    }

    if (str_contains($messageNorm, $questionNorm) || str_contains($questionNorm, $messageNorm)) {
        return 85.0;
    }

    $score = 0.0;
    similar_text($messageNorm, $questionNorm, $score);

    foreach (knowledge_tokenize($message) as $token) {
        if (strlen($token) >= 4 && str_contains($questionNorm, $token)) {
            $score += 8.0;
        }
    }

    return $score;
}

/**
 * @return array{chunk: array<string, mixed>, score: float, answer: string}|null
 */
function knowledge_find_best_faq_match(string $businessId, string $message): ?array
{
    $message = trim($message);
    if ($message === '' || $businessId === '') {
        return null;
    }

    $candidates = [];

    foreach (RepositoryFactory::businessKnowledge()->listBySourceType($businessId, 'faq', 50) as $chunk) {
        $question = trim((string) ($chunk['title'] ?? ''));
        $score = knowledge_score_faq_question_match($message, $question);
        if ($score <= 0) {
            continue;
        }

        $candidates[] = [
            'chunk' => $chunk,
            'score' => $score,
            'answer' => knowledge_extract_faq_answer($chunk),
        ];
    }

    if ($candidates === []) {
        $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId);
        if ($settings !== null) {
            foreach (faq_entries_parse(isset($settings['faq']) ? (string) $settings['faq'] : null) as $entry) {
                $question = trim((string) ($entry['q'] ?? ''));
                $score = knowledge_score_faq_question_match($message, $question);
                if ($score <= 0) {
                    continue;
                }

                $answer = faq_entry_answer_text($entry);
                $candidates[] = [
                    'chunk' => [
                        'id' => (string) ($entry['id'] ?? ''),
                        'title' => $question,
                        'content' => 'Q: ' . $question . "\nA: " . $answer,
                        'source_type' => 'faq',
                    ],
                    'score' => $score,
                    'answer' => $answer,
                ];
            }
        }
    }

    if ($candidates === []) {
        return null;
    }

    usort($candidates, static function (array $a, array $b): int {
        return ($b['score'] <=> $a['score']);
    });

    $best = $candidates[0];

    return (float) ($best['score'] ?? 0) >= 55.0 ? $best : null;
}

/**
 * @param array<string, mixed> $chunk
 */
function knowledge_extract_faq_answer(array $chunk): string
{
    $content = trim((string) ($chunk['content'] ?? ''));
    if ($content !== '' && preg_match('/\bA:\s*(.+)$/s', $content, $matches) === 1) {
        return trim($matches[1]);
    }

    return $content;
}

/**
 * @param array{chunk: array<string, mixed>, score: float, answer: string}|null $faqMatch
 */
function knowledge_polish_faq_reply(string $reply, ?array $faqMatch): string
{
    if ($faqMatch === null) {
        return trim($reply);
    }

    $answer = trim((string) ($faqMatch['answer'] ?? ''));
    $score = (float) ($faqMatch['score'] ?? 0);
    if ($answer === '' || $score < 55.0) {
        return trim($reply);
    }

    if ($score >= 75.0) {
        return $answer;
    }

    if (preg_match('/\b(not sure|don\'?t know|do not know|i\'?m not sure|unsure|can\'?t help with that|no information)\b/i', $reply)) {
        return $answer;
    }

    return trim($reply);
}
