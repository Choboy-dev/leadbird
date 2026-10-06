<?php

declare(strict_types=1);

function meta_webhook_log(string $message): void
{
    $dir = dirname(__DIR__) . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    @file_put_contents(
        $dir . '/meta-webhook.log',
        '[' . date('c') . '] ' . $message . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

function meta_webhook_verify_challenge(): bool
{
    $mode = (string) ($_GET['hub_mode'] ?? '');
    $token = (string) ($_GET['hub_verify_token'] ?? '');
    $challenge = (string) ($_GET['hub_challenge'] ?? '');
    $expected = (string) (app_config()['meta']['webhook_verify_token'] ?? '');

    if ($mode !== 'subscribe' || $expected === '' || !hash_equals($expected, $token)) {
        return false;
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo $challenge;
    return true;
}

function meta_verify_signature(string $rawBody): bool
{
    $secret = (string) (app_config()['meta']['app_secret'] ?? '');
    if ($secret === '') {
        return false;
    }

    $header = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    if (!is_string($header) || !str_starts_with($header, 'sha256=')) {
        return false;
    }

    $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);

    return hash_equals($expected, $header);
}

/**
 * Decode a Meta signed_request (deauthorize + data deletion callbacks).
 * Returns the payload only when the HMAC-SHA256 signature matches the app secret.
 *
 * @return array<string, mixed>|null
 */
function meta_parse_signed_request(string $signedRequest): ?array
{
    $secret = (string) (app_config()['meta']['app_secret'] ?? '');
    if ($secret === '' || !str_contains($signedRequest, '.')) {
        return null;
    }

    [$encodedSig, $encodedPayload] = explode('.', $signedRequest, 2);

    $signature = meta_base64_url_decode($encodedSig);
    $payloadJson = meta_base64_url_decode($encodedPayload);
    if ($signature === null || $payloadJson === null) {
        return null;
    }

    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) {
        return null;
    }

    if (strtoupper((string) ($payload['algorithm'] ?? '')) !== 'HMAC-SHA256') {
        return null;
    }

    $expected = hash_hmac('sha256', $encodedPayload, $secret, true);
    if (!hash_equals($expected, $signature)) {
        return null;
    }

    return $payload;
}

function meta_base64_url_decode(string $value): ?string
{
    $padded = strtr($value, '-_', '+/');
    $remainder = strlen($padded) % 4;
    if ($remainder > 0) {
        $padded .= str_repeat('=', 4 - $remainder);
    }

    $decoded = base64_decode($padded, true);

    return $decoded === false ? null : $decoded;
}

/**
 * Instagram outbound messages must use the linked Facebook Page ID, not the IG account ID.
 *
 * @param array<string, mixed> $connectedAccount
 */
function meta_messaging_page_id(string $platform, array $connectedAccount): string
{
    if ($platform === 'facebook') {
        return (string) ($connectedAccount['platform_account_id'] ?? '');
    }

    if ($platform !== 'instagram') {
        return '';
    }

    $storedPageId = trim((string) ($connectedAccount['linked_page_id'] ?? $connectedAccount['meta_page_id'] ?? ''));
    if ($storedPageId !== '') {
        return $storedPageId;
    }

    $businessId = (string) ($connectedAccount['business_id'] ?? '');
    if ($businessId !== '' && class_exists('RepositoryFactory')) {
        $facebook = RepositoryFactory::connectedAccounts()->findActiveByPlatform($businessId, 'facebook')
            ?? RepositoryFactory::connectedAccounts()->findLinkedFacebookPageForMessaging($businessId);
        if (is_array($facebook)) {
            $pageId = trim((string) ($facebook['platform_account_id'] ?? ''));
            if ($pageId !== '') {
                return $pageId;
            }
        }
    }

    return '';
}

/**
 * @param array<string, mixed> $connectedAccount
 */
function meta_send_text_message(string $platform, array $connectedAccount, string $recipientId, string $text): bool
{
    $token = (string) ($connectedAccount['access_token'] ?? '');
    if ($token === '' || $text === '') {
        return false;
    }

    $version = (string) (app_config()['meta']['graph_version'] ?? 'v21.0');

    if ($platform === 'instagram') {
        $pageId = meta_messaging_page_id($platform, $connectedAccount);
        if ($pageId === '') {
            meta_webhook_log('send failed platform=instagram reason=missing_page_id');

            return false;
        }
        $url = 'https://graph.facebook.com/' . $version . '/' . rawurlencode($pageId) . '/messages';
    } else {
        $url = 'https://graph.facebook.com/' . $version . '/me/messages';
    }

    $payload = [
        'recipient' => ['id' => $recipientId],
        'message' => ['text' => $text],
    ];

    if ($platform === 'facebook') {
        $payload['messaging_type'] = 'RESPONSE';
    }

    $sent = meta_graph_post($url, $token, $payload);
    if (!$sent) {
        meta_webhook_log(
            'send failed platform=' . $platform
            . ' page=' . meta_messaging_page_id($platform, $connectedAccount)
            . ' recipient=' . $recipientId
        );
    }

    return $sent;
}

/**
 * @param array<string, mixed> $connectedAccount
 */
function meta_send_image_message(string $platform, array $connectedAccount, string $recipientId, string $imageUrl): bool
{
    $token = (string) ($connectedAccount['access_token'] ?? '');
    if ($token === '' || $imageUrl === '') {
        return false;
    }

    $version = (string) (app_config()['meta']['graph_version'] ?? 'v21.0');

    if ($platform === 'instagram') {
        $pageId = meta_messaging_page_id($platform, $connectedAccount);
        if ($pageId === '') {
            return false;
        }
        $url = 'https://graph.facebook.com/' . $version . '/' . rawurlencode($pageId) . '/messages';
    } else {
        $url = 'https://graph.facebook.com/' . $version . '/me/messages';
    }

    $payload = [
        'recipient' => ['id' => $recipientId],
        'message' => [
            'attachment' => [
                'type' => 'image',
                'payload' => ['url' => $imageUrl, 'is_reusable' => true],
            ],
        ],
    ];

    if ($platform === 'facebook') {
        $payload['messaging_type'] = 'RESPONSE';
    }

    return meta_graph_post($url, $token, $payload);
}

/**
 * @param array<string, mixed> $connectedAccount
 */
function meta_send_file_message(string $platform, array $connectedAccount, string $recipientId, string $fileUrl): bool
{
    $token = (string) ($connectedAccount['access_token'] ?? '');
    if ($token === '' || $fileUrl === '') {
        return false;
    }

    $version = (string) (app_config()['meta']['graph_version'] ?? 'v21.0');

    if ($platform === 'instagram') {
        $pageId = meta_messaging_page_id($platform, $connectedAccount);
        if ($pageId === '') {
            return false;
        }
        $url = 'https://graph.facebook.com/' . $version . '/' . rawurlencode($pageId) . '/messages';
    } else {
        $url = 'https://graph.facebook.com/' . $version . '/me/messages';
    }

    $payload = [
        'recipient' => ['id' => $recipientId],
        'message' => [
            'attachment' => [
                'type' => 'file',
                'payload' => ['url' => $fileUrl, 'is_reusable' => true],
            ],
        ],
    ];

    if ($platform === 'facebook') {
        $payload['messaging_type'] = 'RESPONSE';
    }

    return meta_graph_post($url, $token, $payload);
}

/**
 * @param array<string, mixed> $connectedAccount
 */
function meta_send_video_message(string $platform, array $connectedAccount, string $recipientId, string $videoUrl): bool
{
    $token = (string) ($connectedAccount['access_token'] ?? '');
    if ($token === '' || $videoUrl === '') {
        return false;
    }

    $version = (string) (app_config()['meta']['graph_version'] ?? 'v21.0');

    if ($platform === 'instagram') {
        $pageId = meta_messaging_page_id($platform, $connectedAccount);
        if ($pageId === '') {
            return false;
        }
        $url = 'https://graph.facebook.com/' . $version . '/' . rawurlencode($pageId) . '/messages';
    } else {
        $url = 'https://graph.facebook.com/' . $version . '/me/messages';
    }

    $payload = [
        'recipient' => ['id' => $recipientId],
        'message' => [
            'attachment' => [
                'type' => 'video',
                'payload' => ['url' => $videoUrl, 'is_reusable' => true],
            ],
        ],
    ];

    if ($platform === 'facebook') {
        $payload['messaging_type'] = 'RESPONSE';
    }

    return meta_graph_post($url, $token, $payload);
}

/**
 * @param array<string, mixed> $payload
 */
function meta_graph_version(): string
{
    return (string) (app_config()['meta']['graph_version'] ?? 'v21.0');
}

/**
 * @param array<string, scalar|null> $query
 * @return array<string, mixed>
 */
function meta_graph_get(string $path, string $accessToken, array $query = []): array
{
    $version = meta_graph_version();
    $path = ltrim($path, '/');
    $query['access_token'] = $accessToken;
    $url = 'https://graph.facebook.com/' . $version . '/' . $path . '?' . http_build_query($query);

    $ch = curl_init($url);
    if ($ch === false) {
        return [];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($response)) {
        return ['error' => ['message' => 'Could not reach Meta.']];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['error' => ['message' => 'Invalid response from Meta.']];
    }

    if ($status < 200 || $status >= 300) {
        error_log('[Meta] GET ' . $path . ' failed HTTP ' . $status . ' body=' . $response);

        return $decoded;
    }

    return $decoded;
}

function meta_exchange_long_lived_token(string $shortLivedToken): string
{
    $config = app_config()['meta'];
    $query = http_build_query([
        'grant_type' => 'fb_exchange_token',
        'client_id' => $config['app_id'] ?? '',
        'client_secret' => $config['app_secret'] ?? '',
        'fb_exchange_token' => $shortLivedToken,
    ]);

    $ch = curl_init('https://graph.facebook.com/' . meta_graph_version() . '/oauth/access_token?' . $query);
    if ($ch === false) {
        return $shortLivedToken;
    }

    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!is_string($response)) {
        return $shortLivedToken;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return $shortLivedToken;
    }

    return (string) ($decoded['access_token'] ?? $shortLivedToken);
}

function meta_subscribe_page_webhooks(string $pageId, string $pageAccessToken): bool
{
    $url = 'https://graph.facebook.com/' . meta_graph_version() . '/' . rawurlencode($pageId) . '/subscribed_apps';

    return meta_graph_post($url, $pageAccessToken, [
        'subscribed_fields' => 'messages,messaging_postbacks,message_echoes',
    ]);
}

function meta_app_access_token(): string
{
    $appId = (string) (app_config()['meta']['app_id'] ?? '');
    $secret = (string) (app_config()['meta']['app_secret'] ?? '');

    return $appId !== '' && $secret !== '' ? $appId . '|' . $secret : '';
}

/**
 * App-level webhook subscription (Meta App Dashboard → Webhooks).
 * Instagram DMs require object=instagram; Messenger uses object=page.
 */
function meta_subscribe_app_object_webhooks(string $object, string $fields): bool
{
    $appId = (string) (app_config()['meta']['app_id'] ?? '');
    $token = meta_app_access_token();
    $callback = rtrim((string) (app_config()['app_url'] ?? ''), '/') . '/webhooks/meta.php';
    $verify = (string) (app_config()['meta']['webhook_verify_token'] ?? '');

    if ($appId === '' || $token === '' || $verify === '' || $object === '' || $fields === '') {
        return false;
    }

    $url = 'https://graph.facebook.com/' . meta_graph_version() . '/' . rawurlencode($appId) . '/subscriptions';

    $ch = curl_init($url);
    if ($ch === false) {
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'object' => $object,
            'callback_url' => $callback,
            'verify_token' => $verify,
            'fields' => $fields,
            'access_token' => $token,
        ]),
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        error_log('[Meta] App webhook subscribe failed object=' . $object . ' HTTP ' . $status . ' body=' . (is_string($response) ? $response : ''));

        return false;
    }

    return true;
}

/** Ensure app-level Instagram + Page messaging webhooks are subscribed. */
function meta_ensure_messaging_app_webhooks(): bool
{
    $instagram = meta_subscribe_app_object_webhooks('instagram', 'messages');
    $page = meta_subscribe_app_object_webhooks('page', 'messages,messaging_postbacks,message_echoes');

    return $instagram && $page;
}

function meta_subscribe_waba_webhooks(string $wabaId, string $accessToken): bool
{
    $url = 'https://graph.facebook.com/' . meta_graph_version() . '/' . rawurlencode($wabaId) . '/subscribed_apps';

    return meta_graph_post($url, $accessToken, [
        'subscribed_fields' => 'messages',
    ]);
}

function meta_unsubscribe_app_webhooks(string $objectId, string $accessToken): bool
{
    if ($objectId === '' || $accessToken === '') {
        return false;
    }

    $url = 'https://graph.facebook.com/' . meta_graph_version() . '/' . rawurlencode($objectId) . '/subscribed_apps';

    $ch = curl_init($url . '?access_token=' . rawurlencode($accessToken));
    if ($ch === false) {
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        error_log('[Meta] Unsubscribe failed HTTP ' . $status . ' object=' . $objectId . ' body=' . (is_string($response) ? $response : ''));

        return false;
    }

    return true;
}

function meta_graph_post(string $url, string $accessToken, array $payload): bool
{
    $ch = curl_init($url . (str_contains($url, '?') ? '&' : '?') . 'access_token=' . rawurlencode($accessToken));
    if ($ch === false) {
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        $body = is_string($response) ? $response : '';
        error_log('[Meta] Send failed HTTP ' . $status . ' body=' . $body);
        meta_webhook_log('graph post failed HTTP ' . $status . ' body=' . mb_substr($body, 0, 240));

        return false;
    }

    if (str_contains($url, '/messages') && is_string($response) && $response !== '') {
        meta_webhook_log('send ok body=' . mb_substr($response, 0, 180));
    }

    return true;
}

/**
 * @param array<string, mixed> $payload
 * @return array<string, mixed>
 */
function meta_graph_post_json(string $path, string $accessToken, array $payload = []): array
{
    $version = meta_graph_version();
    $path = ltrim($path, '/');
    $url = 'https://graph.facebook.com/' . $version . '/' . $path;

    $ch = curl_init($url);
    if ($ch === false) {
        return ['error' => ['message' => 'Could not connect to Meta.']];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
        CURLOPT_TIMEOUT => 45,
    ]);

    $query = http_build_query(['access_token' => $accessToken]);
    curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($response)) {
        return ['error' => ['message' => 'Empty response from Meta.']];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['error' => ['message' => 'Invalid response from Meta.']];
    }

    if ($status < 200 || $status >= 300) {
        error_log('[Meta] POST ' . $path . ' failed HTTP ' . $status . ' body=' . $response);
    }

    return $decoded;
}

/**
 * POST to Graph API with query-string parameters (used for video reel/story finish steps).
 *
 * @param array<string, scalar|null> $params
 * @return array<string, mixed>
 */
function meta_graph_post_query(string $path, string $accessToken, array $params = []): array
{
    $version = meta_graph_version();
    $path = ltrim($path, '/');
    $params['access_token'] = $accessToken;
    $url = 'https://graph.facebook.com/' . $version . '/' . $path . '?' . http_build_query($params);

    $ch = curl_init($url);
    if ($ch === false) {
        return ['error' => ['message' => 'Could not connect to Meta.']];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($response)) {
        return ['error' => ['message' => 'Empty response from Meta.']];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['error' => ['message' => 'Invalid response from Meta.']];
    }

    if ($status < 200 || $status >= 300) {
        error_log('[Meta] POST ' . $path . ' failed HTTP ' . $status . ' body=' . $response);
    }

    return $decoded;
}

/**
 * Upload a hosted video to Meta via rupload (Facebook Reels / Stories).
 *
 * @return array<string, mixed>
 */
function meta_rupload_video_from_url(string $videoId, string $accessToken, string $fileUrl): array
{
    $version = meta_graph_version();
    $url = 'https://rupload.facebook.com/video-upload/' . $version . '/' . ltrim($videoId, '/');

    $ch = curl_init($url);
    if ($ch === false) {
        return ['error' => ['message' => 'Could not connect to Meta upload.']];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: OAuth ' . $accessToken,
            'file_url: ' . $fileUrl,
        ],
        CURLOPT_TIMEOUT => 120,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($response)) {
        return ['error' => ['message' => 'Empty response from Meta upload.']];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['error' => ['message' => 'Invalid response from Meta upload.']];
    }

    if ($status < 200 || $status >= 300) {
        error_log('[Meta] rupload ' . $videoId . ' failed HTTP ' . $status . ' body=' . $response);
    }

    return $decoded;
}

/**
 * Turn Meta Graph publish errors into plain steps for dashboard users.
 */
function meta_humanize_publish_error(string $message, string $platform = 'facebook'): string
{
    $lower = strtolower($message);

    if (
        str_contains($lower, 'pages_manage_posts')
        || str_contains($lower, 'pages_read_engagement')
        || str_contains($lower, 'instagram_content_publish')
        || str_contains($lower, 'instagram_manage_contents')
        || str_contains($message, '(#200)')
    ) {
        if ($platform === 'facebook') {
            return 'Your Facebook Page is missing publish or manage permission. Open Connected Accounts, disconnect Facebook, connect it again, and approve all permissions. You must be a Page admin with Full control on that Page.';
        }

        return 'This account is missing publish or manage permission. Disconnect and reconnect it in Connected Accounts, then approve all permissions.';
    }

    return $message;
}

/**
 * Turn Meta Graph insights errors into plain steps for dashboard users.
 */
function meta_humanize_insights_error(string $message, string $platform = 'instagram'): string
{
    $lower = strtolower($message);

    if (
        str_contains($message, '(#10)')
        || str_contains($lower, 'permission')
        || str_contains($lower, 'instagram_manage_insights')
        || str_contains($lower, 'read_insights')
    ) {
        $label = $platform === 'facebook' ? 'Facebook Page' : 'Instagram account';

        return 'Insights access is not enabled yet. Reconnect this ' . $label . ' in Connected Accounts and approve insights. If the error continues, add the insights permission in your Meta app settings and complete App Review.';
    }

    return meta_humanize_publish_error($message, $platform);
}

/**
 * @return array<string, mixed>
 */
function meta_graph_delete(string $path, string $accessToken): array
{
    $version = meta_graph_version();
    $path = ltrim($path, '/');
    $url = 'https://graph.facebook.com/' . $version . '/' . $path . '?' . http_build_query(['access_token' => $accessToken]);

    $ch = curl_init($url);
    if ($ch === false) {
        return ['error' => ['message' => 'Could not connect to Meta.']];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_TIMEOUT => 45,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($response) || $response === '') {
        return $status >= 200 && $status < 300 ? ['success' => true] : ['error' => ['message' => 'Delete failed.']];
    }

    $decoded = json_decode($response, true);

    return is_array($decoded) ? $decoded : ['success' => true];
}

/**
 * Meta user access token stored on connected_accounts.refresh_token (WhatsApp uses access_token).
 *
 * @param array<string, mixed> $account
 */
function meta_user_token_from_account(array $account): string
{
    $refresh = trim((string) ($account['refresh_token'] ?? ''));
    if ($refresh !== '') {
        return $refresh;
    }

    if ((string) ($account['platform'] ?? '') === 'whatsapp') {
        return trim((string) ($account['access_token'] ?? ''));
    }

    return '';
}

/**
 * Fully de-authorize Lead Bird for this Meta user so the next connect shows a fresh login flow.
 */
function meta_revoke_app_login(string $userAccessToken): bool
{
    if ($userAccessToken === '') {
        return false;
    }

    $appToken = meta_app_access_token();
    $userId = '';

    if ($appToken !== '') {
        $debug = meta_graph_get('debug_token', $appToken, [
            'input_token' => $userAccessToken,
            'fields' => 'user_id,is_valid',
        ]);
        $userId = trim((string) ($debug['data']['user_id'] ?? ''));
    }

    $result = meta_graph_delete('me/permissions', $userAccessToken);
    if (!isset($result['error'])) {
        return true;
    }

    error_log('[Meta] revoke via user token failed: ' . json_encode($result['error']));

    if ($appToken !== '' && $userId !== '') {
        $fallback = meta_graph_delete($userId . '/permissions', $appToken);
        if (!isset($fallback['error'])) {
            return true;
        }

        error_log('[Meta] revoke via app token failed: ' . json_encode($fallback['error']));
    }

    return false;
}

/**
 * @param array<string, mixed> $connectedAccount
 * @return array{name: ?string, phone: ?string, picture_url: ?string}
 */
function meta_fetch_customer_profile(array $connectedAccount, string $platform, string $senderId): array
{
    $accessToken = (string) ($connectedAccount['access_token'] ?? '');
    if ($accessToken === '' || $senderId === '') {
        return ['name' => null, 'phone' => null, 'picture_url' => null];
    }

    $fields = $platform === 'facebook'
        ? 'first_name,last_name,name,profile_pic'
        : 'username,name,profile_pic';

    $profile = meta_graph_get(rawurlencode($senderId), $accessToken, ['fields' => $fields]);
    if ($profile === []) {
        return ['name' => null, 'phone' => null, 'picture_url' => null];
    }

    $name = trim((string) ($profile['name'] ?? ''));
    if ($name === '') {
        $name = trim((string) (($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')));
    }
    if ($name === '') {
        $name = trim((string) ($profile['username'] ?? ''));
    }

    $pictureUrl = trim((string) ($profile['profile_pic'] ?? ''));
    if ($pictureUrl === '') {
        $picture = meta_graph_get(rawurlencode($senderId) . '/picture', $accessToken, [
            'redirect' => '0',
            'type' => 'large',
        ]);
        if (!isset($picture['error'])) {
            $pictureUrl = trim((string) ($picture['data']['url'] ?? ''));
        }
    }

    return [
        'name' => $name !== '' ? $name : null,
        'phone' => null,
        'picture_url' => $pictureUrl !== '' ? $pictureUrl : null,
    ];
}

/**
 * @return array{posts: list<array<string, mixed>>, error: ?string}
 */
function meta_fetch_instagram_media_result(string $instagramAccountId, string $accessToken, int $limit = 25): array
{
    if ($instagramAccountId === '' || $accessToken === '') {
        return ['posts' => [], 'error' => 'Instagram account is not connected properly.'];
    }

    $data = meta_graph_get(
        rawurlencode($instagramAccountId) . '/media',
        $accessToken,
        [
            'fields' => 'id,caption,media_type,media_product_type,media_url,thumbnail_url,permalink,timestamp',
            'limit' => max(1, min(50, $limit)),
        ]
    );

    if (isset($data['error']) && is_array($data['error'])) {
        $message = (string) ($data['error']['message'] ?? 'Could not load Instagram posts.');

        return ['posts' => [], 'error' => meta_humanize_publish_error($message, 'instagram')];
    }

    $posts = [];
    foreach ($data['data'] ?? [] as $item) {
        if (!is_array($item)) {
            continue;
        }

        $rawType = strtoupper((string) ($item['media_type'] ?? 'IMAGE'));
        $productType = strtoupper((string) ($item['media_product_type'] ?? ''));
        $isVideo = $rawType === 'VIDEO' || $rawType === 'REELS' || $productType === 'REELS';
        $mediaUrl = trim((string) ($item['media_url'] ?? ''));
        $thumbnailUrl = trim((string) ($item['thumbnail_url'] ?? ''));

        // Reels/videos: use thumbnail for feed cards. media_url is often an .mp4 that breaks <img>.
        $previewUrl = $isVideo
            ? ($thumbnailUrl !== '' ? $thumbnailUrl : $mediaUrl)
            : ($mediaUrl !== '' ? $mediaUrl : $thumbnailUrl);

        if ($previewUrl === '' && $rawType !== 'CAROUSEL_ALBUM') {
            continue;
        }

        $normalizedType = match (true) {
            $isVideo => 'video',
            $rawType === 'CAROUSEL_ALBUM' => 'carousel',
            default => 'image',
        };

        $posts[] = [
            'id' => (string) ($item['id'] ?? ''),
            'caption' => (string) ($item['caption'] ?? ''),
            'media_type' => $normalizedType,
            'media_product_type' => $productType !== '' ? strtolower($productType) : null,
            'image_url' => $previewUrl,
            'thumbnail_url' => $thumbnailUrl,
            'media_url_raw' => $mediaUrl,
            'permalink' => (string) ($item['permalink'] ?? ''),
            'timestamp' => (string) ($item['timestamp'] ?? ''),
        ];
    }

    return ['posts' => $posts, 'error' => null];
}

/**
 * @return list<array{id: string, caption: string, media_type: string, image_url: string, permalink: string, timestamp: string}>
 */
function meta_fetch_instagram_media(string $instagramAccountId, string $accessToken, int $limit = 25): array
{
    return meta_fetch_instagram_media_result($instagramAccountId, $accessToken, $limit)['posts'];
}

/**
 * @return array{posts: list<array<string, mixed>>, error: ?string}
 */
function meta_fetch_facebook_posts_result(string $pageId, string $accessToken, int $limit = 25): array
{
    if ($pageId === '' || $accessToken === '') {
        return ['posts' => [], 'error' => 'Facebook Page is not connected properly.'];
    }

    $data = meta_graph_get(
        rawurlencode($pageId) . '/posts',
        $accessToken,
        [
            'fields' => 'id,message,full_picture,permalink_url,created_time',
            'limit' => max(1, min(50, $limit)),
        ]
    );

    if (isset($data['error']) && is_array($data['error'])) {
        $message = (string) ($data['error']['message'] ?? 'Could not load Facebook posts.');

        return ['posts' => [], 'error' => meta_humanize_publish_error($message, 'facebook')];
    }

    $posts = [];
    foreach ($data['data'] ?? [] as $item) {
        if (!is_array($item)) {
            continue;
        }

        $message = trim((string) ($item['message'] ?? ''));
        $imageUrl = trim((string) ($item['full_picture'] ?? ''));
        if ($message === '' && $imageUrl === '') {
            continue;
        }

        $posts[] = [
            'id' => (string) ($item['id'] ?? ''),
            'message' => $message,
            'media_type' => $imageUrl !== '' ? 'IMAGE' : 'TEXT',
            'image_url' => $imageUrl,
            'permalink' => (string) ($item['permalink_url'] ?? ''),
            'timestamp' => (string) ($item['created_time'] ?? ''),
        ];
    }

    return ['posts' => $posts, 'error' => null];
}

/**
 * @return list<array{id: string, message: string, media_type: string, image_url: string, permalink: string, timestamp: string}>
 */
function meta_fetch_facebook_posts(string $pageId, string $accessToken, int $limit = 25): array
{
    return meta_fetch_facebook_posts_result($pageId, $accessToken, $limit)['posts'];
}

/**
 * @return list<array{
 *   platform: string,
 *   page_id: string,
 *   sender_id: string,
 *   message_text: string,
 *   message_media_url: ?string,
 *   message_media_type: ?string,
 *   referral_context: ?string
 * }>
 */
function meta_parse_inbound_events(array $payload): array
{
    $events = [];
    $object = (string) ($payload['object'] ?? '');

    foreach ($payload['entry'] ?? [] as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $pageId = (string) ($entry['id'] ?? '');

        foreach ($entry['messaging'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (isset($item['message']['is_echo']) && $item['message']['is_echo'] === true) {
                continue;
            }

            $message = is_array($item['message'] ?? null) ? $item['message'] : [];
            $text = trim((string) ($message['text'] ?? ''));
            $mediaUrl = null;
            $mediaType = null;

            foreach ($message['attachments'] ?? [] as $attachment) {
                if (!is_array($attachment)) {
                    continue;
                }

                $type = (string) ($attachment['type'] ?? '');
                $url = trim((string) ($attachment['payload']['url'] ?? ''));
                if ($type === 'image' && $url !== '') {
                    $mediaType = 'image';
                    $mediaUrl = $url;
                    break;
                }
            }

            if ($text === '' && $mediaUrl === null) {
                continue;
            }

            $senderId = (string) ($item['sender']['id'] ?? '');
            if ($senderId === '') {
                continue;
            }

            $referral = is_array($item['referral'] ?? null) ? $item['referral'] : null;
            if ($referral === null && is_array($message['referral'] ?? null)) {
                $referral = $message['referral'];
            }

            $platform = $object === 'instagram' ? 'instagram' : 'facebook';
            $events[] = [
                'platform' => $platform,
                'page_id' => $pageId,
                'sender_id' => $senderId,
                'message_text' => $text,
                'message_media_url' => $mediaUrl,
                'message_media_type' => $mediaType,
                'referral_context' => $referral !== null ? json_encode($referral, JSON_THROW_ON_ERROR) : null,
            ];
        }
    }

    return $events;
}

/**
 * Canonical Instagram account insights call Meta’s App Review api_precheck looks for.
 * Must use graph.facebook.com + Facebook User access token (not a Page token).
 *
 * @return array{ok: bool, error: ?string, metric: ?string}
 */
function meta_ping_instagram_account_insights(string $instagramAccountId, string $userAccessToken): array
{
    if ($instagramAccountId === '' || $userAccessToken === '') {
        return ['ok' => false, 'error' => 'Missing Instagram account or user token.', 'metric' => null];
    }

    // Meta’s review checklist keys off successful GET /{ig-user-id}/insights.
    // Try classic sample metrics first, then current v22+ metrics.
    $attempts = [
        ['metric' => 'impressions,reach,profile_views', 'period' => 'day'],
        ['metric' => 'reach,profile_views', 'period' => 'day'],
        ['metric' => 'reach', 'period' => 'day'],
        ['metric' => 'views,reach', 'period' => 'day', 'metric_type' => 'total_value'],
        ['metric' => 'reach', 'period' => 'day', 'metric_type' => 'total_value'],
        ['metric' => 'accounts_engaged', 'period' => 'day', 'metric_type' => 'total_value'],
        ['metric' => 'reach,follower_count', 'period' => 'day', 'metric_type' => 'total_value'],
    ];

    $lastError = null;
    foreach ($attempts as $query) {
        $data = meta_graph_get(
            rawurlencode($instagramAccountId) . '/insights',
            $userAccessToken,
            $query
        );

        if (isset($data['error']) && is_array($data['error'])) {
            $lastError = (string) ($data['error']['message'] ?? 'Account insights call failed.');
            continue;
        }

        error_log('[Meta] App Review insights precheck OK for IG ' . $instagramAccountId . ' metric=' . (string) ($query['metric'] ?? ''));

        return [
            'ok' => true,
            'error' => null,
            'metric' => (string) ($query['metric'] ?? ''),
        ];
    }

    error_log('[Meta] App Review insights precheck FAILED for IG ' . $instagramAccountId . ': ' . ($lastError ?? 'unknown'));

    return [
        'ok' => false,
        'error' => $lastError ?? 'Account insights call failed.',
        'metric' => null,
    ];
}

/**
 * @return array{metrics: list<array{name: string, label: string, value: int|float|string|null}>, error: ?string}
 */
function meta_fetch_instagram_media_insights(string $mediaId, string $accessToken, bool $isReel = false): array
{
    if ($mediaId === '' || $accessToken === '') {
        return ['metrics' => [], 'error' => 'Instagram post is not available for insights.'];
    }

    // Prefer common FEED metrics first. Reels add watch-time metrics when we know the media type.
    $metricSets = $isReel
        ? [
            'views,reach,likes,comments,saved,shares,total_interactions,ig_reels_avg_watch_time,ig_reels_video_view_total_time',
            'views,reach,likes,comments,saved,shares,total_interactions,ig_reels_avg_watch_time',
            'views,reach,total_interactions,ig_reels_avg_watch_time,ig_reels_video_view_total_time',
            'views,reach,likes,comments,saved,shares,total_interactions',
            'views,reach,total_interactions',
        ]
        : [
            'views,reach,likes,comments,saved,shares,total_interactions',
            'views,reach,total_interactions',
            'reach,saved,shares',
        ];

    $lastError = null;

    foreach ($metricSets as $metric) {
        $data = meta_graph_get(
            rawurlencode($mediaId) . '/insights',
            $accessToken,
            ['metric' => $metric]
        );

        if (isset($data['error']) && is_array($data['error'])) {
            $lastError = (string) ($data['error']['message'] ?? 'Could not load Instagram insights.');
            continue;
        }

        return [
            'metrics' => meta_normalize_insight_metrics($data['data'] ?? []),
            'error' => null,
        ];
    }

    return [
        'metrics' => [],
        'error' => meta_humanize_insights_error($lastError ?? 'Could not load Instagram insights.', 'instagram'),
    ];
}

/**
 * Follower vs non-follower views for a media object (Meta Audience tab).
 * Only returns post-level Graph data — never account-ratio estimates.
 *
 * @return array{follower: int|null, non_follower: int|null, available: bool, error: ?string, source: ?string}
 */
function meta_fetch_instagram_media_views_audience(string $mediaId, string $accessToken): array
{
    $empty = [
        'follower' => null,
        'non_follower' => null,
        'available' => false,
        'error' => null,
        'source' => null,
    ];
    if ($mediaId === '' || $accessToken === '') {
        return $empty;
    }

    // Only views (or total_views) — never reach/impressions, which are different metrics.
    $attempts = [
        ['metric' => 'views', 'period' => 'lifetime', 'breakdown' => 'follow_type'],
        ['metric' => 'views', 'period' => 'lifetime', 'breakdown' => 'follower_type'],
        ['metric' => 'views', 'breakdown' => 'follow_type'],
        ['metric' => 'views', 'breakdown' => 'follower_type'],
        ['metric' => 'views', 'breakdown' => 'follow_type', 'metric_type' => 'total_value'],
        ['metric' => 'views', 'breakdown' => 'follower_type', 'metric_type' => 'total_value'],
        ['metric' => 'views', 'period' => 'lifetime', 'breakdown' => 'follow_type', 'metric_type' => 'total_value'],
        ['metric' => 'views', 'period' => 'lifetime', 'breakdown' => 'follower_type', 'metric_type' => 'total_value'],
        ['metric' => 'total_views', 'period' => 'lifetime', 'breakdown' => 'follow_type'],
        ['metric' => 'total_views', 'breakdown' => 'follow_type', 'metric_type' => 'total_value'],
    ];

    $lastError = null;
    foreach ($attempts as $query) {
        $data = meta_graph_get(rawurlencode($mediaId) . '/insights', $accessToken, $query);
        if (isset($data['error']) && is_array($data['error'])) {
            $lastError = (string) ($data['error']['message'] ?? 'Audience insights unavailable.');
            continue;
        }

        $parsed = meta_parse_follow_type_breakdown($data['data'] ?? []);
        if ($parsed['available']) {
            $parsed['source'] = 'media_insights';
            $parsed['error'] = null;

            return $parsed;
        }
    }

    // Field-expansion form some Meta clients use for the same media edge.
    $fieldAttempts = [
        'insights.metric(views).period(lifetime).breakdown(follow_type)',
        'insights.metric(views).period(lifetime).breakdown(follower_type)',
        'insights.metric(views).breakdown(follow_type).metric_type(total_value)',
        'insights.metric(total_views).period(lifetime).breakdown(follow_type)',
    ];
    foreach ($fieldAttempts as $fields) {
        $data = meta_graph_get(rawurlencode($mediaId), $accessToken, ['fields' => $fields]);
        if (isset($data['error']) && is_array($data['error'])) {
            $lastError = (string) ($data['error']['message'] ?? 'Audience insights unavailable.');
            continue;
        }

        $insightRows = [];
        if (isset($data['insights']['data']) && is_array($data['insights']['data'])) {
            $insightRows = $data['insights']['data'];
        } elseif (isset($data['data']) && is_array($data['data'])) {
            $insightRows = $data['data'];
        }

        $parsed = meta_parse_follow_type_breakdown($insightRows);
        if ($parsed['available']) {
            $parsed['source'] = 'media_fields';
            $parsed['error'] = null;

            return $parsed;
        }
    }

    return [
        'follower' => null,
        'non_follower' => null,
        'available' => false,
        'error' => $lastError,
        'source' => null,
    ];
}

/**
 * @param list<mixed> $rows
 * @return array{follower: int|null, non_follower: int|null, available: bool, error: ?string, source: ?string}
 */
function meta_parse_follow_type_breakdown(array $rows): array
{
    $follower = null;
    $nonFollower = null;
    $unknown = 0;

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        // Newer total_value.breakdowns shape
        $totalValue = $row['total_value'] ?? null;
        if (is_array($totalValue) && isset($totalValue['breakdowns']) && is_array($totalValue['breakdowns'])) {
            foreach ($totalValue['breakdowns'] as $breakdown) {
                if (!is_array($breakdown) || !isset($breakdown['results']) || !is_array($breakdown['results'])) {
                    continue;
                }
                foreach ($breakdown['results'] as $result) {
                    if (!is_array($result)) {
                        continue;
                    }
                    $dims = $result['dimension_values'] ?? [];
                    $label = strtoupper((string) (is_array($dims) ? ($dims[0] ?? '') : $dims));
                    $value = isset($result['value']) && is_numeric($result['value']) ? (int) $result['value'] : null;
                    if ($value === null) {
                        continue;
                    }
                    if (str_contains($label, 'NON')) {
                        $nonFollower = $value;
                    } elseif (str_contains($label, 'UNKNOWN')) {
                        $unknown += $value;
                    } elseif (str_contains($label, 'FOLLOW')) {
                        $follower = $value;
                    }
                }
            }
        }

        // Legacy values[0].value as associative map
        $values = $row['values'] ?? null;
        if (is_array($values) && isset($values[0]) && is_array($values[0])) {
            $value = $values[0]['value'] ?? null;
            if (is_array($value)) {
                foreach ($value as $key => $amount) {
                    if (!is_numeric($amount)) {
                        continue;
                    }
                    $label = strtoupper((string) $key);
                    if (str_contains($label, 'NON')) {
                        $nonFollower = (int) $amount;
                    } elseif (str_contains($label, 'UNKNOWN')) {
                        $unknown += (int) $amount;
                    } elseif (str_contains($label, 'FOLLOW')) {
                        $follower = (int) $amount;
                    }
                }
            }
        }
    }

    // Meta sometimes parks residual views under UNKNOWN — keep them out of both sides,
    // but still treat the split as available when FOLLOWER/NON_FOLLOWER exist.
    $available = $follower !== null || $nonFollower !== null;

    return [
        'follower' => $follower ?? ($available ? 0 : null),
        'non_follower' => $nonFollower ?? ($available ? 0 : null),
        'available' => $available,
        'error' => null,
        'source' => null,
    ];
}

/**
 * @return array{ok: bool, post?: array<string, mixed>, error?: string}
 */
function meta_fetch_instagram_media_details(string $mediaId, string $accessToken): array
{
    if ($mediaId === '' || $accessToken === '') {
        return ['ok' => false, 'error' => 'Instagram post is not available.'];
    }

    $data = meta_graph_get(
        rawurlencode($mediaId),
        $accessToken,
        [
            'fields' => 'id,caption,media_type,media_product_type,media_url,thumbnail_url,permalink,timestamp,username',
        ]
    );

    if (isset($data['error']) && is_array($data['error'])) {
        return [
            'ok' => false,
            'error' => meta_humanize_insights_error((string) ($data['error']['message'] ?? 'Could not load this Instagram post.'), 'instagram'),
        ];
    }

    return [
        'ok' => true,
        'post' => meta_normalize_instagram_media_post($data, $mediaId),
    ];
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function meta_normalize_instagram_media_post(array $data, string $fallbackId = ''): array
{
    $rawType = strtoupper((string) ($data['media_type'] ?? 'IMAGE'));
    $productType = strtoupper((string) ($data['media_product_type'] ?? ''));
    $isVideo = $rawType === 'VIDEO' || $rawType === 'REELS' || $productType === 'REELS' || $productType === 'REEL';
    $isReel = $productType === 'REELS' || $productType === 'REEL' || $rawType === 'REELS'
        || ($isVideo && ($productType === '' || $productType === 'REELS'));
    $mediaUrl = trim((string) ($data['media_url'] ?? ''));
    $thumbnailUrl = trim((string) ($data['thumbnail_url'] ?? ''));

    // Thumbnails must be images. Never put a thumbnail into media_url — that breaks <video>.
    $previewUrl = $isVideo
        ? $thumbnailUrl
        : ($mediaUrl !== '' ? $mediaUrl : $thumbnailUrl);

    return [
        'id' => (string) ($data['id'] ?? $fallbackId),
        'caption' => (string) ($data['caption'] ?? ''),
        'media_type' => $isVideo ? 'video' : ($rawType === 'CAROUSEL_ALBUM' ? 'carousel' : 'image'),
        'media_product_type' => $productType !== '' ? strtolower($productType) : null,
        'is_reel' => $isReel,
        'media_url' => $mediaUrl,
        'thumbnail_url' => $previewUrl !== '' ? $previewUrl : $thumbnailUrl,
        'permalink' => (string) ($data['permalink'] ?? ''),
        'timestamp' => (string) ($data['timestamp'] ?? ''),
        'username' => (string) ($data['username'] ?? ''),
        'post_format' => $isReel ? 'Reel' : 'Post',
    ];
}

/**
 * Resolve Reel/video media_url using page token, then Facebook user token if needed.
 *
 * @return array{ok: bool, post?: array<string, mixed>, error?: string}
 */
function meta_fetch_instagram_media_details_for_playback(string $mediaId, string $pageToken, string $userToken = ''): array
{
    $primary = meta_fetch_instagram_media_details($mediaId, $pageToken);
    if (($primary['ok'] ?? false) !== true || !is_array($primary['post'] ?? null)) {
        if ($userToken !== '' && $userToken !== $pageToken) {
            return meta_fetch_instagram_media_details($mediaId, $userToken);
        }

        return $primary;
    }

    $post = $primary['post'];
    $needsVideo = !empty($post['is_reel']) || (string) ($post['media_type'] ?? '') === 'video';
    $hasVideoUrl = trim((string) ($post['media_url'] ?? '')) !== '';

    if (!$needsVideo || $hasVideoUrl || $userToken === '' || $userToken === $pageToken) {
        return $primary;
    }

    $alt = meta_fetch_instagram_media_details($mediaId, $userToken);
    if (($alt['ok'] ?? false) !== true || !is_array($alt['post'] ?? null)) {
        return $primary;
    }

    $altPost = $alt['post'];
    if (trim((string) ($altPost['media_url'] ?? '')) === '') {
        return $primary;
    }

    foreach (['caption', 'permalink', 'thumbnail_url', 'username', 'timestamp'] as $key) {
        if (trim((string) ($altPost[$key] ?? '')) === '' && trim((string) ($post[$key] ?? '')) !== '') {
            $altPost[$key] = $post[$key];
        }
    }

    return ['ok' => true, 'post' => $altPost];
}

/**
 * Account-level age × gender demographics from Meta (Graph API does not expose
 * age/gender on a single media object). Prefer reached audience, then followers.
 *
 * @return array{
 *   available: bool,
 *   source: ?string,
 *   women_pct: float|null,
 *   men_pct: float|null,
 *   ages: list<array{age: string, women: float, men: float}>,
 *   error: ?string
 * }
 */
function meta_fetch_instagram_audience_demographics(string $instagramAccountId, string $accessToken): array
{
    $empty = [
        'available' => false,
        'source' => null,
        'women_pct' => null,
        'men_pct' => null,
        'ages' => [],
        'error' => null,
    ];

    if ($instagramAccountId === '' || $accessToken === '') {
        return $empty;
    }

    $attempts = [
        [
            'metric' => 'reached_audience_demographics',
            'period' => 'lifetime',
            'timeframe' => 'this_month',
            'breakdown' => 'age,gender',
            'metric_type' => 'total_value',
        ],
        [
            'metric' => 'engaged_audience_demographics',
            'period' => 'lifetime',
            'timeframe' => 'this_month',
            'breakdown' => 'age,gender',
            'metric_type' => 'total_value',
        ],
        [
            'metric' => 'follower_demographics',
            'period' => 'lifetime',
            'timeframe' => 'this_month',
            'breakdown' => 'age,gender',
            'metric_type' => 'total_value',
        ],
    ];

    $lastError = null;
    foreach ($attempts as $query) {
        $data = meta_graph_get(
            rawurlencode($instagramAccountId) . '/insights',
            $accessToken,
            $query
        );

        if (isset($data['error']) && is_array($data['error'])) {
            $lastError = (string) ($data['error']['message'] ?? 'Audience demographics unavailable.');
            continue;
        }

        $parsed = meta_parse_age_gender_demographics($data['data'] ?? []);
        if ($parsed['available']) {
            $parsed['source'] = (string) ($query['metric'] ?? 'demographics');
            $parsed['error'] = null;

            return $parsed;
        }
    }

    $empty['error'] = $lastError;

    return $empty;
}

/**
 * @param list<mixed> $rows
 * @return array{
 *   available: bool,
 *   source: ?string,
 *   women_pct: float|null,
 *   men_pct: float|null,
 *   ages: list<array{age: string, women: float, men: float}>,
 *   error: ?string
 * }
 */
function meta_parse_age_gender_demographics(array $rows): array
{
    $ageOrder = ['13-17', '18-24', '25-34', '35-44', '45-54', '55-64', '65+'];
    $buckets = [];
    foreach ($ageOrder as $age) {
        $buckets[$age] = ['women' => 0.0, 'men' => 0.0];
    }

    $womenTotal = 0.0;
    $menTotal = 0.0;

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $totalValue = $row['total_value'] ?? null;
        if (!is_array($totalValue) || !isset($totalValue['breakdowns']) || !is_array($totalValue['breakdowns'])) {
            continue;
        }

        foreach ($totalValue['breakdowns'] as $breakdown) {
            if (!is_array($breakdown) || !isset($breakdown['results']) || !is_array($breakdown['results'])) {
                continue;
            }

            $keys = $breakdown['dimension_keys'] ?? [];
            if (!is_array($keys)) {
                continue;
            }

            $ageIdx = array_search('age', $keys, true);
            $genderIdx = array_search('gender', $keys, true);
            if ($ageIdx === false || $genderIdx === false) {
                continue;
            }

            foreach ($breakdown['results'] as $result) {
                if (!is_array($result)) {
                    continue;
                }
                $dims = $result['dimension_values'] ?? [];
                if (!is_array($dims)) {
                    continue;
                }

                $age = (string) ($dims[$ageIdx] ?? '');
                $gender = strtoupper((string) ($dims[$genderIdx] ?? ''));
                $value = isset($result['value']) && is_numeric($result['value']) ? (float) $result['value'] : 0.0;
                if ($age === '' || $value <= 0) {
                    continue;
                }

                if (!isset($buckets[$age])) {
                    $buckets[$age] = ['women' => 0.0, 'men' => 0.0];
                }

                if ($gender === 'F' || $gender === 'FEMALE') {
                    $buckets[$age]['women'] += $value;
                    $womenTotal += $value;
                } elseif ($gender === 'M' || $gender === 'MALE') {
                    $buckets[$age]['men'] += $value;
                    $menTotal += $value;
                }
            }
        }
    }

    $grand = $womenTotal + $menTotal;
    if ($grand <= 0) {
        return [
            'available' => false,
            'source' => null,
            'women_pct' => null,
            'men_pct' => null,
            'ages' => [],
            'error' => null,
        ];
    }

    $ages = [];
    foreach ($buckets as $age => $vals) {
        $ages[] = [
            'age' => $age,
            'women' => round(($vals['women'] / $grand) * 100, 1),
            'men' => round(($vals['men'] / $grand) * 100, 1),
        ];
    }

    return [
        'available' => true,
        'source' => null,
        'women_pct' => round(($womenTotal / $grand) * 100, 1),
        'men_pct' => round(($menTotal / $grand) * 100, 1),
        'ages' => $ages,
        'error' => null,
    ];
}

/**
 * @return array{ok: bool, post?: array<string, mixed>, error?: string}
 */
function meta_fetch_facebook_post_details(string $postId, string $accessToken): array
{
    if ($postId === '' || $accessToken === '') {
        return ['ok' => false, 'error' => 'Facebook post is not available.'];
    }

    $data = meta_graph_get(
        rawurlencode($postId),
        $accessToken,
        [
            'fields' => 'id,message,full_picture,permalink_url,created_time,attachments{media_type,media,subattachments}',
        ]
    );

    if (isset($data['error']) && is_array($data['error'])) {
        return [
            'ok' => false,
            'error' => meta_humanize_insights_error((string) ($data['error']['message'] ?? 'Could not load this Facebook post.'), 'facebook'),
        ];
    }

    $mediaUrl = (string) ($data['full_picture'] ?? '');
    if ($mediaUrl === '' && isset($data['attachments']['data'][0]) && is_array($data['attachments']['data'][0])) {
        $att = $data['attachments']['data'][0];
        $mediaUrl = (string) ($att['media']['image']['src'] ?? '');
    }

    return [
        'ok' => true,
        'post' => [
            'id' => (string) ($data['id'] ?? $postId),
            'caption' => (string) ($data['message'] ?? ''),
            'media_type' => 'image',
            'media_url' => $mediaUrl,
            'permalink' => (string) ($data['permalink_url'] ?? ''),
            'timestamp' => (string) ($data['created_time'] ?? ''),
            'username' => '',
            'post_format' => 'Post',
        ],
    ];
}

/**
 * @return array{metrics: list<array{name: string, label: string, value: int|float|string|null}>, error: ?string}
 */
function meta_fetch_facebook_post_insights(string $postId, string $accessToken): array
{
    if ($postId === '' || $accessToken === '') {
        return ['metrics' => [], 'error' => 'Facebook post is not available for insights.'];
    }

    $data = meta_graph_get(
        rawurlencode($postId) . '/insights',
        $accessToken,
        [
            'metric' => 'post_impressions,post_impressions_unique,post_impressions_fan,post_engaged_users,post_clicks',
        ]
    );

    if (isset($data['error']) && is_array($data['error'])) {
        $message = (string) ($data['error']['message'] ?? 'Could not load Facebook insights.');

        return ['metrics' => [], 'error' => meta_humanize_insights_error($message, 'facebook')];
    }

    return [
        'metrics' => meta_normalize_insight_metrics($data['data'] ?? []),
        'error' => null,
    ];
}

/**
 * @param list<mixed> $rows
 * @return list<array{name: string, label: string, value: int|float|string|null}>
 */
function meta_normalize_insight_metrics(array $rows): array
{
    $labels = [
        'impressions' => 'Impressions',
        'views' => 'Views',
        'reach' => 'Reach',
        'likes' => 'Likes',
        'comments' => 'Comments',
        'saved' => 'Saves',
        'shares' => 'Shares',
        'total_interactions' => 'Interactions',
        'follows' => 'Follows',
        'total_likes' => 'Total likes',
        'total_comments' => 'Total comments',
        'total_views' => 'Total views',
        'engagement' => 'Engagement',
        'ig_reels_avg_watch_time' => 'Average watch time',
        'ig_reels_video_view_total_time' => 'Watch time',
        'post_impressions' => 'Impressions',
        'post_impressions_unique' => 'Reach',
        'post_impressions_fan' => 'Views from followers',
        'post_engaged_users' => 'Engaged users',
        'post_clicks' => 'Clicks',
    ];

    $metrics = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $name = (string) ($row['name'] ?? '');
        if ($name === '') {
            continue;
        }

        $value = null;
        $values = $row['values'] ?? null;
        if (is_array($values) && isset($values[0]) && is_array($values[0])) {
            $value = $values[0]['value'] ?? null;
        }

        // Newer Graph responses (incl. Reel watch time) use total_value.value.
        if ($value === null && isset($row['total_value']) && is_array($row['total_value'])) {
            $value = $row['total_value']['value'] ?? null;
        }

        $metrics[] = [
            'name' => $name,
            'label' => $labels[$name] ?? ucwords(str_replace('_', ' ', $name)),
            'value' => is_scalar($value) || $value === null ? $value : null,
        ];
    }

    return $metrics;
}
