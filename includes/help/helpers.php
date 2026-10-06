<?php

declare(strict_types=1);

require_once __DIR__ . '/content.php';

function help_center_data(): array
{
    static $data = null;
    if ($data === null) {
        $data = help_center_content();
    }

    return $data;
}

function help_home_url(): string
{
    return app_route('help');
}

function help_topic_url(string $topicSlug): string
{
    return app_route('help/' . rawurlencode($topicSlug));
}

function help_article_url(string $topicSlug, string $articleSlug): string
{
    return app_route('help/' . rawurlencode($topicSlug) . '/' . rawurlencode($articleSlug));
}

function help_contact_url(): string
{
    return app_route('contact');
}

/**
 * @return array{label: string, slug: string, sections: list<array<string, mixed>>}|null
 */
function help_find_topic(string $topicSlug): ?array
{
    $topics = help_center_data()['topics'];

    return $topics[$topicSlug] ?? null;
}

/**
 * @return array{slug: string, title: string, body: string, topic: string, topic_label: string, section: string}|null
 */
function help_find_article(string $topicSlug, string $articleSlug): ?array
{
    $topic = help_find_topic($topicSlug);
    if ($topic === null) {
        return null;
    }

    foreach ($topic['sections'] as $section) {
        foreach ($section['articles'] as $article) {
            if (($article['slug'] ?? '') === $articleSlug) {
                return [
                    'slug' => (string) $article['slug'],
                    'title' => (string) $article['title'],
                    'body' => (string) $article['body'],
                    'topic' => $topicSlug,
                    'topic_label' => (string) $topic['label'],
                    'section' => (string) $section['title'],
                ];
            }
        }
    }

    return null;
}

/**
 * @return list<array{slug: string, title: string, body: string, topic: string, topic_label: string}>
 */
function help_all_articles(): array
{
    $articles = [];
    foreach (help_center_data()['topics'] as $topicSlug => $topic) {
        foreach ($topic['sections'] as $section) {
            foreach ($section['articles'] as $article) {
                $articles[] = [
                    'slug' => (string) $article['slug'],
                    'title' => (string) $article['title'],
                    'body' => (string) $article['body'],
                    'topic' => $topicSlug,
                    'topic_label' => (string) $topic['label'],
                ];
            }
        }
    }

    return $articles;
}

/**
 * @return array{slug: string, title: string, body: string, topic: string, topic_label: string}|null
 */
function help_find_recommended_article(array $ref): ?array
{
    return help_find_article((string) ($ref['topic'] ?? ''), (string) ($ref['slug'] ?? ''))
        ? [
            'slug' => (string) $ref['slug'],
            'title' => (string) (help_find_article((string) $ref['topic'], (string) $ref['slug'])['title'] ?? ''),
            'body' => (string) (help_find_article((string) $ref['topic'], (string) $ref['slug'])['body'] ?? ''),
            'topic' => (string) $ref['topic'],
            'topic_label' => (string) (help_find_topic((string) $ref['topic'])['label'] ?? ''),
        ]
        : null;
}

function help_nav_is_active(string $mode, ?string $topicSlug = null): bool
{
    return match ($mode) {
        'home' => $topicSlug === null,
        'topic' => $topicSlug !== null,
        default => false,
    };
}

function help_icon_svg(string $icon): string
{
    return match ($icon) {
        'contact' => '<svg class="hc-shortcut-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">'
            . '<path d="M4 11v2a8 8 0 0 0 8 8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>'
            . '<path d="M20 11v2a8 8 0 0 1-8 8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>'
            . '<path d="M4 11c0-4.418 3.582-8 8-8s8 3.582 8 8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>'
            . '<rect x="2" y="11" width="3" height="5" rx="1.25" stroke="currentColor" stroke-width="1.75"/>'
            . '<rect x="19" y="11" width="3" height="5" rx="1.25" stroke="currentColor" stroke-width="1.75"/>'
            . '</svg>',
        'ai' => brand_favicon_icon_html('hc-shortcut-img hc-shortcut-img--bird', 28, 'Lead Bird AI'),
        'channels' => brand_instagram_icon_html('hc-shortcut-img hc-shortcut-img--instagram', 28, 'Instagram'),
        'billing' => brand_icon_html(
            brand_dashboard_nav_icon_urls()['billing'],
            'hc-shortcut-img',
            28,
            ''
        ),
        'policy' => brand_icon_html(
            'https://cdn-icons-png.flaticon.com/512/2913/2913133.png',
            'hc-shortcut-img',
            28,
            ''
        ),
        default => brand_icon_html(
            brand_dashboard_nav_icon_urls()['help'],
            'hc-shortcut-img',
            28,
            ''
        ),
    };
}
