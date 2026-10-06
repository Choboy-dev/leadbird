<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/tiktok.php';

/**
 * TikTok Login Kit (v2) OAuth for connecting a TikTok account to a business.
 *
 * Access tokens live ~24 hours and refresh tokens ~365 days, so every caller that
 * needs a usable token should go through accessTokenForAccount().
 */
final class TikTokConnectService
{
    public const PLATFORM = 'tiktok';

    private const STATE_PURPOSE = 'tiktok_connect';
    private const TOKEN_REFRESH_SKEW_SECONDS = 300;

    public function isEnabled(): bool
    {
        return tiktok_is_enabled();
    }

    /**
     * Build the TikTok authorize URL and remember the connect context under `state`.
     */
    public function beginConnect(
        string $userId,
        string $returnTo,
        ?string $businessId = null,
        string $connectMode = 'connect'
    ): string {
        $this->assertConfigured();

        $ctx = new BusinessContext();
        if ($businessId !== null && $businessId !== '' && $ctx->userOwnsBusiness($userId, $businessId)) {
            $business = RepositoryFactory::businesses()->findById($businessId);
        } else {
            $business = $ctx->ensureOnboardingBusiness($userId);
        }

        if ($business === null) {
            throw new RuntimeException('Business not found.');
        }

        $businessId = (string) $business['id'];
        $ctx->setOnboardingBusinessId($businessId);

        $connectMode = $this->normalizeConnectMode($connectMode);
        if ($connectMode === 'connect' && RepositoryFactory::connectedAccounts()->hasAnyForPlatform($businessId, self::PLATFORM)) {
            $connectMode = PlanLimits::canConnectPlatform($userId, $businessId, self::PLATFORM) ? 'add' : 'reconnect';
        }

        $state = bin2hex(random_bytes(16));
        $expiresAt = (new DateTimeImmutable('now'))->modify('+15 minutes')->format('Y-m-d H:i:s');

        RepositoryFactory::oauthStates()->purgeExpired();
        RepositoryFactory::oauthStates()->create($state, '', $expiresAt, [
            'purpose' => self::STATE_PURPOSE,
            'user_id' => $userId,
            'business_id' => $businessId,
            'platform' => self::PLATFORM,
            'return_to' => $returnTo,
            'connect_mode' => $connectMode,
        ]);

        return TIKTOK_AUTHORIZE_URL . '?' . http_build_query([
            'client_key' => (string) tiktok_config()['client_key'],
            'scope' => tiktok_scopes(),
            'response_type' => 'code',
            'redirect_uri' => (string) tiktok_config()['redirect_uri'],
            'state' => $state,
        ]);
    }

    /**
     * Exchange the authorization code, store the account, and redirect back.
     */
    public function handleCallback(string $code, string $state): never
    {
        $row = RepositoryFactory::oauthStates()->consumeRow($state);
        if ($row === null) {
            throw new RuntimeException('Invalid or expired TikTok authorization. Please connect again.');
        }

        $context = is_array($row['context'] ?? null) ? $row['context'] : null;
        if ($context === null || ($context['purpose'] ?? '') !== self::STATE_PURPOSE) {
            throw new RuntimeException('Invalid TikTok authorization context.');
        }

        $userId = (string) ($context['user_id'] ?? '');
        $businessId = (string) ($context['business_id'] ?? '');
        $returnTo = (string) ($context['return_to'] ?? app_route('connected-accounts'));

        if ($userId === '' || $businessId === '') {
            throw new RuntimeException('TikTok authorization is missing the business it belongs to.');
        }

        $token = $this->exchangeCodeForToken($code);
        $account = $this->storeAccount($userId, $businessId, $token);

        $name = trim((string) ($account['display_name'] ?? ''));
        flash_set('success', $name !== '' ? $name . ' connected on TikTok.' : 'TikTok account connected.');

        redirect($returnTo);
    }

    /**
     * @return array{access_token: string, refresh_token: string, open_id: string, expires_in: int, refresh_expires_in: int, scope: string}
     */
    private function exchangeCodeForToken(string $code): array
    {
        $config = tiktok_config();

        $response = tiktok_oauth_post('oauth/token/', [
            'client_key' => (string) $config['client_key'],
            'client_secret' => (string) $config['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => (string) $config['redirect_uri'],
        ]);

        $error = tiktok_error_message($response);
        if ($error !== '') {
            throw new RuntimeException(tiktok_humanize_error($error));
        }

        $accessToken = trim((string) ($response['access_token'] ?? ''));
        if ($accessToken === '') {
            throw new RuntimeException('TikTok did not return an access token.');
        }

        return [
            'access_token' => $accessToken,
            'refresh_token' => trim((string) ($response['refresh_token'] ?? '')),
            'open_id' => trim((string) ($response['open_id'] ?? '')),
            'expires_in' => (int) ($response['expires_in'] ?? 86400),
            'refresh_expires_in' => (int) ($response['refresh_expires_in'] ?? 31536000),
            'scope' => trim((string) ($response['scope'] ?? '')),
        ];
    }

    /**
     * @param array{access_token: string, refresh_token: string, open_id: string, expires_in: int, refresh_expires_in: int, scope: string} $token
     * @return array<string, mixed>
     */
    private function storeAccount(string $userId, string $businessId, array $token): array
    {
        $profile = $this->fetchProfile($token['access_token'], $token['scope']);

        $openId = $token['open_id'] !== '' ? $token['open_id'] : trim((string) ($profile['open_id'] ?? ''));
        if ($openId === '') {
            throw new RuntimeException('TikTok did not identify the account. Please connect again.');
        }

        $planSlug = (new OnboardingService())->getActivePlanSlug($userId);

        $account = RepositoryFactory::connectedAccounts()->connectAccount(
            $businessId,
            self::PLATFORM,
            [
                'platform_account_id' => $openId,
                'display_name' => $this->displayNameFromProfile($profile, $openId),
                'profile_picture_url' => trim((string) ($profile['avatar_url_100'] ?? $profile['avatar_url'] ?? '')) ?: null,
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] !== '' ? $token['refresh_token'] : null,
                'token_expires_at' => $this->expiryTimestamp($token['expires_in']),
                'refresh_expires_at' => $this->expiryTimestamp($token['refresh_expires_in']),
                'scopes' => $token['scope'] !== '' ? $token['scope'] : null,
                'is_active' => true,
            ],
            PlanLimits::channelsPerPlatform($planSlug)
        );

        RepositoryFactory::connectedAccounts()->deactivateOtherBusinessAccounts(self::PLATFORM, $openId, $businessId);

        return $account;
    }

    /**
     * Usable access token for API calls, refreshed transparently when close to expiry.
     *
     * @param array<string, mixed> $account
     */
    public function accessTokenForAccount(array $account): string
    {
        $accessToken = trim((string) ($account['access_token'] ?? ''));
        if (!$this->tokenNeedsRefresh($account)) {
            return $accessToken;
        }

        $refreshToken = trim((string) ($account['refresh_token'] ?? ''));
        if ($refreshToken === '' || !tiktok_is_configured()) {
            return $accessToken;
        }

        $config = tiktok_config();
        $response = tiktok_oauth_post('oauth/token/', [
            'client_key' => (string) $config['client_key'],
            'client_secret' => (string) $config['client_secret'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        $error = tiktok_error_message($response);
        $fresh = trim((string) ($response['access_token'] ?? ''));
        if ($error !== '' || $fresh === '') {
            error_log('[TikTok] Token refresh failed: ' . ($error !== '' ? $error : 'no access token'));

            return $accessToken;
        }

        $accountId = trim((string) ($account['id'] ?? ''));
        if ($accountId !== '') {
            RepositoryFactory::connectedAccounts()->update($accountId, [
                'access_token' => $fresh,
                'refresh_token' => trim((string) ($response['refresh_token'] ?? '')) ?: $refreshToken,
                'token_expires_at' => $this->expiryTimestamp((int) ($response['expires_in'] ?? 86400)),
                'refresh_expires_at' => $this->expiryTimestamp((int) ($response['refresh_expires_in'] ?? 31536000)),
            ]);
        }

        return $fresh;
    }

    /**
     * @param array<string, mixed> $account
     */
    public function tokenNeedsRefresh(array $account): bool
    {
        if (trim((string) ($account['access_token'] ?? '')) === '') {
            return true;
        }

        $expiresAt = trim((string) ($account['token_expires_at'] ?? ''));
        if ($expiresAt === '') {
            return false;
        }

        try {
            $expiry = new DateTimeImmutable($expiresAt);
        } catch (Throwable) {
            return false;
        }

        return $expiry->getTimestamp() - self::TOKEN_REFRESH_SKEW_SECONDS <= time();
    }

    /**
     * True when the refresh token itself expired — the user has to reconnect.
     *
     * @param array<string, mixed> $account
     */
    public function needsReconnect(array $account): bool
    {
        $refreshExpiresAt = trim((string) ($account['refresh_expires_at'] ?? ''));
        if ($refreshExpiresAt === '') {
            return trim((string) ($account['refresh_token'] ?? '')) === '';
        }

        try {
            return (new DateTimeImmutable($refreshExpiresAt))->getTimestamp() <= time();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Live profile fields for a connected account (used to refresh names and avatars).
     *
     * @param array<string, mixed> $account
     * @return array<string, mixed>
     */
    public function profileForAccount(array $account): array
    {
        $token = $this->accessTokenForAccount($account);
        if ($token === '') {
            return [];
        }

        return $this->fetchProfile($token, (string) ($account['scopes'] ?? ''));
    }

    /**
     * Only ask for fields the account actually granted — TikTok rejects the whole
     * request when a field needs a scope the user did not approve.
     *
     * @return array<string, mixed>
     */
    private function fetchProfile(string $accessToken, string $grantedScopes = ''): array
    {
        $scopes = trim($grantedScopes) !== '' ? $grantedScopes : tiktok_scopes();

        $fields = ['open_id', 'union_id', 'avatar_url', 'avatar_url_100', 'display_name'];
        if (str_contains($scopes, 'user.info.profile')) {
            $fields[] = 'username';
            $fields[] = 'profile_deep_link';
        }

        $response = tiktok_api_get('user/info/', $accessToken, ['fields' => implode(',', $fields)]);

        $error = tiktok_error_message($response);
        if ($error !== '') {
            error_log('[TikTok] user/info failed: ' . $error);

            return [];
        }

        $user = $response['data']['user'] ?? null;

        return is_array($user) ? $user : [];
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function displayNameFromProfile(array $profile, string $openId): string
    {
        $username = trim((string) ($profile['username'] ?? ''));
        if ($username !== '') {
            return '@' . ltrim($username, '@');
        }

        $displayName = trim((string) ($profile['display_name'] ?? ''));
        if ($displayName !== '') {
            return $displayName;
        }

        return 'TikTok · ' . substr($openId, 0, 8);
    }

    /**
     * Revoke the token at TikTok, then mark the channel inactive.
     *
     * @param array<string, mixed> $account
     */
    public function disconnectAccount(array $account): void
    {
        $accountId = trim((string) ($account['id'] ?? ''));
        if ($accountId === '') {
            return;
        }

        $token = trim((string) ($account['access_token'] ?? ''));
        if ($token !== '' && tiktok_is_configured()) {
            $config = tiktok_config();
            $response = tiktok_oauth_post('oauth/revoke/', [
                'client_key' => (string) $config['client_key'],
                'client_secret' => (string) $config['client_secret'],
                'token' => $token,
            ]);

            $error = tiktok_error_message($response);
            if ($error !== '') {
                error_log('[TikTok] Revoke failed: ' . $error);
            }
        }

        RepositoryFactory::connectedAccounts()->update($accountId, [
            'is_active' => false,
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'refresh_expires_at' => null,
        ]);
    }

    public function defaultReturnTo(?string $userId): string
    {
        if ($userId !== null && $userId !== '' && (new OnboardingService())->isComplete($userId)) {
            return app_route('connected-accounts');
        }

        return app_route('onboarding/channels');
    }

    public function resolveReturnTo(?string $state, ?string $userId): string
    {
        if ($state !== null && $state !== '') {
            $row = RepositoryFactory::oauthStates()->findRowByState($state);
            $context = is_array($row['context'] ?? null) ? $row['context'] : null;
            $stored = trim((string) ($context['return_to'] ?? ''));
            if ($stored !== '') {
                return safe_return_url($stored, $this->defaultReturnTo($userId));
            }
        }

        return $this->defaultReturnTo($userId);
    }

    private function expiryTimestamp(int $secondsFromNow): ?string
    {
        if ($secondsFromNow <= 0) {
            return null;
        }

        return (new DateTimeImmutable('now'))
            ->modify('+' . $secondsFromNow . ' seconds')
            ->format('Y-m-d H:i:s');
    }

    private function normalizeConnectMode(string $mode): string
    {
        $mode = strtolower(trim($mode));

        return in_array($mode, ['connect', 'add', 'reconnect'], true) ? $mode : 'connect';
    }

    private function assertConfigured(): void
    {
        if (!tiktok_is_configured()) {
            throw new RuntimeException('TikTok is not configured yet. Add TIKTOK_CLIENT_KEY and TIKTOK_CLIENT_SECRET.');
        }

        if (!(bool) (tiktok_config()['enabled'] ?? false)) {
            throw new RuntimeException('TikTok connections are turned off right now.');
        }
    }
}
