<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/openai-client.php';

/**
 * WhatsApp broadcasts sent from the seller's own number with Meta-approved templates.
 *
 * Meta bills the seller's WhatsApp Business Account for each template message.
 * Lead Bird charges tokens per recipient for sending and tracking.
 */
final class WhatsAppBroadcastService
{
    public const FOOTER = 'Reply STOP to stop these messages';
    public const BATCH_SIZE = 50;
    public const DEFAULT_DAILY_LIMIT = 250;

    /** @var array<string, int|null> */
    private const CONTACT_LIMITS = [
        'vendor' => 5000,
        'shop' => 50000,
        'enterprise' => null,
    ];

    /** Meta's USD rate per delivered message to Nigerian numbers (rate card effective 1 October 2026). */
    public const META_RATES_NG = [
        'MARKETING' => 0.0516,
        'UTILITY' => 0.0067,
    ];

    /** Tokens to keep back for Hannah's replies, per person messaged. */
    public const REPLY_RESERVE_PER_RECIPIENT = 0.25;
    public const REPLY_RESERVE_MIN = 50;

    private const STOP_WORDS = ['STOP', 'UNSUBSCRIBE', 'STOP PROMOTIONS', 'OPT OUT', 'OPTOUT'];

    private BroadcastRepository $repo;

    public function __construct()
    {
        $this->repo = RepositoryFactory::broadcasts();
    }

    public static function contactLimit(string $planSlug): ?int
    {
        return array_key_exists($planSlug, self::CONTACT_LIMITS) ? self::CONTACT_LIMITS[$planSlug] : 0;
    }

    // —— Account readiness ——

    public function whatsappAccount(string $businessId): ?array
    {
        return RepositoryFactory::connectedAccounts()->findActiveByPlatform($businessId, 'whatsapp');
    }

    /**
     * @param array<string, mixed> $account
     */
    public function resolveWabaId(array $account): ?string
    {
        $stored = trim((string) ($account['waba_id'] ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $token = (string) ($account['access_token'] ?? '');
        $phoneId = (string) ($account['platform_account_id'] ?? '');
        $appToken = meta_app_access_token();
        if ($token === '' || $phoneId === '' || $appToken === '') {
            return null;
        }

        $debug = meta_graph_get('debug_token', $appToken, ['input_token' => $token, 'fields' => 'granular_scopes']);
        $candidates = [];
        foreach ($debug['data']['granular_scopes'] ?? [] as $scope) {
            if (!is_array($scope) || !str_starts_with((string) ($scope['scope'] ?? ''), 'whatsapp_')) {
                continue;
            }
            foreach ($scope['target_ids'] ?? [] as $targetId) {
                $candidates[(string) $targetId] = true;
            }
        }

        foreach (array_keys($candidates) as $wabaId) {
            $phones = meta_graph_get($wabaId . '/phone_numbers', $token, ['fields' => 'id', 'limit' => 50]);
            foreach ($phones['data'] ?? [] as $phone) {
                if ((string) ($phone['id'] ?? '') === $phoneId) {
                    RepositoryFactory::connectedAccounts()->setWabaId((string) $account['id'], (string) $wabaId);

                    return (string) $wabaId;
                }
            }
        }

        return null;
    }

    /**
     * @return array{
     *   connected: bool,
     *   account: ?array<string, mixed>,
     *   waba_id: ?string,
     *   token_ok: bool,
     *   payment: string,
     *   daily_limit: ?int,
     *   quality: ?string,
     *   used_last_24h: int
     * }
     */
    public function accountStatus(string $businessId, bool $fresh = false): array
    {
        $account = $this->whatsappAccount($businessId);
        if ($account === null) {
            return [
                'connected' => false,
                'account' => null,
                'waba_id' => null,
                'token_ok' => false,
                'payment' => 'unknown',
                'daily_limit' => null,
                'quality' => null,
                'used_last_24h' => 0,
            ];
        }

        $cacheKey = 'lb_wa_status_' . $account['id'];
        if (!$fresh && session_status() === PHP_SESSION_ACTIVE) {
            $cached = $_SESSION[$cacheKey] ?? null;
            if (is_array($cached) && (int) ($cached['at'] ?? 0) > time() - 300) {
                $status = $cached['status'];
                $status['account'] = $account;
                $status['used_last_24h'] = $this->repo->uniqueRecipientsLast24h((string) $account['id']);

                return $status;
            }
        }

        $token = (string) ($account['access_token'] ?? '');
        $phoneId = (string) ($account['platform_account_id'] ?? '');
        $phone = meta_graph_get($phoneId, $token, ['fields' => 'quality_rating,messaging_limit_tier']);
        $tokenOk = !isset($phone['error']) || (int) ($phone['error']['code'] ?? 0) !== 190;
        if (isset($phone['error'])) {
            $phone = meta_graph_get($phoneId, $token, ['fields' => 'quality_rating']);
        }

        $wabaId = $tokenOk ? $this->resolveWabaId($account) : null;
        $payment = 'unknown';
        if ($wabaId !== null) {
            $waba = meta_graph_get($wabaId, $token, ['fields' => 'primary_funding_id']);
            if (!isset($waba['error'])) {
                $payment = trim((string) ($waba['primary_funding_id'] ?? '')) !== '' ? 'ok' : 'missing';
            }
        }

        $status = [
            'connected' => true,
            'account' => $account,
            'waba_id' => $wabaId,
            'token_ok' => $tokenOk,
            'payment' => $payment,
            'daily_limit' => self::parseLimitTier((string) ($phone['messaging_limit_tier'] ?? '')),
            'quality' => isset($phone['quality_rating']) ? strtoupper((string) $phone['quality_rating']) : null,
            'used_last_24h' => $this->repo->uniqueRecipientsLast24h((string) $account['id']),
        ];

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[$cacheKey] = ['at' => time(), 'status' => array_diff_key($status, ['account' => true])];
        }

        return $status;
    }

    /**
     * Every active WhatsApp number on the business, with live details from Meta.
     *
     * @return list<array{
     *   account_id: string,
     *   phone: string,
     *   display_name: string,
     *   status: string,
     *   quality: ?string,
     *   daily_limit: ?int,
     *   throughput: ?int,
     *   waba_id: ?string,
     *   waba_name: ?string,
     *   business_manager_id: ?string,
     *   business_manager_name: ?string
     * }>
     */
    public function senders(string $businessId, bool $fresh = false): array
    {
        $cacheKey = 'lb_wa_senders_' . $businessId;
        if (!$fresh && session_status() === PHP_SESSION_ACTIVE) {
            $cached = $_SESSION[$cacheKey] ?? null;
            if (is_array($cached) && (int) ($cached['at'] ?? 0) > time() - 300) {
                return $cached['senders'];
            }
        }

        $wabaCache = [];
        $senders = [];
        foreach (RepositoryFactory::connectedAccounts()->listActiveByBusinessAndPlatform($businessId, 'whatsapp') as $account) {
            $token = (string) ($account['access_token'] ?? '');
            $phoneId = (string) ($account['platform_account_id'] ?? '');
            $phone = meta_graph_get($phoneId, $token, [
                'fields' => 'display_phone_number,verified_name,status,quality_rating,messaging_limit_tier,throughput',
            ]);
            if (isset($phone['error'])) {
                $phone = ['error' => $phone['error']] + meta_graph_get($phoneId, $token, [
                    'fields' => 'display_phone_number,verified_name,status,quality_rating',
                ]);
            }
            $tokenExpired = (int) ($phone['error']['code'] ?? 0) === 190;

            $wabaId = $tokenExpired ? (trim((string) ($account['waba_id'] ?? '')) ?: null) : $this->resolveWabaId($account);
            $waba = null;
            if ($wabaId !== null && !$tokenExpired) {
                $waba = $wabaCache[$wabaId] ??= meta_graph_get($wabaId, $token, ['fields' => 'name,owner_business_info']);
            }

            $metaStatus = strtoupper((string) ($phone['status'] ?? ''));
            $status = match (true) {
                $tokenExpired => 'expired',
                $metaStatus === 'CONNECTED' => 'online',
                in_array($metaStatus, ['FLAGGED', 'RESTRICTED', 'BANNED', 'RATE_LIMITED'], true) => 'restricted',
                $metaStatus === 'PENDING' => 'pending',
                $metaStatus === '' => 'unknown',
                default => 'offline',
            };

            $storedLabel = array_map('trim', explode('·', (string) ($account['display_name'] ?? ''), 2));
            $throughput = strtoupper((string) ($phone['throughput']['level'] ?? ''));
            $senders[] = [
                'account_id' => (string) $account['id'],
                'phone' => trim((string) ($phone['display_phone_number'] ?? '')) ?: ($storedLabel[1] ?? $storedLabel[0]),
                'display_name' => trim((string) ($phone['verified_name'] ?? '')) ?: $storedLabel[0],
                'status' => $status,
                'quality' => isset($phone['quality_rating']) ? strtoupper((string) $phone['quality_rating']) : null,
                'daily_limit' => isset($phone['messaging_limit_tier']) ? self::parseLimitTier((string) $phone['messaging_limit_tier']) : null,
                'throughput' => match ($throughput) {
                    'STANDARD' => 80,
                    'HIGH' => 1000,
                    default => null,
                },
                'waba_id' => $wabaId,
                'waba_name' => is_array($waba) && !isset($waba['error']) ? (trim((string) ($waba['name'] ?? '')) ?: null) : null,
                'business_manager_id' => is_array($waba) ? (trim((string) ($waba['owner_business_info']['id'] ?? '')) ?: null) : null,
                'business_manager_name' => is_array($waba) ? (trim((string) ($waba['owner_business_info']['name'] ?? '')) ?: null) : null,
            ];
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[$cacheKey] = ['at' => time(), 'senders' => $senders];
        }

        return $senders;
    }

    public const PROFILE_VERTICALS = [
        'APPAREL' => 'Clothing and apparel',
        'AUTO' => 'Automotive',
        'BEAUTY' => 'Beauty, spa and salon',
        'EDU' => 'Education',
        'ENTERTAIN' => 'Entertainment',
        'EVENT_PLAN' => 'Event planning and services',
        'FINANCE' => 'Finance and banking',
        'GROCERY' => 'Food and grocery',
        'GOVT' => 'Public service',
        'HOTEL' => 'Hotel and lodging',
        'HEALTH' => 'Medical and health',
        'NONPROFIT' => 'Non-profit',
        'PROF_SERVICES' => 'Professional services',
        'RESTAURANT' => 'Restaurant',
        'RETAIL' => 'Shopping and retail',
        'TRAVEL' => 'Travel and transportation',
        'OTHER' => 'Other',
    ];

    public const PROFILE_ABOUT_MAX = 139;
    public const PROFILE_DESCRIPTION_MAX = 512;
    public const PROFILE_PHOTO_MAX_BYTES = 5242880;

    /**
     * Active WhatsApp connection owned by the business, or null.
     *
     * @return array<string, mixed>|null
     */
    public function senderAccount(string $businessId, string $accountId): ?array
    {
        $account = $accountId !== '' ? RepositoryFactory::connectedAccounts()->findById($accountId) : null;
        if (
            $account === null
            || (string) ($account['business_id'] ?? '') !== $businessId
            || (string) ($account['platform'] ?? '') !== 'whatsapp'
            || (int) ($account['is_active'] ?? 0) !== 1
        ) {
            return null;
        }

        return $account;
    }

    /**
     * @param array<string, mixed> $account
     * @return array{ok: bool, profile: array<string, mixed>, error?: string}
     */
    public function senderProfile(array $account): array
    {
        $result = meta_graph_get((string) $account['platform_account_id'] . '/whatsapp_business_profile', (string) $account['access_token'], [
            'fields' => 'about,address,description,email,profile_picture_url,websites,vertical',
        ]);
        if (isset($result['error'])) {
            return ['ok' => false, 'profile' => [], 'error' => self::profileErrorMessage($result['error'])];
        }

        $profile = is_array($result['data'][0] ?? null) ? $result['data'][0] : [];

        return ['ok' => true, 'profile' => $profile];
    }

    /**
     * @param array<string, mixed> $account
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $photo Entry from $_FILES
     * @return array{ok: bool, error?: string}
     */
    public function updateSenderProfile(array $account, array $input, ?array $photo): array
    {
        $about = trim((string) ($input['about'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $address = trim((string) ($input['address'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $vertical = strtoupper(trim((string) ($input['vertical'] ?? '')));
        $websites = [];
        foreach ([(string) ($input['website_1'] ?? ''), (string) ($input['website_2'] ?? '')] as $website) {
            $website = trim($website);
            if ($website === '') {
                continue;
            }
            if (!preg_match('#^https?://#i', $website)) {
                $website = 'https://' . $website;
            }
            if (filter_var($website, FILTER_VALIDATE_URL) === false || mb_strlen($website) > 256) {
                return ['ok' => false, 'error' => 'Check the website address: ' . $website];
            }
            $websites[] = $website;
        }

        if ($about === '') {
            return ['ok' => false, 'error' => 'Add a short "About" line. WhatsApp requires it.'];
        }
        if (mb_strlen($about) > self::PROFILE_ABOUT_MAX) {
            return ['ok' => false, 'error' => '"About" can be up to ' . self::PROFILE_ABOUT_MAX . ' characters.'];
        }
        if (mb_strlen($description) > self::PROFILE_DESCRIPTION_MAX) {
            return ['ok' => false, 'error' => 'The description can be up to ' . self::PROFILE_DESCRIPTION_MAX . ' characters.'];
        }
        if (mb_strlen($address) > 256) {
            return ['ok' => false, 'error' => 'The address can be up to 256 characters.'];
        }
        if ($email !== '' && (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 128)) {
            return ['ok' => false, 'error' => 'Enter a valid email address.'];
        }
        if ($vertical !== '' && !array_key_exists($vertical, self::PROFILE_VERTICALS)) {
            return ['ok' => false, 'error' => 'Pick a business category from the list.'];
        }

        $payload = array_filter([
            'messaging_product' => 'whatsapp',
            'about' => $about,
            'description' => $description,
            'address' => $address,
            'email' => $email,
            'websites' => $websites,
            'vertical' => $vertical,
        ], static fn ($value): bool => $value !== '' && $value !== []);

        $token = (string) $account['access_token'];
        if ($photo !== null && (int) ($photo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = $this->uploadProfilePhoto($photo, $token);
            if (!$upload['ok']) {
                return ['ok' => false, 'error' => $upload['error']];
            }
            $payload['profile_picture_handle'] = $upload['handle'];
        }

        $result = meta_graph_post_json((string) $account['platform_account_id'] . '/whatsapp_business_profile', $token, $payload);
        if (isset($result['error']) || empty($result['success'])) {
            return ['ok' => false, 'error' => self::profileErrorMessage(is_array($result['error'] ?? null) ? $result['error'] : [])];
        }

        return ['ok' => true];
    }

    /**
     * Meta resumable upload: start a session on the app, send the bytes, get a file handle.
     *
     * @param array<string, mixed> $photo
     * @return array{ok: bool, handle?: string, error?: string}
     */
    private function uploadProfilePhoto(array $photo, string $token): array
    {
        if ((int) ($photo['error'] ?? 0) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($photo['tmp_name'] ?? ''))) {
            return ['ok' => false, 'error' => 'The photo did not upload. Please try again.'];
        }
        $path = (string) $photo['tmp_name'];
        $size = (int) filesize($path);
        if ($size <= 0 || $size > self::PROFILE_PHOTO_MAX_BYTES) {
            return ['ok' => false, 'error' => 'The photo must be under 5 MB.'];
        }
        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return ['ok' => false, 'error' => 'Use a JPG or PNG photo.'];
        }
        $dimensions = @getimagesize($path);
        if (!is_array($dimensions) || $dimensions[0] < 192 || $dimensions[1] < 192) {
            return ['ok' => false, 'error' => 'The photo is too small. Use at least 640 × 640 pixels.'];
        }

        $appId = (string) (app_config()['meta']['app_id'] ?? '');
        if ($appId === '') {
            return ['ok' => false, 'error' => 'Photo upload is not available right now.'];
        }

        $session = meta_graph_post_query($appId . '/uploads', $token, [
            'file_length' => $size,
            'file_type' => $mime,
        ]);
        $sessionId = (string) ($session['id'] ?? '');
        if ($sessionId === '') {
            return ['ok' => false, 'error' => self::profileErrorMessage(is_array($session['error'] ?? null) ? $session['error'] : [])];
        }

        $ch = curl_init('https://graph.facebook.com/' . meta_graph_version() . '/' . $sessionId);
        if ($ch === false) {
            return ['ok' => false, 'error' => 'Could not reach Meta.'];
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Authorization: OAuth ' . $token, 'file_offset: 0'],
            CURLOPT_POSTFIELDS => (string) file_get_contents($path),
            CURLOPT_TIMEOUT => 60,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $decoded = is_string($response) ? json_decode($response, true) : null;
        $handle = is_array($decoded) ? (string) ($decoded['h'] ?? '') : '';
        if ($handle === '') {
            error_log('[WhatsApp] profile photo upload failed: ' . (is_string($response) ? $response : 'no response'));

            return ['ok' => false, 'error' => self::profileErrorMessage(is_array($decoded['error'] ?? null) ? $decoded['error'] : [])];
        }

        return ['ok' => true, 'handle' => $handle];
    }

    /**
     * @param array<string, mixed> $error
     */
    private static function profileErrorMessage(array $error): string
    {
        if ((int) ($error['code'] ?? 0) === 190) {
            return 'Your WhatsApp connection has expired. Reconnect it and try again.';
        }
        $message = trim((string) ($error['error_user_msg'] ?? $error['message'] ?? ''));

        return $message !== '' ? 'Meta said: ' . $message : 'Meta did not accept the change. Please try again.';
    }

    /**
     * TIER_250 → 250, TIER_1K → 1000, TIER_UNLIMITED → null. Unknown tiers fall back to the lowest common limit.
     */
    public static function parseLimitTier(string $tier): ?int
    {
        $tier = strtoupper(trim($tier));
        if ($tier === '') {
            return self::DEFAULT_DAILY_LIMIT;
        }
        if (str_contains($tier, 'UNLIMITED')) {
            return null;
        }
        if (preg_match('/(\d+)\s*([KM]?)/', $tier, $m) !== 1) {
            return self::DEFAULT_DAILY_LIMIT;
        }
        $value = (int) $m[1];

        return match ($m[2]) {
            'K' => $value * 1000,
            'M' => $value * 1000000,
            default => $value,
        };
    }

    // —— Templates ——

    /**
     * @return array{ok: bool, template?: array<string, mixed>, error?: string}
     */
    public function createTemplate(string $businessId, string $title, string $category, string $body): array
    {
        $title = trim($title);
        $body = trim(str_replace("\r\n", "\n", $body));
        $category = strtoupper(trim($category));

        if ($title === '' || mb_strlen($title) > 80) {
            return ['ok' => false, 'error' => 'Give the template a short name (up to 80 characters).'];
        }
        if (!in_array($category, ['MARKETING', 'UTILITY'], true)) {
            return ['ok' => false, 'error' => 'Choose what kind of message this is.'];
        }
        if ($body === '') {
            return ['ok' => false, 'error' => 'Write the message you want to send.'];
        }
        if (mb_strlen($body) > 900) {
            return ['ok' => false, 'error' => 'Keep the message under 900 characters.'];
        }
        if (preg_match('/\{\{(?!name\}\})[^}]*\}\}/i', $body) === 1) {
            return ['ok' => false, 'error' => 'The only placeholder you can use is {{name}}.'];
        }

        $usesName = preg_match('/\{\{name\}\}/i', $body) === 1;
        if (preg_match_all('/\{\{name\}\}/i', $body) > 1) {
            return ['ok' => false, 'error' => 'Use {{name}} only once in the message.'];
        }
        if ($usesName && (preg_match('/^\{\{name\}\}/i', $body) === 1 || preg_match('/\{\{name\}\}[\s.!?,]*$/i', $body) === 1)) {
            return ['ok' => false, 'error' => 'Meta does not allow {{name}} at the very start or end. Try "Hi {{name}}, …".'];
        }

        $account = $this->whatsappAccount($businessId);
        if ($account === null) {
            return ['ok' => false, 'error' => 'Connect your WhatsApp Business number first.'];
        }
        $wabaId = $this->resolveWabaId($account);
        if ($wabaId === null) {
            return ['ok' => false, 'error' => 'We could not reach your WhatsApp Business account. Reconnect WhatsApp in Social Accounts and try again.'];
        }

        $metaBody = preg_replace('/\{\{name\}\}/i', '{{1}}', $body) ?? $body;
        $bodyComponent = ['type' => 'BODY', 'text' => $metaBody];
        if ($usesName) {
            $bodyComponent['example'] = ['body_text' => [['Ada']]];
        }

        $name = self::templateSlug($title);
        $response = meta_graph_post_json($wabaId . '/message_templates', (string) $account['access_token'], [
            'name' => $name,
            'language' => 'en',
            'category' => $category,
            'components' => [
                $bodyComponent,
                ['type' => 'FOOTER', 'text' => self::FOOTER],
            ],
        ]);

        if (isset($response['error']) || trim((string) ($response['id'] ?? '')) === '') {
            return ['ok' => false, 'error' => self::metaErrorMessage($response, 'Meta did not accept this template.')];
        }

        $template = $this->repo->createTemplate([
            'business_id' => $businessId,
            'connected_account_id' => (string) $account['id'],
            'waba_id' => $wabaId,
            'meta_template_id' => (string) $response['id'],
            'name' => $name,
            'title' => $title,
            'category' => strtoupper((string) ($response['category'] ?? $category)),
            'body' => $body,
            'footer' => self::FOOTER,
            'uses_name' => $usesName,
            'status' => strtoupper((string) ($response['status'] ?? 'PENDING')),
        ]);

        return ['ok' => true, 'template' => $template];
    }

    public const TEMPLATE_LANGUAGES = [
        'en' => 'English',
        'en_US' => 'English (US)',
        'en_GB' => 'English (UK)',
        'af' => 'Afrikaans',
        'ar' => 'Arabic',
        'ar_EG' => 'Arabic (Egypt)',
        'ar_MA' => 'Arabic (Morocco)',
        'fr' => 'French',
        'fr_CI' => 'French (Côte d’Ivoire)',
        'fr_MA' => 'French (Morocco)',
        'ha' => 'Hausa',
        'rw_RW' => 'Kinyarwanda',
        'pt_PT' => 'Portuguese',
        'sw' => 'Swahili',
        'zu' => 'Zulu',
    ];

    private const VARIABLE_PATTERN = '/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/';

    public const EDITABLE_TYPES = ['text', 'quick_reply', 'call_to_action', 'media', 'list_picker', 'card', 'whatsapp_card', 'carousel', 'catalog', 'authentication', 'flow'];

    /** Content types WhatsApp only allows as replies within 24 hours, so they are never sent for approval. */
    public const REPLY_ONLY_TYPES = ['list_picker'];

    /** WhatsApp's copy of these can't be turned back into the editor, so the editor fields are kept with the template. */
    private const SNAPSHOT_TYPES = ['card', 'whatsapp_card', 'carousel', 'catalog', 'authentication', 'flow'];

    /** @var array<string, string> */
    public const FIXED_CATEGORIES = ['carousel' => 'MARKETING', 'catalog' => 'MARKETING', 'authentication' => 'AUTHENTICATION'];

    /** @var array<string, int> */
    public const BUTTON_LIMITS = ['quick_reply' => 3, 'call_to_action' => 3, 'card' => 3, 'whatsapp_card' => 10];

    /** @var array<string, array{label: string, max: int, choices: bool}> */
    public const FLOW_QUESTION_TYPES = [
        'text' => ['label' => 'Short answer', 'max' => 20, 'choices' => false],
        'long_text' => ['label' => 'Long answer', 'max' => 20, 'choices' => false],
        'email' => ['label' => 'Email', 'max' => 20, 'choices' => false],
        'phone' => ['label' => 'Phone number', 'max' => 20, 'choices' => false],
        'number' => ['label' => 'Number', 'max' => 20, 'choices' => false],
        'date' => ['label' => 'Date', 'max' => 40, 'choices' => false],
        'single' => ['label' => 'Single choice', 'max' => 30, 'choices' => true],
        'multi' => ['label' => 'Multiple choice', 'max' => 30, 'choices' => true],
        'dropdown' => ['label' => 'Dropdown', 'max' => 20, 'choices' => true],
    ];

    public const AUTH_BODY = '{{1}} is your verification code.';
    public const AUTH_SECURITY = 'For your security, do not share this code.';

    /** @var array<string, array{format: string, ext: string, max: int}> */
    public const TEMPLATE_MEDIA_TYPES = [
        'image/jpeg' => ['format' => 'IMAGE', 'ext' => 'jpg', 'max' => 5_242_880],
        'image/png' => ['format' => 'IMAGE', 'ext' => 'png', 'max' => 5_242_880],
        'video/mp4' => ['format' => 'VIDEO', 'ext' => 'mp4', 'max' => 16_777_216],
        'application/pdf' => ['format' => 'DOCUMENT', 'ext' => 'pdf', 'max' => 16_777_216],
    ];

    /**
     * Our own template ID, shown to sellers. It stays the same when a ready-made template is submitted under the same name.
     */
    public static function templateRef(string $businessId, string $name, string $language): string
    {
        return 'LB' . substr(hash('sha256', $businessId . '|' . $language . '|' . $name), 0, 32);
    }

    /**
     * Turn a stored template into the editor's fields. Templates with parts the editor
     * can't show (carousels, or a variable anywhere but the end of a link) come back read-only.
     *
     * @param array<string, mixed> $template
     * @return array<string, mixed>
     */
    public static function editorSpec(array $template): array
    {
        $components = json_decode((string) ($template['components'] ?? ''), true);
        $spec = [
            'name' => (string) $template['name'],
            'language' => (string) $template['language'],
            'category' => strtoupper((string) $template['category']),
            'content_type' => (string) ($template['content_type'] ?? 'text'),
            'header' => '',
            'header_format' => '',
            'media_url' => (string) ($template['header_media_url'] ?? ''),
            'body' => (string) ($template['body'] ?? ''),
            'footer' => (string) ($template['footer'] ?? ''),
            'samples' => [],
            'buttons' => [],
            'editable' => is_array($components),
        ] + self::editorFields([]);
        $draft = json_decode((string) ($template['draft_spec'] ?? ''), true);
        $snapshot = is_array($draft) && in_array($spec['content_type'], self::SNAPSHOT_TYPES, true);
        if (strtoupper((string) ($template['status'] ?? '')) === 'DRAFT' || $snapshot) {
            $draft = is_array($draft) ? $draft : [];
            $spec = array_merge($spec, self::editorFields($draft));
            $spec['header'] = (string) ($draft['header'] ?? '');
            $spec['body'] = (string) ($draft['body'] ?? $spec['body']);
            $spec['samples'] = is_array($draft['samples'] ?? null) ? $draft['samples'] : [];
            $spec['list_button'] = (string) ($draft['list_button'] ?? '');
            $spec['list_items'] = is_array($draft['list_items'] ?? null) ? $draft['list_items'] : [];
            $spec['editable'] = in_array($spec['content_type'], self::EDITABLE_TYPES, true);

            return $spec;
        }
        if (!is_array($components)) {
            return $spec;
        }

        foreach ($components as $component) {
            $type = strtoupper((string) ($component['type'] ?? ''));
            $example = is_array($component['example'] ?? null) ? $component['example'] : [];
            if ($type === 'HEADER') {
                $format = strtoupper((string) ($component['format'] ?? ''));
                if (in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
                    $spec['header_format'] = $format;
                    if ($spec['media_url'] === '') {
                        $spec['media_url'] = (string) ($example['header_handle'][0] ?? '');
                    }
                    continue;
                }
                if ($format !== 'TEXT') {
                    $spec['editable'] = false;
                    continue;
                }
                $spec['header'] = (string) ($component['text'] ?? '');
                foreach ($example['header_text_named_params'] ?? [] as $param) {
                    $spec['samples'][(string) ($param['param_name'] ?? '')] = (string) ($param['example'] ?? '');
                }
                foreach (array_values($example['header_text'] ?? []) as $i => $value) {
                    $spec['samples'][(string) ($i + 1)] = (string) $value;
                }
            } elseif ($type === 'BODY') {
                $spec['body'] = (string) ($component['text'] ?? '');
                foreach ($example['body_text_named_params'] ?? [] as $param) {
                    $spec['samples'][(string) ($param['param_name'] ?? '')] = (string) ($param['example'] ?? '');
                }
                foreach (array_values($example['body_text'][0] ?? []) as $i => $value) {
                    $spec['samples'][(string) ($i + 1)] = (string) $value;
                }
            } elseif ($type === 'FOOTER') {
                $spec['footer'] = (string) ($component['text'] ?? '');
            } elseif ($type === 'BUTTONS') {
                foreach ($component['buttons'] ?? [] as $button) {
                    $buttonType = strtoupper((string) ($button['type'] ?? ''));
                    if ($buttonType === 'QUICK_REPLY') {
                        $spec['buttons'][] = ['type' => 'QUICK_REPLY', 'text' => (string) ($button['text'] ?? '')];
                    } elseif ($buttonType === 'URL' && preg_match('/\{\{(?!1\}\}$)/', (string) ($button['url'] ?? '')) !== 1) {
                        $spec['buttons'][] = ['type' => 'URL', 'text' => (string) ($button['text'] ?? ''), 'url' => (string) ($button['url'] ?? '')];
                    } elseif ($buttonType === 'PHONE_NUMBER') {
                        $spec['buttons'][] = ['type' => 'PHONE_NUMBER', 'text' => (string) ($button['text'] ?? ''), 'phone' => (string) ($button['phone_number'] ?? '')];
                    } else {
                        $spec['editable'] = false;
                    }
                }
            } else {
                $spec['editable'] = false;
            }
        }

        if (!in_array($spec['content_type'], self::EDITABLE_TYPES, true) || in_array($spec['content_type'], self::SNAPSHOT_TYPES, true)) {
            $spec['editable'] = false;
        }
        if (($spec['header_format'] !== '') !== ($spec['content_type'] === 'media')) {
            $spec['editable'] = false;
        }

        return $spec;
    }

    /**
     * Send a new template, or changes to an existing one, to WhatsApp for approval.
     * A dry run checks everything and returns what would be sent, without contacting WhatsApp.
     *
     * @param array<string, mixed> $input
     * @return array{ok: bool, id?: string, payload?: array<string, mixed>, error?: string}
     */
    public function submitTemplate(string $businessId, array $input, ?string $templateId = null, bool $dryRun = false): array
    {
        $existing = null;
        $draft = null;
        if ($templateId !== null && $templateId !== '') {
            $existing = $this->repo->findTemplate($templateId, $businessId);
            if ($existing === null || $existing['deleted_at'] !== null) {
                return ['ok' => false, 'error' => 'This template no longer exists. Go back to Templates and refresh.'];
            }
            if (strtoupper((string) $existing['status']) === 'DRAFT') {
                $draft = $existing;
                $existing = null;
            } elseif (!in_array(strtoupper((string) $existing['status']), ['APPROVED', 'REJECTED', 'PAUSED'], true)) {
                return ['ok' => false, 'error' => 'WhatsApp is still reviewing this template. You can edit it once they have decided.'];
            } elseif (trim((string) ($existing['meta_template_id'] ?? '')) === '') {
                return ['ok' => false, 'error' => 'We could not find this template on WhatsApp. Refresh Templates and try again.'];
            }
        }

        $str = static fn(string $key): string => trim(str_replace("\r\n", "\n", (string) ($input[$key] ?? '')));
        $kept = $existing ?? $draft;
        $name = $kept !== null ? (string) $kept['name'] : $str('name');
        $language = $kept !== null ? (string) $kept['language'] : $str('language');
        $contentType = $str('content_type');
        $category = self::FIXED_CATEGORIES[$contentType] ?? strtoupper($str('category'));
        $extras = self::editorFields($input);
        $samples = is_array($input['samples'] ?? null) ? $input['samples'] : [];

        if ($existing === null && preg_match('/^[a-z0-9_]{1,512}$/', $name) !== 1) {
            return ['ok' => false, 'error' => 'Use only small letters, numbers and underscores in the template name, like summer_sale.'];
        }
        if (!isset(self::TEMPLATE_LANGUAGES[$language]) && $existing === null) {
            return ['ok' => false, 'error' => 'Choose a language.'];
        }
        if (!in_array($contentType, self::EDITABLE_TYPES, true)) {
            return ['ok' => false, 'error' => 'Choose a content type.'];
        }
        if (in_array($contentType, self::REPLY_ONLY_TYPES, true)) {
            return ['ok' => false, 'error' => 'WhatsApp doesn’t review list pickers. Save it, then send it as a reply within 24 hours of a customer’s message.'];
        }
        if (!isset(self::FIXED_CATEGORIES[$contentType]) && !in_array($category, ['MARKETING', 'UTILITY'], true)) {
            return ['ok' => false, 'error' => 'Choose a category.'];
        }
        if ($existing !== null && strtoupper((string) $existing['status']) === 'APPROVED' && $category !== strtoupper((string) $existing['category'])) {
            return ['ok' => false, 'error' => 'WhatsApp does not let you change the category of an approved template. Duplicate it and submit the copy instead.'];
        }

        $content = $contentType === 'authentication'
            ? self::authContent($extras['auth'])
            : self::templateContent($contentType, $input, $extras, $samples);
        if (isset($content['error'])) {
            return ['ok' => false, 'error' => $content['error']];
        }

        $account = null;
        $wabaId = null;
        if (!$dryRun) {
            foreach (RepositoryFactory::connectedAccounts()->listActiveByBusinessAndPlatform($businessId, 'whatsapp') as $candidate) {
                $candidateWaba = $this->resolveWabaId($candidate);
                if ($candidateWaba !== null && ($existing === null || $candidateWaba === (string) $existing['waba_id'])) {
                    $account = $candidate;
                    $wabaId = $candidateWaba;
                    break;
                }
            }
            if ($account === null || $wabaId === null) {
                return ['ok' => false, 'error' => 'Connect your WhatsApp number in Senders first.'];
            }
        }
        $token = (string) ($account['access_token'] ?? '');

        $handles = [];
        $cardFormat = null;
        foreach ($content['media'] as $item) {
            $media = $this->templateMediaFile($item['url']);
            $where = $item['card'] > 0 ? 'Card ' . $item['card'] . ': ' : '';
            if (isset($media['error'])) {
                return ['ok' => false, 'error' => $where . $media['error']];
            }
            if ($item['card'] > 0) {
                if ($media['format'] === 'DOCUMENT') {
                    return ['ok' => false, 'error' => $where . 'Carousel cards can only show a photo or a video.'];
                }
                $cardFormat ??= $media['format'];
                if ($media['format'] !== $cardFormat) {
                    return ['ok' => false, 'error' => 'Use photos on every card or videos on every card. WhatsApp doesn’t allow a mix.'];
                }
            }
            $handle = $dryRun ? 'FILE_HANDLE_FROM_WHATSAPP_UPLOAD' : $this->uploadToMeta($token, $media['bytes'], $media['mime'], $media['name']);
            if ($handle === null) {
                return ['ok' => false, 'error' => $where . 'WhatsApp did not accept the file. Try a different photo, video or PDF.'];
            }
            $handles[] = ['format' => $media['format'], 'handle' => $handle];
        }
        $components = ($content['build'])($handles);

        $payload = ['category' => $category, 'components' => $components];
        if ($content['parameter_format'] !== null) {
            $payload['parameter_format'] = $content['parameter_format'];
        }

        if ($dryRun) {
            return ['ok' => true, 'payload' => ['name' => $name, 'language' => $language] + $payload];
        }

        if ($existing !== null) {
            $response = meta_graph_post_json((string) $existing['meta_template_id'], $token, $payload);
            if (isset($response['error']) || empty($response['success'])) {
                return ['ok' => false, 'error' => self::metaErrorMessage($response, 'WhatsApp did not accept these changes.')];
            }
            $metaId = (string) $existing['meta_template_id'];
            $status = 'PENDING';
        } else {
            $response = meta_graph_post_json($wabaId . '/message_templates', $token, ['name' => $name, 'language' => $language] + $payload);
            if (isset($response['error']) || trim((string) ($response['id'] ?? '')) === '') {
                if ((int) ($response['error']['error_subcode'] ?? 0) === 2388024) {
                    return ['ok' => false, 'error' => 'You already have a template called ' . $name . ' in this language. Change the name and submit again.'];
                }

                return ['ok' => false, 'error' => self::metaErrorMessage($response, 'WhatsApp did not accept this template.')];
            }
            $metaId = (string) $response['id'];
            $status = strtoupper((string) ($response['status'] ?? 'PENDING'));
            $category = strtoupper((string) ($response['category'] ?? $category));
        }

        $id = $this->repo->saveSubmittedTemplate([
            'business_id' => $businessId,
            'connected_account_id' => (string) $account['id'],
            'waba_id' => $wabaId,
            'meta_template_id' => $metaId,
            'name' => $name,
            'language' => $language,
            'category' => $category,
            'content_type' => $contentType,
            'body' => $content['body'],
            'footer' => $content['footer'] !== '' ? $content['footer'] : null,
            'components' => json_encode($components, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'header_media_url' => $content['media_url'] !== '' ? $content['media_url'] : null,
            'draft_spec' => in_array($contentType, self::SNAPSHOT_TYPES, true)
                ? json_encode(['header' => $content['header'], 'body' => $content['editor_body'] ?? $content['body'], 'samples' => self::cleanSamples($samples)] + $extras, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
            'status' => $status,
        ]);
        if ($draft !== null && (string) $draft['id'] !== $id) {
            $this->repo->softDeleteTemplate((string) $draft['id'], $businessId);
        }

        return ['ok' => true, 'id' => $id];
    }

    /**
     * Check every content type except authentication, and describe what to send to WhatsApp.
     * Media is only listed here; it is downloaded and uploaded once all the checks pass.
     *
     * @param array<string, mixed> $input
     * @param array<string, mixed> $extras
     * @param array<string, mixed> $samples
     * @return array<string, mixed>
     */
    private static function templateContent(string $contentType, array $input, array $extras, array $samples): array
    {
        $str = static fn(string $key): string => trim(str_replace("\r\n", "\n", (string) ($input[$key] ?? '')));
        $fail = static fn(string $message): array => ['error' => $message];
        $headerType = match ($contentType) {
            'media' => 'media',
            'card' => $str('media_url') !== '' ? 'media' : 'none',
            'whatsapp_card' => $extras['header_type'],
            'catalog' => $extras['catalog_format'] === 'mpm' ? 'text' : 'none',
            'carousel', 'flow' => 'none',
            default => $str('header') !== '' ? 'text' : 'none',
        };
        $header = $headerType === 'text' ? $str('header') : '';
        $body = $str('body');
        $footer = in_array($contentType, ['carousel', 'flow'], true) ? '' : $str('footer');
        $bodyName = $contentType === 'card' ? 'title' : 'message';
        $footerName = $contentType === 'card' ? 'subtitle' : 'footer';
        $mediaUrl = $headerType === 'media' ? $str('media_url') : '';
        $mediaVar = null;

        if ($headerType === 'media') {
            if ($mediaUrl === '') {
                return $fail($contentType === 'whatsapp_card'
                    ? 'Add the media URL for the header, or choose a different header type.'
                    : 'Add the media URL of the photo, video or PDF that goes with this message.');
            }
            if (mb_strlen($mediaUrl) > 1600) {
                return $fail('Keep the media URL to 1,600 characters or fewer.');
            }
            if (preg_match('/^\{\{\s*([A-Za-z0-9_]+)\s*\}\}$/', $mediaUrl, $mediaMatch) === 1) {
                $mediaVar = $mediaMatch[1];
            } elseif (str_contains($mediaUrl, '{{') || str_contains($mediaUrl, '}}')) {
                return $fail('Use the full link to the photo, video or PDF.');
            }
        }
        if ($headerType === 'text' && $header === '') {
            return $fail($contentType === 'catalog'
                ? 'Add the header text shown above your products.'
                : 'Write the header text, or choose a different header type.');
        }
        if (mb_strlen($header) > 60) {
            return $fail('Keep the header to 60 characters or fewer.');
        }
        if ($body === '') {
            return $fail('Write the ' . $bodyName . '.');
        }
        if (mb_strlen($body) > 1024) {
            return $fail('Keep the ' . $bodyName . ' to 1,024 characters or fewer.');
        }
        if (mb_strlen($footer) > 60) {
            return $fail('Keep the ' . $footerName . ' to 60 characters or fewer.');
        }
        if (str_contains($footer, '{{')) {
            return $fail('The ' . $footerName . ' can’t have variables.');
        }

        foreach (['header' => $header, $bodyName => $body] as $label => $text) {
            $stripped = preg_replace(self::VARIABLE_PATTERN, '', $text) ?? $text;
            if (str_contains($stripped, '{{') || str_contains($stripped, '}}')) {
                return $fail('Write variables in the ' . $label . ' like {{first_name}}, using small letters and underscores.');
            }
        }
        if (preg_match('/^\{\{/', $body) === 1 || preg_match('/\}\}$/', $body) === 1) {
            return $fail('WhatsApp rejects messages that start or end with a variable. Add some words before or after it.');
        }
        if (preg_match('/\}\}\s*\{\{/', $body) === 1) {
            return $fail('Put some words between variables that sit next to each other.');
        }

        preg_match_all(self::VARIABLE_PATTERN, $header, $headerMatches);
        preg_match_all(self::VARIABLE_PATTERN, $body, $bodyMatches);
        $headerVars = $headerMatches[1];
        $bodyVars = array_values(array_unique($bodyMatches[1]));
        if (count($headerVars) > 1) {
            return $fail('The header can have only one variable.');
        }

        $allVars = array_merge($headerVars, $bodyVars);
        $positional = $allVars !== [] && count(array_filter($allVars, static fn(string $v): bool => ctype_digit($v))) === count($allVars);
        if (!$positional) {
            foreach ($allVars as $var) {
                if (preg_match('/^[a-z][a-z0-9_]*$/', $var) !== 1) {
                    return $fail('Name variables with small letters and underscores, like {{first_name}}.');
                }
            }
        }

        $sample = static function (string $var) use ($samples): string {
            return trim((string) ($samples[$var] ?? ''));
        };
        foreach ($allVars as $var) {
            if ($sample($var) === '') {
                return $fail('Add a sample for {{' . $var . '}} so WhatsApp can see how the message will look.');
            }
        }

        $metaHeader = $header;
        $metaBody = $body;
        if ($positional) {
            $ordered = $bodyVars;
            sort($ordered, SORT_NUMERIC);
            $numbers = array_flip($ordered);
            $metaHeader = preg_replace(self::VARIABLE_PATTERN, '{{1}}', $header) ?? $header;
            $metaBody = preg_replace_callback(self::VARIABLE_PATTERN, static fn(array $m): string => '{{' . ($numbers[$m[1]] + 1) . '}}', $body) ?? $body;
        }

        if ($mediaVar !== null) {
            if (in_array($mediaVar, $allVars, true)) {
                return $fail('{{' . $mediaVar . '}} is already used in the message. Use a different variable for the media URL.');
            }
            if ($sample($mediaVar) === '') {
                return $fail('Add a link to an example photo, video or PDF for {{' . $mediaVar . '}} so WhatsApp can review the template.');
            }
        }

        $media = [];
        if ($headerType === 'media') {
            $media[] = ['url' => $mediaVar !== null ? $sample($mediaVar) : $mediaUrl, 'card' => 0];
        }

        $buttons = [];
        $cards = [];
        if (isset(self::BUTTON_LIMITS[$contentType])) {
            $allowed = match ($contentType) {
                'quick_reply' => ['QUICK_REPLY'],
                'call_to_action' => ['URL', 'PHONE_NUMBER'],
                'card' => ['QUICK_REPLY', 'URL', 'PHONE_NUMBER'],
                default => ['QUICK_REPLY', 'URL', 'PHONE_NUMBER', 'COPY_CODE'],
            };
            $seenText = [];
            $counts = ['QUICK_REPLY' => 0, 'URL' => 0, 'PHONE_NUMBER' => 0, 'COPY_CODE' => 0];
            foreach ($extras['buttons'] as $button) {
                if (!in_array($button['type'], $allowed, true)) {
                    continue;
                }
                $parsed = self::templateButton($button, $allVars, $positional, $samples, true, $seenText);
                if (is_string($parsed)) {
                    return $fail($parsed);
                }
                $counts[$button['type']]++;
                $buttons[] = $parsed;
            }
            $limit = self::BUTTON_LIMITS[$contentType];
            if ($contentType === 'quick_reply' && ($buttons === [] || count($buttons) > $limit)) {
                return $fail('Add between 1 and 3 quick reply buttons.');
            }
            if ($contentType === 'call_to_action' && ($buttons === [] || $counts['URL'] > 2 || $counts['PHONE_NUMBER'] > 1)) {
                return $fail('Add up to two website buttons and one phone button.');
            }
            if ($buttons === []) {
                return $fail('Add at least one button.');
            }
            if (count($buttons) > $limit) {
                return $fail('Add up to ' . $limit . ' buttons.');
            }
            if ($counts['URL'] > 2) {
                return $fail('You can add up to two website buttons.');
            }
            if ($counts['PHONE_NUMBER'] > 1) {
                return $fail('You can add one phone button.');
            }
            if ($counts['COPY_CODE'] > 1) {
                return $fail('You can add one copy code button.');
            }
            $buttons = self::groupQuickReplies($buttons);
        } elseif ($contentType === 'catalog') {
            $buttons = [$extras['catalog_format'] === 'mpm'
                ? ['type' => 'MPM', 'text' => 'View items']
                : ['type' => 'CATALOG', 'text' => 'View catalog']];
        } elseif ($contentType === 'flow') {
            $flowButton = self::flowButton($extras['flow']);
            if (is_string($flowButton)) {
                return $fail($flowButton);
            }
            $buttons = [$flowButton];
        } elseif ($contentType === 'carousel') {
            $cardsIn = $extras['cards'];
            if (count($cardsIn) < 2 || count($cardsIn) > 10) {
                return $fail('Add between 2 and 10 cards.');
            }
            $withText = count(array_filter($cardsIn, static fn(array $card): bool => $card['body'] !== ''));
            if ($withText !== 0 && $withText !== count($cardsIn)) {
                return $fail('Give every card some text, or leave the text off all of them. WhatsApp needs the cards to match.');
            }
            $layout = null;
            foreach ($cardsIn as $i => $card) {
                $n = $i + 1;
                if ($card['media_url'] === '') {
                    return $fail('Add the photo or video link for card ' . $n . '.');
                }
                if (str_contains($card['media_url'], '{{') || str_contains($card['media_url'], '}}')) {
                    return $fail('Use the full link to the photo or video for card ' . $n . '.');
                }
                if (str_contains($card['body'], '{{') || str_contains($card['body'], '}}')) {
                    return $fail('Card text can’t have variables. Take the variable out of card ' . $n . '.');
                }
                $cardButtons = array_values(array_filter($card['buttons'], static fn(array $b): bool => $b['type'] !== 'COPY_CODE'));
                $types = array_column($cardButtons, 'type');
                if ($types === []) {
                    return $fail('Add at least one button to card ' . $n . '.');
                }
                $layout ??= $types;
                if ($types !== $layout) {
                    return $fail('Every card needs the same buttons in the same order. Check card ' . $n . '.');
                }
                $seenText = [];
                $parsedButtons = [];
                foreach ($cardButtons as $button) {
                    $parsed = self::templateButton($button, [], true, [], false, $seenText);
                    if (is_string($parsed)) {
                        return $fail('Card ' . $n . ': ' . $parsed);
                    }
                    $parsedButtons[] = $parsed;
                }
                $cards[] = ['body' => $card['body'], 'buttons' => $parsedButtons, 'media' => count($media)];
                $media[] = ['url' => $card['media_url'], 'card' => $n];
            }
        }

        $build = static function (array $handles) use ($headerType, $metaHeader, $headerVars, $metaBody, $bodyVars, $footer, $positional, $sample, $cards, $buttons): array {
            $components = [];
            if ($headerType === 'media') {
                $components[] = ['type' => 'HEADER', 'format' => $handles[0]['format'], 'example' => ['header_handle' => [$handles[0]['handle']]]];
            }
            if ($metaHeader !== '') {
                $headerComponent = ['type' => 'HEADER', 'format' => 'TEXT', 'text' => $metaHeader];
                if ($headerVars !== []) {
                    $headerComponent['example'] = $positional
                        ? ['header_text' => [$sample($headerVars[0])]]
                        : ['header_text_named_params' => [['param_name' => $headerVars[0], 'example' => $sample($headerVars[0])]]];
                }
                $components[] = $headerComponent;
            }
            $bodyComponent = ['type' => 'BODY', 'text' => $metaBody];
            if ($bodyVars !== []) {
                if ($positional) {
                    $ordered = $bodyVars;
                    sort($ordered, SORT_NUMERIC);
                    $bodyComponent['example'] = ['body_text' => [array_map($sample, $ordered)]];
                } else {
                    $bodyComponent['example'] = ['body_text_named_params' => array_map(
                        static fn(string $var): array => ['param_name' => $var, 'example' => $sample($var)],
                        $bodyVars
                    )];
                }
            }
            $components[] = $bodyComponent;
            if ($footer !== '') {
                $components[] = ['type' => 'FOOTER', 'text' => $footer];
            }
            if ($cards !== []) {
                $components[] = ['type' => 'CAROUSEL', 'cards' => array_map(static function (array $card) use ($handles): array {
                    $parts = [['type' => 'HEADER', 'format' => $handles[$card['media']]['format'], 'example' => ['header_handle' => [$handles[$card['media']]['handle']]]]];
                    if ($card['body'] !== '') {
                        $parts[] = ['type' => 'BODY', 'text' => $card['body']];
                    }
                    $parts[] = ['type' => 'BUTTONS', 'buttons' => $card['buttons']];

                    return ['components' => $parts];
                }, $cards)];
            }
            if ($buttons !== []) {
                $components[] = ['type' => 'BUTTONS', 'buttons' => $buttons];
            }

            return $components;
        };

        return [
            'media' => $media,
            'build' => $build,
            'parameter_format' => $allVars === [] ? null : ($positional ? 'POSITIONAL' : 'NAMED'),
            'header' => $header,
            'editor_body' => $body,
            'body' => $metaBody,
            'footer' => $footer,
            'media_url' => $mediaUrl,
        ];
    }

    /**
     * WhatsApp writes the words of a login code message itself; the seller only picks the extras.
     *
     * @param array{security: bool, expires: bool, expiry: int, button: string} $auth
     * @return array<string, mixed>
     */
    private static function authContent(array $auth): array
    {
        $text = $auth['button'] !== '' ? $auth['button'] : 'Copy code';
        if ($auth['expires'] && ($auth['expiry'] < 1 || $auth['expiry'] > 90)) {
            return ['error' => 'Set the code to expire between 1 and 90 minutes.'];
        }

        $components = [['type' => 'BODY', 'add_security_recommendation' => $auth['security']]];
        if ($auth['expires']) {
            $components[] = ['type' => 'FOOTER', 'code_expiration_minutes' => $auth['expiry']];
        }
        $components[] = ['type' => 'BUTTONS', 'buttons' => [['type' => 'OTP', 'otp_type' => 'COPY_CODE', 'text' => $text]]];

        return [
            'media' => [],
            'build' => static fn(array $handles): array => $components,
            'parameter_format' => null,
            'header' => '',
            'body' => self::authBody($auth),
            'footer' => self::authFooter($auth),
            'media_url' => '',
        ];
    }

    /**
     * @param array{security: bool, expires: bool, expiry: int, button: string} $auth
     */
    public static function authBody(array $auth): string
    {
        return self::AUTH_BODY . ($auth['security'] ? ' ' . self::AUTH_SECURITY : '');
    }

    /**
     * @param array{security: bool, expires: bool, expiry: int, button: string} $auth
     */
    public static function authFooter(array $auth): string
    {
        return $auth['expires'] ? 'This code expires in ' . $auth['expiry'] . ' ' . ($auth['expiry'] === 1 ? 'minute' : 'minutes') . '.' : '';
    }

    /**
     * @param array{type: string, text: string, url: string, phone: string, code: string} $button
     * @param list<string> $allVars
     * @param array<string, mixed> $samples
     * @param array<string, bool> $seenText
     * @return array<string, mixed>|string the button as WhatsApp expects it, or what to fix
     */
    private static function templateButton(array $button, array $allVars, bool $positional, array $samples, bool $urlVariables, array &$seenText): array|string
    {
        if ($button['type'] === 'COPY_CODE') {
            if (preg_match('/^[A-Za-z0-9]{1,15}$/', $button['code']) !== 1) {
                return 'Add a sample offer code for the copy code button, using up to 15 letters and numbers, like SAVE20.';
            }

            return ['type' => 'COPY_CODE', 'example' => $button['code']];
        }

        $text = $button['text'];
        if ($text === '') {
            return 'Give every button some text.';
        }
        if (mb_strlen($text) > 25) {
            return 'Keep button text to 25 characters or fewer.';
        }
        if (isset($seenText[mb_strtolower($text)])) {
            return 'Each button needs different text.';
        }
        $seenText[mb_strtolower($text)] = true;

        if ($button['type'] === 'QUICK_REPLY') {
            return ['type' => 'QUICK_REPLY', 'text' => $text];
        }
        if ($button['type'] === 'PHONE_NUMBER') {
            $digits = preg_replace('/\D+/', '', $button['phone']) ?? '';
            if (strlen($digits) < 8 || strlen($digits) > 15) {
                return 'Add the phone number for the "' . $text . '" button with the country code, like +2348012345678.';
            }

            return ['type' => 'PHONE_NUMBER', 'text' => $text, 'phone_number' => '+' . $digits];
        }

        $url = $button['url'];
        $urlVar = preg_match('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}$/', $url, $urlMatch) === 1;
        $baseUrl = $urlVar ? substr($url, 0, -strlen($urlMatch[0])) : $url;
        if ($urlVar && !$urlVariables) {
            return 'Use the full website URL for the "' . $text . '" button. Card links can’t have variables.';
        }
        if (str_contains($baseUrl, '{{') || str_contains($baseUrl, '}}')) {
            return 'Only one variable can be added to the end of a URL.';
        }
        if ($urlVar && in_array($urlMatch[1], $allVars, true)) {
            $next = $positional ? '{{' . (max(array_map('intval', $allVars)) + 1) . '}}' : 'a new name, like {{event_page}}';

            return '{{' . $urlMatch[1] . '}} is already used in the message. Use ' . $next . ' at the end of the "' . $text . '" link instead.';
        }
        if (filter_var($baseUrl . ($urlVar ? 'x' : ''), FILTER_VALIDATE_URL) === false || preg_match('#^https?://#i', $baseUrl) !== 1 || mb_strlen($url) > 2000) {
            return 'Add a full website URL for the "' . $text . '" button, starting with https://';
        }

        return $urlVar
            ? ['type' => 'URL', 'text' => $text, 'url' => $baseUrl . '{{1}}', 'example' => [$baseUrl . (trim((string) ($samples[$urlMatch[1]] ?? '')) ?: 'LB1024')]]
            : ['type' => 'URL', 'text' => $text, 'url' => $url];
    }

    /**
     * WhatsApp needs quick replies next to each other. They stay first or last, wherever the seller put the first one.
     *
     * @param list<array<string, mixed>> $buttons
     * @return list<array<string, mixed>>
     */
    private static function groupQuickReplies(array $buttons): array
    {
        $quick = array_values(array_filter($buttons, static fn(array $b): bool => $b['type'] === 'QUICK_REPLY'));
        $other = array_values(array_filter($buttons, static fn(array $b): bool => $b['type'] !== 'QUICK_REPLY'));

        return ($buttons[0]['type'] ?? '') === 'QUICK_REPLY' ? array_merge($quick, $other) : array_merge($other, $quick);
    }

    /**
     * Turn the seller's form into a one-screen WhatsApp Flow. WhatsApp publishes it when the template is approved.
     *
     * @param array{button: string, title: string, submit: string, questions: list<array{type: string, label: string, required: bool, options: list<string>}>} $flow
     * @return array<string, mixed>|string the Flow button, or what to fix
     */
    private static function flowButton(array $flow): array|string
    {
        if ($flow['button'] === '') {
            return 'Add the text for the button that opens the form.';
        }
        if ($flow['title'] === '') {
            return 'Give the form a title.';
        }
        if ($flow['questions'] === []) {
            return 'Add at least one question to the form.';
        }
        if ($flow['submit'] === '') {
            return 'Add the text for the form’s submit button.';
        }

        $children = [];
        $payload = [];
        $names = [];
        foreach ($flow['questions'] as $i => $question) {
            $n = $i + 1;
            $kind = self::FLOW_QUESTION_TYPES[$question['type']];
            if ($question['label'] === '') {
                return 'Write question ' . $n . '.';
            }
            if (mb_strlen($question['label']) > $kind['max']) {
                return 'Keep question ' . $n . ' to ' . $kind['max'] . ' characters or fewer.';
            }

            $base = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $question['label']) ?? '', '_'));
            $base = preg_match('/^[a-z]/', $base) === 1 ? substr($base, 0, 30) : 'question_' . $n;
            $name = $base;
            for ($copy = 2; isset($names[$name]); $copy++) {
                $name = $base . '_' . $copy;
            }
            $names[$name] = true;

            $field = ['name' => $name, 'label' => $question['label'], 'required' => $question['required']];
            if ($kind['choices']) {
                $options = $question['options'];
                if (count($options) < 2) {
                    return 'Add at least two options to question ' . $n . '.';
                }
                if (count(array_unique(array_map('mb_strtolower', $options))) !== count($options)) {
                    return 'Each option in question ' . $n . ' needs different text.';
                }
                $field['data-source'] = array_map(
                    static fn(string $option, int $index): array => ['id' => $index . '_' . trim(preg_replace('/[^A-Za-z0-9]+/', '_', $option) ?? '', '_'), 'title' => $option],
                    $options,
                    array_keys($options)
                );
            }

            $children[] = match ($question['type']) {
                'long_text' => ['type' => 'TextArea'] + $field,
                'date' => ['type' => 'DatePicker'] + $field,
                'single' => ['type' => 'RadioButtonsGroup'] + $field,
                'multi' => ['type' => 'CheckboxGroup'] + $field,
                'dropdown' => ['type' => 'Dropdown'] + $field,
                default => ['type' => 'TextInput', 'input-type' => $question['type']] + $field,
            };
            $payload[$name] = '${form.' . $name . '}';
        }
        $children[] = ['type' => 'Footer', 'label' => $flow['submit'], 'on-click-action' => ['name' => 'complete', 'payload' => $payload]];

        $json = [
            'version' => '7.3',
            'screens' => [[
                'id' => 'QUESTIONS',
                'title' => $flow['title'],
                'terminal' => true,
                'success' => true,
                'data' => new \stdClass(),
                'layout' => ['type' => 'SingleColumnLayout', 'children' => $children],
            ]],
        ];

        return [
            'type' => 'FLOW',
            'text' => $flow['button'],
            'flow_json' => (string) json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'flow_action' => 'navigate',
            'navigate_screen' => 'QUESTIONS',
        ];
    }

    /**
     * The editor fields beyond header, body and footer, trimmed to the limits the editor allows.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private static function editorFields(array $input): array
    {
        $text = static fn(mixed $value, int $max): string => mb_substr(trim(str_replace("\r\n", "\n", is_scalar($value) ? (string) $value : '')), 0, $max);
        $rows = static fn(mixed $value, int $max): array => array_slice(array_values(array_filter(is_array($value) ? $value : [], 'is_array')), 0, $max);
        $buttons = static function (mixed $value, int $max) use ($text, $rows): array {
            $list = [];
            foreach ($rows($value, $max) as $button) {
                $type = strtoupper($text($button['type'] ?? '', 20));
                if (in_array($type, ['QUICK_REPLY', 'URL', 'PHONE_NUMBER', 'COPY_CODE'], true)) {
                    $list[] = [
                        'type' => $type,
                        'text' => $text($button['text'] ?? '', 25),
                        'url' => $text($button['url'] ?? '', 2000),
                        'phone' => $text($button['phone'] ?? '', 20),
                        'code' => $text($button['code'] ?? '', 15),
                    ];
                }
            }

            return $list;
        };

        $cards = [];
        foreach ($rows($input['cards'] ?? null, 10) as $card) {
            $cards[] = [
                'media_url' => $text($card['media_url'] ?? '', 1600),
                'body' => $text($card['body'] ?? '', 160),
                'buttons' => $buttons($card['buttons'] ?? null, 2),
            ];
        }

        $flow = is_array($input['flow'] ?? null) ? $input['flow'] : [];
        $questions = [];
        foreach ($rows($flow['questions'] ?? null, 10) as $question) {
            $type = $text($question['type'] ?? '', 20);
            $options = [];
            foreach (array_slice(is_array($question['options'] ?? null) ? $question['options'] : [], 0, 10) as $option) {
                $option = $text($option, 30);
                if ($option !== '') {
                    $options[] = $option;
                }
            }
            $questions[] = [
                'type' => isset(self::FLOW_QUESTION_TYPES[$type]) ? $type : 'text',
                'label' => $text($question['label'] ?? '', 40),
                'required' => !empty($question['required']),
                'options' => $options,
            ];
        }

        $auth = is_array($input['auth'] ?? null) ? $input['auth'] : [];
        $headerType = $text($input['header_type'] ?? '', 10);

        return [
            'header_type' => in_array($headerType, ['none', 'text', 'media'], true) ? $headerType : 'none',
            'buttons' => $buttons($input['buttons'] ?? null, 10),
            'cards' => $cards,
            'catalog_format' => ($input['catalog_format'] ?? '') === 'mpm' ? 'mpm' : 'catalog',
            'auth' => [
                'security' => (bool) ($auth['security'] ?? true),
                'expires' => !empty($auth['expires']),
                'expiry' => max(0, min(999, (int) ($auth['expiry'] ?? 10))),
                'button' => $text($auth['button'] ?? '', 25),
            ],
            'flow' => [
                'button' => $text($flow['button'] ?? '', 25),
                'title' => $text($flow['title'] ?? '', 30),
                'submit' => $text($flow['submit'] ?? '', 30),
                'questions' => $questions,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private static function cleanSamples(array $input): array
    {
        $samples = [];
        foreach ($input as $var => $value) {
            if (preg_match('/^[A-Za-z0-9_]{1,64}$/', (string) $var) === 1 && is_scalar($value)) {
                $samples[(string) $var] = mb_substr(trim((string) $value), 0, 500);
            }
        }

        return $samples;
    }

    /**
     * Keep a template in Lead Bird without sending it to WhatsApp. It shows as "Not submitted" until the seller submits it.
     *
     * @param array<string, mixed> $input
     * @return array{ok: bool, id?: string, error?: string}
     */
    public function saveTemplateDraft(string $businessId, array $input, ?string $templateId = null): array
    {
        $draft = null;
        if ($templateId !== null && $templateId !== '') {
            $draft = $this->repo->findTemplate($templateId, $businessId);
            if ($draft === null || $draft['deleted_at'] !== null) {
                return ['ok' => false, 'error' => 'This template no longer exists. Go back to Templates and refresh.'];
            }
            if (strtoupper((string) $draft['status']) !== 'DRAFT') {
                return ['ok' => false, 'error' => 'This template is already with WhatsApp, so it can’t be saved as a draft.'];
            }
        }

        $str = static fn(string $key): string => trim(str_replace("\r\n", "\n", (string) ($input[$key] ?? '')));
        $name = $draft !== null ? (string) $draft['name'] : $str('name');
        $language = $draft !== null ? (string) $draft['language'] : $str('language');
        $category = strtoupper($str('category'));
        $contentType = $str('content_type');
        $extras = self::editorFields($input);
        $header = $str('header');
        $body = $contentType === 'authentication' ? self::authBody($extras['auth']) : $str('body');
        $footer = $contentType === 'authentication' ? self::authFooter($extras['auth']) : $str('footer');
        $mediaUrl = in_array($contentType, ['media', 'card', 'whatsapp_card'], true) ? $str('media_url') : '';

        if (preg_match('/^[a-z0-9_]{1,512}$/', $name) !== 1) {
            return ['ok' => false, 'error' => 'Use only small letters, numbers and underscores in the template name, like summer_sale.'];
        }
        if (!isset(self::TEMPLATE_LANGUAGES[$language])) {
            return ['ok' => false, 'error' => 'Choose a language.'];
        }
        if (!in_array($contentType, self::EDITABLE_TYPES, true)) {
            return ['ok' => false, 'error' => 'Choose a content type.'];
        }
        if (mb_strlen($header) > 60 || mb_strlen($footer) > 60) {
            return ['ok' => false, 'error' => 'Keep the header and footer to 60 characters or fewer.'];
        }
        if (mb_strlen($body) > 1024) {
            return ['ok' => false, 'error' => 'Keep the message to 1,024 characters or fewer.'];
        }
        if (mb_strlen($mediaUrl) > 1600) {
            return ['ok' => false, 'error' => 'Keep the media URL to 1,600 characters or fewer.'];
        }

        $samples = self::cleanSamples(is_array($input['samples'] ?? null) ? $input['samples'] : []);

        $listButton = '';
        $listItems = [];
        if (in_array($contentType, self::REPLY_ONLY_TYPES, true)) {
            $listButton = $str('list_button');
            if ($body === '') {
                return ['ok' => false, 'error' => 'Write the message.'];
            }
            if ($listButton === '' || mb_strlen($listButton) > 20) {
                return ['ok' => false, 'error' => 'Add the list button text, up to 20 characters.'];
            }
            $seenIds = [];
            foreach (is_array($input['list_items'] ?? null) ? array_values($input['list_items']) : [] as $item) {
                $itemName = trim((string) (is_array($item) ? ($item['name'] ?? '') : ''));
                $itemId = trim((string) (is_array($item) ? ($item['id'] ?? '') : ''));
                $itemDescription = trim((string) (is_array($item) ? ($item['description'] ?? '') : ''));
                if ($itemName === '' || $itemId === '') {
                    return ['ok' => false, 'error' => 'Give every list item a name and an ID.'];
                }
                if (mb_strlen($itemName) > 24 || mb_strlen($itemId) > 200 || mb_strlen($itemDescription) > 72) {
                    return ['ok' => false, 'error' => 'Keep item names to 24 characters, IDs to 200 and descriptions to 72.'];
                }
                if (isset($seenIds[$itemId])) {
                    return ['ok' => false, 'error' => 'Each list item needs a different ID.'];
                }
                $seenIds[$itemId] = true;
                $listItems[] = ['name' => $itemName, 'id' => $itemId, 'description' => $itemDescription];
            }
            if ($listItems === [] || count($listItems) > 10) {
                return ['ok' => false, 'error' => 'Add between 1 and 10 list items.'];
            }
            $extras['buttons'] = [];
        }

        $account = null;
        $wabaId = null;
        foreach (RepositoryFactory::connectedAccounts()->listActiveByBusinessAndPlatform($businessId, 'whatsapp') as $candidate) {
            $candidateWaba = $this->resolveWabaId($candidate);
            if ($candidateWaba === null) {
                continue;
            }
            if ($account === null || ($draft !== null && $candidateWaba === (string) $draft['waba_id'])) {
                $account = $candidate;
                $wabaId = $candidateWaba;
            }
        }
        if ($account === null || $wabaId === null) {
            return ['ok' => false, 'error' => 'Connect your WhatsApp number in Senders first.'];
        }

        $id = $draft !== null ? (string) $draft['id'] : '';
        $clash = $this->repo->findTemplateByKey($wabaId, $name, $language);
        if ($clash !== null && (string) $clash['id'] !== $id) {
            if ($clash['deleted_at'] === null) {
                return ['ok' => false, 'error' => 'You already have a template called ' . $name . ' in this language. Change the name and save again.'];
            }
            if ($id !== '') {
                $this->repo->softDeleteTemplate($id, $businessId);
            }
            $id = (string) $clash['id'];
        }

        $id = $this->repo->saveDraftTemplate([
            'id' => $id,
            'business_id' => $businessId,
            'connected_account_id' => (string) $account['id'],
            'waba_id' => $wabaId,
            'name' => $name,
            'language' => $language,
            'category' => self::FIXED_CATEGORIES[$contentType] ?? (in_array($category, ['MARKETING', 'UTILITY'], true) ? $category : 'MARKETING'),
            'content_type' => $contentType,
            'body' => $body,
            'footer' => $footer !== '' ? $footer : null,
            'header_media_url' => $mediaUrl !== '' ? $mediaUrl : null,
            'draft_spec' => json_encode([
                'header' => $header,
                'samples' => $samples,
                'list_button' => $listButton,
                'list_items' => $listItems,
            ] + $extras, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return ['ok' => true, 'id' => $id];
    }

    /**
     * Download the file behind a template's media URL. Only public https addresses are allowed.
     *
     * @return array{bytes: string, mime: string, format: string, name: string}|array{error: string}
     */
    private function templateMediaFile(string $url): array
    {
        $unreachable = ['error' => 'We could not download a file from that media URL. Use a direct https:// link to a JPG or PNG photo, MP4 video or PDF.'];
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || $host === '' || isset($parts['user'])) {
            return ['error' => 'Start the media URL with https://'];
        }
        $ips = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : (gethostbynamel($host) ?: []);
        if ($ips === []) {
            return $unreachable;
        }
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return $unreachable;
            }
        }

        $max = 16_777_216;
        $ch = curl_init($url);
        if ($ch === false) {
            return $unreachable;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_RESOLVE => [$host . ':' . (int) ($parts['port'] ?? 443) . ':' . $ips[0]],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => 'LeadBird/1.0',
            CURLOPT_NOPROGRESS => false,
            CURLOPT_XFERINFOFUNCTION => static fn($c, int $total, int $done): int => ($total > $max || $done > $max) ? 1 : 0,
        ]);
        $bytes = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($bytes) || $bytes === '' || $status !== 200) {
            return strlen(is_string($bytes) ? $bytes : '') > $max
                ? ['error' => 'The file at that media URL is too big. Videos and PDFs can be up to 16 MB.']
                : $unreachable;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? (string) finfo_buffer($finfo, $bytes) : '';
        if ($finfo !== false) {
            finfo_close($finfo);
        }
        $type = self::TEMPLATE_MEDIA_TYPES[$mime] ?? null;
        if ($type === null) {
            return ['error' => 'The media URL must point to a JPG or PNG photo, an MP4 video or a PDF.'];
        }
        if (strlen($bytes) > $type['max']) {
            return ['error' => $type['format'] === 'IMAGE' ? 'Photos can be up to 5 MB.' : 'Videos and PDFs can be up to 16 MB.'];
        }

        return ['bytes' => $bytes, 'mime' => $mime, 'format' => $type['format'], 'name' => 'template-sample.' . $type['ext']];
    }

    /**
     * Meta's resumable upload: WhatsApp reviews templates against a sample file referenced by this handle.
     */
    private function uploadToMeta(string $token, string $bytes, string $mime, string $name): ?string
    {
        $appId = trim((string) (app_config()['meta']['app_id'] ?? ''));
        if ($appId === '' || $token === '') {
            return null;
        }
        $base = 'https://graph.facebook.com/' . meta_graph_version() . '/';

        $ch = curl_init($base . $appId . '/uploads?' . http_build_query([
            'file_name' => $name,
            'file_length' => strlen($bytes),
            'file_type' => $mime,
            'access_token' => $token,
        ]));
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => '', CURLOPT_TIMEOUT => 30]);
        $session = json_decode((string) curl_exec($ch), true);
        curl_close($ch);
        $sessionId = is_array($session) ? (string) ($session['id'] ?? '') : '';
        if ($sessionId === '') {
            error_log('[Broadcast] template media session: ' . json_encode($session['error'] ?? $session));

            return null;
        }

        $ch = curl_init($base . $sessionId);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $bytes,
            CURLOPT_HTTPHEADER => ['Authorization: OAuth ' . $token, 'file_offset: 0', 'Content-Type: application/octet-stream'],
            CURLOPT_TIMEOUT => 120,
        ]);
        $upload = json_decode((string) curl_exec($ch), true);
        curl_close($ch);
        $handle = is_array($upload) ? (string) ($upload['h'] ?? '') : '';
        if ($handle === '') {
            error_log('[Broadcast] template media upload: ' . json_encode($upload['error'] ?? $upload));

            return null;
        }

        return $handle;
    }

    /**
     * Copy every template on the business's WhatsApp Business Accounts from Meta,
     * including ones created in WhatsApp Manager or with another provider.
     *
     * @return array{ok: bool, synced: int, error?: string}
     */
    public function syncTemplates(string $businessId): array
    {
        $byWaba = [];
        foreach (RepositoryFactory::connectedAccounts()->listActiveByBusinessAndPlatform($businessId, 'whatsapp') as $account) {
            $wabaId = $this->resolveWabaId($account);
            if ($wabaId !== null && !isset($byWaba[$wabaId])) {
                $byWaba[$wabaId] = $account;
            }
        }

        $synced = 0;
        $failed = false;
        foreach ($byWaba as $wabaId => $account) {
            $token = (string) $account['access_token'];
            $seen = [];
            $after = null;
            $complete = false;
            for ($page = 0; $page < 20; $page++) {
                $query = [
                    'fields' => 'id,name,status,category,language,components,quality_score,rejected_reason',
                    'limit' => 100,
                ];
                if ($after !== null) {
                    $query['after'] = $after;
                }
                $response = meta_graph_get($wabaId . '/message_templates', $token, $query);
                if (isset($response['error'])) {
                    error_log('[Broadcast] template sync ' . $wabaId . ': ' . (string) ($response['error']['message'] ?? 'unknown'));
                    $failed = true;
                    break;
                }

                foreach ($response['data'] ?? [] as $item) {
                    if (!is_array($item) || trim((string) ($item['name'] ?? '')) === '') {
                        continue;
                    }
                    $this->repo->upsertSyncedTemplate(self::syncedTemplateRow($businessId, (string) $account['id'], (string) $wabaId, $item));
                    $seen[] = (string) $item['name'] . '|' . (string) ($item['language'] ?? 'en');
                    $synced++;
                }

                $after = $response['paging']['cursors']['after'] ?? null;
                if (empty($response['paging']['next']) || !is_string($after) || $after === '') {
                    $complete = true;
                    break;
                }
            }

            if ($complete) {
                $this->repo->markMissingTemplatesDeleted($businessId, (string) $wabaId, $seen);
            }
        }

        return $failed
            ? ['ok' => false, 'synced' => $synced, 'error' => 'We could not load your templates from WhatsApp. Try again in a few minutes.']
            : ['ok' => true, 'synced' => $synced];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private static function syncedTemplateRow(string $businessId, string $accountId, string $wabaId, array $item): array
    {
        $components = is_array($item['components'] ?? null) ? $item['components'] : [];
        $body = '';
        $footer = null;
        foreach ($components as $component) {
            $type = strtoupper((string) ($component['type'] ?? ''));
            if ($type === 'BODY') {
                $body = (string) ($component['text'] ?? '');
            } elseif ($type === 'FOOTER') {
                $footer = mb_substr((string) ($component['text'] ?? ''), 0, 60) ?: null;
            }
        }

        $reason = trim((string) ($item['rejected_reason'] ?? ''));
        $quality = strtoupper(trim((string) ($item['quality_score']['score'] ?? '')));
        $category = strtoupper((string) ($item['category'] ?? 'MARKETING'));

        return [
            'business_id' => $businessId,
            'connected_account_id' => $accountId,
            'waba_id' => $wabaId,
            'meta_template_id' => trim((string) ($item['id'] ?? '')) ?: null,
            'name' => (string) $item['name'],
            'language' => (string) ($item['language'] ?? 'en'),
            'category' => $category,
            'content_type' => self::contentTypeFor($components, $category),
            'body' => $body,
            'footer' => $footer,
            'components' => json_encode($components, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null,
            'status' => strtoupper((string) ($item['status'] ?? 'PENDING')),
            'quality' => in_array($quality, ['GREEN', 'YELLOW', 'RED'], true) ? $quality : null,
            'rejected_reason' => ($reason === '' || strtoupper($reason) === 'NONE') ? null : mb_substr($reason, 0, 255),
        ];
    }

    /**
     * @param list<array<string, mixed>> $components
     */
    public static function contentTypeFor(array $components, string $category): string
    {
        if (strtoupper($category) === 'AUTHENTICATION') {
            return 'authentication';
        }

        $buttonTypes = [];
        $headerFormat = '';
        foreach ($components as $component) {
            $type = strtoupper((string) ($component['type'] ?? ''));
            if ($type === 'CAROUSEL') {
                return 'carousel';
            }
            if ($type === 'HEADER') {
                $headerFormat = strtoupper((string) ($component['format'] ?? ''));
            }
            if ($type === 'BUTTONS') {
                foreach ($component['buttons'] ?? [] as $button) {
                    $buttonTypes[strtoupper((string) ($button['type'] ?? ''))] = true;
                }
            }
        }

        return match (true) {
            isset($buttonTypes['CATALOG']) || isset($buttonTypes['MPM']) || isset($buttonTypes['SPM']) => 'catalog',
            isset($buttonTypes['FLOW']) => 'flow',
            isset($buttonTypes['URL']) || isset($buttonTypes['PHONE_NUMBER']) || isset($buttonTypes['COPY_CODE']) || isset($buttonTypes['VOICE_CALL']) => 'call_to_action',
            isset($buttonTypes['QUICK_REPLY']) => 'quick_reply',
            in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT'], true) => 'media',
            $headerFormat === 'LOCATION' => 'location',
            default => 'text',
        };
    }

    /**
     * Delete a template from WhatsApp and hide it here. Past broadcasts keep its name.
     *
     * @return array{ok: bool, error?: string}
     */
    public function deleteTemplate(string $businessId, string $templateId): array
    {
        $template = $this->repo->findTemplate($templateId, $businessId);
        if ($template === null || $template['deleted_at'] !== null) {
            return ['ok' => true];
        }
        if (strtoupper((string) $template['status']) === 'DRAFT') {
            $this->repo->softDeleteTemplate($templateId, $businessId);

            return ['ok' => true];
        }

        $token = '';
        foreach (RepositoryFactory::connectedAccounts()->listActiveByBusinessAndPlatform($businessId, 'whatsapp') as $account) {
            if ($this->resolveWabaId($account) === (string) $template['waba_id']) {
                $token = (string) $account['access_token'];
                break;
            }
        }
        if ($token === '') {
            return ['ok' => false, 'error' => 'The WhatsApp number this template belongs to is no longer connected. Reconnect it, then try again.'];
        }

        $query = ['name' => (string) $template['name']];
        if (trim((string) ($template['meta_template_id'] ?? '')) !== '') {
            $query['hsm_id'] = (string) $template['meta_template_id'];
        }
        $response = meta_graph_delete((string) $template['waba_id'] . '/message_templates', $token, $query);
        if (isset($response['error'])) {
            return ['ok' => false, 'error' => self::metaErrorMessage($response, 'WhatsApp did not delete this template.')];
        }

        $this->repo->softDeleteTemplate($templateId, $businessId);

        return ['ok' => true];
    }

    /**
     * @return array{ok: bool, body?: string, error?: string, tokens_depleted?: bool}
     */
    public function draftTemplate(string $businessId, string $brief, string $category): array
    {
        $brief = trim($brief);
        if ($brief === '') {
            return ['ok' => false, 'error' => 'Tell Hannah what the message is about.'];
        }

        $business = RepositoryFactory::businesses()->findById($businessId);
        $userId = trim((string) ($business['user_id'] ?? ''));
        $tokens = new TokenService();
        $cost = TokenCosts::BROADCAST_DRAFT;
        if ($userId === '' || !$tokens->canSpend($userId, $cost)) {
            return ['ok' => false, 'error' => TokenCosts::insufficientMessage($cost), 'tokens_depleted' => true];
        }

        $apiKey = app_config()['openai']['api_key'] ?? '';
        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'AI writing is not configured yet.'];
        }
        if ((new OpenAiBudgetService())->isExceeded($businessId)) {
            return ['ok' => false, 'error' => (new OpenAiBudgetService())->blockedUserMessage($businessId)];
        }

        $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId);
        $about = trim((string) ($settings['business_description'] ?? ''));
        $kind = strtoupper($category) === 'UTILITY'
            ? 'an update about an order, booking or account (no promotions)'
            : 'a short promotional message';

        $system = implode("\n", array_filter([
            'You write WhatsApp broadcast messages for small businesses in Nigeria.',
            'Write ' . $kind . ' the business will send to customers who opted in.',
            'Rules:',
            '- Plain, friendly English. Short sentences. At most 2 emojis.',
            '- 250 to 550 characters.',
            '- Start with "Hi {{name}}," so the customer\'s first name is filled in. Use {{name}} only once.',
            '- End with one clear next step, such as "Reply YES to order".',
            '- Do not invent prices, dates, links or discounts that are not in the brief.',
            '- Do not add a sign-off about unsubscribing; it is added automatically.',
            '- Return only the message text. No quotes, no labels.',
            'Business name: ' . (string) ($business['business_name'] ?? 'Business'),
            $about !== '' ? 'About the business: ' . mb_substr($about, 0, 600) : null,
        ]));

        $api = openai_chat_completion([
            'model' => app_config()['openai']['model'] ?? 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => 'Brief: ' . mb_substr($brief, 0, 600)],
            ],
            'temperature' => 0.6,
            'max_tokens' => 260,
        ], $businessId, 'broadcast_draft', 0.001);

        if (!($api['ok'] ?? false)) {
            error_log('[Broadcast] draft: ' . (string) ($api['error'] ?? 'unknown'));

            return ['ok' => false, 'error' => 'Hannah could not write a draft right now. Try again in a moment.'];
        }

        $text = trim((string) ($api['body']['choices'][0]['message']['content'] ?? ''), " \n\r\t\"'");
        if ($text === '') {
            return ['ok' => false, 'error' => 'Hannah returned an empty draft. Add more detail and try again.'];
        }

        $tokens->spend($userId, $cost, 'broadcast_draft', 'broadcast_ai', Uuid::v4(), ['business_id' => $businessId]);

        return ['ok' => true, 'body' => mb_substr($text, 0, 900)];
    }

    /**
     * Hannah on the template editor: writes one field of the template from what is already filled in.
     *
     * @param array{name?: string, body?: string, buttons?: list<string>, category?: string, content_type?: string, variable?: string, button_type?: string, current?: string} $context
     * @return array{ok: true, text: string, samples: array<string, string>}|array{ok: false, error: string}
     */
    public function writeTemplateContent(string $businessId, string $field, array $context): array
    {
        if (!in_array($field, ['name', 'body', 'sample', 'button_text', 'pick'], true)) {
            return ['ok' => false, 'error' => 'Hannah can’t write that field.'];
        }

        $business = RepositoryFactory::businesses()->findById($businessId);
        $userId = trim((string) ($business['user_id'] ?? ''));
        $tokens = new TokenService();
        $cost = TokenCosts::BROADCAST_DRAFT;
        if ($userId === '' || !$tokens->canSpend($userId, $cost)) {
            return ['ok' => false, 'error' => TokenCosts::insufficientMessage($cost)];
        }
        if ($field === 'pick') {
            $tokens->spend($userId, $cost, 'broadcast_draft', 'broadcast_ai', Uuid::v4(), ['business_id' => $businessId, 'source' => 'library']);

            return ['ok' => true, 'text' => '', 'samples' => []];
        }
        if ((new OpenAiBudgetService())->isExceeded($businessId)) {
            return ['ok' => false, 'error' => (new OpenAiBudgetService())->blockedUserMessage($businessId)];
        }

        $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId);
        $about = trim((string) ($settings['business_description'] ?? ''));
        $category = strtoupper((string) ($context['category'] ?? ''));
        $contentType = (string) ($context['content_type'] ?? 'text');
        $currentName = trim((string) ($context['name'] ?? ''));
        $currentBody = trim((string) ($context['body'] ?? ''));
        $buttonTexts = array_values(array_filter(array_map(
            static fn($text): string => trim((string) $text),
            is_array($context['buttons'] ?? null) ? $context['buttons'] : []
        )));
        $variable = trim((string) ($context['variable'] ?? ''));
        $buttonType = (string) ($context['button_type'] ?? 'QUICK_REPLY');

        $around = '';
        if ($variable !== '' && preg_match('/[^\n]{0,60}\{\{\s*' . preg_quote($variable, '/') . '\s*\}\}[^\n]{0,40}/u', $currentBody, $match) === 1) {
            $around = trim($match[0]);
        }

        $kind = $category === 'UTILITY'
            ? 'a Utility template: an update about an order, booking, payment, registration or account, with no promotion'
            : 'a Marketing template: a promotion, offer, invitation or announcement';
        $typeNote = [
            'text' => 'It is a text-only message.',
            'quick_reply' => 'It has quick reply buttons the customer taps to answer, so the body ends with a question the buttons answer.',
            'call_to_action' => 'It has a button that opens a website or calls a phone number, so the body points to the button.',
            'media' => 'It is sent with a photo, video or document, so the body can refer to it.',
        ][$contentType] ?? '';

        $task = match ($field) {
            'name' => [
                'Your job: suggest the template name.',
                '- Small letters, numbers and underscores only. 2 to 5 words, like christmas_sale_invite or order_ready_for_pickup.',
                '- It describes what the message is for. Do not include the business name.',
            ],
            'body' => [
                'Your job: write the message body.',
                '- 2 to 4 short sentences, at most 350 characters.',
                '- Placeholders (variables) stand for details that change. Use numbered placeholders {{1}}, {{2}}, {{3}} in order.',
                '- Always start with "Hi {{1}}," where {{1}} is the customer\'s first name, so every customer sees their own name.',
                '- Every date, day, time, price, amount, discount, venue, order number and product or service name is a placeholder. Never write a real date, day or time in the text.',
                '- Use 2 to 5 placeholders in total, including the name.',
                '- The business name is never a placeholder. Write it out in full where it fits, like "at Axon Analytics LTD".',
                '- Never start or end the body with a placeholder. Never put two placeholders next to each other.',
                '- Never use square brackets, "XXX" or made-up details. Never invent prices, discounts, addresses, links or social handles. Use a placeholder for any detail you don\'t have.',
                '- Work out what the message is for from every word of the template name and anything already written. Read the name the way a Nigerian business owner means it. For example "2nd_term_resumption_announcement" is a school telling parents when the second term starts, so it addresses the parent and the dates are placeholders. If there is nothing to go on, write a short general message the business can send.',
                '- Write to whoever the business sends to: customers for a shop, parents for a school, members for a church or gym, guests for an event.',
                '- No links in the body; links go in the button. No unsubscribe line; it is handled separately.',
                '- Only mention things every small business has. Never mention portals, apps, websites, account numbers or staff names unless they are already in the template.',
                '- Don\'t end with filler like "Let us know if you need assistance" or "Thank you for your understanding".',
            ],
            'sample' => [
                'Your job: write the sample content for the placeholder {{' . $variable . '}}. WhatsApp uses it to understand the message when it reviews it.',
                '- Work out what kind of detail {{' . $variable . '}} is from the words right before and after it: ' . ($around !== '' ? '"' . $around . '"' : 'see the body') . '. For example "Hi {{1}}" is a first name, "on {{3}}" is a date, "at {{4}}" after a date is a time.',
                '- Give one realistic Nigerian example value of that kind, like "Ada" for a first name, "₦25,000" for an amount or "Saturday, 20 December" for a date.',
                '- At most 200 characters. Just the value, no quotes and no explanation.',
            ],
            'button_text' => [
                'Your job: write the text for a ' . (['URL' => 'button that opens a website', 'PHONE_NUMBER' => 'button that calls the business'][$buttonType] ?? 'quick reply button the customer taps to answer') . '.',
                '- At most 20 characters. 1 to 4 plain words, like "Yes, keep one", "Book now" or "Call us".',
                '- It must fit the body and must not repeat another button.',
            ],
        };

        $system = implode("\n", array_filter(array_merge(
            [
                'You are Hannah, a sales assistant at Lead Bird. You help a small business in Nigeria fill in a WhatsApp message template.',
                'The template is ' . $kind . '. ' . $typeNote,
            ],
            $task,
            [
                'How to write:',
                '- Sound like a real Nigerian shop owner or sales person texting a customer they know. Warm, direct and natural.',
                '- Very simple, everyday words. Short sentences.',
                '- No emojis at all. No exclamation marks unless one really fits.',
                '- Never use marketing or AI phrases like "We\'re excited to", "Don\'t miss out", "exclusive", "festive opportunity", "festivities", "great deals", "elevate", "unlock", "dive in", "look no further", "we\'ve got you covered" or "Happy holidays".',
                '- Say things the way people actually text: "Your order LB1024 is ready" not "Your order with number LB1024 is ready".',
                '- Example of the right tone (do not copy it): "Hi {{1}}, our Christmas sale is on {{2}} at our Ikeja shop. Every shoe is {{3}} off. Come early so you get your size. See you there."',
                'Today is ' . date('l, j F Y') . '. Example dates must be after today.',
                'Reply only with JSON: {"text": "...", "samples": {}}.',
                '"text" is exactly what goes in the field. Always write it; never ask a question. If the field already has something, write a different version.',
                $field === 'body' ? '"samples" gives a realistic Nigerian example value for every placeholder in "text", keyed by the placeholder name, like {"first_name": "Ada"} or {"1": "Ada"}. Write dates like "Saturday, 24 October", times like "2:00 PM" and amounts like "₦25,000".' : '"samples" is always {}.',
                'Business name: ' . (string) ($business['business_name'] ?? 'Business'),
                $about !== '' ? 'About the business: ' . mb_substr($about, 0, 600) : null,
                $currentName !== '' ? 'Template name: ' . mb_substr($currentName, 0, 100) : null,
                $currentBody !== '' ? "Current body:\n" . mb_substr($currentBody, 0, 1100) : null,
                $buttonTexts !== [] ? 'Current buttons: ' . mb_substr(implode(', ', $buttonTexts), 0, 200) : null,
                $field !== 'body' && trim((string) ($context['current'] ?? '')) !== '' ? 'What is in the field now: ' . mb_substr(trim((string) $context['current']), 0, 200) : null,
            ]
        )));

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => [
                'name' => 'Write the template name only.',
                'body' => 'Write the message body.',
                'sample' => 'Write the sample value for {{' . $variable . '}} only.',
                'button_text' => 'Write the button text only.',
            ][$field]],
        ];

        $bodyProblem = static function (string $text): ?string {
            if (preg_match('/^\s*(hi|hello|dear|good (morning|afternoon|evening))\s+\{\{\s*1\s*\}\}/i', $text) !== 1) {
                return 'It must start with "Hi {{1}}," where {{1}} is the customer\'s first name.';
            }
            if (mb_strlen($text) > 400) {
                return 'It is too long. Keep it to 2 to 4 short sentences, at most 350 characters.';
            }
            if (preg_match('/\b(\d{1,2}(st|nd|rd|th)?\s+(January|February|March|April|May|June|July|August|September|October|November|December)|(January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{1,2}|(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)|\d{1,2}(:\d{2})?\s?(am|pm))\b/i', $text) === 1) {
                return 'It has a real date, day or time. Use a numbered placeholder for it instead.';
            }

            return null;
        };

        $data = null;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $api = openai_chat_completion([
                'model' => app_config()['openai']['model'] ?? 'gpt-4o-mini',
                'messages' => $messages,
                'temperature' => 0.8,
                'max_tokens' => 700,
                'response_format' => ['type' => 'json_object'],
            ], $businessId, 'broadcast_draft', 0.002);

            $reply = ($api['ok'] ?? false) ? (string) ($api['body']['choices'][0]['message']['content'] ?? '') : '';
            $data = json_decode($reply, true);
            if (!is_array($data) || $field !== 'body') {
                break;
            }
            $problem = $bodyProblem((string) ($data['text'] ?? ''));
            if ($problem === null) {
                break;
            }
            $messages[] = ['role' => 'assistant', 'content' => $reply];
            $messages[] = ['role' => 'user', 'content' => $problem . ' Write it again following every rule.'];
        }
        if (!is_array($data)) {
            error_log('[Broadcast] template writer: ' . (string) ($api['error'] ?? 'invalid response'));

            return ['ok' => false, 'error' => 'Hannah couldn’t write that right now. Please try again in a moment.'];
        }

        $plain = static fn(string $text): string => trim((string) preg_replace(
            ['/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}]/u', '/[ \t]+$/m', '/[ \t]{2,}/'],
            ['', '', ' '],
            $text
        ));
        $text = $plain((string) ($data['text'] ?? ''));
        $samples = [];

        if ($field === 'name') {
            $text = trim((string) preg_replace('/_+/', '_', (string) preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '-'], '_', mb_strtolower($text)))), '_');
            $text = implode('_', array_slice(array_filter(explode('_', $text)), 0, 5));
        } elseif ($field === 'sample') {
            $text = mb_substr(trim($text, "\"' "), 0, 200);
        } elseif ($field === 'button_text') {
            $text = mb_substr(trim($text, "\"' "), 0, 25);
        } else {
            $text = mb_substr($text, 0, 1024);
            $given = is_array($data['samples'] ?? null) ? $data['samples'] : [];
            if (preg_match('/^\s*(hi|hello|dear|good (morning|afternoon|evening))\s+\{\{/i', $text) !== 1) {
                $text = 'Hi {{0}}, ' . ltrim((string) preg_replace('/^\s*(hi|hello|dear)\b[^,.!\n]*[,.!]?\s*/i', '', $text));
                $given['0'] = 'Ada';
            }
            preg_match_all('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', $text, $found);
            $vars = array_values(array_unique($found[1]));
            $numbered = array_filter($vars, static fn(string $var): bool => ctype_digit($var)) !== [];
            foreach ($vars as $index => $var) {
                $key = $numbered ? (string) ($index + 1) : strtolower($var);
                $samples[$key] = mb_substr(trim((string) ($given[$var] ?? '')), 0, 200);
            }
            $order = array_flip($vars);
            $text = (string) preg_replace_callback(
                '/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/',
                static fn(array $m): string => '{{' . ($numbered ? (string) ($order[$m[1]] + 1) : strtolower($m[1])) . '}}',
                $text
            );
        }

        if ($text === '') {
            return ['ok' => false, 'error' => 'Hannah couldn’t write that right now. Please try again in a moment.'];
        }

        $tokens->spend($userId, $cost, 'broadcast_draft', 'broadcast_ai', Uuid::v4(), ['business_id' => $businessId]);

        return ['ok' => true, 'text' => $text, 'samples' => $samples];
    }

    // —— Contacts ——

    public static function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = '234' . substr($digits, 1);
        } elseif (strlen($digits) === 10 && in_array($digits[0], ['7', '8', '9'], true)) {
            $digits = '234' . $digits;
        }

        return (strlen($digits) >= 10 && strlen($digits) <= 15) ? $digits : null;
    }

    /**
     * Parse pasted lines like "Ada Obi, 0803 123 4567" or "+2348031234567".
     *
     * @return array{rows: list<array{phone: string, name: ?string}>, invalid: int}
     */
    public static function parseContactLines(string $text): array
    {
        $rows = [];
        $invalid = 0;
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_values(array_filter(array_map('trim', preg_split('/[,;\t|]/', $line) ?: []), 'strlen'));
            $phone = null;
            $nameParts = [];
            foreach ($parts as $part) {
                $candidate = preg_match('/\d{7,}/', preg_replace('/[\s\-().]/', '', $part) ?? '') === 1
                    ? self::normalizePhone($part)
                    : null;
                if ($phone === null && $candidate !== null) {
                    $phone = $candidate;
                } else {
                    $nameParts[] = $part;
                }
            }
            if ($phone === null) {
                $invalid++;
                continue;
            }
            $name = trim(implode(' ', $nameParts));
            if ($name === '' && isset($rows[$phone])) {
                continue;
            }
            $rows[$phone] = ['phone' => $phone, 'name' => $name !== '' ? mb_substr($name, 0, 120) : null];
        }

        return ['rows' => array_values($rows), 'invalid' => $invalid];
    }

    /**
     * @param list<array{phone: string, name: ?string}> $rows
     * @return array{ok: bool, added?: int, skipped?: int, error?: string}
     */
    public function addContacts(string $businessId, string $planSlug, array $rows, string $source): array
    {
        if ($rows === []) {
            return ['ok' => false, 'error' => 'We could not find any valid phone numbers.'];
        }

        $limit = self::contactLimit($planSlug);
        if ($limit === 0) {
            return ['ok' => false, 'error' => 'Bulk WhatsApp Messaging is available on the Vendor plan and above.'];
        }
        $current = $this->repo->countContacts($businessId);
        if ($limit !== null && $current + count($rows) > $limit) {
            $room = max(0, $limit - $current);
            if ($room === 0) {
                return ['ok' => false, 'error' => 'You have reached your plan\'s limit of ' . number_format($limit) . ' contacts. Upgrade to add more.'];
            }
            $rows = array_slice($rows, 0, $room);
        }

        $added = $this->repo->addContacts($businessId, $rows, $source);

        return ['ok' => true, 'added' => $added, 'skipped' => count($rows) - $added];
    }

    /**
     * @return array{ok: bool, added?: int, skipped?: int, error?: string}
     */
    public function importInboxContacts(string $businessId, string $planSlug): array
    {
        $rows = [];
        foreach ($this->repo->whatsappInboxCustomers($businessId) as $customer) {
            $phone = self::normalizePhone($customer['phone']);
            if ($phone !== null) {
                $rows[$phone] = ['phone' => $phone, 'name' => $customer['name']];
            }
        }

        if ($rows === []) {
            return ['ok' => false, 'error' => 'No WhatsApp customers in your inbox yet.'];
        }

        return $this->addContacts($businessId, $planSlug, array_values($rows), 'inbox');
    }

    // —— Broadcasts ——

    public static function tokenCost(int $recipients): int
    {
        return max(0, $recipients) * TokenCosts::BROADCAST_RECIPIENT;
    }

    public static function replyReserve(int $recipients): int
    {
        return $recipients <= 0 ? 0 : max(self::REPLY_RESERVE_MIN, (int) ceil($recipients * self::REPLY_RESERVE_PER_RECIPIENT));
    }

    public static function metaEstimateUsd(string $category, int $nigerianRecipients): float
    {
        $rate = self::META_RATES_NG[strtoupper($category)] ?? self::META_RATES_NG['MARKETING'];

        return round(max(0, $nigerianRecipients) * $rate, 2);
    }

    /**
     * Days needed when Meta's daily limit is smaller than the list. Null limit means no cap.
     */
    public static function sendDays(int $recipients, ?int $dailyLimit, int $usedToday): int
    {
        if ($recipients <= 0 || $dailyLimit === null || $dailyLimit <= 0) {
            return $recipients > 0 ? 1 : 0;
        }
        $today = max(0, $dailyLimit - $usedToday);
        if ($recipients <= $today) {
            return 1;
        }

        return ($today > 0 ? 1 : 0) + (int) ceil(($recipients - $today) / $dailyLimit);
    }

    /**
     * What a broadcast will cost before it is sent: tokens, Meta's charge and how long it takes.
     *
     * @return array{ok: bool, error?: string, summary?: array<string, mixed>, template?: array<string, mixed>, account?: array<string, mixed>, owner_id?: string}
     */
    public function estimateBroadcast(string $businessId, string $userId, string $templateId): array
    {
        $template = $this->repo->findTemplate($templateId, $businessId);
        if ($template === null || (string) $template['status'] !== 'APPROVED') {
            return ['ok' => false, 'error' => 'Pick a template Meta has approved.'];
        }

        $account = $this->whatsappAccount($businessId);
        if ($account === null) {
            return ['ok' => false, 'error' => 'Connect your WhatsApp Business number first.'];
        }
        if ($this->resolveWabaId($account) !== (string) $template['waba_id']) {
            return ['ok' => false, 'error' => 'This template belongs to a different WhatsApp number. Create it again for the number you have connected.'];
        }

        $counts = $this->repo->subscribedContactCounts($businessId);
        $recipients = $counts['total'];
        if ($recipients === 0) {
            return ['ok' => false, 'error' => 'Add contacts who agreed to hear from you first.'];
        }

        $business = RepositoryFactory::businesses()->findById($businessId);
        $ownerId = (string) ($business['user_id'] ?? $userId);
        $balance = (new TokenService())->getBalance($ownerId);
        $tokensNeeded = self::tokenCost($recipients);
        $tokensAfter = $balance - $tokensNeeded;
        $reserve = self::replyReserve($recipients);
        $enoughTokens = $tokensAfter >= 0;
        $lowReserve = $enoughTokens && $tokensAfter < $reserve;

        $category = strtoupper((string) $template['category']);
        $metaUsd = self::metaEstimateUsd($category, $counts['nigeria']);
        $others = $recipients - $counts['nigeria'];

        $dailyLimit = $this->dailyLimitForAccount($account);
        $usedToday = $dailyLimit === null ? 0 : $this->repo->uniqueRecipientsLast24h((string) $account['id']);
        $days = self::sendDays($recipients, $dailyLimit, $usedToday);

        $lines = [
            'This broadcast goes to ' . number_format($recipients) . ' ' . ($recipients === 1 ? 'customer' : 'customers') . ' and uses ' . number_format($tokensNeeded) . ' tokens. You have ' . number_format($balance) . '.',
            'Meta will charge about $' . number_format($metaUsd, 2) . ' to the card on your Meta account'
                . ($others > 0 ? ', plus its own rate for ' . number_format($others) . ' ' . ($others === 1 ? 'number' : 'numbers') . ' outside Nigeria' : '') . '.',
        ];
        if ($days > 1) {
            $lines[] = 'Meta lets your number message ' . number_format((int) $dailyLimit) . ' people a day, so this sends over ' . $days . ' days.';
        }

        $warning = null;
        if (!$enoughTokens) {
            $warning = 'You need ' . number_format($tokensNeeded - $balance) . ' more tokens to send this broadcast. Top up on Billing, then try again.';
        } elseif ($lowReserve) {
            $warning = 'After sending you will have ' . number_format($tokensAfter) . ' tokens left. Hannah needs about ' . number_format($reserve) . ' to answer the replies this broadcast brings in. Top up first so she keeps replying.';
        }

        return [
            'ok' => true,
            'summary' => [
                'recipients' => $recipients,
                'recipients_nigeria' => $counts['nigeria'],
                'recipients_other' => $others,
                'tokens_needed' => $tokensNeeded,
                'tokens_balance' => $balance,
                'tokens_after' => max(0, $tokensAfter),
                'reply_reserve' => $reserve,
                'enough_tokens' => $enoughTokens,
                'low_reply_tokens' => $lowReserve,
                'can_send' => $enoughTokens,
                'needs_confirm' => $lowReserve,
                'category' => $category,
                'meta_estimate_usd' => $metaUsd,
                'daily_limit' => $dailyLimit,
                'days' => $days,
                'lines' => $lines,
                'warning' => $warning,
                'topup_url' => app_route('billing'),
            ],
            'template' => $template,
            'account' => $account,
            'owner_id' => $ownerId,
        ];
    }

    /**
     * @return array{ok: bool, broadcast_id?: string, error?: string, needs_confirm?: bool, summary?: array<string, mixed>}
     */
    public function createBroadcast(string $businessId, string $userId, string $name, string $templateId, string $fallback, bool $confirmLowTokens = false): array
    {
        $name = trim($name);
        $fallback = trim($fallback) !== '' ? mb_substr(trim($fallback), 0, 60) : 'there';
        if ($name === '') {
            return ['ok' => false, 'error' => 'Give this broadcast a name so you can find it later.'];
        }

        $estimate = $this->estimateBroadcast($businessId, $userId, $templateId);
        if (!$estimate['ok']) {
            return ['ok' => false, 'error' => (string) $estimate['error']];
        }
        $summary = $estimate['summary'];
        if (!$summary['can_send']) {
            return ['ok' => false, 'error' => (string) $summary['warning'], 'summary' => $summary];
        }
        if ($summary['needs_confirm'] && !$confirmLowTokens) {
            return ['ok' => false, 'error' => (string) $summary['warning'], 'needs_confirm' => true, 'summary' => $summary];
        }

        $account = $estimate['account'];
        $contacts = $this->repo->listSubscribedContacts($businessId);
        if ($contacts === []) {
            return ['ok' => false, 'error' => 'Add contacts who agreed to hear from you first.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $broadcastId = $this->repo->createBroadcast([
                'business_id' => $businessId,
                'connected_account_id' => (string) $account['id'],
                'template_id' => $templateId,
                'name' => mb_substr($name, 0, 120),
                'name_fallback' => $fallback,
                'total_recipients' => count($contacts),
                'created_by' => $userId,
            ]);
            $this->repo->addRecipients($broadcastId, $contacts);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('[Broadcast] create: ' . $e->getMessage());

            return ['ok' => false, 'error' => 'Could not start the broadcast. Please try again.'];
        }

        return ['ok' => true, 'broadcast_id' => $broadcastId];
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public function resume(string $broadcastId, string $businessId): array
    {
        $broadcast = $this->repo->findBroadcast($broadcastId, $businessId);
        if ($broadcast === null || (string) $broadcast['status'] !== 'paused') {
            return ['ok' => false, 'error' => 'This broadcast is not paused.'];
        }
        $this->repo->setBroadcastStatus($broadcastId, 'sending');

        return ['ok' => true];
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public function cancel(string $broadcastId, string $businessId): array
    {
        $broadcast = $this->repo->findBroadcast($broadcastId, $businessId);
        if ($broadcast === null || !in_array((string) $broadcast['status'], ['sending', 'paused'], true)) {
            return ['ok' => false, 'error' => 'This broadcast has already finished.'];
        }
        $this->repo->cancelQueuedRecipients($broadcastId);
        $this->repo->setBroadcastStatus($broadcastId, 'cancelled');

        return ['ok' => true];
    }

    public function processQueue(int $maxBroadcasts = 5): int
    {
        $sent = 0;
        foreach ($this->repo->listSendingBroadcasts($maxBroadcasts) as $broadcast) {
            $sent += $this->processBroadcast((string) $broadcast['id']);
        }

        return $sent;
    }

    /**
     * Send the next batch for one broadcast. Returns how many messages Meta accepted.
     */
    public function processBroadcast(string $broadcastId): int
    {
        if (!$this->repo->acquireLock('lb_broadcast_' . $broadcastId)) {
            return 0;
        }

        try {
            return $this->sendNextBatch($broadcastId);
        } finally {
            $this->repo->releaseLock('lb_broadcast_' . $broadcastId);
        }
    }

    private function sendNextBatch(string $broadcastId): int
    {
        $broadcast = $this->repo->findBroadcast($broadcastId);
        if ($broadcast === null || (string) $broadcast['status'] !== 'sending') {
            return 0;
        }
        $businessId = (string) $broadcast['business_id'];

        $account = RepositoryFactory::connectedAccounts()->findById((string) $broadcast['connected_account_id']);
        if ($account === null || empty($account['is_active'])) {
            $this->pause($broadcastId, 'Your WhatsApp number is disconnected. Reconnect it in Social Accounts, then resume.');

            return 0;
        }

        $template = $this->repo->findTemplate((string) $broadcast['template_id'], $businessId);
        if ($template === null || (string) $template['status'] !== 'APPROVED') {
            $this->pause($broadcastId, 'The template is no longer approved by Meta.');

            return 0;
        }

        $limit = $this->dailyLimitForAccount($account);
        $batchSize = self::BATCH_SIZE;
        if ($limit !== null) {
            $remaining = $limit - $this->repo->uniqueRecipientsLast24h((string) $account['id']);
            if ($remaining <= 0) {
                $this->repo->setBroadcastStatus($broadcastId, 'sending', 'Daily limit reached. Sending continues automatically when it resets.');

                return 0;
            }
            $batchSize = min($batchSize, $remaining);
        }

        $recipients = $this->repo->nextQueuedRecipients($broadcastId, $batchSize);
        if ($recipients === []) {
            $this->repo->setBroadcastStatus($broadcastId, 'completed');

            return 0;
        }

        $business = RepositoryFactory::businesses()->findById($businessId);
        $ownerId = (string) ($business['user_id'] ?? '');
        $tokens = new TokenService();
        $cost = self::tokenCost(count($recipients));
        $batchRef = Uuid::v4();
        $ledgerMeta = ['business_id' => $businessId, 'broadcast_id' => $broadcastId];
        if ($ownerId === '' || !$tokens->spend($ownerId, $cost, 'broadcast_send', 'broadcast', $batchRef, $ledgerMeta)) {
            $this->pause($broadcastId, 'You ran out of tokens. Top up on Billing, then resume.');

            return 0;
        }

        $accepted = 0;
        $notCharged = 0;
        $stopReason = null;
        foreach ($recipients as $index => $recipient) {
            $result = $this->sendTemplate($account, $template, $recipient, (string) $broadcast['name_fallback']);
            if ($result['ok']) {
                $this->repo->markRecipientSent((string) $recipient['id'], (string) $result['message_id']);
                $accepted++;
                continue;
            }

            $code = (string) ($result['code'] ?? '');
            $stopReason = self::stopReasonFor($code);
            if ($stopReason !== null) {
                $notCharged += count($recipients) - $index;
                break;
            }

            $this->repo->markRecipientFailed((string) $recipient['id'], $code, (string) ($result['message'] ?? 'Not delivered'));
            $notCharged++;
        }

        $charged = $cost - self::tokenCost($notCharged);
        if ($notCharged > 0) {
            $tokens->refund($ownerId, self::tokenCost($notCharged), 'broadcast_refund', 'broadcast', $batchRef, $ledgerMeta);
        }
        $this->repo->addTokensCharged($broadcastId, max(0, $charged));

        if ($stopReason !== null) {
            if ($stopReason === 'rate_limited') {
                $this->repo->setBroadcastStatus($broadcastId, 'sending', 'Meta asked us to slow down. Sending continues automatically.');
            } else {
                $this->pause($broadcastId, $stopReason);
            }
        } elseif ($this->repo->nextQueuedRecipients($broadcastId, 1) === []) {
            $this->repo->setBroadcastStatus($broadcastId, 'completed');
        } elseif ((string) ($broadcast['pause_reason'] ?? '') !== '') {
            $this->repo->setBroadcastStatus($broadcastId, 'sending');
        }

        return $accepted;
    }

    /**
     * @param array<string, mixed> $account
     * @param array<string, mixed> $template
     * @param array<string, mixed> $recipient
     * @return array{ok: bool, message_id?: string, code?: string, message?: string}
     */
    private function sendTemplate(array $account, array $template, array $recipient, string $fallback): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => (string) $recipient['phone'],
            'type' => 'template',
            'template' => [
                'name' => (string) $template['name'],
                'language' => ['code' => (string) $template['language']],
            ],
        ];

        if (!empty($template['uses_name'])) {
            $first = trim(explode(' ', trim((string) ($recipient['name'] ?? '')))[0] ?? '');
            $payload['template']['components'] = [[
                'type' => 'body',
                'parameters' => [['type' => 'text', 'text' => $first !== '' ? mb_substr($first, 0, 40) : $fallback]],
            ]];
        }

        $response = meta_graph_post_json(
            (string) $account['platform_account_id'] . '/messages',
            (string) $account['access_token'],
            $payload
        );

        $messageId = (string) ($response['messages'][0]['id'] ?? '');
        if ($messageId !== '') {
            return ['ok' => true, 'message_id' => $messageId];
        }

        return [
            'ok' => false,
            'code' => (string) ($response['error']['code'] ?? 'unknown'),
            'message' => self::metaErrorMessage($response, 'Not delivered'),
        ];
    }

    private static function stopReasonFor(string $code): ?string
    {
        return match ($code) {
            '131042' => 'Meta could not charge your WhatsApp account. Add or fix the payment method in Meta Business, then resume.',
            '190', '102', '10', '200' => 'Your WhatsApp connection expired. Reconnect WhatsApp in Social Accounts, then resume.',
            '132000', '132001', '132005', '132007', '132015', '132016' => 'Meta rejected the template for this send. Check the template, then resume.',
            '131031' => 'Meta has restricted your WhatsApp Business account. Check WhatsApp Manager for details.',
            '4', '80007', '130429', '131048', '131056' => 'rate_limited',
            default => null,
        };
    }

    private function pause(string $broadcastId, string $reason): void
    {
        $this->repo->setBroadcastStatus($broadcastId, 'paused', $reason);
    }

    /** @var array<string, int|null> */
    private array $limitCache = [];

    /**
     * @param array<string, mixed> $account
     */
    private function dailyLimitForAccount(array $account): ?int
    {
        $id = (string) $account['id'];
        if (!array_key_exists($id, $this->limitCache)) {
            $phone = meta_graph_get((string) $account['platform_account_id'], (string) $account['access_token'], [
                'fields' => 'messaging_limit_tier',
            ]);
            $this->limitCache[$id] = isset($phone['error'])
                ? self::DEFAULT_DAILY_LIMIT
                : self::parseLimitTier((string) ($phone['messaging_limit_tier'] ?? ''));
        }

        return $this->limitCache[$id];
    }

    // —— Webhooks ——

    /**
     * Delivery receipts and STOP replies from the WhatsApp webhook.
     *
     * @param array<string, mixed> $payload
     */
    public function handleWebhook(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ((is_array($entry) ? ($entry['changes'] ?? []) : []) as $change) {
                $value = is_array($change) ? ($change['value'] ?? null) : null;
                if (!is_array($value)) {
                    continue;
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    if (!is_array($status) || (string) ($status['id'] ?? '') === '') {
                        continue;
                    }
                    $error = is_array($status['errors'][0] ?? null) ? $status['errors'][0] : [];
                    $this->repo->applyDeliveryStatus(
                        (string) $status['id'],
                        strtolower((string) ($status['status'] ?? '')),
                        isset($error['code']) ? (string) $error['code'] : null,
                        isset($error['title']) ? (string) ($error['message'] ?? $error['title']) : null
                    );
                }

                $phoneNumberId = (string) ($value['metadata']['phone_number_id'] ?? '');
                foreach ($value['messages'] ?? [] as $message) {
                    if (!is_array($message)) {
                        continue;
                    }
                    $text = strtoupper(trim((string) ($message['text']['body'] ?? $message['button']['text'] ?? '')));
                    $text = trim(preg_replace('/[^A-Z ]+/', '', $text) ?? '');
                    if ($phoneNumberId === '' || !in_array($text, self::STOP_WORDS, true)) {
                        continue;
                    }
                    $account = RepositoryFactory::connectedAccounts()->findActiveByPlatformAccountId('whatsapp', $phoneNumberId);
                    $phone = self::normalizePhone((string) ($message['from'] ?? ''));
                    if ($account !== null && $phone !== null) {
                        $this->repo->unsubscribe((string) $account['business_id'], $phone);
                    }
                }
            }
        }
    }

    // —— Helpers ——

    private static function templateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $title) ?? '', '_'));
        $slug = $slug !== '' ? mb_substr($slug, 0, 40) : 'broadcast';

        return 'lb_' . $slug . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
    }

    /**
     * @param array<string, mixed> $response
     */
    private static function metaErrorMessage(array $response, string $fallback): string
    {
        $error = is_array($response['error'] ?? null) ? $response['error'] : [];
        $message = trim((string) ($error['error_user_msg'] ?? $error['error_data']['details'] ?? $error['message'] ?? ''));

        return $message !== '' ? mb_substr($message, 0, 240) : $fallback;
    }
}
