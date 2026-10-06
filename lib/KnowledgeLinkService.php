<?php

declare(strict_types=1);

/**
 * Auto-links social post knowledge chunks to catalog offerings.
 */
final class KnowledgeLinkService
{
    public function autoLinkSocialPosts(string $businessId): int
    {
        $socialChunks = RepositoryFactory::businessKnowledge()->listBySourceType($businessId, 'social_post');
        $offerings = RepositoryFactory::offerings()->listByBusinessId($businessId, true);

        if ($socialChunks === [] || $offerings === []) {
            RepositoryFactory::businessKnowledge()->touchLinkSync($businessId, 0);
            return 0;
        }

        RepositoryFactory::knowledgeLinks()->deleteByBusinessId($businessId);

        $offeringLines = [];
        foreach ($offerings as $offering) {
            $offeringLines[] = (string) ($offering['id'] ?? '') . ' | '
                . (string) ($offering['name'] ?? '') . ' | '
                . trim((string) ($offering['description'] ?? ''));
        }

        $linked = 0;
        foreach (array_slice($socialChunks, 0, 15) as $chunk) {
            $chunkId = (string) ($chunk['id'] ?? '');
            if ($chunkId === '') {
                continue;
            }

            $matches = $this->matchPostToOfferings(
                (string) ($chunk['title'] ?? ''),
                (string) ($chunk['content'] ?? ''),
                $offeringLines
            );

            foreach ($matches as $match) {
                $offeringId = (string) ($match['offering_id'] ?? '');
                $score = (float) ($match['score'] ?? 0);
                if ($offeringId === '' || $score < 0.55) {
                    continue;
                }

                RepositoryFactory::knowledgeLinks()->upsertLink($businessId, $chunkId, $offeringId, $score, 'auto');
                $linked++;
            }
        }

        RepositoryFactory::businessKnowledge()->touchLinkSync($businessId, RepositoryFactory::knowledgeLinks()->countByBusinessId($businessId));

        return $linked;
    }

    /**
     * @param list<string> $offeringLines
     * @return list<array{offering_id: string, score: float}>
     */
    private function matchPostToOfferings(string $title, string $content, array $offeringLines): array
    {
        return $this->fallbackKeywordMatch($title . ' ' . $content, $offeringLines);
    }

    /**
     * @param list<string> $offeringLines
     * @return list<array{offering_id: string, score: float}>
     */
    private function fallbackKeywordMatch(string $haystack, array $offeringLines): array
    {
        $haystack = strtolower($haystack);
        $matches = [];

        foreach ($offeringLines as $line) {
            $parts = explode(' | ', $line, 3);
            $offeringId = trim($parts[0] ?? '');
            $name = strtolower(trim($parts[1] ?? ''));
            if ($offeringId === '' || $name === '' || !str_contains($haystack, $name)) {
                continue;
            }

            $matches[] = ['offering_id' => $offeringId, 'score' => 0.6];
        }

        return $matches;
    }
}
