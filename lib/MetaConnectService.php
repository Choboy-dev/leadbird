<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/meta.php';

/**
 * Meta OAuth for WhatsApp, Instagram, and Facebook Messenger channel connect.
 */
final class MetaConnectService
{
    private const SESSION_PENDING = 'meta_connect_pending';
    private const SESSION_BROWSE_CACHE = 'meta_browse_options';
    private const BROWSE_CACHE_TTL_SECONDS = 1800;

    private static bool $appWebhooksEnsured = false;

    public function beginConnect(
        string $userId,
        string $platform,
        string $returnTo,
        ?string $businessId = null,
        string $connectMode = 'connect',
        bool $forceOAuth = false
    ): string {
        $this->assertPlatform($platform);
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
        if ($connectMode === 'connect') {
            $activeCount = RepositoryFactory::connectedAccounts()->countActiveByBusinessAndPlatform($businessId, $platform);
            if ($activeCount > 0 && PlanLimits::canConnectPlatform($userId, $businessId, $platform)) {
                $connectMode = 'add';
            } elseif (RepositoryFactory::connectedAccounts()->hasAnyForPlatform($businessId, $platform)) {
                $connectMode = 'reconnect';
            }
        }

        if ($forceOAuth) {
            RepositoryFactory::businessMetaConnections()->deleteByBusinessId($businessId);
            $this->clearBrowseOptionsCache($businessId);
        }

        if (!$forceOAuth && $this->shouldSkipOAuthForBrowse($businessId, $platform, $connectMode)) {
            return $this->buildBrowseUrl($platform, $businessId, $returnTo);
        }

        $state = bin2hex(random_bytes(16));
        $expiresAt = (new DateTimeImmutable('now'))->modify('+15 minutes')->format('Y-m-d H:i:s');

        $forceRerequest = $this->shouldForceMetaRerequest($connectMode, $businessId, $platform, $forceOAuth);

        RepositoryFactory::oauthStates()->purgeExpired();
        RepositoryFactory::oauthStates()->create($state, '', $expiresAt, [
            'purpose' => 'meta_connect',
            'user_id' => $userId,
            'business_id' => $businessId,
            'platform' => $platform,
            'return_to' => $returnTo,
            'connect_mode' => $connectMode,
        ]);

        return $this->buildOAuthAuthorizeUrl($state, $platform, $forceRerequest);
    }

    private function buildOAuthAuthorizeUrl(string $state, string $platform, bool $forceRerequest): string
    {
        $meta = app_config()['meta'];
        if ((bool) ($meta['oauth_use_gdp_flow'] ?? false)) {
            return $this->buildGdpConsentUrl($state, $platform, $forceRerequest);
        }

        return 'https://www.facebook.com/' . meta_graph_version() . '/dialog/oauth?'
            . http_build_query($this->loginDialogQuery($state, $platform, $forceRerequest));
    }

    /**
     * Flockler / Hootsuite-style GDP consent (facebook.com/privacy/consent/gdp/).
     * dialog/oauth + page scopes on Login-for-Business apps always shows Meta's asset picker.
     */
    private function buildGdpConsentUrl(string $state, string $platform, bool $forceRerequest): string
    {
        $meta = app_config()['meta'];
        $appId = (string) ($meta['app_id'] ?? '');
        $redirectUri = (string) ($meta['redirect_uri'] ?? '');
        $scopeList = array_values(array_filter(array_map(
            'trim',
            explode(',', $this->connectScopesForPlatform($platform))
        )));

        $gdpParams = [
            'app_id' => $appId,
            'display' => 'page',
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scopeList,
            'state' => $state,
            'return_scopes' => false,
            'steps' => (object) [],
            'cui_gk' => '[PASS]:',
            'tp' => 'unspecified',
            'is_limited_login_shim' => false,
            'next' => 'confirm',
        ];

        if ($forceRerequest) {
            $gdpParams['auth_type'] = 'rerequest';
        }

        $parts = ['source=gdp_delegated'];
        foreach ($gdpParams as $key => $value) {
            if ($key === 'scope') {
                $encoded = json_encode($value, JSON_UNESCAPED_SLASHES);
            } elseif ($key === 'steps') {
                $encoded = '{}';
            } elseif (is_bool($value)) {
                $encoded = $value ? 'true' : 'false';
            } else {
                $encoded = (string) $value;
            }
            $parts[] = 'params[' . $key . ']=' . rawurlencode($encoded);
        }

        return 'https://www.facebook.com/privacy/consent/gdp/?' . implode('&', $parts);
    }

    private function normalizeConnectMode(string $mode): string
    {
        $mode = strtolower(trim($mode));

        return in_array($mode, ['connect', 'add', 'reconnect'], true) ? $mode : 'connect';
    }

    /**
     * @return array<string, string>
     */
    private function loginDialogQuery(string $state, string $platform, bool $forceRerequest): array
    {
        $meta = app_config()['meta'];

        $base = [
            'client_id' => (string) ($meta['app_id'] ?? ''),
            'redirect_uri' => (string) ($meta['redirect_uri'] ?? ''),
            'state' => $state,
            'response_type' => 'code',
            'display' => 'page',
        ];

        $useLoginConfig = $this->usesLoginConfigForPlatform($platform);
        $systemUserConfig = (bool) ($meta['oauth_system_user_config'] ?? false);

        if ($useLoginConfig) {
            $configId = $this->loginConfigIdForPlatform($platform);
            if ($configId !== '') {
                $base['config_id'] = $configId;
                $base['scope'] = $this->connectScopesForPlatform($platform);
                if ($systemUserConfig) {
                    $base['override_default_response_type'] = 'true';
                }
            } elseif (($meta['use_bundled_login_config'] ?? false) && trim((string) ($meta['login_config_id'] ?? '')) !== '') {
                $base['config_id'] = (string) $meta['login_config_id'];
                $base['scope'] = $this->connectScopesForPlatform($platform);
                if ($systemUserConfig) {
                    $base['override_default_response_type'] = 'true';
                }
            } else {
                $base['scope'] = $this->connectScopesForPlatform($platform);
            }
        } else {
            $base['scope'] = $this->connectScopesForPlatform($platform);
        }

        if ($platform === 'instagram') {
            $base['extras'] = json_encode(['setup' => ['channel' => 'IG_API_ONBOARDING']], JSON_UNESCAPED_SLASHES);
        }

        if ($forceRerequest && $platform === 'facebook') {
            $base['auth_type'] = 'reauthenticate';
        } elseif ($forceRerequest) {
            $base['auth_type'] = 'rerequest';
        }

        return $base;
    }

    private function usesLoginConfigForPlatform(string $platform): bool
    {
        $meta = app_config()['meta'];
        if (!(bool) ($meta['oauth_use_login_config'] ?? false)) {
            return false;
        }

        // Scope-based OAuth is required for Facebook so business_management and all Pages are granted.
        // Login Config IDs replace scope and often omit Business Manager Pages.
        return $platform !== 'facebook';
    }

    private function shouldSkipOAuthForBrowse(string $businessId, string $platform, string $connectMode): bool
    {
        if ($connectMode !== 'add') {
            return false;
        }

        $userToken = $this->resolveBusinessMetaToken($businessId);
        if ($userToken === null) {
            return false;
        }

        try {
            return $this->discoverPlatformOptions($platform, $userToken) !== [];
        } catch (Throwable $e) {
            error_log('[MetaConnect] Browse skip check failed for ' . $platform . ': ' . $e->getMessage());

            return false;
        }
    }

    private function shouldForceMetaRerequest(
        string $connectMode,
        string $businessId,
        string $platform,
        bool $forceOAuth = false
    ): bool {
        return $forceOAuth;
    }

    private function loginConfigIdForPlatform(string $platform): string
    {
        $meta = app_config()['meta'];

        return match ($platform) {
            'whatsapp' => trim((string) ($meta['login_config_id_whatsapp'] ?? '')),
            'instagram' => trim((string) ($meta['login_config_id_instagram'] ?? '')),
            'facebook' => trim((string) ($meta['login_config_id_facebook'] ?? '')),
            default => '',
        };
    }

    public function handleCallback(string $code, string $state): never
    {
        $row = RepositoryFactory::oauthStates()->consumeRow($state);
        if ($row === null) {
            throw new RuntimeException('Invalid or expired Meta OAuth state.');
        }

        $context = is_array($row['context'] ?? null) ? $row['context'] : null;
        if ($context === null || ($context['purpose'] ?? '') !== 'meta_connect') {
            throw new RuntimeException('Invalid Meta OAuth context.');
        }

        $platform = (string) ($context['platform'] ?? '');
        $businessId = (string) ($context['business_id'] ?? '');
        $returnTo = (string) ($context['return_to'] ?? app_route('onboarding/channels'));
        $connectMode = $this->normalizeConnectMode((string) ($context['connect_mode'] ?? 'connect'));

        $this->assertPlatform($platform);

        $shortToken = $this->exchangeCodeForToken($code);
        $userToken = meta_exchange_long_lived_token($shortToken);
        $this->saveBusinessMetaToken($businessId, $userToken);

        redirect($this->buildBrowseUrl($platform, $businessId, $returnTo));
    }

    public function buildBrowseUrl(string $platform, string $businessId, string $returnTo): string
    {
        return app_route('auth/meta/browse', [
            'platform' => $platform,
            'business_id' => $businessId,
            'return_to' => $returnTo,
        ]);
    }

    public function buildSetupUrl(string $platform, string $businessId, string $returnTo): string
    {
        return app_route('auth/meta/setup', [
            'platform' => $platform,
            'business_id' => $businessId,
            'return_to' => $returnTo,
        ]);
    }

    public function hasBusinessMetaToken(string $businessId): bool
    {
        return $this->resolveBusinessMetaToken($businessId) !== null;
    }

    public function resolveBusinessMetaToken(string $businessId): ?string
    {
        $row = RepositoryFactory::businessMetaConnections()->findByBusinessId($businessId);
        if ($row !== null) {
            $token = trim((string) ($row['access_token'] ?? ''));
            if ($token !== '') {
                return $token;
            }
        }

        $fallback = RepositoryFactory::connectedAccounts()->findAnyMetaUserTokenForBusiness($businessId);
        if ($fallback !== null) {
            $this->saveBusinessMetaToken($businessId, $fallback);
        }

        return $fallback;
    }

    private function saveBusinessMetaToken(string $businessId, string $accessToken, ?string $metaUserId = null): void
    {
        RepositoryFactory::businessMetaConnections()->upsert($businessId, [
            'access_token' => $accessToken,
            'meta_user_id' => $metaUserId ?? $this->resolveMetaUserId($accessToken),
        ]);
    }

    private function resolveMetaUserId(string $userToken): ?string
    {
        try {
            $data = meta_graph_get('me', $userToken, ['fields' => 'id']);
            $id = trim((string) ($data['id'] ?? ''));

            return $id !== '' ? $id : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function discoverPlatformOptions(string $platform, string $userToken): array
    {
        $this->assertPlatform($platform);

        return match ($platform) {
            'whatsapp' => $this->discoverWhatsAppNumbers($userToken),
            'instagram' => $this->discoverInstagramAccounts($userToken),
            'facebook' => $this->discoverFacebookPages($userToken),
        };
    }

    /**
     * @return array{
     *   accounts: list<array{key: string, label: string, subtitle: string, platform_account_id: string, is_connected: bool}>,
     *   can_add_more: bool,
     *   has_meta_token: bool
     * }
     */
    public function getBrowseAccounts(string $userId, string $businessId, string $platform): array
    {
        $this->assertPlatform($platform);

        if (!(new BusinessContext())->userOwnsBusiness($userId, $businessId)) {
            throw new RuntimeException('Business not found.');
        }

        $userToken = $this->resolveBusinessMetaToken($businessId);
        if ($userToken === null) {
            return [
                'accounts' => [],
                'can_add_more' => PlanLimits::canConnectPlatform($userId, $businessId, $platform),
                'has_meta_token' => false,
            ];
        }

        $options = $this->discoverPlatformOptions($platform, $userToken);
        $this->cacheBrowseOptions($businessId, $platform, $userToken, $options);
        $connectedIds = RepositoryFactory::connectedAccounts()->listActivePlatformAccountIds($businessId, $platform);
        $canAddMore = PlanLimits::canConnectPlatform($userId, $businessId, $platform);

        $accounts = [];
        foreach ($options as $key => $option) {
            if (!is_array($option)) {
                continue;
            }

            $accountId = (string) ($option['platform_account_id'] ?? '');
            $row = [
                'key' => (string) $key,
                'label' => (string) ($option['label'] ?? $key),
                'subtitle' => $accountId,
                'platform_account_id' => $accountId,
                'is_connected' => $accountId !== '' && in_array($accountId, $connectedIds, true),
            ];

            if ($platform === 'instagram') {
                $username = trim((string) ($option['username'] ?? ''));
                $pageName = trim((string) ($option['page_name'] ?? ''));
                $row['username'] = $username;
                $row['page_name'] = $pageName;
                $row['profile_picture_url'] = trim((string) ($option['profile_picture_url'] ?? ''));
                $row['subtitle'] = $username !== '' ? '@' . ltrim($username, '@') : $accountId;
                $row['search_text'] = strtolower(trim($pageName . ' ' . $username . ' ' . $accountId));
            } else {
                $row['profile_picture_url'] = trim((string) ($option['profile_picture_url'] ?? ''));
                $row['search_text'] = strtolower(trim($row['label'] . ' ' . $accountId));
            }

            $accounts[] = $row;
        }

        usort($accounts, static function (array $a, array $b): int {
            $aKey = (string) ($a['page_name'] ?? $a['label'] ?? '');
            $bKey = (string) ($b['page_name'] ?? $b['label'] ?? '');

            return strcasecmp($aKey, $bKey);
        });

        return [
            'accounts' => $accounts,
            'can_add_more' => $canAddMore,
            'has_meta_token' => true,
        ];
    }

    /**
     * @return array{label: string, platform_account_id: string, is_connected: bool, can_add_more: bool}
     */
    public function attachAccount(string $userId, string $businessId, string $platform, string $optionKey): array
    {
        $this->assertPlatform($platform);

        if (!(new BusinessContext())->userOwnsBusiness($userId, $businessId)) {
            throw new RuntimeException('Business not found.');
        }

        $userToken = $this->resolveBusinessMetaToken($businessId);
        if ($userToken === null) {
            throw new RuntimeException('Meta is not linked to this business. Sign in with Meta first.');
        }

        $option = $this->resolveBrowseOption($businessId, $platform, $userToken, $optionKey);
        $accountId = (string) ($option['platform_account_id'] ?? '');
        if ($accountId === '') {
            throw new RuntimeException('Incomplete account data from Meta.');
        }

        $existing = RepositoryFactory::connectedAccounts()->findByBusinessPlatformAccount($businessId, $platform, $accountId);
        if ($existing !== null && (int) ($existing['is_active'] ?? 0) === 1) {
            return [
                'label' => (string) ($option['label'] ?? $accountId),
                'platform_account_id' => $accountId,
                'is_connected' => true,
                'can_add_more' => PlanLimits::canConnectPlatform($userId, $businessId, $platform),
            ];
        }

        $this->finalizeConnection($businessId, $platform, $option, $userToken);

        return [
            'label' => (string) ($option['label'] ?? $accountId),
            'platform_account_id' => $accountId,
            'is_connected' => true,
            'can_add_more' => PlanLimits::canConnectPlatform($userId, $businessId, $platform),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPendingSelection(): ?array
    {
        $pending = $_SESSION[self::SESSION_PENDING] ?? null;
        if (!is_array($pending)) {
            return null;
        }

        if ((int) ($pending['expires_at'] ?? 0) < time()) {
            unset($_SESSION[self::SESSION_PENDING]);
            return null;
        }

        return $pending;
    }

    public function clearPendingSelection(): void
    {
        unset($_SESSION[self::SESSION_PENDING]);
    }

    public function finalizeFromSelection(string $optionKey): void
    {
        $pending = $this->getPendingSelection();
        if ($pending === null) {
            throw new RuntimeException('Channel selection session expired. Please connect again.');
        }

        $options = is_array($pending['options'] ?? null) ? $pending['options'] : [];
        if (!isset($options[$optionKey]) || !is_array($options[$optionKey])) {
            throw new RuntimeException('Invalid channel selection.');
        }

        $this->finalizeConnection(
            (string) $pending['business_id'],
            (string) $pending['platform'],
            $options[$optionKey],
            (string) $pending['user_token']
        );

        $returnTo = (string) ($pending['return_to'] ?? app_route('onboarding/channels'));
        $connectMode = $this->normalizeConnectMode((string) ($pending['connect_mode'] ?? 'connect'));
        $this->clearPendingSelection();

        flash_set('success', $this->successMessageForPlatform((string) $pending['platform'], $connectMode));
        redirect($returnTo);
    }

    /**
     * @param array<string, array<string, mixed>> $options
     * @return array<string, array<string, mixed>>
     */
    private function filterUnconnectedOptions(string $businessId, string $platform, array $options): array
    {
        $connectedIds = RepositoryFactory::connectedAccounts()->listActivePlatformAccountIds($businessId, $platform);
        if ($connectedIds === []) {
            return $options;
        }

        $filtered = [];
        foreach ($options as $key => $option) {
            if (!is_array($option)) {
                continue;
            }

            $accountId = (string) ($option['platform_account_id'] ?? '');
            if ($accountId !== '' && !in_array($accountId, $connectedIds, true)) {
                $filtered[$key] = $option;
            }
        }

        return $filtered;
    }

    private function successMessageForPlatform(string $platform, string $connectMode): string
    {
        $label = match ($platform) {
            'facebook' => 'Facebook Page',
            'instagram' => 'Instagram',
            'whatsapp' => 'WhatsApp',
            default => ucfirst($platform),
        };

        return $connectMode === 'add'
            ? $label . ' account added successfully.'
            : $label . ' connected successfully.';
    }

    /**
     * @param array<string, mixed> $option
     */
    public function finalizeConnection(string $businessId, string $platform, array $option, string $userAccessToken): void
    {
        $this->assertPlatform($platform);
        $this->assertChannelLimit($businessId, $platform);

        $accountId = (string) ($option['platform_account_id'] ?? '');
        $accessToken = trim((string) ($option['access_token'] ?? ''));
        if ($platform === 'facebook') {
            if ($accessToken === '') {
                $accessToken = trim($this->fetchPageAccessToken($userAccessToken, $accountId));
            }
            if ($accessToken !== '') {
                $accessToken = meta_exchange_long_lived_token($accessToken);
            }
        }
        if ($accessToken === '') {
            $accessToken = $userAccessToken;
        }
        if ($accountId === '' || $accessToken === '') {
            throw new RuntimeException('Incomplete account data from Meta.');
        }

        $businessRow = RepositoryFactory::businesses()->findById($businessId);
        $ownerUserId = is_array($businessRow) ? (string) ($businessRow['user_id'] ?? '') : '';
        $planSlug = (new OnboardingService())->getActivePlanSlug($ownerUserId) ?? 'starter';

        RepositoryFactory::connectedAccounts()->connectAccount(
            $businessId,
            $platform,
            [
                'platform_account_id' => $accountId,
                'linked_page_id' => $platform === 'instagram'
                    ? trim((string) ($option['page_id'] ?? '')) ?: null
                    : ($platform === 'facebook' ? $accountId : null),
                'display_name' => $this->displayNameFromOption($platform, $option),
                'profile_picture_url' => $this->profilePictureFromOption($platform, $option, $accessToken),
                'access_token' => $accessToken,
                'refresh_token' => $userAccessToken,
                'is_active' => true,
            ],
            PlanLimits::channelsPerPlatform($planSlug)
        );

        if ($accountId !== '') {
            RepositoryFactory::connectedAccounts()->deactivateOtherBusinessAccounts($platform, $accountId, $businessId);
        }

        if ($platform === 'whatsapp') {
            meta_subscribe_app_object_webhooks('whatsapp_business_account', 'messages');

            $wabaId = (string) ($option['waba_id'] ?? '');
            if ($wabaId !== '') {
                $subscribed = meta_subscribe_waba_webhooks($wabaId, $userAccessToken);
                if (!$subscribed) {
                    error_log('[MetaConnect] WABA webhook subscribe failed waba=' . $wabaId . ' business=' . $businessId);
                }
            }
        } else {
            if (in_array($platform, ['instagram', 'facebook'], true) && !self::$appWebhooksEnsured) {
                meta_ensure_messaging_app_webhooks();
                self::$appWebhooksEnsured = true;
            }

            $pageId = (string) ($option['page_id'] ?? $accountId);
            $subscribed = meta_subscribe_page_webhooks($pageId, $accessToken);
            if (!$subscribed) {
                error_log('[MetaConnect] Page webhook subscribe failed for ' . $platform . ' page=' . $pageId . ' business=' . $businessId);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveBrowseOption(string $businessId, string $platform, string $userToken, string $optionKey): array
    {
        $options = $this->getCachedBrowseOptions($businessId, $platform, $userToken);
        if ($options !== null && isset($options[$optionKey]) && is_array($options[$optionKey])) {
            return $options[$optionKey];
        }

        $options = $this->discoverPlatformOptions($platform, $userToken);
        $this->cacheBrowseOptions($businessId, $platform, $userToken, $options);

        if (!isset($options[$optionKey]) || !is_array($options[$optionKey])) {
            throw new RuntimeException('That account is no longer available from Meta. Refresh and try again.');
        }

        return $options[$optionKey];
    }

    /**
     * @param array<string, array<string, mixed>> $options
     */
    private function cacheBrowseOptions(string $businessId, string $platform, string $userToken, array $options): void
    {
        if (!isset($_SESSION[self::SESSION_BROWSE_CACHE]) || !is_array($_SESSION[self::SESSION_BROWSE_CACHE])) {
            $_SESSION[self::SESSION_BROWSE_CACHE] = [];
        }

        if (!isset($_SESSION[self::SESSION_BROWSE_CACHE][$businessId]) || !is_array($_SESSION[self::SESSION_BROWSE_CACHE][$businessId])) {
            $_SESSION[self::SESSION_BROWSE_CACHE][$businessId] = [];
        }

        $_SESSION[self::SESSION_BROWSE_CACHE][$businessId][$platform] = [
            'token_hash' => hash('sha256', $userToken),
            'options' => $options,
            'expires_at' => time() + self::BROWSE_CACHE_TTL_SECONDS,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>|null
     */
    private function getCachedBrowseOptions(string $businessId, string $platform, string $userToken): ?array
    {
        $entry = $_SESSION[self::SESSION_BROWSE_CACHE][$businessId][$platform] ?? null;
        if (!is_array($entry)) {
            return null;
        }

        if ((int) ($entry['expires_at'] ?? 0) < time()) {
            unset($_SESSION[self::SESSION_BROWSE_CACHE][$businessId][$platform]);

            return null;
        }

        if ((string) ($entry['token_hash'] ?? '') !== hash('sha256', $userToken)) {
            return null;
        }

        $options = $entry['options'] ?? null;

        return is_array($options) ? $options : null;
    }

    private function clearBrowseOptionsCache(?string $businessId = null): void
    {
        if ($businessId !== null && $businessId !== '') {
            unset($_SESSION[self::SESSION_BROWSE_CACHE][$businessId]);

            return;
        }

        unset($_SESSION[self::SESSION_BROWSE_CACHE]);
    }

    /** @return list<array<string, mixed>> */
    public function listChannelsForBusiness(string $businessId): array
    {
        return RepositoryFactory::connectedAccounts()->listByBusinessId($businessId, false);
    }

    public function revokeAllChannelsForBusiness(string $businessId): void
    {
        foreach ($this->listChannelsForBusiness($businessId) as $account) {
            $this->revokeChannelConnection($account);
        }
    }

    /**
     * @param array<string, mixed> $account
     */
    public function revokeChannelConnection(array $account): void
    {
        $platform = (string) ($account['platform'] ?? '');
        $token = (string) ($account['access_token'] ?? '');
        $platformAccountId = (string) ($account['platform_account_id'] ?? '');

        if ($token === '' || $platformAccountId === '') {
            return;
        }

        match ($platform) {
            'facebook' => meta_unsubscribe_app_webhooks($platformAccountId, $token),
            'instagram' => $this->unsubscribeInstagram($platformAccountId, $token),
            'whatsapp' => $this->unsubscribeWhatsApp($platformAccountId, $token),
            default => null,
        };
    }

    /**
     * Disconnect a channel in Lead Bird. Does not revoke the shared Meta login used by other channels.
     *
     * @param array<string, mixed> $account
     */
    public function disconnectAccount(array $account): void
    {
        $accountId = trim((string) ($account['id'] ?? ''));
        if ($accountId === '') {
            return;
        }

        $this->revokeChannelConnection($account);
        RepositoryFactory::connectedAccounts()->update($accountId, ['is_active' => false]);
    }

    private function unsubscribeInstagram(string $instagramAccountId, string $pageToken): void
    {
        $data = meta_graph_get('me/accounts', $pageToken, [
            'fields' => 'id,instagram_business_account',
            'limit' => 50,
        ]);

        foreach ($data['data'] ?? [] as $page) {
            if (!is_array($page)) {
                continue;
            }

            $ig = $page['instagram_business_account'] ?? null;
            $pageId = (string) ($page['id'] ?? '');
            if (!is_array($ig) || $pageId === '') {
                continue;
            }

            if ((string) ($ig['id'] ?? '') === $instagramAccountId) {
                meta_unsubscribe_app_webhooks($pageId, $pageToken);

                return;
            }
        }

        meta_unsubscribe_app_webhooks($instagramAccountId, $pageToken);
    }

    private function unsubscribeWhatsApp(string $phoneNumberId, string $userToken): void
    {
        $businesses = meta_graph_get('me/businesses', $userToken, [
            'fields' => 'id',
            'limit' => 25,
        ]);

        foreach ($businesses['data'] ?? [] as $business) {
            if (!is_array($business)) {
                continue;
            }

            $metaBusinessId = (string) ($business['id'] ?? '');
            if ($metaBusinessId === '') {
                continue;
            }

            $wabas = meta_graph_get($metaBusinessId . '/owned_whatsapp_business_accounts', $userToken, [
                'fields' => 'id',
                'limit' => 25,
            ]);

            foreach ($wabas['data'] ?? [] as $waba) {
                if (!is_array($waba)) {
                    continue;
                }

                $wabaId = (string) ($waba['id'] ?? '');
                if ($wabaId === '') {
                    continue;
                }

                $phones = meta_graph_get($wabaId . '/phone_numbers', $userToken, [
                    'fields' => 'id',
                    'limit' => 25,
                ]);

                foreach ($phones['data'] ?? [] as $phone) {
                    if (!is_array($phone)) {
                        continue;
                    }

                    if ((string) ($phone['id'] ?? '') === $phoneNumberId) {
                        meta_unsubscribe_app_webhooks($wabaId, $userToken);

                        return;
                    }
                }
            }
        }
    }

    private function assertChannelLimit(string $businessId, string $platform): void
    {
        $business = RepositoryFactory::businesses()->findById($businessId);
        if ($business === null) {
            throw new RuntimeException('Business not found.');
        }

        $userId = (string) ($business['user_id'] ?? '');
        if (!PlanLimits::canConnectPlatform($userId, $businessId, $platform)) {
            throw new RuntimeException(
                'This business already has the maximum ' . $platform . ' accounts for your plan. Upgrade or disconnect one first.'
            );
        }
    }

    private function exchangeCodeForToken(string $code): string
    {
        $config = app_config()['meta'];
        $query = http_build_query([
            'client_id' => $config['app_id'] ?? '',
            'client_secret' => $config['app_secret'] ?? '',
            'redirect_uri' => $config['redirect_uri'] ?? '',
            'code' => $code,
        ]);

        $ch = curl_init('https://graph.facebook.com/' . meta_graph_version() . '/oauth/access_token?' . $query);
        if ($ch === false) {
            throw new RuntimeException('Failed to start Meta token exchange.');
        }

        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($response) || $status < 200 || $status >= 300) {
            error_log('[Meta] Token exchange failed HTTP ' . $status . ' body=' . (is_string($response) ? $response : ''));
            throw new RuntimeException('Meta token exchange failed.');
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['access_token'])) {
            error_log('[Meta] Token exchange missing access_token body=' . (is_string($response) ? $response : ''));
            throw new RuntimeException('Meta did not return an access token.');
        }

        return (string) $decoded['access_token'];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function discoverFacebookPages(string $userToken): array
    {
        return array_merge(
            $this->discoverFacebookFromPagesEndpoint($userToken),
            $this->discoverFacebookFromBusinessPortfolioPages($userToken),
            $this->discoverFacebookFromGranularScopes($userToken)
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function discoverFacebookFromPagesEndpoint(string $userToken): array
    {
        $options = [];
        $path = 'me/accounts';
        $query = [
            'fields' => 'id,name,access_token,picture.type(large)',
            'limit' => 100,
        ];

        while ($path !== '') {
            $data = str_starts_with($path, 'http')
                ? $this->metaGraphGetUrl($path, $userToken)
                : meta_graph_get($path, $userToken, $query);

            if (isset($data['error'])) {
                error_log('[MetaConnect] me/accounts (facebook) failed: ' . json_encode($data['error']));
                break;
            }

            foreach ($data['data'] ?? [] as $page) {
                if (!is_array($page)) {
                    continue;
                }

                $option = $this->buildFacebookPageOption($page, $userToken);
                if ($option !== null) {
                    $options[$option['key']] = $option['row'];
                }
            }

            $next = trim((string) ($data['paging']['next'] ?? ''));
            $path = $next;
            $query = [];
        }

        return $options;
    }

    /**
     * Pages owned by or shared with a Business Portfolio (requires business_management).
     *
     * @return array<string, array<string, mixed>>
     */
    private function discoverFacebookFromBusinessPortfolioPages(string $userToken): array
    {
        $options = [];
        $businesses = meta_graph_get('me/businesses', $userToken, [
            'fields' => 'id,name',
            'limit' => 25,
        ]);

        if (isset($businesses['error'])) {
            error_log('[MetaConnect] me/businesses failed: ' . json_encode($businesses['error']));

            return [];
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
                    'fields' => 'id,name,access_token,picture.type(large)',
                    'limit' => 100,
                ]);

                if (isset($pages['error'])) {
                    error_log('[MetaConnect] ' . $businessId . '/' . $edge . ' failed: ' . json_encode($pages['error']));
                    continue;
                }

                foreach ($pages['data'] ?? [] as $page) {
                    if (!is_array($page)) {
                        continue;
                    }

                    $option = $this->buildFacebookPageOption($page, $userToken);
                    if ($option !== null) {
                        $options[$option['key']] = $option['row'];
                    }
                }
            }
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $page
     * @return array{key: string, row: array<string, mixed>}|null
     */
    private function buildFacebookPageOption(array $page, string $userToken): ?array
    {
        $pageId = (string) ($page['id'] ?? '');
        if ($pageId === '') {
            return null;
        }

        $pageToken = trim((string) ($page['access_token'] ?? ''));

        $label = (string) ($page['name'] ?? 'Facebook Page');
        $row = [
            'label' => $label,
            'platform_account_id' => $pageId,
            'page_id' => $pageId,
            'access_token' => $pageToken,
            'profile_picture_url' => $this->profilePictureFromPageData($page),
        ];

        if ($row['profile_picture_url'] === '' && $pageToken !== '') {
            $row['profile_picture_url'] = $this->fetchFacebookPagePicture($pageId, $pageToken);
        }

        if ($row['access_token'] === '' && $label === 'Facebook Page') {
            $nameOnly = meta_graph_get($pageId, $userToken, ['fields' => 'name']);
            if (!isset($nameOnly['error'])) {
                $row['label'] = (string) ($nameOnly['name'] ?? $label);
            }
        }

        if ($row['access_token'] === '') {
            error_log('[MetaConnect] Page listed without token (grant on Meta): ' . $pageId . ' ' . $row['label']);
        }

        return [
            'key' => 'page_' . $pageId,
            'row' => $row,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metaGraphGetUrl(string $url, string $userToken): array
    {
        $separator = str_contains($url, '?') ? '&' : '?';
        $fetchUrl = $url . $separator . 'access_token=' . rawurlencode($userToken);

        $ch = curl_init($fetchUrl);
        if ($ch === false) {
            return ['error' => ['message' => 'Could not reach Meta.']];
        }

        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($response)) {
            return ['error' => ['message' => 'Could not reach Meta.']];
        }

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : ['error' => ['message' => 'Invalid response from Meta.']];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function discoverFacebookFromGranularScopes(string $userToken): array
    {
        $pageIds = $this->granularTargetIds($this->fetchGranularScopes($userToken), ['pages_']);
        if ($pageIds === []) {
            return [];
        }

        $options = [];
        foreach ($pageIds as $pageId) {
            $page = meta_graph_get($pageId, $userToken, ['fields' => 'id,name,access_token']);
            if (isset($page['error'])) {
                error_log('[MetaConnect] page lookup failed id=' . $pageId . ': ' . json_encode($page['error']));
                continue;
            }

            $resolvedPageId = (string) ($page['id'] ?? $pageId);
            $option = $this->buildFacebookPageOption([
                'id' => $resolvedPageId,
                'name' => $page['name'] ?? 'Facebook Page',
                'access_token' => $page['access_token'] ?? '',
            ], $userToken);
            if ($option !== null) {
                $options[$option['key']] = $option['row'];
            }
        }

        return $options;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function discoverInstagramAccounts(string $userToken): array
    {
        $options = $this->discoverInstagramFromPagesEndpoint($userToken);
        $options = array_merge($options, $this->discoverInstagramFromGranularScopes($userToken));
        if ($options !== []) {
            return $options;
        }

        error_log('[MetaConnect] Instagram discovery empty. granular=' . json_encode($this->fetchGranularScopes($userToken)));

        return [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function discoverInstagramFromPagesEndpoint(string $userToken): array
    {
        $data = meta_graph_get('me/accounts', $userToken, [
            'fields' => 'id,name,access_token,instagram_business_account{id,username,profile_picture_url}',
            'limit' => 50,
        ]);

        if (isset($data['error'])) {
            error_log('[MetaConnect] me/accounts failed: ' . json_encode($data['error']));
        }

        $options = [];
        foreach ($data['data'] ?? [] as $page) {
            if (!is_array($page)) {
                continue;
            }

            $ig = $page['instagram_business_account'] ?? null;
            if (!is_array($ig)) {
                continue;
            }

            $option = $this->buildInstagramOptionFromPageAndIg($userToken, $page, $ig);
            if ($option !== null) {
                $options['ig_' . $option['platform_account_id']] = $option;
            }
        }

        return $options;
    }

    /**
     * Login for Business often grants Instagram assets via granular scopes instead of nested page data.
     *
     * @return array<string, array<string, mixed>>
     */
    private function discoverInstagramFromGranularScopes(string $userToken): array
    {
        $granular = $this->fetchGranularScopes($userToken);
        $igIds = $this->granularTargetIds($granular, ['instagram_']);
        if ($igIds === []) {
            return [];
        }

        $pageIds = $this->granularTargetIds($granular, ['pages_']);
        $pages = $this->fetchGrantedPagesMap($userToken, $pageIds);

        $options = [];
        foreach ($igIds as $igId) {
            $pageId = '';
            $pageToken = '';
            $pageName = 'Page';

            foreach ($pages as $page) {
                $linkedIg = $page['instagram_business_account'] ?? null;
                if (!is_array($linkedIg) || (string) ($linkedIg['id'] ?? '') !== $igId) {
                    continue;
                }

                $pageId = (string) ($page['id'] ?? '');
                $pageToken = $this->resolvePageAccessToken($userToken, $page);
                $pageName = (string) ($page['name'] ?? 'Page');
                break;
            }

            if ($pageId === '' && $pageIds !== []) {
                $candidateId = $pageIds[0];
                $page = $pages[$candidateId] ?? [];
                $pageId = (string) ($page['id'] ?? $candidateId);
                $pageToken = $this->resolvePageAccessToken($userToken, $page);
                $pageName = (string) ($page['name'] ?? 'Page');
            }

            if ($pageToken === '' && $pageId !== '') {
                $pageToken = $this->fetchPageAccessToken($userToken, $pageId);
            }

            $igProfile = meta_graph_get($igId, $userToken, ['fields' => 'id,username,profile_picture_url']);
            $username = trim((string) ($igProfile['username'] ?? ''));
            if ($username === '') {
                $username = 'Instagram';
            }
            $profilePicture = trim((string) ($igProfile['profile_picture_url'] ?? ''));

            if ($pageToken === '') {
                error_log('[MetaConnect] Missing page token for IG ' . $igId . '; falling back to user token');
                $pageToken = $userToken;
            }

            if ($pageId === '') {
                $pageId = $pageIds[0] ?? $igId;
            }

            $longPageToken = meta_exchange_long_lived_token($pageToken);
            $options['ig_' . $igId] = [
                'label' => '@' . $username,
                'platform_account_id' => $igId,
                'page_id' => $pageId,
                'page_name' => $pageName,
                'username' => $username,
                'profile_picture_url' => $profilePicture,
                'access_token' => $longPageToken,
            ];
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $ig
     * @return array<string, mixed>|null
     */
    private function buildInstagramOptionFromPageAndIg(string $userToken, array $page, array $ig): ?array
    {
        $igId = (string) ($ig['id'] ?? '');
        $pageId = (string) ($page['id'] ?? '');
        if ($igId === '' || $pageId === '') {
            return null;
        }

        $pageToken = $this->resolvePageAccessToken($userToken, $page);
        if ($pageToken === '') {
            return null;
        }

        $username = trim((string) ($ig['username'] ?? ''));
        if ($username === '') {
            $igProfile = meta_graph_get($igId, $userToken, ['fields' => 'username,profile_picture_url']);
            $username = trim((string) ($igProfile['username'] ?? 'Instagram'));
            $profilePicture = trim((string) ($igProfile['profile_picture_url'] ?? ''));
        } else {
            $profilePicture = trim((string) ($ig['profile_picture_url'] ?? ''));
        }

        $longPageToken = meta_exchange_long_lived_token($pageToken);
        $pageName = trim((string) ($page['name'] ?? ''));

        return [
            'label' => '@' . $username,
            'platform_account_id' => $igId,
            'page_id' => $pageId,
            'page_name' => $pageName !== '' ? $pageName : 'Page',
            'username' => $username,
            'profile_picture_url' => $profilePicture,
            'access_token' => $longPageToken,
        ];
    }

    /**
     * @param list<string> $pageIds
     * @return array<string, array<string, mixed>>
     */
    private function fetchGrantedPagesMap(string $userToken, array $pageIds): array
    {
        $pages = [];
        $data = meta_graph_get('me/accounts', $userToken, [
            'fields' => 'id,name,access_token,instagram_business_account{id,username,profile_picture_url}',
            'limit' => 50,
        ]);

        foreach ($data['data'] ?? [] as $page) {
            if (!is_array($page)) {
                continue;
            }

            $pageId = (string) ($page['id'] ?? '');
            if ($pageId !== '') {
                $pages[$pageId] = $page;
            }
        }

        foreach ($pageIds as $pageId) {
            if (isset($pages[$pageId])) {
                continue;
            }

            $page = meta_graph_get($pageId, $userToken, [
                'fields' => 'id,name,access_token,instagram_business_account{id,username}',
            ]);

            if (!isset($page['error']) && trim((string) ($page['id'] ?? '')) !== '') {
                $pages[$pageId] = $page;
            }
        }

        return $pages;
    }

    /**
     * @param array<string, mixed> $page
     */
    private function resolvePageAccessToken(string $userToken, array $page): string
    {
        $pageToken = trim((string) ($page['access_token'] ?? ''));
        if ($pageToken !== '') {
            return $pageToken;
        }

        $pageId = trim((string) ($page['id'] ?? ''));

        return $pageId !== '' ? $this->fetchPageAccessToken($userToken, $pageId) : '';
    }

    private function fetchPageAccessToken(string $userToken, string $pageId): string
    {
        $data = meta_graph_get($pageId, $userToken, ['fields' => 'access_token']);

        return trim((string) ($data['access_token'] ?? ''));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchGranularScopes(string $userToken): array
    {
        $appToken = meta_app_access_token();
        if ($appToken === '') {
            return [];
        }

        $debug = meta_graph_get('debug_token', $appToken, [
            'input_token' => $userToken,
            'fields' => 'granular_scopes,scopes',
        ]);

        $scopes = [];
        foreach ($debug['data']['granular_scopes'] ?? [] as $scope) {
            if (is_array($scope)) {
                $scopes[] = $scope;
            }
        }

        return $scopes;
    }

    /**
     * @param list<array<string, mixed>> $granularScopes
     * @param list<string> $prefixes
     * @return list<string>
     */
    private function granularTargetIds(array $granularScopes, array $prefixes): array
    {
        $ids = [];
        foreach ($granularScopes as $scope) {
            $name = (string) ($scope['scope'] ?? '');
            $matches = false;
            foreach ($prefixes as $prefix) {
                if (str_starts_with($name, $prefix)) {
                    $matches = true;
                    break;
                }
            }

            if (!$matches) {
                continue;
            }

            foreach ($scope['target_ids'] ?? [] as $targetId) {
                $id = (string) $targetId;
                if ($id !== '') {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function discoverWhatsAppNumbers(string $userToken): array
    {
        $options = [];
        $seenWabas = [];

        foreach ($this->discoverBusinessOwnedWabaIds($userToken) as $wabaId) {
            $seenWabas[$wabaId] = true;
            $options = array_merge($options, $this->buildWhatsAppOptionsForWaba($wabaId, $userToken));
        }

        // Meta sandbox / app-granted WABAs are not always under Business Portfolio ownership.
        foreach ($this->discoverGrantedWhatsAppWabaIds($userToken) as $wabaId) {
            if (isset($seenWabas[$wabaId])) {
                continue;
            }
            $options = array_merge($options, $this->buildWhatsAppOptionsForWaba($wabaId, $userToken));
        }

        return $options;
    }

    /** @return list<string> */
    private function discoverBusinessOwnedWabaIds(string $userToken): array
    {
        $ids = [];
        $businesses = meta_graph_get('me/businesses', $userToken, [
            'fields' => 'id,name',
            'limit' => 25,
        ]);

        foreach ($businesses['data'] ?? [] as $business) {
            if (!is_array($business)) {
                continue;
            }

            $businessId = (string) ($business['id'] ?? '');
            if ($businessId === '') {
                continue;
            }

            $wabas = meta_graph_get($businessId . '/owned_whatsapp_business_accounts', $userToken, [
                'fields' => 'id,name',
                'limit' => 25,
            ]);

            foreach ($wabas['data'] ?? [] as $waba) {
                if (!is_array($waba)) {
                    continue;
                }

                $wabaId = (string) ($waba['id'] ?? '');
                if ($wabaId !== '') {
                    $ids[] = $wabaId;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /** @return list<string> */
    private function discoverGrantedWhatsAppWabaIds(string $userToken): array
    {
        return $this->granularTargetIds($this->fetchGranularScopes($userToken), ['whatsapp_']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildWhatsAppOptionsForWaba(string $wabaId, string $userToken): array
    {
        $options = [];
        $phones = meta_graph_get($wabaId . '/phone_numbers', $userToken, [
            'fields' => 'id,display_phone_number,verified_name',
            'limit' => 25,
        ]);

        foreach ($phones['data'] ?? [] as $phone) {
            if (!is_array($phone)) {
                continue;
            }

            $phoneNumberId = (string) ($phone['id'] ?? '');
            if ($phoneNumberId === '') {
                continue;
            }

            $display = trim((string) ($phone['display_phone_number'] ?? ''));
            $verified = trim((string) ($phone['verified_name'] ?? ''));
            $label = $display !== '' ? $display : $phoneNumberId;
            if ($verified !== '') {
                $label = $verified . ' · ' . $label;
            }
            $label .= ' (Phone ID ' . $phoneNumberId . ')';

            $key = 'wa_' . $phoneNumberId;
            $options[$key] = [
                'label' => $label,
                'platform_account_id' => $phoneNumberId,
                'waba_id' => $wabaId,
                'access_token' => $userToken,
            ];
        }

        return $options;
    }

    /**
     * Scopes requested at OAuth connect (minimal — no optional publish scopes).
     */
    private function connectScopesForPlatform(string $platform): string
    {
        $meta = app_config()['meta'];
        $enablePagePublish = (bool) ($meta['enable_page_publish'] ?? false);
        $enableIgPublish = (bool) ($meta['enable_ig_publish'] ?? false);

        return match ($platform) {
            'whatsapp' => 'whatsapp_business_management,whatsapp_business_messaging,business_management',
            'instagram' => implode(',', array_values(array_filter([
                'business_management',
                'pages_show_list',
                'pages_read_engagement',
                'instagram_basic',
                'instagram_manage_messages',
                'instagram_manage_insights',
                'pages_messaging',
                'pages_manage_metadata',
                $enableIgPublish ? 'instagram_content_publish' : null,
                $enableIgPublish ? 'instagram_manage_contents' : null,
            ]))),
            'facebook' => implode(',', array_values(array_filter([
                'business_management',
                'pages_show_list',
                'pages_read_engagement',
                'pages_messaging',
                'pages_manage_metadata',
                $enablePagePublish ? 'pages_manage_posts' : null,
            ]))),
            default => throw new InvalidArgumentException('Unsupported platform.'),
        };
    }

    /**
     * Where to send the user after Meta connect, cancel, or error.
     */
    public function resolveReturnTo(?string $state, ?string $userId): string
    {
        $appUrl = rtrim((string) app_config()['app_url'], '/');

        if ($state !== null && $state !== '') {
            $row = RepositoryFactory::oauthStates()->findRowByState($state);
            $context = is_array($row['context'] ?? null) ? $row['context'] : null;
            if ($context !== null) {
                $stored = trim((string) ($context['return_to'] ?? ''));
                if ($stored !== '') {
                    return safe_return_url($stored, $this->defaultReturnTo($userId));
                }
            }
        }

        return $this->defaultReturnTo($userId);
    }

    /**
     * Default landing page when return_to was not stored.
     */
    public function defaultReturnTo(?string $userId): string
    {
        $appUrl = app_config()['app_url'];

        if ($userId !== null && $userId !== '' && (new OnboardingService())->isComplete($userId)) {
            return app_route('connected-accounts');
        }

        return app_route('onboarding/channels');
    }

    private function displayNameFromOption(string $platform, array $option): string
    {
        if ($platform === 'instagram') {
            $username = trim((string) ($option['username'] ?? ''));
            if ($username !== '') {
                return '@' . ltrim($username, '@');
            }
        }

        $label = trim((string) ($option['label'] ?? ''));
        if ($label !== '') {
            return $this->cleanWhatsAppDisplayLabel($platform, $label);
        }

        return trim((string) ($option['platform_account_id'] ?? ''));
    }

    /**
     * @param array<string, mixed> $option
     */
    private function profilePictureFromOption(string $platform, array $option, string $accessToken): string
    {
        $stored = trim((string) ($option['profile_picture_url'] ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        if ($platform === 'facebook') {
            $pageId = trim((string) ($option['platform_account_id'] ?? ''));
            if ($pageId !== '' && $accessToken !== '') {
                return $this->fetchFacebookPagePicture($pageId, $accessToken);
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $page
     */
    private function profilePictureFromPageData(array $page): string
    {
        $picture = $page['picture'] ?? null;
        if (!is_array($picture)) {
            return '';
        }

        $data = $picture['data'] ?? null;
        if (is_array($data)) {
            return trim((string) ($data['url'] ?? ''));
        }

        return trim((string) ($picture['url'] ?? ''));
    }

    private function fetchFacebookPagePicture(string $pageId, string $accessToken): string
    {
        $picture = meta_graph_get($pageId . '/picture', $accessToken, [
            'redirect' => '0',
            'type' => 'large',
        ]);
        if (!isset($picture['error'])) {
            $url = trim((string) ($picture['data']['url'] ?? ''));
            if ($url !== '') {
                return $url;
            }
        }

        $page = meta_graph_get($pageId, $accessToken, ['fields' => 'picture.type(large)']);
        if (!isset($page['error'])) {
            return $this->profilePictureFromPageData($page);
        }

        return '';
    }

    private function cleanWhatsAppDisplayLabel(string $platform, string $label): string
    {
        if ($platform !== 'whatsapp') {
            return $label;
        }

        $cleaned = preg_replace('/\s*\(Phone ID [^)]+\)\s*$/', '', $label);

        return is_string($cleaned) && $cleaned !== '' ? trim($cleaned) : $label;
    }

    private function assertPlatform(string $platform): void
    {
        if (!in_array($platform, ['whatsapp', 'instagram', 'facebook'], true)) {
            throw new InvalidArgumentException('Unsupported platform.');
        }
    }

    private function assertConfigured(): void
    {
        $meta = app_config()['meta'];
        if (($meta['app_id'] ?? '') === '' || ($meta['app_secret'] ?? '') === '') {
            throw new RuntimeException('META_APP_ID and META_APP_SECRET must be configured.');
        }
    }
}
