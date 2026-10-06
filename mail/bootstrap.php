<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
Mail::ensureStorage();

header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; script-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

function mail_page_title(string $title): string
{
    return $title . ' - ' . (string) app_config()['mail']['app_name'];
}

function mail_flash(string $type, string $message): void
{
    $_SESSION['mail_flash'] = ['type' => $type, 'message' => $message];
}

function mail_consume_flash(): ?array
{
    $flash = $_SESSION['mail_flash'] ?? null;
    unset($_SESSION['mail_flash']);
    return is_array($flash) ? $flash : null;
}

/**
 * Renders a mailbox avatar: the uploaded picture when there is one, otherwise
 * the initials circle. $tag lets the topbar keep its clickable button.
 */
function mail_avatar(string $url, string $initials, string $tag = 'span', string $attributes = ''): string
{
    $classes = 'mail-avatar' . ($url !== '' ? ' mail-avatar--photo' : '');
    $inner = $url !== ''
        ? '<img src="' . e($url) . '" alt="" width="36" height="36" decoding="async">'
        : e($initials);

    return '<' . $tag . ' class="' . $classes . '"' . ($attributes !== '' ? ' ' . $attributes : '') . '>'
        . $inner . '</' . $tag . '>';
}

/** Inbox category tabs mirror Gmail's icon set. */
function mail_category_icon(string $category): string
{
    return mail_icon(match ($category) {
        'promotions' => 'tag',
        'social' => 'users',
        'updates' => 'info',
        default => 'inbox',
    });
}

/**
 * Single-colour stroke icons drawn on a 24x24 grid. A value may be one path or a
 * list of paths for glyphs that need more than one stroke.
 *
 * @return array<string, string|list<string>>
 */
function mail_icon_paths(): array
{
    return [
        'menu' => 'M4 6h16M4 12h16M4 18h16', 'search' => 'm21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z',
        'compose' => 'M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z', 'inbox' => 'M4 4h16v16H4zM4 14h5l2 3h2l2-3h5',
        'star' => 'm12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z',
        'send' => 'm3 3 18 9-18 9 4-9Zm4 9h14', 'drafts' => 'M5 4h14v16H5zM8 9h8M8 13h8',
        'archive' => 'M4 7h16v13H4zM3 3h18v4H3zM9 11h6', 'spam' => 'M12 8v5M12 17h.01M4 4h16v16H4z',
        'trash' => 'M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13',
        'back' => 'm15 18-6-6 6-6M9 12h12', 'attach' => 'm21.4 11.6-8.9 8.9a6 6 0 0 1-8.5-8.5l9.6-9.6a4 4 0 1 1 5.7 5.7l-9.6 9.6a2 2 0 1 1-2.8-2.8l8.9-8.9',
        'close' => 'M6 6l12 12M18 6 6 18', 'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87',
        'tag' => 'M20.6 13.4 12 22l-9-9V3h10l7.6 7.6a2 2 0 0 1 0 2.8ZM7.5 7.5h.01',
        'info' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM12 16v-5M12 8h.01',
        'clock' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20ZM12 7v5l3 2',
        'calendar' => 'M4 5h16v16H4zM8 3v4M16 3v4M4 10h16',
        'settings' => ['M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z', 'M19.4 13.5a8 8 0 0 0 0-3l2-1.6-2-3.4-2.4 1a8 8 0 0 0-2.6-1.5L14 2h-4l-.4 3a8 8 0 0 0-2.6 1.5l-2.4-1-2 3.4 2 1.6a8 8 0 0 0 0 3l-2 1.6 2 3.4 2.4-1a8 8 0 0 0 2.6 1.5L10 22h4l.4-3a8 8 0 0 0 2.6-1.5l2.4 1 2-3.4Z'],
        'minimize' => 'M6 15h12', 'expand' => 'M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M8 21H5a2 2 0 0 1-2-2v-3M16 21h3a2 2 0 0 0 2-2v-3',
        'collapse' => 'M9 3v3a2 2 0 0 1-2 2H4M15 3v3a2 2 0 0 0 2 2h3M9 21v-3a2 2 0 0 0-2-2H4M15 21v-3a2 2 0 0 1 2-2h3',
        'chevron-down' => 'm6 9 6 6 6-6', 'chevron-up' => 'm18 15-6-6-6 6', 'check' => 'M20 6 9 17l-5-5',
        'bold' => 'M7 5h5.5a3.5 3.5 0 0 1 0 7H7zM7 12h6.5a3.5 3.5 0 0 1 0 7H7z',
        'italic' => 'M19 5h-6M11 19H5M15 5 9 19', 'underline' => 'M7 4v6a5 5 0 0 0 10 0V4M5 20h14',
        'strike' => 'M5 12h14M16 8a4 4 0 0 0-8 0M8 16a4 4 0 0 0 8 0',
        'text-color' => 'M6 16 12 4l6 12M8.4 12h7.2M4 20h16',
        'align-left' => 'M4 6h16M4 12h10M4 18h13', 'align-center' => 'M4 6h16M7 12h10M6 18h12',
        'align-right' => 'M4 6h16M10 12h10M7 18h13', 'align-justify' => 'M4 6h16M4 12h16M4 18h16',
        'list-ul' => 'M9 6h12M9 12h12M9 18h12M4 6h.01M4 12h.01M4 18h.01',
        'list-ol' => 'M10 6h11M10 12h11M10 18h11M3 5h2v4M3 9h3M3 15h2v2H3v2h2',
        'indent' => 'M11 6h10M11 12h10M11 18h10M3 8l4 4-4 4', 'outdent' => 'M11 6h10M11 12h10M11 18h10M7 8l-4 4 4 4',
        'undo' => 'M9 14 4 9l5-5M4 9h11a5 5 0 0 1 0 10h-4', 'redo' => 'm15 14 5-5-5-5M20 9H9a5 5 0 0 0 0 10h4',
        'link' => 'M10 13a5 5 0 0 0 7 0l2-2a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7 0l-2 2a5 5 0 0 0 7 7l1-1',
        'quote' => 'M6 7h4v6H6zM14 7h4v6h-4zM6 13c0 2.5 1.5 3.5 3.5 4M14 13c0 2.5 1.5 3.5 3.5 4',
        'clear-format' => 'M4 7V5h13v2M9 5l-2 14M6 19h7M15 13l6 6M21 13l-6 6',
        'more' => 'M12 5h.01M12 12h.01M12 19h.01',
        'font' => 'M4 20 12 4l8 16M7.5 14h9', 'font-size' => 'M3 7V5h9v2M7.5 5v14M14 12v-1.5h7V12M17.5 10.5V19',
        'signature' => 'M3 17c3.5 0 4.5-10 7.5-10S13 15 16 15s2.5-2.5 5-2.5M3 21h18',
        'image' => ['M4 5h16v14H4z', 'm5 16 4-4 3 3 3-3 4 4', 'M9.2 9.2h.01'],
        'bell' => ['M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9', 'M13.7 20.5a2 2 0 0 1-3.4 0'],
        'bell-off' => ['M18.6 12.7A19 19 0 0 1 18 8a6 6 0 0 0-9.2-5', 'M6 9v-1M6 9c0 7-3 8-3 8h13', 'M13.7 20.5a2 2 0 0 1-3.4 0', 'M3 3l18 18'],
    ];
}

function mail_icon(string $name, string $class = ''): string
{
    if ($name === 'refresh') {
        $appUrl = rtrim((string) (app_config()['app_url'] ?? ''), '/');

        return '<img class="mail-refresh-icon" src="' . e($appUrl) . '/assets/images/mail-refresh.png?v=1" alt="" width="20" height="20" decoding="async">';
    }

    $paths = mail_icon_paths();
    $shapes = $paths[$name] ?? $paths['inbox'];
    $body = '';
    foreach ((array) $shapes as $shape) {
        $body .= '<path d="' . e((string) $shape) . '"/>';
    }

    return '<svg' . ($class !== '' ? ' class="' . e($class) . '"' : '')
        . ' viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        . $body . '</svg>';
}
