<?php

declare(strict_types=1);

function brand_favicon_url(): string
{
    return rtrim((string) app_config()['app_url'], '/') . '/assets/images/leadbird-favicon.png';
}

function brand_favicon_icon_html(string $extraClass = '', int $size = 24, string $alt = ''): string
{
    return brand_icon_html(brand_favicon_url(), 'brand-favicon-icon ' . $extraClass, $size, $alt);
}

function brand_ai_icon_url(): string
{
    return brand_favicon_url();
}

function brand_ai_icon_html(string $extraClass = '', int $size = 24, string $alt = ''): string
{
    $altText = $alt !== '' ? $alt : 'AI Salesgirl';

    return brand_icon_html(
        brand_ai_icon_url(),
        'brand-favicon-icon brand-ai-icon ' . trim($extraClass),
        $size,
        $altText
    );
}

function brand_ai_knowledge_icon_url(): string
{
    return 'https://cdn-icons-png.flaticon.com/512/5562/5562686.png';
}

function brand_ai_knowledge_icon_html(string $extraClass = '', int $size = 28, string $alt = ''): string
{
    return brand_icon_html(brand_ai_knowledge_icon_url(), 'brand-section-icon ' . $extraClass, $size, $alt);
}

function brand_upload_icon_url(string $variant = 'blue'): string
{
    $file = $variant === 'white' ? 'cloud-upload-white.png' : 'cloud-upload.png';

    return rtrim((string) app_config()['app_url'], '/') . '/assets/images/' . $file . '?v=2';
}

function brand_upload_icon_html(string $extraClass = '', int $size = 24, string $alt = '', string $variant = 'blue'): string
{
    return sprintf(
        '<img src="%s" class="%s" alt="%s" width="%d" height="%d" loading="lazy" decoding="async" aria-hidden="true">',
        e(brand_upload_icon_url($variant)),
        e(trim('lb-upload-icon ' . $extraClass)),
        e($alt),
        $size,
        $size
    );
}

function brand_instagram_icon_url(): string
{
    return 'https://cdn-icons-png.flaticon.com/512/4103/4103007.png';
}

function brand_facebook_icon_url(): string
{
    return 'https://cdn.simpleicons.org/facebook/1877F2';
}

function brand_facebook_icon_html(string $extraClass = '', int $size = 22, string $alt = ''): string
{
    return brand_icon_html(brand_facebook_icon_url(), 'brand-platform-icon ' . $extraClass, $size, $alt);
}

function brand_instagram_icon_html(string $extraClass = '', int $size = 22, string $alt = ''): string
{
    return brand_icon_html(brand_instagram_icon_url(), 'brand-platform-icon ' . $extraClass, $size, $alt);
}

function brand_messenger_icon_url(): string
{
    return 'https://cdn-icons-png.flaticon.com/512/5968/5968771.png';
}

function brand_messenger_icon_html(string $extraClass = '', int $size = 22, string $alt = ''): string
{
    return brand_icon_html(brand_messenger_icon_url(), 'brand-platform-icon ' . $extraClass, $size, $alt);
}

function brand_whatsapp_icon_url(): string
{
    return 'https://cdn-icons-png.flaticon.com/512/1383/1383269.png';
}

function brand_whatsapp_icon_html(string $extraClass = '', int $size = 22, string $alt = ''): string
{
    return brand_icon_html(brand_whatsapp_icon_url(), 'brand-platform-icon ' . $extraClass, $size, $alt);
}

function brand_tiktok_icon_url(): string
{
    return 'https://cdn.simpleicons.org/tiktok/000000';
}

function brand_tiktok_icon_html(string $extraClass = '', int $size = 22, string $alt = ''): string
{
    return brand_icon_html(brand_tiktok_icon_url(), 'brand-platform-icon ' . $extraClass, $size, $alt);
}

function brand_offers_icon_url(): string
{
    return rtrim((string) app_config()['app_url'], '/') . '/assets/images/offers-catalog-icon.png';
}

function brand_offers_icon_html(string $extraClass = '', int $size = 20, string $alt = ''): string
{
    return brand_icon_html(brand_offers_icon_url(), 'brand-nav-icon ' . $extraClass, $size, $alt);
}

/**
 * @return array<string, string>
 */
function brand_dashboard_nav_icon_urls(): array
{
    return [
        'overview' => rtrim((string) app_config()['app_url'], '/') . '/assets/images/icons/dashboard-admin.png?v=2',
        'inbox' => rtrim((string) app_config()['app_url'], '/') . '/assets/images/icons/inbox-chat.png?v=2',
        'orders' => 'https://cdn-icons-png.flaticon.com/512/2331/2331966.png',
        'customers' => 'https://cdn-icons-png.flaticon.com/512/9070/9070578.png',
        'my_business' => 'https://cdn-icons-png.flaticon.com/512/1810/1810755.png',
        'offers' => brand_offers_icon_url(),
        'analytics' => 'https://cdn-icons-png.flaticon.com/512/6104/6104564.png',
        'social_media' => 'https://cdn-icons-png.flaticon.com/512/5968/5968776.png',
        'broadcasts' => rtrim((string) app_config()['app_url'], '/') . '/assets/images/icons/bulk-whatsapp.png',        'connected_accounts' => 'https://cdn-icons-png.flaticon.com/512/535/535156.png',
        'billing' => 'https://cdn-icons-png.flaticon.com/512/9361/9361196.png',
        'refer' => rtrim((string) app_config()['app_url'], '/') . '/assets/images/icons/refer-earn.png',
        'settings' => 'https://cdn-icons-png.flaticon.com/512/8187/8187143.png',
        'help' => 'https://cdn-icons-png.flaticon.com/512/5726/5726532.png',
    ];
}

function brand_dashboard_nav_icon_html(string $iconKey): ?string
{
    if ($iconKey === 'ai') {
        return brand_favicon_icon_html('brand-favicon-icon--nav', 20, 'AI Salesgirl');
    }

    $urls = brand_dashboard_nav_icon_urls();
    if (!isset($urls[$iconKey])) {
        return null;
    }

    return brand_icon_html($urls[$iconKey], 'brand-nav-icon brand-nav-icon--sidebar', 20);
}

function brand_overview_icon_url(): string
{
    return brand_dashboard_nav_icon_urls()['overview'];
}

function brand_overview_icon_html(string $extraClass = '', int $size = 20, string $alt = ''): string
{
    return brand_icon_html(brand_overview_icon_url(), 'brand-nav-icon ' . $extraClass, $size, $alt);
}

function brand_overview_refer_icon_url(): string
{
    return rtrim((string) app_config()['app_url'], '/') . '/assets/images/overview-refer-icon.png';
}

function brand_overview_plan_icon_url(): string
{
    return rtrim((string) app_config()['app_url'], '/') . '/assets/images/overview-plan-icon.png';
}

function brand_inbox_empty_illustration_url(): string
{
    return rtrim((string) app_config()['app_url'], '/') . '/assets/images/inbox-empty-illustration.png';
}

function brand_inbox_icon_url(): string
{
    return brand_dashboard_nav_icon_urls()['inbox'];
}

function brand_inbox_icon_html(string $extraClass = '', int $size = 20, string $alt = ''): string
{
    return brand_icon_html(brand_inbox_icon_url(), 'brand-nav-icon ' . $extraClass, $size, $alt);
}

function brand_icon_html(string $url, string $class, int $size, string $alt = ''): string
{
    return sprintf(
        '<img src="%s" class="%s" alt="%s" width="%d" height="%d" loading="lazy" decoding="async">',
        e($url),
        e(trim($class)),
        e($alt),
        $size,
        $size
    );
}
