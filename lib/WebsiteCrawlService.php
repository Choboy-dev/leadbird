<?php

declare(strict_types=1);

/**
 * Lightweight same-site page discovery and text extraction (no AI).
 */
final class WebsiteCrawlService
{
    public const MAX_DISCOVERED = 30;
    public const MAX_SELECTABLE = 10;
    public const MAX_PAGE_CHARS = 12000;
    private const MAX_SITEMAP_PRODUCT_URLS = 3;

    private const PATH_KEYWORDS = [
        'about' => 90,
        'contact' => 88,
        'menu' => 85,
        'shop' => 84,
        'products' => 83,
        'product' => 82,
        'services' => 81,
        'service' => 80,
        'events' => 79,
        'event' => 78,
        'reservation' => 77,
        'reserve' => 76,
        'booking' => 75,
        'faq' => 74,
        'terms' => 72,
        'privacy' => 71,
        'policy' => 70,
        'policies' => 70,
        'delivery' => 68,
        'returns' => 67,
        'pricing' => 66,
        'gallery' => 60,
        'team' => 58,
        'blog' => 55,
        'news' => 54,
    ];

    /**
     * @return list<array{url: string, title: string, path: string}>
     */
    public function discoverPages(string $baseUrl): array
    {
        $baseUrl = $this->normalizeUrl($baseUrl);
        $host = (string) parse_url($baseUrl, PHP_URL_HOST);
        if ($host === '') {
            return [];
        }

        $candidates = [];
        $seen = [];

        $this->addCandidate($candidates, $seen, $baseUrl, $this->titleFromUrl($baseUrl), 100);

        $homeHtml = $this->fetchHtml($baseUrl);
        if ($homeHtml !== '') {
            $homeTitle = $this->extractHtmlTitle($homeHtml);
            if ($homeTitle !== '') {
                $candidates[0]['title'] = $homeTitle;
            }

            foreach ($this->extractLinksFromHtml($homeHtml, $baseUrl, $host) as $link) {
                $score = $this->scorePath((string) ($link['path'] ?? ''));
                if (!empty($link['in_nav'])) {
                    $score += 15;
                }
                $this->addCandidate($candidates, $seen, $link['url'], $link['title'], $score);
            }
        }

        foreach ($this->discoverFromSitemap($baseUrl, $host) as $link) {
            $path = (string) ($link['path'] ?? '');
            $score = $this->scorePath($path);
            if ($this->isProductPath($path)) {
                $score -= 25;
            }
            $this->addCandidate(
                $candidates,
                $seen,
                $link['url'],
                $link['title'],
                $score
            );
        }

        usort($candidates, static function (array $a, array $b): int {
            $scoreCmp = ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }

            return strcmp((string) ($a['path'] ?? ''), (string) ($b['path'] ?? ''));
        });

        $pages = [];
        foreach (array_slice($candidates, 0, self::MAX_DISCOVERED) as $candidate) {
            $pages[] = [
                'url' => (string) $candidate['url'],
                'title' => (string) $candidate['title'],
                'path' => (string) $candidate['path'],
            ];
        }

        return $pages;
    }

    /**
     * @param list<string> $urls
     * @return list<array{url: string, title: string, text: string}>
     */
    public function fetchPages(array $urls): array
    {
        $results = [];
        foreach (array_slice(array_values(array_unique($urls)), 0, self::MAX_SELECTABLE) as $url) {
            $url = $this->normalizeUrl($url);
            $html = $this->fetchHtml($url);
            if ($html === '') {
                continue;
            }

            $title = $this->extractHtmlTitle($html);
            if ($title === '') {
                $title = $this->titleFromUrl($url);
            }

            $text = $this->extractReadableText($html);
            if ($text === '') {
                continue;
            }

            $results[] = [
                'url' => $url,
                'title' => $title,
                'text' => $this->truncate($text, self::MAX_PAGE_CHARS),
            ];
        }

        return $results;
    }

    /**
     * @return list<array{url: string, title: string, path: string}>
     */
    private function discoverFromSitemap(string $baseUrl, string $host): array
    {
        $parts = parse_url($baseUrl);
        if (!is_array($parts)) {
            return [];
        }

        $scheme = (string) ($parts['scheme'] ?? 'https');
        $origin = $scheme . '://' . $host;
        $sitemapUrls = [
            $origin . '/sitemap.xml',
            $origin . '/sitemap_index.xml',
            rtrim($baseUrl, '/') . '/sitemap.xml',
        ];

        $found = [];
        $productCount = 0;
        foreach (array_unique($sitemapUrls) as $sitemapUrl) {
            $xml = $this->fetchHtml($sitemapUrl);
            if ($xml === '' || !str_contains($xml, '<')) {
                continue;
            }

            if (preg_match_all('#<loc>\s*([^<]+)\s*</loc>#i', $xml, $matches)) {
                foreach ($matches[1] as $loc) {
                    $loc = html_entity_decode(trim($loc), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if (!$this->isSameHost($loc, $host)) {
                        continue;
                    }

                    $path = (string) parse_url($loc, PHP_URL_PATH);
                    if ($this->isProductPath($path)) {
                        if ($productCount >= self::MAX_SITEMAP_PRODUCT_URLS) {
                            continue;
                        }
                        $productCount++;
                    }

                    $found[] = [
                        'url' => $this->normalizeUrl($loc),
                        'title' => $this->titleFromPath($path),
                        'path' => $path,
                    ];
                }
            }
        }

        return $found;
    }

    /**
     * @return list<array{url: string, title: string, path: string, in_nav: bool}>
     */
    private function extractLinksFromHtml(string $html, string $baseUrl, string $host): array
    {
        $dom = $this->loadHtmlDom($html);
        if ($dom === null) {
            return [];
        }

        $links = [];
        $anchors = $dom->getElementsByTagName('a');
        foreach ($anchors as $anchor) {
            if (!$anchor instanceof DOMElement) {
                continue;
            }

            $href = trim((string) $anchor->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            $absolute = $this->resolveUrl($href, $baseUrl);
            if ($absolute === '' || !$this->isSameHost($absolute, $host)) {
                continue;
            }

            $path = (string) parse_url($absolute, PHP_URL_PATH);
            if ($this->shouldSkipPath($path)) {
                continue;
            }

            $label = $this->cleanText($anchor->textContent ?? '');
            if ($label === '' || mb_strlen($label) > 80) {
                $label = $this->titleFromPath($path);
            }

            $inNav = $this->isInsideNav($anchor);

            $links[] = [
                'url' => $absolute,
                'title' => $label,
                'path' => $path,
                'in_nav' => $inNav,
            ];
        }

        return $links;
    }

    private function isInsideNav(DOMElement $node): bool
    {
        $parent = $node->parentNode;
        while ($parent instanceof DOMElement) {
            $tag = strtolower($parent->tagName);
            if (in_array($tag, ['nav', 'header', 'footer'], true)) {
                return true;
            }

            $role = strtolower((string) $parent->getAttribute('role'));
            if (in_array($role, ['navigation', 'menubar'], true)) {
                return true;
            }

            $class = strtolower((string) $parent->getAttribute('class'));
            if (preg_match('/\b(nav|menu|header|footer)\b/', $class)) {
                return true;
            }

            $parent = $parent->parentNode;
        }

        return false;
    }

    /**
     * @param list<array{url: string, title: string, path: string, score: int}> $candidates
     * @param array<string, true> $seen
     */
    private function addCandidate(array &$candidates, array &$seen, string $url, string $title, int $score): void
    {
        $url = $this->normalizeUrl($url);
        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($this->shouldSkipPath($path)) {
            return;
        }

        $key = strtolower(rtrim($url, '/'));
        if (isset($seen[$key])) {
            return;
        }

        $seen[$key] = true;
        $title = $this->cleanText($title);
        if ($title === '') {
            $title = $this->titleFromPath($path);
        }

        $candidates[] = [
            'url' => $url,
            'title' => $title,
            'path' => $path,
            'score' => $score,
        ];
    }

    private function scorePath(string $path): int
    {
        $path = strtolower($path);
        $score = 10;

        foreach (self::PATH_KEYWORDS as $keyword => $points) {
            if (str_contains($path, $keyword)) {
                $score = max($score, $points);
            }
        }

        if ($path === '/' || $path === '') {
            return 100;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        if (count($segments) === 1) {
            $score += 8;
        } elseif (count($segments) > 3) {
            $score -= 10;
        }

        if ($this->isProductPath($path)) {
            $score -= 18;
            if (preg_match('#/(product|products|item|shop)/[^/]+#i', $path)) {
                $score -= 12;
            }
        }

        return $score;
    }

    private function isProductPath(string $path): bool
    {
        return (bool) preg_match('#/(product|products|item|shop)/#i', $path);
    }

    private function shouldSkipPath(string $path): bool
    {
        $path = strtolower($path);
        if ($path === '') {
            return false;
        }

        if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg|pdf|zip|mp4|mp3|css|js|xml)$/i', $path)) {
            return true;
        }

        foreach (['wp-admin', 'wp-login', 'cart', 'checkout', 'account', 'login', 'signup', 'sign-up', 'auth'] as $blocked) {
            if (str_contains($path, $blocked)) {
                return true;
            }
        }

        return false;
    }

    private function fetchHtml(string $url): string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return '';
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 4,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT => 'LeadBirdKnowledgeBot/1.0 (+https://leadbird.app)',
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml,text/xml;q=0.9,*/*;q=0.8'],
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if (!is_string($body) || $body === '' || $status < 200 || $status >= 400) {
            return '';
        }

        if ($type !== '' && !preg_match('/text\/|application\/xhtml|application\/xml/i', $type)) {
            return '';
        }

        return $body;
    }

    private function loadHtmlDom(string $html): ?DOMDocument
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        return $loaded ? $dom : null;
    }

    private function extractHtmlTitle(string $html): string
    {
        if (preg_match('#<title[^>]*>(.*?)</title>#is', $html, $match)) {
            return $this->cleanText(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return '';
    }

    private function extractReadableText(string $html): string
    {
        $dom = $this->loadHtmlDom($html);
        if ($dom === null) {
            return $this->cleanText(strip_tags($html));
        }

        $this->removeNoiseNodes($dom);
        $body = $dom->getElementsByTagName('body')->item(0);
        $text = $body instanceof DOMNode ? ($body->textContent ?? '') : ($dom->textContent ?? '');

        return $this->cleanText($text);
    }

    private function removeNoiseNodes(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);
        $queries = [
            '//script',
            '//style',
            '//noscript',
            '//svg',
            '//nav',
            '//footer',
            '//*[@role="navigation"]',
            '//*[contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"cookie")]',
            '//*[contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"popup")]',
        ];

        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes === false) {
                continue;
            }

            $toRemove = [];
            foreach ($nodes as $node) {
                $toRemove[] = $node;
            }

            foreach ($toRemove as $node) {
                if ($node->parentNode instanceof DOMNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
    }

    private function resolveUrl(string $href, string $baseUrl): string
    {
        if (preg_match('#^https?://#i', $href)) {
            return $this->normalizeUrl($href);
        }

        $parts = parse_url($baseUrl);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $scheme = (string) $parts['scheme'];
        $host = (string) $parts['host'];
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        if (str_starts_with($href, '//')) {
            return $this->normalizeUrl($scheme . ':' . $href);
        }

        if (str_starts_with($href, '/')) {
            return $this->normalizeUrl($scheme . '://' . $host . $port . $href);
        }

        $basePath = (string) ($parts['path'] ?? '/');
        $dir = str_contains($basePath, '/') ? substr($basePath, 0, (int) strrpos($basePath, '/')) : '';

        return $this->normalizeUrl($scheme . '://' . $host . $port . rtrim($dir, '/') . '/' . $href);
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return $url;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = strtolower((string) $parts['host']);
        $path = (string) ($parts['path'] ?? '/');
        if ($path === '') {
            $path = '/';
        }

        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

        return $scheme . '://' . $host . $path . $query;
    }

    private function isSameHost(string $url, string $host): bool
    {
        $urlHost = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $urlHost !== '' && ($urlHost === strtolower($host) || str_ends_with($urlHost, '.' . strtolower($host)));
    }

    private function titleFromUrl(string $url): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        return $host !== '' ? $host : $url;
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

    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }
}
