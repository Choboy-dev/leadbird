<?php

declare(strict_types=1);

/**
 * Short-lived checkout intent for the plan a visitor chose before sign-up.
 *
 * Uses a dedicated HttpOnly cookie with an explicit expiry (browser-enforced)
 * plus session storage for OAuth redirects. Default TTL: 30 minutes.
 */
final class PlanSelection
{
    private const SESSION_SLUG_KEY = 'pending_plan_slug';
    private const SESSION_EXPIRES_KEY = 'pending_plan_expires_at';
    private const COOKIE_SEPARATOR = '|';

    public static function captureFromRequest(): void
    {
        $slug = strtolower(trim((string) ($_GET['plan'] ?? '')));
        if ($slug !== '' && PlanService::isValidSlug($slug)) {
            self::persist($slug);
            return;
        }

        self::pendingSlug();
    }

    public static function set(string $slug): void
    {
        if (!PlanService::isValidSlug($slug)) {
            throw new InvalidArgumentException('Invalid plan slug.');
        }

        self::persist($slug);
    }

    public static function pendingSlug(): ?string
    {
        $intent = self::readValidIntent();
        if ($intent === null) {
            self::clear();
            return null;
        }

        return $intent['slug'];
    }

    public static function clear(): void
    {
        unset($_SESSION[self::SESSION_SLUG_KEY], $_SESSION[self::SESSION_EXPIRES_KEY]);
        self::clearCookie();
    }

    private static function persist(string $slug): void
    {
        $expiresAt = time() + self::ttlSeconds();
        self::storeIntent($slug, $expiresAt);
    }

    /**
     * @return array{slug: string, expires_at: int}|null
     */
    private static function readValidIntent(): ?array
    {
        $sessionSlug = $_SESSION[self::SESSION_SLUG_KEY] ?? null;
        $sessionExpires = $_SESSION[self::SESSION_EXPIRES_KEY] ?? null;

        if (
            is_string($sessionSlug)
            && PlanService::isValidSlug($sessionSlug)
            && is_int($sessionExpires)
            && !self::isExpired($sessionExpires)
        ) {
            return ['slug' => $sessionSlug, 'expires_at' => $sessionExpires];
        }

        $cookieIntent = self::decodeCookie((string) ($_COOKIE[self::cookieName()] ?? ''));
        if ($cookieIntent !== null) {
            self::storeIntent($cookieIntent['slug'], $cookieIntent['expires_at']);
            return $cookieIntent;
        }

        return null;
    }

    private static function storeIntent(string $slug, int $expiresAt): void
    {
        $_SESSION[self::SESSION_SLUG_KEY] = $slug;
        $_SESSION[self::SESSION_EXPIRES_KEY] = $expiresAt;
        self::setCookie($slug, $expiresAt);
    }

    private static function isExpired(int $expiresAt): bool
    {
        return time() >= $expiresAt;
    }

    private static function ttlSeconds(): int
    {
        return app_config()['plan_selection']['ttl_minutes'] * 60;
    }

    private static function cookieName(): string
    {
        return app_config()['plan_selection']['cookie_name'];
    }

    private static function encodeCookie(string $slug, int $expiresAt): string
    {
        return $slug . self::COOKIE_SEPARATOR . $expiresAt;
    }

    /**
     * @return array{slug: string, expires_at: int}|null
     */
    private static function decodeCookie(string $value): ?array
    {
        if ($value === '' || !str_contains($value, self::COOKIE_SEPARATOR)) {
            return null;
        }

        [$slug, $expiresRaw] = explode(self::COOKIE_SEPARATOR, $value, 2);
        $slug = strtolower(trim($slug));
        if (!PlanService::isValidSlug($slug) || !ctype_digit($expiresRaw)) {
            return null;
        }

        $expiresAt = (int) $expiresRaw;
        if (self::isExpired($expiresAt)) {
            return null;
        }

        return ['slug' => $slug, 'expires_at' => $expiresAt];
    }

    private static function setCookie(string $slug, int $expiresAt): void
    {
        if (headers_sent()) {
            return;
        }

        setcookie(self::cookieName(), self::encodeCookie($slug, $expiresAt), self::cookieOptions($expiresAt));
    }

    private static function clearCookie(): void
    {
        if (headers_sent()) {
            return;
        }

        setcookie(self::cookieName(), '', self::cookieOptions(time() - 3600));
    }

    /**
     * @return array{expires: int, path: string, httponly: bool, samesite: string, secure: bool}
     */
    private static function cookieOptions(int $expiresAt): array
    {
        $secure = str_starts_with(app_config()['app_url'], 'https://');

        return [
            'expires' => $expiresAt,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $secure,
        ];
    }
}
