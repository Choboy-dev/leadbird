<?php

declare(strict_types=1);

/**
 * Google OAuth 2.0 with PKCE (no Supabase).
 */
final class GoogleAuthService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';

    public static function generateCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public static function generateCodeChallenge(string $codeVerifier): string
    {
        $hash = hash('sha256', $codeVerifier, true);
        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }

    public function beginLogin(): string
    {
        $config = app_config()['google'];
        if (($config['client_id'] ?? '') === '') {
            throw new RuntimeException('GOOGLE_CLIENT_ID is not configured');
        }

        $codeVerifier = self::generateCodeVerifier();
        $challenge = self::generateCodeChallenge($codeVerifier);
        $state = bin2hex(random_bytes(16));
        $expiresAt = (new DateTimeImmutable('now'))->modify('+15 minutes')->format('Y-m-d H:i:s');

        RepositoryFactory::oauthStates()->purgeExpired();
        RepositoryFactory::oauthStates()->create($state, $codeVerifier, $expiresAt);

        $query = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => app_config()['auth']['google_redirect'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return self::AUTH_URL . '?' . $query;
    }

    /**
     * @return array{id: string, email: string, name: ?string}
     */
    public function handleCallback(string $code, string $state): array
    {
        $codeVerifier = RepositoryFactory::oauthStates()->consume($state);
        if ($codeVerifier === null) {
            throw new RuntimeException('Invalid or expired OAuth state');
        }

        $tokens = $this->exchangeCode($code, $codeVerifier);
        $profile = $this->fetchUserInfo($tokens['access_token']);

        $googleId = (string) ($profile['id'] ?? '');
        $email = normalize_email((string) ($profile['email'] ?? ''));
        if ($googleId === '' || $email === '' || !is_valid_email($email)) {
            throw new RuntimeException('Google did not return a valid user profile');
        }

        $user = (new UserService())->findOrCreateByGoogle(
            $googleId,
            $email,
            isset($profile['name']) ? (string) $profile['name'] : null,
        );

        return (new UserService())->toSessionUser($user);
    }

    /**
     * @return array{access_token: string}
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
            'redirect_uri' => app_config()['auth']['google_redirect'],
        ];

        $response = $this->httpPost(self::TOKEN_URL, $body);
        if (!isset($response['access_token'])) {
            throw new RuntimeException('Google token exchange failed');
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
            throw new RuntimeException('Failed to initialize cURL');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_TIMEOUT => 30,
        ]);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('Failed to fetch Google user profile');
        }

        $decoded = json_decode($raw, true);
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
            throw new RuntimeException('Failed to initialize cURL');
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
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('HTTP request failed: ' . $error);
        }

        $decoded = json_decode($raw, true);
        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded)
                ? ($decoded['error_description'] ?? $decoded['error'] ?? $raw)
                : $raw;
            throw new RuntimeException('Google API error (' . $status . '): ' . (is_string($message) ? $message : json_encode($message)));
        }

        return is_array($decoded) ? $decoded : [];
    }
}
