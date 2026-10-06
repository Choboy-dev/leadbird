<?php

declare(strict_types=1);

/**
 * Allowlist sanitizer for rich text produced by the compose editor and signature
 * editor. Everything the user types is stored and re-sent as HTML, so nothing
 * outside this allowlist is ever persisted.
 */
final class MailHtml
{
    /** @var array<string, list<string>> tag => allowed attributes */
    private const ALLOWED_TAGS = [
        'p' => ['style'], 'div' => ['style'], 'span' => ['style'], 'br' => [], 'hr' => [],
        'b' => [], 'strong' => [], 'i' => [], 'em' => [], 'u' => [], 's' => [], 'strike' => [], 'sub' => [], 'sup' => [],
        'h1' => ['style'], 'h2' => ['style'], 'h3' => ['style'], 'h4' => ['style'],
        'ul' => ['style'], 'ol' => ['style'], 'li' => ['style'],
        'blockquote' => ['style'], 'pre' => ['style'], 'code' => ['style'],
        'a' => ['href', 'title', 'target', 'rel'],
        'font' => ['color', 'face', 'size'],
        'img' => ['src', 'alt', 'width', 'height', 'style'],
        'table' => ['style', 'border', 'cellpadding', 'cellspacing', 'width'],
        'thead' => ['style'], 'tbody' => ['style'], 'tfoot' => ['style'],
        'tr' => ['style'], 'td' => ['style', 'colspan', 'rowspan', 'align', 'valign', 'width'],
        'th' => ['style', 'colspan', 'rowspan', 'align', 'valign', 'width'],
    ];

    private const ALLOWED_STYLES = [
        'color', 'background-color', 'font-family', 'font-size', 'font-weight', 'font-style',
        'text-align', 'text-decoration', 'line-height', 'list-style-type', 'white-space',
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'border', 'border-left', 'border-top', 'border-radius', 'border-collapse',
        'width', 'max-width', 'height', 'display', 'vertical-align',
    ];

    /** Tags whose contents are dropped along with the tag itself. */
    private const STRIP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'link', 'meta', 'base', 'svg', 'math'];
    public static function sanitize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><body>' . $html . '</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return nl2br(e(strip_tags($html)));
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if (!$body instanceof DOMElement) {
            return nl2br(e(strip_tags($html)));
        }

        self::cleanChildren($body);

        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    /**
     * Convert sanitized HTML into the plain-text alternative part so recipients
     * on text-only clients still get a readable message.
     */
    public static function toText(string $html): string
    {
        $text = preg_replace('#<(br|/p|/div|/li|/h[1-4]|/tr|hr)[^>]*>#i', "\n", $html) ?? $html;
        $text = preg_replace('#</(blockquote|table)>#i', "\n\n", $text) ?? $text;
        $text = preg_replace('#<li[^>]*>#i', '- ', $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/ *\n */', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /** Escape plain text into HTML that keeps the author's line breaks. */
    public static function fromText(string $text): string
    {
        $text = trim($text);

        return $text === '' ? '' : '<div>' . nl2br(e($text)) . '</div>';
    }

    private static function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                self::cleanElement($child);
                continue;
            }
            if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) {
                $child->parentNode?->removeChild($child);
            }
        }
    }

    private static function cleanElement(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);

        if (in_array($tag, self::STRIP_WITH_CONTENT, true)) {
            $element->parentNode?->removeChild($element);
            return;
        }

        if (!array_key_exists($tag, self::ALLOWED_TAGS)) {
            self::cleanChildren($element);
            self::unwrap($element);
            return;
        }

        $allowed = self::ALLOWED_TAGS[$tag];
        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            $name = strtolower($attribute->nodeName);
            if (!in_array($name, $allowed, true)) {
                $element->removeAttribute($attribute->nodeName);
                continue;
            }
            $value = (string) $attribute->nodeValue;
            $clean = match ($name) {
                'style' => self::cleanStyle($value),
                'href' => self::cleanUrl($value, ['http', 'https', 'mailto', 'tel']),
                'src' => self::cleanUrl($value, ['https', 'data', 'cid']),
                default => trim($value),
            };
            if ($clean === '') {
                $element->removeAttribute($attribute->nodeName);
                continue;
            }
            $element->setAttribute($attribute->nodeName, $clean);
        }

        // An image whose source was rejected would only render as a broken icon.
        if ($tag === 'img' && !$element->hasAttribute('src')) {
            $element->parentNode?->removeChild($element);
            return;
        }

        if ($tag === 'a' && $element->hasAttribute('href')) {
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer nofollow');
        }
        if ($tag === 'a' && !$element->hasAttribute('href')) {
            self::cleanChildren($element);
            self::unwrap($element);
            return;
        }

        self::cleanChildren($element);
    }

    /** Replace an element with its children so wrapped text is not lost. */
    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }
        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }
        $parent->removeChild($element);
    }

    private static function cleanStyle(string $style): string
    {
        $kept = [];
        foreach (explode(';', $style) as $declaration) {
            if (!str_contains($declaration, ':')) {
                continue;
            }
            [$property, $value] = explode(':', $declaration, 2);
            $property = strtolower(trim($property));
            $value = trim($value);
            if ($value === '' || !in_array($property, self::ALLOWED_STYLES, true)) {
                continue;
            }
            if (preg_match('/url\s*\(|expression\s*\(|javascript:|@import/i', $value)) {
                continue;
            }
            $kept[] = $property . ':' . $value;
        }

        return implode(';', $kept);
    }

    /** @param list<string> $schemes */
    private static function cleanUrl(string $url, array $schemes): string
    {
        $url = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $url) ?? '');
        if ($url === '' || str_starts_with($url, '//')) {
            return '';
        }
        if (str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return $url;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ($scheme === '' && !str_contains($url, ':')) {
            return $url;
        }
        if (!in_array($scheme, $schemes, true)) {
            return '';
        }
        if ($scheme === 'data' && !preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[a-z0-9+/=\s]+$#i', $url)) {
            return '';
        }

        return $url;
    }
}
