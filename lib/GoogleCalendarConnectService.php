<?php

declare(strict_types=1);

final class GoogleCalendarConnectService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';

    private const SCOPES = [
        'openid',
        'email',
        'https://www.googleapis.com/auth/calendar.events',
        'https://www.googleapis.com/auth/calendar.freebusy',
    ];

    public function beginConnect(string $businessId, string $returnUrl): string
    {
        $config = app_config()['google'];
        if (($config['client_id'] ?? '') === '') {
            throw new RuntimeException('Google OAuth is not configured.');
        }

        $codeVerifier = GoogleAuthService::generateCodeVerifier();
        $challenge = GoogleAuthService::generateCodeChallenge($codeVerifier);
        $state = bin2hex(random_bytes(16));
        $expiresAt = (new DateTimeImmutable('now'))->modify('+15 minutes')->format('Y-m-d H:i:s');

        RepositoryFactory::oauthStates()->purgeExpired();
        RepositoryFactory::oauthStates()->create($state, $codeVerifier, $expiresAt, [
            'flow' => 'google_calendar',
            'business_id' => $businessId,
            'return_url' => $returnUrl,
        ]);

        $query = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => app_config()['auth']['google_calendar_redirect'],
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return self::AUTH_URL . '?' . $query;
    }

    /**
     * @return array{return_url: string}
     */
    public function handleCallback(string $code, string $state): array
    {
        $row = RepositoryFactory::oauthStates()->consumeRow($state);
        if ($row === null) {
            throw new RuntimeException('Invalid or expired OAuth state.');
        }

        $context = is_array($row['context'] ?? null) ? $row['context'] : [];
        $businessId = trim((string) ($context['business_id'] ?? ''));
        $returnUrl = trim((string) ($context['return_url'] ?? app_route('onboarding/offerings')));
        if ($businessId === '') {
            throw new RuntimeException('Missing business context for calendar connect.');
        }

        $tokens = $this->exchangeCode($code, (string) ($row['code_verifier'] ?? ''));
        $profile = $this->fetchUserInfo((string) $tokens['access_token']);
        $expiresIn = (int) ($tokens['expires_in'] ?? 3600);
        $expiresAt = (new DateTimeImmutable('now'))->modify('+' . $expiresIn . ' seconds')->format('Y-m-d H:i:s');

        RepositoryFactory::businessGoogleCalendar()->upsert($businessId, [
            'google_email' => isset($profile['email']) ? (string) $profile['email'] : null,
            'calendar_id' => 'primary',
            'access_token' => (string) $tokens['access_token'],
            'refresh_token' => isset($tokens['refresh_token']) ? (string) $tokens['refresh_token'] : null,
            'token_expires_at' => $expiresAt,
            'timezone' => 'Africa/Lagos',
        ]);

        return ['return_url' => $returnUrl];
    }

    /**
     * @return array{access_token: string, refresh_token?: string, expires_in?: int}
     */
    private function exchangeCode(string $code, string $codeVerifier): array
    {
        $config = app_config()['google'];
        $body = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'code_verifier' => $codeVerifier,
            'grant_type' => 'authorization_code',
            'redirect_uri' => app_config()['auth']['google_calendar_redirect'],
        ];

        $response = $this->httpPost(self::TOKEN_URL, $body);
        if (!isset($response['access_token'])) {
            throw new RuntimeException('Google token exchange failed.');
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchUserInfo(string $accessToken): array
    {
        $ch = curl_init(self::USERINFO_URL);
        if ($ch === false) {
            return [];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_TIMEOUT => 30,
        ]);

        $raw = curl_exec($ch);
        curl_close($ch);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, string> $body
     * @return array<string, mixed>
     */
    private function httpPost(string $url, array $body): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return [];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($body),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30,
        ]);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded)
                ? ($decoded['error_description'] ?? $decoded['error'] ?? $raw)
                : $raw;
            throw new RuntimeException('Google API error: ' . (is_string($message) ? $message : 'unknown'));
        }

        return is_array($decoded) ? $decoded : [];
    }
}
