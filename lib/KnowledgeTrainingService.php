<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';
require_once dirname(__DIR__) . '/includes/openai-client.php';

/**
 * User-facing knowledge training: text, URLs, documents, and FAQs.
 */
final class KnowledgeTrainingService
{
    public const SOURCE_CUSTOM = 'custom_text';
    public const SOURCE_LINK = 'web_link';
    public const SOURCE_DOCUMENT = 'document';

    private const LEGACY_KNOWLEDGE_SOURCE_ID = 'knowledge_notes';
    private const KNOWLEDGE_ENTRY_PREFIX = 'entry_';

    private const MAX_TEXT_CHARS = 20000;
    private const MAX_URL_CHARS = 50000;
    private const MAX_FILE_BYTES = 5_242_880;

    public const KNOWLEDGE_CHAR_LIMIT = 10000;
    public const DOCUMENT_USAGE_CHAR_LIMIT = 300;

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * @return array{
     *     business_description: string,
     *     knowledge_notes: string,
     *     knowledge_content: string,
     *     knowledge_entries: list<array{id: string, title: string, content: string, char_count: int, learned_at: string}>,
     *     knowledge_count: int,
     *     links: list<array{id: string, title: string, url: string, status: string, subpages: list<array{url: string, title: string}>, subpage_count: int, learned_at: string, recrawl_enabled: bool, recrawl_frequency: string, error_message: string}>,
     *     link_count: int,
     *     link_subpage_count: int,
     *     link_count_label: string,
     *     documents: list<array{id: string, title: string, subheading: string, file_name: string, char_count: int, learned_at: string, preview_url: string, is_image: bool, usage_instructions: string}>,
     *     document_count: int,
     *     faqs: list<array{id: string, q: string, a: string, answer_type: string, payload: array<string, mixed>, char_count: int, learned_at: string}>,
     *     faq_count: int,
     *     faq_count_label: string,
     */
    public function getTrainingState(string $businessId): array
    {
        $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId)
            ?? RepositoryFactory::businessSettings()->create($businessId);

        $knowledgeEntries = $this->listKnowledgeEntries($businessId);

        $links = $this->listLinks($businessId);
        $linkSubpageCount = array_sum(array_map(static fn (array $link): int => (int) ($link['subpage_count'] ?? 0), $links));

        $documents = $this->listDocuments($businessId);

        $faqEntries = $this->listFaqEntries($businessId);

        return [
            'business_description' => trim((string) ($settings['business_description'] ?? '')),
            'knowledge_notes' => '',
            'knowledge_content' => '',
            'knowledge_entries' => $knowledgeEntries,
            'knowledge_count' => count($knowledgeEntries),
            'links' => $links,
            'link_count' => count($links),
            'link_subpage_count' => $linkSubpageCount,
            'link_count_label' => self::formatLinkCountLabel(count($links), $linkSubpageCount),
            'documents' => $documents,
            'document_count' => count($documents),
            'faqs' => $faqEntries,
            'faq_count' => count($faqEntries),
            'faq_count_label' => self::formatFaqCountLabel(count($faqEntries)),
        ];
    }

    /**
     * @return list<array{id: string, title: string, content: string, char_count: int, learned_at: string}>
     */
    public function listKnowledgeEntries(string $businessId): array
    {
        $entries = [];
        foreach (RepositoryFactory::businessKnowledge()->listBySourceType($businessId, self::SOURCE_CUSTOM, 100) as $row) {
            $sourceId = (string) ($row['source_id'] ?? '');
            if ($sourceId !== self::LEGACY_KNOWLEDGE_SOURCE_ID && !str_starts_with($sourceId, self::KNOWLEDGE_ENTRY_PREFIX)) {
                continue;
            }

            $content = trim((string) ($row['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            $entries[] = $this->mapKnowledgeEntryRow($row);
        }

        usort($entries, static function (array $a, array $b): int {
            return strcmp((string) ($b['learned_at'] ?? ''), (string) ($a['learned_at'] ?? ''));
        });

        return $entries;
    }

    /**
     * @return array{id: string, title: string, content: string, char_count: int, learned_at: string}
     */
    public function saveTextKnowledge(string $businessId, string $content, ?string $chunkId = null): array
    {
        $content = $this->truncate(trim($content), self::KNOWLEDGE_CHAR_LIMIT);
        if ($content === '') {
            throw new InvalidArgumentException('Enter some knowledge for your AI to learn from.');
        }

        $repo = RepositoryFactory::businessKnowledge();
        $sourceId = self::KNOWLEDGE_ENTRY_PREFIX . substr(str_replace('-', '', Uuid::v4()), 0, 16);

        if ($chunkId !== null && $chunkId !== '') {
            $existing = $repo->findById($chunkId, $businessId);
            if (!is_array($existing) || (string) ($existing['source_type'] ?? '') !== self::SOURCE_CUSTOM) {
                throw new InvalidArgumentException('That knowledge entry was not found.');
            }

            $existingSourceId = (string) ($existing['source_id'] ?? '');
            if ($existingSourceId === self::LEGACY_KNOWLEDGE_SOURCE_ID) {
                $repo->deleteBySource($businessId, self::SOURCE_CUSTOM, self::LEGACY_KNOWLEDGE_SOURCE_ID);
            } elseif (str_starts_with($existingSourceId, self::KNOWLEDGE_ENTRY_PREFIX)) {
                $sourceId = $existingSourceId;
            }
        }

        $title = $this->generateKnowledgeTitle($businessId, $content);
        $savedChunkId = $repo->upsertChunk($businessId, self::SOURCE_CUSTOM, [
            'source_id' => $sourceId,
            'title' => $title,
            'content' => $content,
            'keywords' => $title . ' ' . $content,
        ]);

        (new KnowledgeEmbeddingService())->embedBusinessChunks($businessId);
        (new BusinessKnowledgeSyncService())->syncManualSources($businessId);

        $saved = $repo->findById($savedChunkId, $businessId);
        if (!is_array($saved)) {
            throw new RuntimeException('Could not save knowledge.');
        }

        return $this->mapKnowledgeEntryRow($saved);
    }

    public function deleteKnowledgeEntry(string $businessId, string $chunkId): void
    {
        $row = RepositoryFactory::businessKnowledge()->findById($chunkId, $businessId);
        if (!is_array($row) || (string) ($row['source_type'] ?? '') !== self::SOURCE_CUSTOM) {
            throw new InvalidArgumentException('That knowledge entry was not found.');
        }

        RepositoryFactory::businessKnowledge()->deleteById($chunkId, $businessId);
        (new BusinessKnowledgeSyncService())->syncManualSources($businessId);
    }

    public static function formatEntryCountLabel(int $count): string
    {
        if ($count <= 0) {
            return 'Add information to train your AI.';
        }

        return $count === 1 ? '1 entry' : $count . ' entries';
    }

    /**
     * @return array{url: string, pages: list<array{url: string, title: string, path: string}>}
     */
    public function discoverLinkPages(string $url): array
    {
        $url = $this->normalizeUrl($url);
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            throw new InvalidArgumentException('Enter a valid website link.');
        }

        $pages = (new WebsiteCrawlService())->discoverPages($url);
        if ($pages === []) {
            throw new RuntimeException('Could not find pages on that site. Check the URL and try again.');
        }

        return ['url' => $url, 'pages' => $pages];
    }

    /**
     * @param list<string> $selectedUrls
     * @param array{recrawl_enabled?: bool, recrawl_frequency?: string} $options
     * @return array{id: string, title: string, url: string, status: string, subpages: list<array{url: string, title: string}>, subpage_count: int, learned_at: string, recrawl_enabled: bool, recrawl_frequency: string, error_message: string}
     */
    public function saveLinkTraining(
        string $businessId,
        string $url,
        array $selectedUrls,
        array $options = [],
        ?string $chunkId = null
    ): array {
        $url = $this->normalizeUrl($url);
        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($host === '') {
            throw new InvalidArgumentException('Enter a valid website link.');
        }

        $selectedUrls = $this->sanitizeSelectedUrls($url, $selectedUrls);
        if ($selectedUrls === []) {
            throw new InvalidArgumentException('Select at least one page to train on.');
        }

        $recrawlEnabled = (bool) ($options['recrawl_enabled'] ?? false);
        $recrawlFrequency = $this->normalizeRecrawlFrequency((string) ($options['recrawl_frequency'] ?? 'monthly'));
        $sourceId = 'link_' . substr(hash('sha256', $url), 0, 16);
        $repo = RepositoryFactory::businessKnowledge();

        if ($chunkId !== null && $chunkId !== '') {
            $existing = $repo->findById($chunkId, $businessId);
            if (!is_array($existing) || (string) ($existing['source_type'] ?? '') !== self::SOURCE_LINK) {
                throw new InvalidArgumentException('That link was not found.');
            }

            $existingMeta = $this->decodeMetadata($existing['metadata'] ?? null);
            $sourceId = (string) ($existing['source_id'] ?? $sourceId);
            if (isset($existingMeta['url']) && (string) $existingMeta['url'] !== $url) {
                $sourceId = 'link_' . substr(hash('sha256', $url), 0, 16);
            }
        }

        $subpages = array_map(static function (string $pageUrl) use ($host): array {
            $path = (string) parse_url($pageUrl, PHP_URL_PATH);
            $segment = trim(basename($path !== '' ? $path : '/'), '/');
            $title = $segment !== '' ? ucwords(str_replace(['-', '_'], ' ', $segment)) : $host;

            return ['url' => $pageUrl, 'title' => $title];
        }, $selectedUrls);

        $chunkPayload = [
            'source_id' => $sourceId,
            'title' => $host,
            'content' => 'Processing website content…',
            'keywords' => $url . ' ' . $host,
            'metadata' => [
                'url' => $url,
                'host' => $host,
                'status' => 'processing',
                'subpages' => $subpages,
                'subpage_count' => count($subpages),
                'selected_urls' => $selectedUrls,
                'recrawl_enabled' => $recrawlEnabled,
                'recrawl_frequency' => $recrawlFrequency,
                'error_message' => '',
            ],
        ];

        if ($chunkId !== null && $chunkId !== '') {
            $repo->updateChunkById($chunkId, $businessId, $chunkPayload);
            $savedChunkId = $chunkId;
        } else {
            $savedChunkId = $repo->upsertChunk($businessId, self::SOURCE_LINK, $chunkPayload);
        }

        $saved = $repo->findById($savedChunkId, $businessId);
        if (!is_array($saved)) {
            throw new RuntimeException('Could not save link.');
        }

        return $this->mapLinkRow($saved);
    }

    /**
     * @return array{id: string, title: string, url: string, status: string, subpages: list<array{url: string, title: string}>, subpage_count: int, learned_at: string, recrawl_enabled: bool, recrawl_frequency: string, error_message: string}
     */
    public function processLinkTraining(string $businessId, string $chunkId): array
    {
        $repo = RepositoryFactory::businessKnowledge();
        $row = $repo->findById($chunkId, $businessId);
        if (!is_array($row) || (string) ($row['source_type'] ?? '') !== self::SOURCE_LINK) {
            throw new InvalidArgumentException('That link was not found.');
        }

        $meta = $this->decodeMetadata($row['metadata'] ?? null);
        $status = (string) ($meta['status'] ?? 'ready');
        if ($status === 'ready') {
            return $this->mapLinkRow($row);
        }

        $selectedUrls = is_array($meta['selected_urls'] ?? null) ? $meta['selected_urls'] : [];
        $selectedUrls = array_values(array_filter(array_map('strval', $selectedUrls)));
        if ($selectedUrls === []) {
            $url = (string) ($meta['url'] ?? '');
            if ($url !== '') {
                $selectedUrls = [$url];
            }
        }

        try {
            $fetched = (new WebsiteCrawlService())->fetchPages($selectedUrls);
            if ($fetched === []) {
                throw new RuntimeException('Could not read useful text from the selected pages.');
            }

            $subpages = [];
            $contentParts = [];
            foreach ($fetched as $page) {
                $subpages[] = [
                    'url' => (string) $page['url'],
                    'title' => (string) $page['title'],
                ];
                $contentParts[] = '## ' . $page['title'] . "\nURL: " . $page['url'] . "\n\n" . $page['text'];
            }

            $content = $this->truncate(implode("\n\n---\n\n", $contentParts), self::MAX_URL_CHARS);
            $rootUrl = (string) ($meta['url'] ?? ($fetched[0]['url'] ?? ''));
            $host = (string) parse_url($rootUrl, PHP_URL_HOST);

            $repo->updateChunkById($chunkId, $businessId, [
                'source_id' => (string) ($row['source_id'] ?? ('link_' . substr(hash('sha256', $rootUrl), 0, 16))),
                'title' => $host !== '' ? $host : 'Website link',
                'content' => $content,
                'keywords' => trim($rootUrl . ' ' . implode(' ', array_merge(
                    array_column($subpages, 'title'),
                    array_column($subpages, 'url')
                ))),
                'metadata' => array_merge($meta, [
                    'status' => 'ready',
                    'subpages' => $subpages,
                    'subpage_count' => count($subpages),
                    'error_message' => '',
                    'processed_at' => date('c'),
                ]),
            ]);

            (new KnowledgeEmbeddingService())->embedBusinessChunks($businessId);
            (new BusinessKnowledgeSyncService())->syncManualSources($businessId);
        } catch (Throwable $e) {
            $repo->updateChunkById($chunkId, $businessId, [
                'source_id' => (string) ($row['source_id'] ?? ''),
                'title' => (string) ($row['title'] ?? 'Website link'),
                'content' => (string) ($row['content'] ?? 'Processing website content…'),
                'keywords' => (string) ($row['keywords'] ?? ''),
                'metadata' => array_merge($meta, [
                    'status' => 'error',
                    'error_message' => $e->getMessage(),
                ]),
            ]);

            throw $e;
        }

        $saved = $repo->findById($chunkId, $businessId);
        if (!is_array($saved)) {
            throw new RuntimeException('Could not finish processing link.');
        }

        return $this->mapLinkRow($saved);
    }

    /**
     * @return array{id: string, title: string, url: string, status: string, subpages: list<array{url: string, title: string}>, subpage_count: int, learned_at: string, recrawl_enabled: bool, recrawl_frequency: string, error_message: string}
     */
    public function getLinkStatus(string $businessId, string $chunkId): array
    {
        $row = RepositoryFactory::businessKnowledge()->findById($chunkId, $businessId);
        if (!is_array($row) || (string) ($row['source_type'] ?? '') !== self::SOURCE_LINK) {
            throw new InvalidArgumentException('That link was not found.');
        }

        return $this->mapLinkRow($row);
    }

    /**
     * @return list<array{id: string, title: string, url: string, status: string, subpages: list<array{url: string, title: string}>, subpage_count: int, learned_at: string, recrawl_enabled: bool, recrawl_frequency: string, error_message: string}>
     */
    public function listLinks(string $businessId): array
    {
        $links = [];
        foreach (RepositoryFactory::businessKnowledge()->listBySourceType($businessId, self::SOURCE_LINK, 50) as $row) {
            $links[] = $this->mapLinkRow($row);
        }

        usort($links, static function (array $a, array $b): int {
            return strcmp((string) ($b['learned_at'] ?? ''), (string) ($a['learned_at'] ?? ''));
        });

        return $links;
    }

    public static function formatLinkCountLabel(int $linkCount, int $subpageCount): string
    {
        if ($linkCount <= 0) {
            return 'Add website URLs to train your agent with dynamic information.';
        }

        if ($linkCount === 1) {
            if ($subpageCount <= 1) {
                return '1 link';
            }

            return '1 link, ' . $subpageCount . ' subpages';
        }

        return $linkCount . ' links, ' . $subpageCount . ' subpages';
    }

    public function deleteLink(string $businessId, string $chunkId): void
    {
        RepositoryFactory::businessKnowledge()->deleteById($chunkId, $businessId);
        (new BusinessKnowledgeSyncService())->syncManualSources($businessId);
    }

    public static function formatFileCountLabel(int $count): string
    {
        if ($count <= 0) {
            return 'Upload files to train your agent.';
        }

        return $count === 1 ? '1 file' : $count . ' files';
    }

    /**
     * @return list<array{id: string, title: string, subheading: string, file_name: string, char_count: int, learned_at: string, preview_url: string, is_image: bool, usage_instructions: string}>
     */
    public function listDocuments(string $businessId): array
    {
        $documents = [];
        foreach (RepositoryFactory::businessKnowledge()->listBySourceType($businessId, self::SOURCE_DOCUMENT, 100) as $row) {
            $documents[] = $this->mapDocumentRow($row);
        }

        usort($documents, static function (array $a, array $b): int {
            return strcmp((string) ($b['learned_at'] ?? ''), (string) ($a['learned_at'] ?? ''));
        });

        return $documents;
    }

    /**
     * @return array{id: string, title: string, subheading: string, file_name: string, char_count: int, learned_at: string, preview_url: string, is_image: bool, usage_instructions: string}
     */
    public function importDocument(string $businessId, array $file, string $usageInstructions = ''): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed. Please try again.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_BYTES) {
            throw new InvalidArgumentException('File must be smaller than 5 MB.');
        }

        $originalName = trim((string) ($file['name'] ?? 'document'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['txt', 'md', 'csv', 'pdf', ...self::IMAGE_EXTENSIONS];
        if (!in_array($extension, $allowed, true)) {
            throw new InvalidArgumentException('Upload a .txt, .md, .csv, .pdf, or image file (.jpg, .png, .webp).');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $dir = dirname(__DIR__) . '/storage/uploads/knowledge/' . $businessId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not save the uploaded file.');
        }

        $storedName = Uuid::v4() . '_' . preg_replace('/[^a-zA-Z0-9._-]+/', '_', $originalName);
        $storedPath = $dir . '/' . $storedName;
        if (!move_uploaded_file($tmpPath, $storedPath)) {
            throw new RuntimeException('Could not save the uploaded file.');
        }

        $usageInstructions = $this->truncate(trim($usageInstructions), self::DOCUMENT_USAGE_CHAR_LIMIT);
        $isImage = $this->isImageExtension($extension);
        $analysis = $this->analyzeDocumentWithAi($businessId, $storedPath, $extension, $usageInstructions, $originalName);
        $title = $analysis['title'];
        $subheading = $analysis['subheading'];
        $extractedText = $analysis['extracted_text'];

        if ($extractedText === '' && $usageInstructions === '') {
            @unlink($storedPath);
            throw new RuntimeException('Could not read useful information from that file.');
        }

        $content = $this->buildDocumentContent($title, $subheading, $extractedText, $usageInstructions);
        $sourceId = 'doc_' . substr(hash('sha256', $storedName), 0, 16);
        $chunkId = RepositoryFactory::businessKnowledge()->upsertChunk($businessId, self::SOURCE_DOCUMENT, [
            'source_id' => $sourceId,
            'title' => $title,
            'content' => $this->truncate($content, self::MAX_URL_CHARS),
            'keywords' => $title . ' ' . $subheading . ' ' . $originalName . ' document file',
            'metadata' => [
                'file_name' => $originalName,
                'stored_name' => $storedName,
                'extension' => $extension,
                'subheading' => $subheading,
                'extracted_text' => $extractedText,
                'usage_instructions' => $usageInstructions,
                'is_image' => $isImage,
            ],
        ]);

        (new KnowledgeEmbeddingService())->embedBusinessChunks($businessId);

        $saved = RepositoryFactory::businessKnowledge()->findById($chunkId, $businessId);
        if (!is_array($saved)) {
            throw new RuntimeException('Could not save the uploaded file.');
        }

        return $this->mapDocumentRow($saved);
    }

    public function deleteDocument(string $businessId, string $chunkId): void
    {
        $row = RepositoryFactory::businessKnowledge()->findById($chunkId, $businessId);

        if (is_array($row)) {
            $meta = $this->decodeMetadata($row['metadata'] ?? null);
            $storedName = trim((string) ($meta['stored_name'] ?? ''));
            if ($storedName !== '') {
                $path = dirname(__DIR__) . '/storage/uploads/knowledge/' . $businessId . '/' . $storedName;
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }

        RepositoryFactory::businessKnowledge()->deleteById($chunkId, $businessId);
    }

    /**
     * @return array{path: string, mime: string, file_name: string}|null
     */
    public function resolveDocumentFile(string $businessId, string $chunkId): ?array
    {
        $row = RepositoryFactory::businessKnowledge()->findById($chunkId, $businessId);
        if (!is_array($row) || (string) ($row['source_type'] ?? '') !== self::SOURCE_DOCUMENT) {
            return null;
        }

        $meta = $this->decodeMetadata($row['metadata'] ?? null);
        $storedName = trim((string) ($meta['stored_name'] ?? ''));
        if ($storedName === '') {
            return null;
        }

        $path = dirname(__DIR__) . '/storage/uploads/knowledge/' . $businessId . '/' . $storedName;
        if (!is_file($path)) {
            return null;
        }

        $extension = strtolower((string) ($meta['extension'] ?? pathinfo($storedName, PATHINFO_EXTENSION)));

        return [
            'path' => $path,
            'mime' => $this->mimeForExtension($extension),
            'file_name' => trim((string) ($meta['file_name'] ?? $storedName)),
        ];
    }

    /**
     * @return list<array{id: string, q: string, a: string, answer_type: string, payload: array<string, mixed>, char_count: int, learned_at: string}>
     */
    public function listFaqEntries(string $businessId): array
    {
        $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId)
            ?? RepositoryFactory::businessSettings()->create($businessId);

        $entries = [];
        foreach (faq_entries_parse(isset($settings['faq']) ? (string) $settings['faq'] : null) as $entry) {
            $entries[] = $this->mapFaqEntry($entry);
        }

        return $entries;
    }

    public static function formatFaqCountLabel(int $count): string
    {
        if ($count <= 0) {
            return 'Provide a question-and-answer pairing your agent can use in conversations.';
        }

        return $count === 1 ? '1 answer' : $count . ' answers';
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{id: string, q: string, a: string, answer_type: string, payload: array<string, mixed>, char_count: int, learned_at: string}
     */
    public function saveFaqEntry(
        string $businessId,
        string $question,
        string $answerType,
        array $payload,
        ?string $entryId = null
    ): array {
        $question = trim($question);
        if ($question === '') {
            throw new InvalidArgumentException('Enter a question for your agent to answer.');
        }

        $allowed = ['text', 'list', 'video', 'images', 'checklist', 'pdf', 'location'];
        if (!in_array($answerType, $allowed, true)) {
            throw new InvalidArgumentException('Choose a valid answer type.');
        }

        $this->validateFaqPayload($answerType, $payload);

        $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId)
            ?? RepositoryFactory::businessSettings()->create($businessId);
        $entries = faq_entries_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);

        $entry = faq_normalize_entry([
            'id' => $entryId ?? '',
            'q' => $question,
            'answer_type' => $answerType,
            'payload' => $payload,
            'learned_at' => date('c'),
        ]);

        $replaced = false;
        if ($entryId !== null && $entryId !== '') {
            foreach ($entries as $index => $existing) {
                if ((string) ($existing['id'] ?? '') === $entryId) {
                    $entries[$index] = $entry;
                    $replaced = true;
                    break;
                }
            }
        }

        if (!$replaced) {
            $entries[] = $entry;
        }

        $this->persistFaqs($businessId, $settings, $entries);

        return $this->mapFaqEntry($entry);
    }

    /**
     * @return list<array{id: string, q: string, a: string, answer_type: string, payload: array<string, mixed>, char_count: int, learned_at: string}>
     */
    public function deleteFaqEntry(string $businessId, string $entryId): array
    {
        $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId)
            ?? RepositoryFactory::businessSettings()->create($businessId);
        $entries = faq_entries_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);

        $found = false;
        $entries = array_values(array_filter(
            $entries,
            static function (array $entry) use ($entryId, &$found): bool {
                if ((string) ($entry['id'] ?? '') === $entryId) {
                    $found = true;
                    return false;
                }

                return true;
            }
        ));

        if (!$found) {
            throw new InvalidArgumentException('That question was not found.');
        }

        $this->persistFaqs($businessId, $settings, $entries);

        return $this->listFaqEntries($businessId);
    }

    /**
     * @return array{url: string, file_name: string}
     */
    public function uploadFaqAsset(string $businessId, array $file, string $assetType): array
    {
        if (!in_array($assetType, ['image', 'pdf'], true)) {
            throw new InvalidArgumentException('Unsupported file type.');
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Could not upload that file.');
        }

        $originalName = trim((string) ($file['name'] ?? 'upload'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = $assetType === 'pdf' ? ['pdf'] : ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($extension, $allowed, true)) {
            throw new InvalidArgumentException('That file type is not supported.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_BYTES) {
            throw new InvalidArgumentException('File is too large. Maximum size is 5 MB.');
        }

        $dir = dirname(__DIR__) . '/storage/uploads/knowledge/' . $businessId . '/faq';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not prepare upload folder.');
        }

        $storedName = 'faq_' . substr(str_replace('-', '', Uuid::v4()), 0, 12) . '.' . $extension;
        $storedPath = $dir . '/' . $storedName;
        if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $storedPath)) {
            throw new RuntimeException('Could not save uploaded file.');
        }

        return [
            'url' => app_route('ai-salesgirl/knowledge-faq-asset') . '?file=' . rawurlencode($storedName),
            'file_name' => $originalName,
            'stored_name' => $storedName,
        ];
    }

    /**
     * @return list<array{q: string, a: string}>
     */
    public function saveFaq(string $businessId, string $question, string $answer, ?int $editIndex = null): array
    {
        $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId)
            ?? RepositoryFactory::businessSettings()->create($businessId);
        $entries = faq_entries_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);
        $entryId = null;
        if ($editIndex !== null && isset($entries[$editIndex])) {
            $entryId = (string) ($entries[$editIndex]['id'] ?? '');
        }

        $saved = $this->saveFaqEntry($businessId, $question, 'text', ['text' => $answer], $entryId !== '' ? $entryId : null);

        return faq_parse(faq_entries_format($this->listFaqEntriesRaw($businessId)));
    }

    /**
     * @return list<array{q: string, a: string}>
     */
    public function deleteFaq(string $businessId, int $index): array
    {
        $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId)
            ?? RepositoryFactory::businessSettings()->create($businessId);
        $entries = faq_entries_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);
        if (!isset($entries[$index])) {
            throw new InvalidArgumentException('That question was not found.');
        }

        $entryId = (string) ($entries[$index]['id'] ?? '');
        if ($entryId === '') {
            throw new InvalidArgumentException('That question was not found.');
        }

        $this->deleteFaqEntry($businessId, $entryId);

        return faq_parse(faq_entries_format($this->listFaqEntriesRaw($businessId)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listFaqEntriesRaw(string $businessId): array
    {
        $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId)
            ?? RepositoryFactory::businessSettings()->create($businessId);

        return faq_entries_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validateFaqPayload(string $answerType, array $payload): void
    {
        $answerText = faq_entry_answer_text(['answer_type' => $answerType, 'payload' => $payload, 'a' => '']);
        if ($answerText === '') {
            throw new InvalidArgumentException('Complete the answer before saving.');
        }
    }

    /**
     * @param array<string, mixed> $entry
     * @return array{id: string, q: string, a: string, answer_type: string, payload: array<string, mixed>, char_count: int, learned_at: string}
     */
    private function mapFaqEntry(array $entry): array
    {
        $answer = faq_entry_answer_text($entry);
        $learnedAt = trim((string) ($entry['learned_at'] ?? ''));
        $formattedLearnedAt = $learnedAt !== ''
            ? date('M j, Y g:i A', strtotime($learnedAt))
            : date('M j, Y g:i A');

        return [
            'id' => (string) ($entry['id'] ?? ''),
            'q' => (string) ($entry['q'] ?? ''),
            'a' => $answer,
            'answer_type' => (string) ($entry['answer_type'] ?? 'text'),
            'payload' => is_array($entry['payload'] ?? null) ? $entry['payload'] : [],
            'char_count' => mb_strlen($answer),
            'learned_at' => $formattedLearnedAt,
        ];
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @param array<string, mixed> $settings
     */
    private function persistFaqs(string $businessId, array $settings, array $entries): void
    {
        RepositoryFactory::businessSettings()->update($businessId, [
            'ai_name' => (string) ($settings['ai_name'] ?? 'Sales Girl'),
            'business_description' => $settings['business_description'] ?? null,
            'faq' => faq_entries_format($entries),
            'delivery_policy' => $settings['delivery_policy'] ?? null,
            'return_policy' => $settings['return_policy'] ?? null,
            'payment_instructions' => $settings['payment_instructions'] ?? null,
            'payment_method' => $settings['payment_method'] ?? null,
            'escalation_phone' => $settings['escalation_phone'] ?? null,
            'escalation_email' => $settings['escalation_email'] ?? null,
            'handoff_to_whatsapp' => (bool) ($settings['handoff_to_whatsapp'] ?? true),
            'notify_owner_on_hot_lead' => (bool) ($settings['notify_owner_on_hot_lead'] ?? true),
            'notify_owner_on_payment' => (bool) ($settings['notify_owner_on_payment'] ?? true),
            'notify_owner_on_complaint' => (bool) ($settings['notify_owner_on_complaint'] ?? true),
            'ai_tone' => (string) ($settings['ai_tone'] ?? 'Friendly and helpful'),
            'ai_greeting' => (string) ($settings['ai_greeting'] ?? ''),
        ]);

        (new BusinessKnowledgeSyncService())->syncManualSources($businessId);
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            throw new InvalidArgumentException('Enter a website or document link.');
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            throw new InvalidArgumentException('Enter a valid website or document link.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only http and https links are supported.');
        }

        return $url;
    }

    private function fetchUrlText(string $url): string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return '';
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'LeadBirdKnowledgeBot/1.0',
            CURLOPT_HTTPHEADER => ['Accept: text/html,text/plain,application/pdf;q=0.8,*/*;q=0.5'],
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($body) || $body === '' || $status < 200 || $status >= 400) {
            return '';
        }

        $text = preg_replace('/\s+/u', ' ', strip_tags($body)) ?? '';
        $text = html_entity_decode(trim($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $this->truncate($text, self::MAX_URL_CHARS);
    }

    private function extractDocumentText(string $path, string $extension): string
    {
        if (!is_readable($path)) {
            return '';
        }

        if ($extension === 'pdf') {
            return PdfTextExtractor::extract($path);
        }

        $raw = file_get_contents($path);

        return is_string($raw) ? trim($raw) : '';
    }

    private function guessPageTitle(string $text, string $host): string
    {
        $snippet = trim(mb_substr($text, 0, 80));
        if ($snippet === '') {
            return $host;
        }

        return mb_strlen($snippet) > 60 ? mb_substr($snippet, 0, 57) . '…' : $snippet;
    }

    private function generateKnowledgeTitle(string $businessId, string $content): string
    {
        $fallback = $this->fallbackKnowledgeTitle($content);
        $apiKey = app_config()['openai']['api_key'] ?? '';
        if ($apiKey === '' || (new OpenAiBudgetService())->isExceeded($businessId)) {
            return $fallback;
        }

        $model = (string) (app_config()['openai']['model'] ?? 'gpt-4o-mini');
        $payload = [
            'model' => $model,
            'temperature' => 0.3,
            'max_tokens' => 32,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You write short knowledge-base titles (3-8 words). Return ONLY the title. No quotes. No punctuation at the end unless needed.',
                ],
                [
                    'role' => 'user',
                    'content' => "Give a clear topic title for this training text:\n\n" . $this->truncate($content, 1800),
                ],
            ],
        ];

        $api = openai_chat_completion($payload, $businessId, 'knowledge_title', 0.001);
        if (!($api['ok'] ?? false)) {
            return $fallback;
        }

        $title = trim((string) ($api['body']['choices'][0]['message']['content'] ?? ''));
        $title = trim($title, " \t\n\r\0\x0B\"'“”‘’");
        $title = preg_replace('/\s+/u', ' ', $title) ?? $title;

        if ($title === '' || mb_strlen($title) < 3) {
            return $fallback;
        }

        return $this->truncate($title, 120);
    }

    private function fallbackKnowledgeTitle(string $content): string
    {
        $line = trim(strtok($content, "\n") ?: '');
        $line = preg_replace('/\s+/u', ' ', $line) ?? $line;
        if ($line === '') {
            return 'Knowledge entry';
        }

        if (mb_strlen($line) > 72) {
            $line = rtrim(mb_substr($line, 0, 69)) . '…';
        }

        return $line;
    }

    /**
     * @return array{title: string, subheading: string, extracted_text: string}
     */
    private function analyzeDocumentWithAi(
        string $businessId,
        string $storedPath,
        string $extension,
        string $usageInstructions,
        string $originalName
    ): array {
        if ($this->isImageExtension($extension)) {
            return $this->analyzeImageDocument($businessId, $storedPath, $usageInstructions, $originalName);
        }

        $extractedText = $this->extractDocumentText($storedPath, $extension);

        return $this->analyzeTextDocument($businessId, $extractedText, $usageInstructions, $originalName);
    }

    /**
     * @return array{title: string, subheading: string, extracted_text: string}
     */
    private function analyzeImageDocument(
        string $businessId,
        string $storedPath,
        string $usageInstructions,
        string $originalName
    ): array {
        $fallbackTitle = $this->fallbackDocumentTitle($originalName);
        $fallbackSubheading = $usageInstructions !== '' ? $usageInstructions : 'Uploaded image file';
        $apiKey = app_config()['openai']['api_key'] ?? '';
        if ($apiKey === '' || (new OpenAiBudgetService())->isExceeded($businessId) || !is_readable($storedPath)) {
            return [
                'title' => $fallbackTitle,
                'subheading' => $fallbackSubheading,
                'extracted_text' => $usageInstructions,
            ];
        }

        $mime = $this->mimeForExtension(strtolower(pathinfo($storedPath, PATHINFO_EXTENSION)));
        $raw = file_get_contents($storedPath);
        if (!is_string($raw) || $raw === '') {
            return [
                'title' => $fallbackTitle,
                'subheading' => $fallbackSubheading,
                'extracted_text' => $usageInstructions,
            ];
        }

        $dataUrl = 'data:' . $mime . ';base64,' . base64_encode($raw);
        $model = (string) (app_config()['openai']['vision_model'] ?? 'gpt-4o-mini');
        $instructionBlock = $usageInstructions !== ''
            ? "\nOwner instructions for how the agent should use this file: {$usageInstructions}"
            : '';

        $payload = [
            'model' => $model,
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.2,
            'max_tokens' => 220,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You analyze uploaded business training files. Return JSON only: {"title":"short topic title (3-8 words)","subheading":"one neat sentence summarizing visible text/content","extracted_text":"key text or facts from the file"}. Title should be a policy/topic name, not the filename.',
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Analyze this uploaded file for an AI sales assistant knowledge base. Filename: ' . $originalName . $instructionBlock,
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $dataUrl,
                                'detail' => 'high',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $api = openai_chat_completion($payload, $businessId, 'knowledge_document_vision', 0.004);
        $parsed = $this->parseDocumentAnalysisResponse($api, $fallbackTitle, $fallbackSubheading, $usageInstructions);

        if ($parsed['extracted_text'] === '' && $usageInstructions !== '') {
            $parsed['extracted_text'] = $usageInstructions;
        }

        return $parsed;
    }

    /**
     * @return array{title: string, subheading: string, extracted_text: string}
     */
    private function analyzeTextDocument(
        string $businessId,
        string $extractedText,
        string $usageInstructions,
        string $originalName
    ): array {
        $fallbackTitle = $this->fallbackDocumentTitle($originalName);
        $fallbackSubheading = $extractedText !== ''
            ? $this->truncate(preg_replace('/\s+/u', ' ', $extractedText) ?? $extractedText, 140)
            : ($usageInstructions !== '' ? $usageInstructions : 'Uploaded document');

        if ($extractedText === '' && $usageInstructions === '') {
            return [
                'title' => $fallbackTitle,
                'subheading' => $fallbackSubheading,
                'extracted_text' => '',
            ];
        }

        $apiKey = app_config()['openai']['api_key'] ?? '';
        if ($apiKey === '' || (new OpenAiBudgetService())->isExceeded($businessId)) {
            return [
                'title' => $fallbackTitle,
                'subheading' => $fallbackSubheading,
                'extracted_text' => $extractedText !== '' ? $extractedText : $usageInstructions,
            ];
        }

        $model = (string) (app_config()['openai']['model'] ?? 'gpt-4o-mini');
        $instructionBlock = $usageInstructions !== ''
            ? "\nOwner instructions: {$usageInstructions}"
            : '';

        $payload = [
            'model' => $model,
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.2,
            'max_tokens' => 220,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You analyze uploaded business training documents. Return JSON only: {"title":"short topic title (3-8 words)","subheading":"one neat summary sentence","extracted_text":"the important facts to train an AI"}.',
                ],
                [
                    'role' => 'user',
                    'content' => "Filename: {$originalName}{$instructionBlock}\n\nDocument text:\n" . $this->truncate($extractedText, 4000),
                ],
            ],
        ];

        $api = openai_chat_completion($payload, $businessId, 'knowledge_document_text', 0.002);
        $parsed = $this->parseDocumentAnalysisResponse($api, $fallbackTitle, $fallbackSubheading, $extractedText !== '' ? $extractedText : $usageInstructions);

        if ($parsed['extracted_text'] === '' && $extractedText !== '') {
            $parsed['extracted_text'] = $extractedText;
        }

        return $parsed;
    }

    /**
     * @param array<string, mixed> $api
     * @return array{title: string, subheading: string, extracted_text: string}
     */
    private function parseDocumentAnalysisResponse(
        array $api,
        string $fallbackTitle,
        string $fallbackSubheading,
        string $fallbackExtracted
    ): array {
        if (!($api['ok'] ?? false)) {
            return [
                'title' => $fallbackTitle,
                'subheading' => $fallbackSubheading,
                'extracted_text' => $fallbackExtracted,
            ];
        }

        $content = (string) ($api['body']['choices'][0]['message']['content'] ?? '');
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return [
                'title' => $fallbackTitle,
                'subheading' => $fallbackSubheading,
                'extracted_text' => $fallbackExtracted,
            ];
        }

        $title = trim((string) ($decoded['title'] ?? ''));
        $subheading = trim((string) ($decoded['subheading'] ?? ''));
        $extractedText = trim((string) ($decoded['extracted_text'] ?? ''));

        return [
            'title' => $title !== '' ? $this->truncate($title, 120) : $fallbackTitle,
            'subheading' => $subheading !== '' ? $this->truncate($subheading, 220) : $fallbackSubheading,
            'extracted_text' => $extractedText !== '' ? $extractedText : $fallbackExtracted,
        ];
    }

    private function buildDocumentContent(string $title, string $subheading, string $extractedText, string $usageInstructions): string
    {
        $parts = array_filter([
            $title !== '' ? 'Title: ' . $title : null,
            $subheading !== '' ? 'Summary: ' . $subheading : null,
            $extractedText !== '' ? $extractedText : null,
            $usageInstructions !== '' ? 'How to use this file: ' . $usageInstructions : null,
        ]);

        return implode("\n\n", $parts);
    }

    private function fallbackDocumentTitle(string $originalName): string
    {
        $title = trim(pathinfo($originalName, PATHINFO_FILENAME));
        $title = preg_replace('/[_-]+/', ' ', $title) ?? $title;
        $title = preg_replace('/\s+/u', ' ', $title) ?? $title;

        return $title !== '' ? $this->truncate($title, 120) : 'Uploaded file';
    }

    private function isImageExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::IMAGE_EXTENSIONS, true);
    }

    private function mimeForExtension(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    private function documentPreviewUrl(string $chunkId): string
    {
        return app_route('ai-salesgirl/knowledge-file') . '?chunk_id=' . rawurlencode($chunkId);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: string, title: string, subheading: string, file_name: string, char_count: int, learned_at: string, preview_url: string, is_image: bool, usage_instructions: string}
     */
    private function mapDocumentRow(array $row): array
    {
        $meta = $this->decodeMetadata($row['metadata'] ?? null);
        $fileName = trim((string) ($meta['file_name'] ?? ''));
        $extension = strtolower((string) ($meta['extension'] ?? pathinfo($fileName, PATHINFO_EXTENSION)));
        $isImage = (bool) ($meta['is_image'] ?? $this->isImageExtension($extension));
        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') {
            $title = $this->fallbackDocumentTitle($fileName !== '' ? $fileName : 'document');
        }

        $subheading = trim((string) ($meta['subheading'] ?? ''));
        if ($subheading === '') {
            $content = trim((string) ($row['content'] ?? ''));
            $subheading = $content !== '' ? $this->truncate(preg_replace('/\s+/u', ' ', $content) ?? $content, 140) : $fileName;
        }

        $chunkId = (string) ($row['id'] ?? '');
        $learnedAt = (string) ($row['updated_at'] ?? $row['created_at'] ?? '');

        return [
            'id' => $chunkId,
            'title' => $title,
            'subheading' => $subheading,
            'file_name' => $fileName,
            'char_count' => mb_strlen(trim((string) ($row['content'] ?? ''))),
            'learned_at' => $learnedAt !== '' ? date('M j, Y g:i A', strtotime($learnedAt)) : '',
            'preview_url' => $isImage && $chunkId !== '' ? $this->documentPreviewUrl($chunkId) : '',
            'is_image' => $isImage,
            'usage_instructions' => trim((string) ($meta['usage_instructions'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: string, title: string, content: string, char_count: int, learned_at: string}
     */
    private function mapKnowledgeEntryRow(array $row): array
    {
        $content = trim((string) ($row['content'] ?? ''));
        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '' || $title === 'Knowledge') {
            $title = $this->fallbackKnowledgeTitle($content);
        }

        $learnedAt = (string) ($row['updated_at'] ?? $row['created_at'] ?? '');
        $formattedLearnedAt = $learnedAt !== ''
            ? date('M j, Y g:i A', strtotime($learnedAt))
            : '';

        return [
            'id' => (string) ($row['id'] ?? ''),
            'title' => $title,
            'content' => $content,
            'char_count' => mb_strlen($content),
            'learned_at' => $formattedLearnedAt,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: string, title: string, url: string, status: string, subpages: list<array{url: string, title: string}>, subpage_count: int, learned_at: string, recrawl_enabled: bool, recrawl_frequency: string, error_message: string}
     */
    private function mapLinkRow(array $row): array
    {
        $meta = $this->decodeMetadata($row['metadata'] ?? null);
        $url = trim((string) ($meta['url'] ?? ''));
        $host = trim((string) ($meta['host'] ?? ''));
        if ($host === '' && $url !== '') {
            $host = (string) parse_url($url, PHP_URL_HOST);
        }

        $subpages = is_array($meta['subpages'] ?? null) ? $meta['subpages'] : [];
        $normalizedSubpages = [];
        foreach ($subpages as $subpage) {
            if (!is_array($subpage)) {
                continue;
            }

            $subUrl = trim((string) ($subpage['url'] ?? ''));
            if ($subUrl === '') {
                continue;
            }

            $normalizedSubpages[] = [
                'url' => $subUrl,
                'title' => trim((string) ($subpage['title'] ?? '')) !== ''
                    ? trim((string) $subpage['title'])
                    : $this->titleFromPath((string) parse_url($subUrl, PHP_URL_PATH)),
            ];
        }

        if ($normalizedSubpages === [] && $url !== '') {
            $normalizedSubpages[] = ['url' => $url, 'title' => $host !== '' ? $host : 'Home'];
        }

        $subpageCount = (int) ($meta['subpage_count'] ?? count($normalizedSubpages));
        if ($subpageCount <= 0) {
            $subpageCount = count($normalizedSubpages);
        }

        $status = (string) ($meta['status'] ?? 'ready');
        if ($status === '' && trim((string) ($row['content'] ?? '')) !== '' && !str_starts_with(trim((string) ($row['content'] ?? '')), 'Processing')) {
            $status = 'ready';
        }

        $selectedUrls = is_array($meta['selected_urls'] ?? null) ? $meta['selected_urls'] : [];
        $selectedUrls = array_values(array_filter(array_map('strval', $selectedUrls)));
        if ($selectedUrls === []) {
            $selectedUrls = array_column($normalizedSubpages, 'url');
        }

        $learnedAt = (string) ($row['updated_at'] ?? $row['created_at'] ?? '');

        return [
            'id' => (string) ($row['id'] ?? ''),
            'title' => $host !== '' ? $host : ((string) ($row['title'] ?? 'Website link')),
            'url' => $url,
            'status' => $status !== '' ? $status : 'ready',
            'subpages' => $normalizedSubpages,
            'subpage_count' => $subpageCount,
            'selected_urls' => $selectedUrls,
            'learned_at' => $learnedAt !== '' ? date('M j, Y g:i A', strtotime($learnedAt)) : '',
            'recrawl_enabled' => (bool) ($meta['recrawl_enabled'] ?? false),
            'recrawl_frequency' => $this->normalizeRecrawlFrequency((string) ($meta['recrawl_frequency'] ?? 'monthly')),
            'error_message' => trim((string) ($meta['error_message'] ?? '')),
        ];
    }

    /**
     * @param list<string> $selectedUrls
     * @return list<string>
     */
    private function sanitizeSelectedUrls(string $rootUrl, array $selectedUrls): array
    {
        $host = strtolower((string) parse_url($rootUrl, PHP_URL_HOST));
        $clean = [];

        foreach ($selectedUrls as $selectedUrl) {
            $selectedUrl = trim((string) $selectedUrl);
            if ($selectedUrl === '') {
                continue;
            }

            try {
                $selectedUrl = $this->normalizeUrl($selectedUrl);
            } catch (InvalidArgumentException) {
                continue;
            }

            $selectedHost = strtolower((string) parse_url($selectedUrl, PHP_URL_HOST));
            if ($selectedHost === '' || ($selectedHost !== $host && !str_ends_with($selectedHost, '.' . $host))) {
                continue;
            }

            $clean[] = $selectedUrl;
        }

        return array_values(array_unique(array_slice($clean, 0, WebsiteCrawlService::MAX_SELECTABLE)));
    }

    private function normalizeRecrawlFrequency(string $frequency): string
    {
        $frequency = strtolower(trim($frequency));

        return in_array($frequency, ['daily', 'weekly', 'monthly'], true) ? $frequency : 'monthly';
    }

    private function titleFromPath(string $path): string
    {
        $path = trim($path, '/');
        if ($path === '') {
            return 'Home';
        }

        $segment = basename($path);
        $segment = str_replace(['-', '_'], ' ', $segment);

        return ucwords($segment);
    }

    private function truncate(string $text, int $max): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        return mb_strlen($text) > $max ? mb_substr($text, 0, $max) : $text;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMetadata(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
