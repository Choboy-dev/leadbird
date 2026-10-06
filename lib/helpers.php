<?php

declare(strict_types=1);

function redirect(string $url, int $status = 302): never
{
    header('Location: ' . $url, true, $status);
    exit;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function flash_set(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $message = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function app_config(): array
{
    static $config;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config.php';
    }
    return $config;
}

/**
 * Build a clean app URL without .php (requires root .htaccess rewrites).
 *
 * @param array<string, scalar|null> $query
 */
function app_route(string $path = '', array $query = []): string
{
    $base = rtrim(app_config()['app_url'], '/');
    $fragment = '';

    if (str_contains($path, '#')) {
        [$path, $fragment] = explode('#', $path, 2);
        $fragment = '#' . $fragment;
    }

    $pathQuery = [];
    if (str_contains($path, '?')) {
        [$path, $qs] = explode('?', $path, 2);
        parse_str($qs, $pathQuery);
    }

    $path = ltrim(trim($path), '/');
    if (str_ends_with($path, '.php')) {
        $path = substr($path, 0, -4);
    }

    $mergedQuery = array_merge($pathQuery, $query);

    if ($path === '' || $path === 'index') {
        $url = $base . '/';
    } else {
        $url = $base . '/' . $path;
    }

    if ($mergedQuery !== []) {
        $url .= '?' . http_build_query($mergedQuery);
    }

    return $url . $fragment;
}

function app_timezone(): DateTimeZone
{
    static $tz = null;
    if ($tz instanceof DateTimeZone) {
        return $tz;
    }

    $name = (string) (app_config()['timezone'] ?? 'Africa/Lagos');

    try {
        $tz = new DateTimeZone($name);
    } catch (Throwable) {
        $tz = new DateTimeZone('Africa/Lagos');
    }

    return $tz;
}

function app_utc_datetime(?string $dbTimestamp): ?DateTimeImmutable
{
    if ($dbTimestamp === null || trim($dbTimestamp) === '') {
        return null;
    }

    try {
        return new DateTimeImmutable(trim($dbTimestamp), new DateTimeZone('UTC'));
    } catch (Throwable) {
        return null;
    }
}

function app_format_datetime(?string $dbTimestamp, string $format, bool $includeDate = true): string
{
    $utc = app_utc_datetime($dbTimestamp);
    if ($utc === null) {
        return 'Just now';
    }

    $local = $utc->setTimezone(app_timezone());

    if (!$includeDate) {
        return $local->format('g:ia');
    }

    return $local->format($format);
}

function app_relative_datetime(?string $dbTimestamp): string
{
    $utc = app_utc_datetime($dbTimestamp);
    if ($utc === null) {
        return 'Just now';
    }

    $delta = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->getTimestamp() - $utc->getTimestamp();
    if ($delta < 60) {
        return 'Just now';
    }
    if ($delta < 3600) {
        return (string) max(1, (int) floor($delta / 60)) . 'm ago';
    }
    if ($delta < 86400) {
        return (string) max(1, (int) floor($delta / 3600)) . 'h ago';
    }
    if ($delta < 604800) {
        return (string) max(1, (int) floor($delta / 86400)) . 'd ago';
    }

    return $utc->setTimezone(app_timezone())->format('M j');
}

function offering_public_image_url(?string $imageUrl): ?string
{
    if ($imageUrl === null || trim($imageUrl) === '') {
        return null;
    }

    return (new OfferingImageService())->publicUrl($imageUrl);
}

/**
 * Strip markdown formatting from AI replies before sending to customers or storing.
 */
function chat_normalize_ai_reply(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    $text = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/iu', '$1: $2', $text) ?? $text;
    $text = preg_replace('/\*\*([^*]+)\*\*/u', '$1', $text) ?? $text;
    $text = preg_replace('/\*([^*]+)\*/u', '$1', $text) ?? $text;

    return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
}

/**
 * Make AI DM replies sound human: no exclamation marks or dash-heavy AI phrasing.
 */
function chat_polish_salesgirl_reply(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    $text = str_replace(['—', '–'], ',', $text);
    $text = preg_replace('/!+/', '.', $text) ?? $text;
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;
    $text = preg_replace('/\.\s*\./', '.', $text) ?? $text;

    return trim($text);
}

/**
 * Render chat message text as safe HTML with clickable links.
 */
function chat_format_message_html(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    $linkTokens = [];
    $tokenIndex = 0;
    $withTokens = preg_replace_callback(
        '/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/iu',
        static function (array $matches) use (&$linkTokens, &$tokenIndex): string {
            $token = '{{LBCHATLINK' . $tokenIndex . '}}';
            $linkTokens[$token] = [
                'label' => $matches[1],
                'url' => $matches[2],
            ];
            $tokenIndex++;

            return $token;
        },
        $text
    ) ?? $text;

    $escaped = htmlspecialchars($withTokens, ENT_QUOTES, 'UTF-8');

    $escaped = preg_replace_callback(
        '#(?<!["\'=])(https?://[^\s<>&"\']+)#iu',
        static function (array $matches): string {
            $url = rtrim($matches[1], '.,!?');
            $suffix = substr($matches[1], strlen($url));

            return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
                . '" target="_blank" rel="noopener noreferrer" class="chat-link">'
                . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</a>'
                . htmlspecialchars($suffix, ENT_QUOTES, 'UTF-8');
        },
        $escaped
    ) ?? $escaped;

    foreach ($linkTokens as $token => $link) {
        $anchor = '<a href="' . htmlspecialchars((string) $link['url'], ENT_QUOTES, 'UTF-8')
            . '" target="_blank" rel="noopener noreferrer" class="chat-link">'
            . htmlspecialchars((string) $link['label'], ENT_QUOTES, 'UTF-8') . '</a>';
        $escaped = str_replace(htmlspecialchars($token, ENT_QUOTES, 'UTF-8'), $anchor, $escaped);
    }

    return nl2br($escaped, false);
}
