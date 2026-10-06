<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/brand-ai-icon.php';

function dash_offering_type_meta(string $type): array
{
    $meta = OfferingTypeRegistry::meta($type);

    return [
        'label' => $meta['label'],
        'icon' => $meta['icon'],
        'class' => $meta['class'],
    ];
}

function dash_billing_label(string $billingType): string
{
    return match ($billingType) {
        'monthly' => 'Monthly',
        'per_session' => 'Per session',
        default => 'One-time',
    };
}

function dash_format_money(float $amount, ?string $currency = null): string
{
    return money_format($amount, $currency);
}

function dash_format_catalog_money(float $amount, ?string $currency = null): string
{
    return money_format_catalog($amount, $currency);
}

function dash_first_name(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '';
    }

    $parts = preg_split('/\s+/u', $name, 2);

    return $parts[0] ?? $name;
}

function dash_offering_image_url(?string $imageUrl): ?string
{
    return offering_public_image_url($imageUrl);
}

function dash_knowledge_link_thumb(array $link): ?string
{
    $offeringThumb = dash_offering_image_url((string) ($link['offering_image_url'] ?? ''));
    if ($offeringThumb !== null) {
        return $offeringThumb;
    }

    $postThumb = trim((string) ($link['post_image_url'] ?? ''));
    if ($postThumb !== '' && preg_match('#^https?://#i', $postThumb) === 1) {
        return $postThumb;
    }

    return null;
}

function dash_ai_tone_options(?string $currentTone = null): array
{
    /** @var list<string> $tones */
    $tones = require dirname(__DIR__, 2) . '/config/ai-tones.php';

    $currentTone = trim((string) $currentTone);
    if ($currentTone !== '' && !in_array($currentTone, $tones, true)) {
        array_unshift($tones, $currentTone);
    }

    return $tones;
}

function dash_platform_label(string $platform): string
{
    return match ($platform) {
        'instagram' => 'Instagram',
        'facebook' => 'Facebook Messenger',
        'whatsapp' => 'WhatsApp',
        'tiktok' => 'TikTok',
        default => ucfirst($platform),
    };
}

/** Labels for Social Media Management (Page wall / IG feed — not DMs). */
function dash_social_feed_label(string $platform): string
{
    return match ($platform) {
        'instagram' => 'Instagram feed',
        'facebook' => 'Facebook Page',
        'tiktok' => 'TikTok videos',
        default => dash_platform_label($platform),
    };
}

/** Group heading for compose modal account picker (Hootsuite-style). */
function dash_social_compose_group_heading(string $platform, int $count): string
{
    $label = match ($platform) {
        'instagram' => 'Instagram',
        'facebook' => 'Facebook Page',
        'tiktok' => 'TikTok',
        default => dash_platform_label($platform),
    };

    return strtoupper($label) . ' (' . $count . ')';
}

/** Subtitle under account name in compose picker. */
function dash_social_compose_account_subtitle(string $platform): string
{
    return match ($platform) {
        'instagram' => 'Business',
        'facebook' => 'Page',
        'tiktok' => '',
        default => 'Account',
    };
}

function dash_platform_icon(string $platform): string
{
    return match ($platform) {
        'instagram' => brand_instagram_icon_html(),
        'facebook' => brand_messenger_icon_html(),
        'whatsapp' => brand_whatsapp_icon_html(),
        'tiktok' => brand_tiktok_icon_html(),
        default => '🔗',
    };
}

/** Platform logo for social-accounts table badge overlay (Facebook f, not Messenger). */
function dash_platform_badge_icon(string $platform, int $size = 18): string
{
    return match ($platform) {
        'instagram' => brand_instagram_icon_html('conn-badge-icon', $size),
        'facebook' => brand_facebook_icon_html('conn-badge-icon', $size),
        'whatsapp' => brand_whatsapp_icon_html('conn-badge-icon', $size),
        'tiktok' => brand_tiktok_icon_html('conn-badge-icon', $size),
        default => '',
    };
}

/** Pre-OAuth instructions page for a platform (Meta channels and TikTok use different apps). */
function dash_platform_setup_url(string $platform, string $businessId, string $returnTo): string
{
    $route = $platform === 'tiktok' ? 'auth/tiktok/setup' : 'auth/meta/setup';
    $query = [
        'business_id' => $businessId,
        'return_to' => $returnTo,
    ];

    if ($platform !== 'tiktok') {
        $query = ['platform' => $platform] + $query;
    }

    return app_route($route, $query);
}

/** Straight-to-OAuth URL used for reconnecting an existing channel. */
function dash_platform_reconnect_url(string $platform, string $businessId, string $returnTo): string
{
    $route = $platform === 'tiktok' ? 'auth/tiktok/connect' : 'auth/meta/connect';
    $query = [
        'business_id' => $businessId,
        'return_to' => $returnTo,
        'mode' => 'reconnect',
    ];

    if ($platform !== 'tiktok') {
        $query = ['platform' => $platform] + $query;
    }

    return app_route($route, $query);
}

/** Large platform logo for social-accounts modal tiles. */
function dash_platform_tile_icon(string $platform, int $size = 48): string
{
    return match ($platform) {
        'instagram' => brand_instagram_icon_html('conn-tile-icon', $size),
        'facebook' => brand_facebook_icon_html('conn-tile-icon', $size),
        'whatsapp' => brand_whatsapp_icon_html('conn-tile-icon', $size),
        'tiktok' => brand_tiktok_icon_html('conn-tile-icon', $size),
        default => '',
    };
}

/** Human-readable name for a connected social account (Page name, @handle, phone, etc.). */
function dash_connected_account_name(array $account): string
{
    $stored = trim((string) ($account['display_name'] ?? ''));
    if ($stored !== '' && !dash_connected_account_name_is_stale($account, $stored)) {
        return $stored;
    }

    $resolved = dash_resolve_connected_account_name($account);
    if ($resolved !== '') {
        $accountId = trim((string) ($account['id'] ?? ''));
        if ($accountId !== '') {
            try {
                RepositoryFactory::connectedAccounts()->update($accountId, ['display_name' => $resolved]);
            } catch (Throwable) {
                // Non-fatal: show resolved name even if cache write fails.
            }
        }

        return $resolved;
    }

    if ($stored !== '') {
        return $stored;
    }

    $platformAccountId = trim((string) ($account['platform_account_id'] ?? ''));
    if ($platformAccountId !== '') {
        return $platformAccountId;
    }

    return dash_platform_label((string) ($account['platform'] ?? ''));
}

/** Profile photo URL for a connected account (cached in DB, fetched from Meta when missing). */
function dash_connected_account_picture_url(array $account): string
{
    $platform = (string) ($account['platform'] ?? '');
    $stored = trim((string) ($account['profile_picture_url'] ?? ''));
    if ($stored !== '' && !dash_connected_account_picture_url_is_stale($stored, $platform)) {
        return $stored;
    }

    if ($stored !== '' && dash_connected_account_picture_url_is_stale($stored, $platform)) {
        $accountId = trim((string) ($account['id'] ?? ''));
        if ($accountId !== '') {
            try {
                RepositoryFactory::connectedAccounts()->update($accountId, ['profile_picture_url' => null]);
            } catch (Throwable) {
                // Continue with a fresh lookup.
            }
        }
        $account['profile_picture_url'] = null;
    }

    $resolved = dash_resolve_connected_account_picture($account);
    if ($resolved !== '') {
        $accountId = trim((string) ($account['id'] ?? ''));
        if ($accountId !== '') {
            try {
                RepositoryFactory::connectedAccounts()->update($accountId, ['profile_picture_url' => $resolved]);
            } catch (Throwable) {
                // Non-fatal: show resolved picture even if cache write fails.
            }
        }
    }

    return $resolved;
}

function dash_connected_account_picture_url_is_stale(string $url, string $platform = ''): bool
{
    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        return true;
    }

    // TikTok CDN avatars are long and signed — trust the expiry stamp instead of the length.
    if ($platform === 'tiktok') {
        return dash_signed_cdn_url_expired($url);
    }

    // Legacy VARCHAR(512) rows were truncated and always fail in the browser.
    return strlen($url) >= 500;
}

/** True when a signed CDN URL (TikTok `x-expires`) is past its expiry. */
function dash_signed_cdn_url_expired(string $url): bool
{
    $query = (string) (parse_url($url, PHP_URL_QUERY) ?? '');
    if ($query === '') {
        return false;
    }

    parse_str($query, $params);
    $expires = (int) ($params['x-expires'] ?? 0);

    return $expires > 0 && $expires <= time();
}

/** Authenticated proxy URL for a connected account avatar (use in <img src>). */
function dash_connected_account_avatar_src(array $account): string
{
    $accountId = trim((string) ($account['id'] ?? ''));
    if ($accountId === '') {
        return '';
    }

    return app_route('connected-accounts/avatar', ['account_id' => $accountId]);
}

function dash_initials_for_name(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $letters .= strtoupper(substr($part, 0, 1));
        if (strlen($letters) >= 2) {
            break;
        }
    }

    return $letters !== '' ? $letters : 'CU';
}

/** Authenticated proxy URL for a customer avatar (use in <img src>). */
function dash_customer_avatar_src(?array $customer): string
{
    if ($customer === null) {
        return '';
    }

    $customerId = trim((string) ($customer['id'] ?? ''));
    if ($customerId === '') {
        return '';
    }

    return app_route('customers/avatar', ['customer_id' => $customerId]);
}

function dash_customer_picture_url_is_stale(string $url): bool
{
    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        return true;
    }

    return strlen($url) >= 500;
}

function dash_resolve_customer_picture(array $customer): string
{
    $platform = trim((string) ($customer['platform'] ?? ''));
    $platformUserId = trim((string) ($customer['platform_user_id'] ?? ''));
    $businessId = trim((string) ($customer['business_id'] ?? ''));
    if ($platformUserId === '' || $businessId === '' || $platform === '' || $platform === 'whatsapp') {
        return '';
    }

    if (!function_exists('meta_fetch_customer_profile')) {
        require_once dirname(__DIR__) . '/meta.php';
    }

    $accounts = RepositoryFactory::connectedAccounts()->listActiveByBusinessAndPlatform($businessId, $platform);
    foreach ($accounts as $account) {
        $profile = meta_fetch_customer_profile($account, $platform, $platformUserId);
        $pictureUrl = trim((string) ($profile['picture_url'] ?? ''));
        if ($pictureUrl !== '') {
            return $pictureUrl;
        }
    }

    return '';
}

/** Profile photo URL for a customer (cached in DB, fetched from Meta when missing). */
function dash_customer_picture_url(array $customer): string
{
    $stored = trim((string) ($customer['profile_picture_url'] ?? ''));
    if ($stored !== '' && !dash_customer_picture_url_is_stale($stored)) {
        return $stored;
    }

    if ($stored !== '' && dash_customer_picture_url_is_stale($stored)) {
        $customerId = trim((string) ($customer['id'] ?? ''));
        if ($customerId !== '') {
            try {
                RepositoryFactory::customers()->updateProfilePicture($customerId, null);
            } catch (Throwable) {
                // Continue with a fresh lookup.
            }
        }
        $customer['profile_picture_url'] = null;
    }

    $resolved = dash_resolve_customer_picture($customer);
    if ($resolved !== '') {
        $customerId = trim((string) ($customer['id'] ?? ''));
        if ($customerId !== '') {
            try {
                RepositoryFactory::customers()->updateProfilePicture($customerId, $resolved);
            } catch (Throwable) {
                // Non-fatal: show resolved picture even if cache write fails.
            }
        }
    }

    return $resolved;
}

/**
 * @param array<string, mixed>|null $customer
 */
function dash_customer_avatar_html(?array $customer, string $displayName, string $className = 'inbox-thread-row-avatar'): string
{
    $initials = dash_initials_for_name($displayName);
    $avatarSrc = dash_customer_avatar_src($customer);
    $className = trim($className);

    if ($avatarSrc === '') {
        return '<span class="' . e($className) . '" aria-hidden="true">' . e($initials) . '</span>';
    }

    return '<span class="' . e($className) . '" aria-hidden="true">'
        . '<img src="' . e($avatarSrc) . '" alt="" class="' . e($className) . '-img" data-inbox-avatar-img loading="lazy" decoding="async">'
        . '<span class="' . e($className) . '-initials is-hidden" aria-hidden="true">' . e($initials) . '</span>'
        . '</span>';
}

function dash_resolve_connected_account_picture(array $account): string
{
    $platform = (string) ($account['platform'] ?? '');
    $platformAccountId = trim((string) ($account['platform_account_id'] ?? ''));
    if ($platformAccountId === '') {
        return '';
    }

    if ($platform === 'tiktok') {
        $profile = (new TikTokConnectService())->profileForAccount($account);

        return trim((string) ($profile['avatar_url_100'] ?? $profile['avatar_url'] ?? ''));
    }

    $tokens = dash_meta_lookup_tokens($account);
    if ($tokens === []) {
        return '';
    }

    if ($platform === 'facebook') {
        foreach ($tokens as $token) {
            $picture = meta_graph_get($platformAccountId . '/picture', $token, [
                'redirect' => '0',
                'type' => 'large',
            ]);
            if (!isset($picture['error'])) {
                $url = trim((string) ($picture['data']['url'] ?? ''));
                if ($url !== '') {
                    return $url;
                }
            }

            $page = meta_graph_get($platformAccountId, $token, ['fields' => 'picture.type(large)']);
            if (!isset($page['error'])) {
                $pictureData = $page['picture']['data'] ?? null;
                if (is_array($pictureData)) {
                    $url = trim((string) ($pictureData['url'] ?? ''));
                    if ($url !== '') {
                        return $url;
                    }
                }
            }
        }

        return '';
    }

    if ($platform === 'instagram') {
        foreach ($tokens as $token) {
            $profile = meta_graph_get($platformAccountId, $token, ['fields' => 'profile_picture_url']);
            if (!isset($profile['error'])) {
                $url = trim((string) ($profile['profile_picture_url'] ?? ''));
                if ($url !== '') {
                    return $url;
                }
            }
        }

        return '';
    }

    return '';
}

function dash_connected_account_name_is_stale(array $account, string $stored): bool
{
    $platformAccountId = trim((string) ($account['platform_account_id'] ?? ''));
    if ($platformAccountId === '') {
        return false;
    }

    if ($stored === $platformAccountId) {
        return true;
    }

    return ctype_digit($stored) && strlen($stored) >= 10;
}

/** @return list<string> */
function dash_meta_lookup_tokens(array $account): array
{
    $tokens = [];
    foreach (['access_token', 'refresh_token'] as $key) {
        $token = trim((string) ($account[$key] ?? ''));
        if ($token !== '' && !in_array($token, $tokens, true)) {
            $tokens[] = $token;
        }
    }

    $businessId = trim((string) ($account['business_id'] ?? ''));
    if ($businessId !== '' && class_exists('MetaConnectService')) {
        $userToken = (new MetaConnectService())->resolveBusinessMetaToken($businessId);
        if ($userToken !== null && !in_array($userToken, $tokens, true)) {
            $tokens[] = $userToken;
        }
    }

    return $tokens;
}

function dash_resolve_connected_account_name(array $account): string
{
    $platform = (string) ($account['platform'] ?? '');
    $platformAccountId = trim((string) ($account['platform_account_id'] ?? ''));

    if ($platformAccountId === '') {
        return '';
    }

    if ($platform === 'facebook') {
        return dash_resolve_facebook_page_name($platformAccountId, $account);
    }

    if ($platform === 'tiktok') {
        $profile = (new TikTokConnectService())->profileForAccount($account);
        $username = trim((string) ($profile['username'] ?? ''));
        if ($username !== '') {
            return '@' . ltrim($username, '@');
        }

        return trim((string) ($profile['display_name'] ?? ''));
    }

    $tokens = dash_meta_lookup_tokens($account);
    if ($tokens === []) {
        return '';
    }

    if ($platform === 'instagram') {
        foreach ($tokens as $token) {
            $profile = meta_graph_get($platformAccountId, $token, ['fields' => 'username,name']);
            if (!isset($profile['error'])) {
                $username = trim((string) ($profile['username'] ?? ''));
                if ($username !== '') {
                    return '@' . ltrim($username, '@');
                }

                $name = trim((string) ($profile['name'] ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return '';
    }

    if ($platform === 'whatsapp') {
        foreach ($tokens as $token) {
            $phone = meta_graph_get($platformAccountId, $token, ['fields' => 'display_phone_number,verified_name']);
            if (isset($phone['error'])) {
                continue;
            }

            $verified = trim((string) ($phone['verified_name'] ?? ''));
            $display = trim((string) ($phone['display_phone_number'] ?? ''));
            if ($verified !== '' && $display !== '') {
                return $verified . ' · ' . $display;
            }

            if ($verified !== '') {
                return $verified;
            }

            if ($display !== '') {
                return $display;
            }
        }

        return '';
    }

    return '';
}

function dash_resolve_facebook_page_name(string $pageId, array $account): string
{
    foreach (dash_meta_lookup_tokens($account) as $token) {
        $page = meta_graph_get($pageId, $token, ['fields' => 'name']);
        if (!isset($page['error'])) {
            $name = trim((string) ($page['name'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }
    }

    foreach (dash_meta_lookup_tokens($account) as $token) {
        $name = dash_find_facebook_page_name_in_accounts($pageId, $token);
        if ($name !== '') {
            return $name;
        }
    }

    foreach (dash_meta_lookup_tokens($account) as $token) {
        $name = dash_find_facebook_page_name_in_business_portfolio($pageId, $token);
        if ($name !== '') {
            return $name;
        }
    }

    return '';
}

function dash_find_facebook_page_name_in_business_portfolio(string $pageId, string $userToken): string
{
    $businesses = meta_graph_get('me/businesses', $userToken, [
        'fields' => 'id,name',
        'limit' => 25,
    ]);

    if (isset($businesses['error'])) {
        return '';
    }

    foreach ($businesses['data'] ?? [] as $business) {
        if (!is_array($business)) {
            continue;
        }

        $businessId = (string) ($business['id'] ?? '');
        if ($businessId === '') {
            continue;
        }

        foreach (['owned_pages', 'client_pages'] as $edge) {
            $pages = meta_graph_get($businessId . '/' . $edge, $userToken, [
                'fields' => 'id,name',
                'limit' => 100,
            ]);

            if (isset($pages['error'])) {
                continue;
            }

            foreach ($pages['data'] ?? [] as $page) {
                if (!is_array($page)) {
                    continue;
                }

                if ((string) ($page['id'] ?? '') !== $pageId) {
                    continue;
                }

                $name = trim((string) ($page['name'] ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
        }
    }

    return '';
}

function dash_find_facebook_page_name_in_accounts(string $pageId, string $userToken): string
{
    $after = null;

    for ($page = 0; $page < 10; $page++) {
        $query = ['fields' => 'id,name', 'limit' => 100];
        if ($after !== null) {
            $query['after'] = $after;
        }

        $data = meta_graph_get('me/accounts', $userToken, $query);
        if (isset($data['error'])) {
            break;
        }

        foreach ($data['data'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }

            if ((string) ($row['id'] ?? '') !== $pageId) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        $after = trim((string) ($data['paging']['cursors']['after'] ?? ''));
        if ($after === '') {
            break;
        }
    }

    return '';
}

function dash_activity_icon(string $type): string
{
    return match ($type) {
        'message_received' => '💬',
        'payment_received' => '💰',
        'owner_alerted' => '🔔',
        'payment_failed' => '⚠️',
        'account_disconnected' => '🔗',
        'ai_training' => '📚',
        'dm_limit' => '📊',
        'tokens_depleted' => '🔋',
        'tokens_low' => '⚡',
        default => '•',
    };
}

function dash_token_ledger_label(string $reason): string
{
    return match ($reason) {
        'topup' => 'Token top-up',
        'subscription_start' => 'Plan tokens',
        'subscription_renewal' => 'Plan renewal',
        'dm_reply' => 'AI reply sent',
        'ai_test_chat' => 'AI test chat',
        'social_caption' => 'AI caption',
        'social_hashtags' => 'AI hashtags',
        'social_publish' => 'Social publish',
        'social_publish_refund' => 'Publish refund',
        'dashboard_assistant' => 'Dashboard assistant',
        'knowledge_ai_refresh' => 'Knowledge refresh',
        default => ucwords(str_replace('_', ' ', $reason)),
    };
}

function dash_token_ring_offset(?float $percentRemaining, float $radius = 45): float
{
    if ($percentRemaining === null) {
        return 0.0;
    }

    $circumference = 2 * M_PI * $radius;

    return round($circumference * (1 - min(100.0, max(0.0, $percentRemaining)) / 100), 2);
}

function dash_render_activity_icon(string $type, string $wrapperClass = 'dash-timeline-icon'): void
{
    if ($type === 'ai_replied') {
        echo '<span class="', e($wrapperClass), ' brand-ai-icon-wrap">';
        echo brand_ai_icon_html('brand-ai-icon--timeline', 18);
        echo '</span>';

        return;
    }

    echo '<span class="', e($wrapperClass), '">', dash_activity_icon($type), '</span>';
}

/**
 * @param list<array{label: string, value: int}> $data
 */
function dash_render_bar_chart(array $data, string $id = 'chart'): void
{
    $max = 1;
    foreach ($data as $point) {
        $max = max($max, (int) ($point['value'] ?? 0));
    }
    ?>
    <div class="dash-chart" id="<?= e($id) ?>" data-chart>
        <div class="dash-chart-bars">
            <?php foreach ($data as $point): ?>
                <?php
                $value = (int) ($point['value'] ?? 0);
                $height = $max > 0 ? max(4, (int) round(($value / $max) * 100)) : 4;
                ?>
                <div class="dash-chart-bar-wrap" title="<?= e((string) $point['label']) ?>: <?= $value ?>">
                    <div class="dash-chart-bar" style="height: <?= $height ?>%"></div>
                    <span class="dash-chart-label"><?= e((string) $point['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Compose preview disclaimer (Hootsuite-style).
 */
function dash_compose_preview_disclaimer_html(): string
{
    return '<p class="smm-compose-preview-disclaimer font-sans">Social networks regularly make updates to formatting, so your post may appear slightly different when published. <a href="https://help.hootsuite.com/hc/en-us/articles/4403585079015-Create-and-publish-posts" target="_blank" rel="noopener noreferrer">Learn more</a></p>';
}

/**
 * Facebook post preview footer icons (Hootsuite-style outlines).
 */
function dash_facebook_post_preview_action_icon(string $action, int $size = 20): string
{
    $class = 'smm-compose-preview-fb-action-icon';
    $icons = [
        'like' => '<path d="M7 10v12M7 10l1.45-4.35h4.05l-1.05 4.35h3.05L11 22H7V10Z" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round" stroke-linejoin="round"/>',
        'comment' => '<path d="M12 20.5c4.14 0 7.5-3.03 7.5-6.75S16.14 7 12 7 4.5 10.03 4.5 13.75c0 1.98.95 3.77 2.47 5.02L6 22l3.35-2.01c.84.18 1.72.28 2.65.28Z" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linejoin="round"/>',
        'send' => '<path d="M12 21a9 9 0 1 0-9-9 9 9 0 0 0 9 9Zm-3.75-5.25 7.5-3.75-7.5-3.75v2.63L6.75 11.25" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round" stroke-linejoin="round"/>',
        'share' => '<path d="M16 8.5 20 4.5m0 0-4-4m4 4H9a4.5 4.5 0 0 0 0 9h1" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    $path = $icons[$action] ?? '';
    if ($path === '') {
        return '';
    }

    $viewBox = '0 0 24 24';

    return '<svg class="' . e($class) . '" viewBox="' . e($viewBox) . '" width="' . $size . '" height="' . $size . '" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">' . $path . '</svg>';
}
