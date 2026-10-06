<?php

declare(strict_types=1);

/**
 * Extract plain text from PDF files without requiring shell access.
 * Uses pdftotext when available, otherwise parses common PDF text operators in PHP.
 */
final class PdfTextExtractor
{
    public static function extract(string $path): string
    {
        if (!is_readable($path)) {
            return '';
        }

        $fromShell = self::extractWithPdftotext($path);
        if ($fromShell !== '') {
            return $fromShell;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return '';
        }

        $chunks = [];
        foreach (self::decodeStreams($raw) as $stream) {
            $chunk = self::extractFromContentStream($stream);
            if ($chunk !== '') {
                $chunks[] = $chunk;
            }
        }

        if ($chunks === []) {
            $literal = self::extractParenthesisLiterals($raw);
            if ($literal !== '') {
                $chunks[] = $literal;
            }
        }

        $text = trim(preg_replace('/\s+/u', ' ', implode(' ', $chunks)) ?? '');

        return $text;
    }

    private static function extractWithPdftotext(string $path): string
    {
        if (!self::canUseExec()) {
            return '';
        }

        $output = [];
        $code = 1;
        @exec('pdftotext ' . escapeshellarg($path) . ' - 2>/dev/null', $output, $code);

        return $code === 0 ? trim(implode("\n", $output)) : '';
    }

    private static function canUseExec(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabled = strtolower((string) ini_get('disable_functions'));
        if ($disabled === '') {
            return true;
        }

        foreach (preg_split('/\s*,\s*/', $disabled) ?: [] as $function) {
            if ($function === 'exec') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private static function decodeStreams(string $pdf): array
    {
        if (!preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches)) {
            return [];
        }

        $streams = [];
        foreach ($matches[1] as $stream) {
            $decoded = @gzuncompress($stream);
            if ($decoded === false) {
                $decoded = @gzinflate($stream);
            }
            if ($decoded === false) {
                $decoded = @gzinflate(substr($stream, 2));
            }
            if (is_string($decoded) && $decoded !== '') {
                $streams[] = $decoded;
            }
        }

        return $streams;
    }

    private static function extractFromContentStream(string $stream): string
    {
        $parts = [];

        if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)\s*Tj/s', $stream, $matches)) {
            foreach ($matches[0] as $match) {
                if (preg_match('/\((.*)\)\s*Tj/s', $match, $inner)) {
                    $parts[] = self::unescapePdfString($inner[1]);
                }
            }
        }

        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $stream, $tjMatches)) {
            foreach ($tjMatches[1] as $arrayBody) {
                if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $arrayBody, $strings)) {
                    foreach ($strings[0] as $literal) {
                        $parts[] = self::unescapePdfString(substr($literal, 1, -1));
                    }
                }
            }
        }

        if (preg_match_all('/<([0-9A-Fa-f]+)>\s*Tj/', $stream, $hexMatches)) {
            foreach ($hexMatches[1] as $hex) {
                $parts[] = self::decodeHexString($hex);
            }
        }

        if ($parts !== []) {
            return implode(' ', array_filter($parts));
        }

        return self::extractParenthesisLiterals($stream);
    }

    private static function extractParenthesisLiterals(string $data): string
    {
        if (!preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $data, $matches)) {
            return '';
        }

        $parts = [];
        foreach ($matches[0] as $match) {
            $parts[] = self::unescapePdfString(substr($match, 1, -1));
        }

        return implode(' ', array_filter($parts));
    }

    private static function unescapePdfString(string $value): string
    {
        $value = preg_replace_callback(
            '/\\\\([nrtbf()\\\\])|\\\\([0-7]{1,3})/',
            static function (array $matches): string {
                if (isset($matches[2]) && $matches[2] !== '') {
                    return chr((int) octdec($matches[2]));
                }

                return match ($matches[1]) {
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    'b' => "\x08",
                    'f' => "\x0C",
                    '(' => '(',
                    ')' => ')',
                    '\\' => '\\',
                    default => $matches[0],
                };
            },
            $value
        ) ?? $value;

        return trim($value);
    }

    private static function decodeHexString(string $hex): string
    {
        $hex = preg_replace('/\s+/', '', $hex) ?? $hex;
        if ($hex === '' || strlen($hex) % 2 !== 0) {
            return '';
        }

        $binary = hex2bin($hex);
        if ($binary === false) {
            return '';
        }

        if (str_starts_with($binary, "\xFE\xFF")) {
            $converted = @iconv('UTF-16BE', 'UTF-8//IGNORE', substr($binary, 2));

            return is_string($converted) ? trim($converted) : '';
        }

        return trim($binary);
    }
}
