<?php

declare(strict_types=1);

/**
 * Low-level TikTok Open API (v2) transport helpers.
 *
 * OAuth token endpoints require application/x-www-form-urlencoded.
 * Content/user endpoints are JSON with a Bearer access token.
 */

const TIKTOK_API_BASE = 'https://open.tiktokapis.com/v2';
const TIKTOK_AUTHORIZE_URL = 'https://www.tiktok.com/v2/auth/authorize/';

/**
 * Publishing failures are rewritten into friendly copy before anyone sees them,
 * so keep the raw TikTok text here. `log_errors` is off on this host, which makes
 * error_log() unreliable — write to our own file like the Meta webhook does.
 */
function tiktok_log(string $message): void
{
    $dir = dirname(__DIR__) . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    @file_put_contents(
        $dir . '/tiktok.log',
        '[' . date('c') . '] ' . $message . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

/**
 * @return array<string, mixed>
 */
function tiktok_config(): array
{
    $config = app_config();

    return is_array($config['tiktok'] ?? null) ? $config['tiktok'] : [];
}

function tiktok_is_configured(): bool
{
    $config = tiktok_config();

    return (string) ($config['client_key'] ?? '') !== '' && (string) ($config['client_secret'] ?? '') !== '';
}

function tiktok_is_enabled(): bool
{
    return (bool) (tiktok_config()['enabled'] ?? false) && tiktok_is_configured();
}

/** Comma-separated scopes requested during authorization. */
function tiktok_scopes(): string
{
    $scopes = trim((string) (tiktok_config()['scopes'] ?? ''));

    return $scopes !== '' ? $scopes : 'user.info.basic';
}

/**
 * Form-encoded POST used by all /v2/oauth/* endpoints.
 *
 * @param array<string, string> $params
 * @return array<string, mixed>
 */
function tiktok_oauth_post(string $path, array $params): array
{
    $url = TIKTOK_API_BASE . '/' . ltrim($path, '/');

    $ch = curl_init($url);
    if ($ch === false) {
        return ['error' => 'transport_error', 'error_description' => 'Could not connect to TikTok.'];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Cache-Control: no-cache',
        ],
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($response) || $response === '') {
        return ['error' => 'transport_error', 'error_description' => 'Empty response from TikTok.'];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['error' => 'invalid_response', 'error_description' => 'Invalid response from TikTok.'];
    }

    if ($status < 200 || $status >= 300 || isset($decoded['error'])) {
        error_log('[TikTok] POST ' . $path . ' failed HTTP ' . $status . ' body=' . mb_substr($response, 0, 400));
    }

    return $decoded;
}

/**
 * Bearer-authenticated GET (user info, publish status, video list).
 *
 * @param array<string, string> $query
 * @return array<string, mixed>
 */
function tiktok_api_get(string $path, string $accessToken, array $query = []): array
{
    $url = TIKTOK_API_BASE . '/' . ltrim($path, '/');
    if ($query !== []) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return ['error' => ['code' => 'transport_error', 'message' => 'Could not connect to TikTok.']];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($response) || $response === '') {
        return ['error' => ['code' => 'transport_error', 'message' => 'Empty response from TikTok.']];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['error' => ['code' => 'invalid_response', 'message' => 'Invalid response from TikTok.']];
    }

    if ($status < 200 || $status >= 300) {
        error_log('[TikTok] GET ' . $path . ' failed HTTP ' . $status . ' body=' . mb_substr($response, 0, 400));
    }

    return $decoded;
}

/**
 * Bearer-authenticated JSON POST (content posting endpoints).
 *
 * @param array<string, mixed> $payload
 * @return array<string, mixed>
 */
function tiktok_api_post(string $path, string $accessToken, array $payload = []): array
{
    $url = TIKTOK_API_BASE . '/' . ltrim($path, '/');

    $ch = curl_init($url);
    if ($ch === false) {
        return ['error' => ['code' => 'transport_error', 'message' => 'Could not connect to TikTok.']];
    }

    // PHP encodes [] as the JSON array "[]", but TikTok requires a JSON object.
    // An empty body must be "{}" or TikTok rejects it as "request parameter type is incorrect".
    $body = $payload === []
        ? '{}'
        : json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json; charset=UTF-8',
        ],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($response) || $response === '') {
        return ['error' => ['code' => 'transport_error', 'message' => 'Empty response from TikTok.']];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['error' => ['code' => 'invalid_response', 'message' => 'Invalid response from TikTok.']];
    }

    if ($status < 200 || $status >= 300) {
        error_log('[TikTok] POST ' . $path . ' failed HTTP ' . $status . ' body=' . mb_substr($response, 0, 400));
    }

    return $decoded;
}

/**
 * TikTok wraps errors in {error: {code, message, log_id}} with code "ok" on success.
 *
 * @param array<string, mixed> $response
 */
function tiktok_error_message(array $response): string
{
    $error = $response['error'] ?? null;

    if (is_string($error)) {
        $description = trim((string) ($response['error_description'] ?? ''));

        return $description !== '' ? $description : $error;
    }

    if (is_array($error)) {
        $code = strtolower(trim((string) ($error['code'] ?? '')));
        if ($code === '' || $code === 'ok') {
            return '';
        }

        $message = trim((string) ($error['message'] ?? ''));

        /*
         * Keep the code in front of the message. Every humanizer matches on codes,
         * and TikTok's own message is often just a link to their guidelines.
         */
        return $message !== '' ? $code . ': ' . $message : $code;
    }

    return '';
}

/**
 * Videos currently on the creator's TikTok profile (Display API).
 *
 * This is read-only on purpose: TikTok publishes no endpoint to edit or delete a
 * live video. It is also how we notice a video the creator deleted in the TikTok
 * app — it simply stops coming back in this list.
 *
 * @return array{posts: list<array<string, mixed>>, error: ?string}
 */
function tiktok_fetch_videos_result(string $accessToken, int $limit = 20): array
{
    $fields = 'id,title,video_description,create_time,cover_image_url,share_url,duration'
        . ',like_count,comment_count,share_count,view_count';

    $response = tiktok_api_post(
        'video/list/?fields=' . $fields,
        $accessToken,
        ['max_count' => max(1, min(20, $limit))]
    );

    $error = tiktok_error_message($response);
    if ($error !== '') {
        return ['posts' => [], 'error' => tiktok_humanize_error($error)];
    }

    $videos = is_array($response['data']['videos'] ?? null) ? $response['data']['videos'] : [];
    $posts = [];

    foreach ($videos as $video) {
        if (!is_array($video)) {
            continue;
        }

        $id = trim((string) ($video['id'] ?? ''));
        if ($id === '') {
            continue;
        }

        $caption = trim((string) ($video['video_description'] ?? ''));
        if ($caption === '') {
            $caption = trim((string) ($video['title'] ?? ''));
        }

        $createdAt = (int) ($video['create_time'] ?? 0);

        $posts[] = [
            'id' => $id,
            'caption' => $caption,
            'media_type' => 'VIDEO',
            'image_url' => '',
            // Signed CDN cover art; it expires, so it is only ever used fresh.
            'thumbnail_url' => trim((string) ($video['cover_image_url'] ?? '')),
            'permalink' => trim((string) ($video['share_url'] ?? '')),
            'timestamp' => $createdAt > 0 ? gmdate('c', $createdAt) : '',
            'duration' => (int) ($video['duration'] ?? 0),
            'like_count' => (int) ($video['like_count'] ?? 0),
            'comment_count' => (int) ($video['comment_count'] ?? 0),
            'share_count' => (int) ($video['share_count'] ?? 0),
            'view_count' => (int) ($video['view_count'] ?? 0),
        ];
    }

    return ['posts' => $posts, 'error' => null];
}

/**
 * Stats for a single video. TikTok ships the counts with the video record, so this
 * is one call — there is no separate insights endpoint to hit.
 *
 * @return array{
 *   ok: bool,
 *   post?: array<string, mixed>,
 *   metrics?: list<array{name: string, label: string, value: int|float|string|null}>,
 *   error?: string
 * }
 */
function tiktok_fetch_video_insights_result(string $accessToken, string $videoId): array
{
    $videoId = trim($videoId);
    if ($videoId === '') {
        return ['ok' => false, 'error' => 'This post is not ready for insights yet.'];
    }

    $fields = 'id,title,video_description,create_time,cover_image_url,share_url,duration'
        . ',like_count,comment_count,share_count,view_count';

    $response = tiktok_api_post(
        'video/query/?fields=' . $fields,
        $accessToken,
        ['filters' => ['video_ids' => [$videoId]]]
    );

    $error = tiktok_error_message($response);
    if ($error !== '') {
        return ['ok' => false, 'error' => tiktok_humanize_error($error)];
    }

    $videos = is_array($response['data']['videos'] ?? null) ? $response['data']['videos'] : [];
    $video = is_array($videos[0] ?? null) ? $videos[0] : null;
    if ($video === null) {
        return ['ok' => false, 'error' => 'This video is no longer on TikTok.'];
    }

    $caption = trim((string) ($video['video_description'] ?? ''));
    if ($caption === '') {
        $caption = trim((string) ($video['title'] ?? ''));
    }

    $createdAt = (int) ($video['create_time'] ?? 0);
    $cover = trim((string) ($video['cover_image_url'] ?? ''));

    return [
        'ok' => true,
        'post' => [
            'id' => trim((string) ($video['id'] ?? $videoId)),
            'caption' => $caption,
            'media_type' => 'video',
            'media_url' => '',
            'thumbnail_url' => $cover,
            'permalink' => trim((string) ($video['share_url'] ?? '')),
            'timestamp' => $createdAt > 0 ? gmdate('c', $createdAt) : '',
            'username' => '',
            'post_format' => 'Video',
        ],
        'metrics' => [
            ['name' => 'view_count', 'label' => 'Views', 'value' => (int) ($video['view_count'] ?? 0)],
            ['name' => 'like_count', 'label' => 'Likes', 'value' => (int) ($video['like_count'] ?? 0)],
            ['name' => 'comment_count', 'label' => 'Comments', 'value' => (int) ($video['comment_count'] ?? 0)],
            ['name' => 'share_count', 'label' => 'Shares', 'value' => (int) ($video['share_count'] ?? 0)],
        ],
    ];
}

/**
 * Strips the machine code so users never read raw TikTok error codes.
 */
function tiktok_error_display_text(string $message): string
{
    $message = trim($message);
    if ($message === '') {
        return '';
    }

    if (preg_match('/^[a-z0-9_.]+:\s*(.+)$/s', $message, $matches) === 1) {
        return trim($matches[1]);
    }

    return $message;
}

/** Plain-English version of TikTok OAuth/API failures for dashboard users. */
function tiktok_humanize_error(string $message): string
{
    $lower = strtolower($message);

    if (str_contains($lower, 'scope_not_authorized') || str_contains($lower, 'scope not authorized')) {
        return 'TikTok did not grant all the permissions Lead Bird needs. Try connecting again and approve every permission.';
    }

    if (str_contains($lower, 'unaudited') || str_contains($lower, 'sandbox')) {
        return 'This TikTok app is still in sandbox. Add the TikTok account as a target user in the TikTok developer portal, then connect again.';
    }

    if (str_contains($lower, 'invalid_grant') || str_contains($lower, 'authorization code')) {
        return 'The TikTok authorization expired before we could finish. Please connect again.';
    }

    if (str_contains($lower, 'invalid_client') || str_contains($lower, 'client_key')) {
        return 'TikTok rejected the Lead Bird app credentials. Check the TikTok client key and secret.';
    }

    if (str_contains($lower, 'access_token_invalid') || str_contains($lower, 'token expired')) {
        return 'Your TikTok connection expired. Reconnect the account to continue.';
    }

    $display = tiktok_error_display_text($message);

    return $display !== '' ? $display : 'TikTok connection failed. Please try again.';
}
