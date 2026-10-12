<?php

declare(strict_types=1);

/**
 * Load environment variables from .env (simple parser, no Composer required).
 */
if (!function_exists('load_env')) {
function load_env(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\"'");
        if ($name !== '' && getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }
}
}

if (!defined('AISG_ENV_LOADED')) {
    load_env(__DIR__ . '/.env');
    define('AISG_ENV_LOADED', true);
}

if (!function_exists('env')) {
function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return (string) $value;
}
}

if (!function_exists('env_int')) {
function env_int(string $key, int $default): int
{
    $value = env($key);
    return $value !== null && is_numeric($value) ? (int) $value : $default;
}
}

if (!function_exists('env_float')) {
function env_float(string $key, float $default): float
{
    $value = env($key);
    return $value !== null && is_numeric($value) ? (float) $value : $default;
}
}

return [
    'app_url' => rtrim(env('APP_URL', 'https://leadbird.app') ?? 'https://leadbird.app', '/'),
    'app_env' => env('APP_ENV', 'production') ?? 'production',
    'timezone' => env('APP_TIMEZONE', 'Africa/Lagos') ?? 'Africa/Lagos',
    'database' => [
        'host' => env('DB_HOST', 'localhost') ?? 'localhost',
        'port' => env_int('DB_PORT', 3306),
        'name' => env('DB_NAME', '') ?? '',
        'user' => env('DB_USER', '') ?? '',
        'password' => env('DB_PASSWORD', '') ?? '',
        'charset' => env('DB_CHARSET', 'utf8mb4') ?? 'utf8mb4',
    ],
    'brand_name' => env('BRAND_NAME', 'Lead Bird') ?? 'Lead Bird',
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', '') ?? '',
        'client_secret' => env('GOOGLE_CLIENT_SECRET', '') ?? '',
    ],
    'resend' => [
        'api_key' => env('RESEND_API_KEY', '') ?? '',
        'from' => env('RESEND_FROM_EMAIL', 'AI Sales Girl <noreply@example.com>') ?? 'AI Sales Girl <noreply@example.com>',
    ],
    'mail' => [
        'domain' => env('MAIL_DOMAIN', 'leadbird.app') ?? 'leadbird.app',
        'app_name' => env('MAIL_APP_NAME', 'Lead Bird Mail') ?? 'Lead Bird Mail',
        'admin_email' => env('MAIL_ADMIN_EMAIL', 'admin@leadbird.app') ?? 'admin@leadbird.app',
        'admin_display_name' => env('MAIL_ADMIN_DISPLAY_NAME', 'Lead Bird Admin') ?? 'Lead Bird Admin',
        'path' => env('MAIL_PATH', '/mail') ?? '/mail',
        'catch_all_email' => env('MAIL_CATCH_ALL_EMAIL', '') ?? '',
        'max_attachment_bytes' => env_int('MAIL_MAX_ATTACHMENT_BYTES', 10485760),
        'max_attachments' => env_int('MAIL_MAX_ATTACHMENTS', 10),
        'webhook_secret' => env('RESEND_WEBHOOK_SECRET', '') ?? '',
    ],
    'otp' => [
        'length' => env_int('OTP_LENGTH', 6),
        'ttl_minutes' => env_int('OTP_TTL_MINUTES', 10),
        'max_attempts' => env_int('OTP_MAX_ATTEMPTS', 5),
        'resend_cooldown_seconds' => env_int('OTP_RESEND_COOLDOWN_SECONDS', 60),
    ],
    'auth' => [
        'google_redirect' => rtrim(env('APP_URL', 'https://leadbird.app') ?? 'https://leadbird.app', '/') . '/auth/callback',
        'google_calendar_redirect' => rtrim(env('APP_URL', 'https://leadbird.app') ?? 'https://leadbird.app', '/') . '/integrations/google-calendar/callback',
        'session_cookie' => 'aisg_session',
        'idle_timeout_minutes' => env_int('AUTH_IDLE_TIMEOUT_MINUTES', 30),
        'idle_warning_seconds' => env_int('AUTH_IDLE_WARNING_SECONDS', 120),
        'absolute_timeout_hours' => env_int('AUTH_ABSOLUTE_TIMEOUT_HOURS', 12),
    ],
    'plan_selection' => [
        'ttl_minutes' => env_int('PLAN_SELECTION_TTL_MINUTES', 30),
        'cookie_name' => 'lb_plan_intent',
    ],
    'flutterwave' => [
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY', '') ?? '',
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY', '') ?? '',
        'secret_hash' => env('FLUTTERWAVE_SECRET_HASH', '') ?? '',
    ],
    'contact' => [
        'phone' => env('CONTACT_PHONE', '+234 800 000 0000') ?? '+234 800 000 0000',
        'email' => env('CONTACT_EMAIL', 'hello@leadbird.com') ?? 'hello@leadbird.com',
        'inbox_email' => env('CONTACT_INBOX_EMAIL', env('CONTACT_EMAIL', 'hello@leadbird.com') ?? 'hello@leadbird.com') ?? 'hello@leadbird.com',
        'web_label' => env('CONTACT_WEB_LABEL', '') ?? '',
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY', '') ?? '',
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini') ?? 'gpt-4o-mini',
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small') ?? 'text-embedding-3-small',
        'vision_model' => env('OPENAI_VISION_MODEL', 'gpt-4o-mini') ?? 'gpt-4o-mini',
        'monthly_budget_usd' => env_float('OPENAI_MONTHLY_BUDGET_USD', 3.0),
        'platform_monthly_budget_usd' => env_float('OPENAI_PLATFORM_MONTHLY_BUDGET_USD', 10.0),
    ],
    'meta' => [
        'app_id' => env('META_APP_ID', '') ?? '',
        'app_secret' => env('META_APP_SECRET', '') ?? '',
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN', '') ?? '',
        'graph_version' => env('META_GRAPH_VERSION', 'v21.0') ?? 'v21.0',
        'enable_page_publish' => filter_var(env('META_ENABLE_PAGE_PUBLISH', '0'), FILTER_VALIDATE_BOOL),
        'enable_ig_publish' => filter_var(env('META_ENABLE_IG_PUBLISH', '0'), FILTER_VALIDATE_BOOL),
        'login_config_id' => trim((string) (env('META_FB_LOGIN_CONFIG_ID', '') ?? '')),
        'login_config_id_whatsapp' => trim((string) (env('META_FB_LOGIN_CONFIG_ID_WHATSAPP', '') ?? '')),
        'embedded_signup_config_id' => trim((string) (env('META_EMBEDDED_SIGNUP_CONFIG_ID', '') ?? '')),
        'login_config_id_instagram' => trim((string) (env('META_FB_LOGIN_CONFIG_ID_INSTAGRAM', '') ?? '')),
        'login_config_id_facebook' => trim((string) (env('META_FB_LOGIN_CONFIG_ID_FACEBOOK', '') ?? '')),
        'use_bundled_login_config' => filter_var(env('META_USE_BUNDLED_LOGIN_CONFIG', '0'), FILTER_VALIDATE_BOOL),
        // 0 = scope-only dialog/oauth. 1 = Login Config ID (recommended after dashboard setup below).
        'oauth_use_login_config' => filter_var(env('META_OAUTH_USE_LOGIN_CONFIG', '0'), FILTER_VALIDATE_BOOL),
        // 0 = User access token Login Config (Hootsuite-style, no page picker). 1 = System-user config only.
        'oauth_system_user_config' => filter_var(env('META_OAUTH_SYSTEM_USER_CONFIG', '0'), FILTER_VALIDATE_BOOL),
        // 1 = GDP consent URL (usually fails on new apps). 0 = dialog/oauth or Login Config.
        'oauth_use_gdp_flow' => filter_var(env('META_OAUTH_USE_GDP_FLOW', '0'), FILTER_VALIDATE_BOOL),
        'redirect_uri' => rtrim(env('APP_URL', 'https://leadbird.app') ?? 'https://leadbird.app', '/')
            . '/auth/meta/callback',
    ],
    'whatsapp' => [
        'system_user_token' => env('WHATSAPP_SYSTEM_USER_TOKEN', env('META_SYSTEM_USER_TOKEN', '') ?? '') ?? '',
    ],
    'onboarding' => [
        // Off: new businesses skip the 6-step wizard and land on the dashboard.
        'legacy_wizard' => filter_var(env('ONBOARDING_LEGACY_WIZARD', '0'), FILTER_VALIDATE_BOOL),
    ],
    'tiktok' => [
        'enabled' => filter_var(env('TIKTOK_ENABLED', '0'), FILTER_VALIDATE_BOOL),
        'client_key' => trim((string) (env('TIKTOK_CLIENT_KEY', '') ?? '')),
        'client_secret' => trim((string) (env('TIKTOK_CLIENT_SECRET', '') ?? '')),
        // Sandbox apps only work for target users added in the TikTok developer portal.
        'sandbox' => filter_var(env('TIKTOK_SANDBOX', '1'), FILTER_VALIDATE_BOOL),
        'scopes' => trim((string) (env(
            'TIKTOK_SCOPES',
            'user.info.basic,user.info.profile,user.info.stats,video.publish,video.upload,video.list'
        ) ?? '')),
        // TikTok rejects redirect URIs with query parameters — context travels in `state`.
        'redirect_uri' => rtrim(env('APP_URL', 'https://leadbird.app') ?? 'https://leadbird.app', '/')
            . '/auth/tiktok/callback',
    ],
    'admin' => [
        'emails' => array_values(array_filter(array_map(
            static fn(string $email): string => strtolower(trim($email)),
            explode(',', env('ADMIN_EMAILS', '') ?? '')
        ))),
    ],
    'app_review' => [
        'enabled' => filter_var(env('APP_REVIEW_ENABLED', '0'), FILTER_VALIDATE_BOOL),
        'email' => trim((string) (env('APP_REVIEW_EMAIL', '') ?? '')),
        'password' => (string) (env('APP_REVIEW_PASSWORD', '') ?? ''),
    ],
];
