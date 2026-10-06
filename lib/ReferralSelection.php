<?php

declare(strict_types=1);

/**
 * Referral intent captured from /r/CODE and signup?referral_code=… URLs.
 *
 * Persists in session (OAuth-safe) and sets the lb_ref cookie only for
 * visitors who are not already signed in.
 */
final class ReferralSelection
{
    private const SESSION_CODE = 'pending_referral_code';
    private const SESSION_NAME = 'pending_referral_name';
    private const SESSION_AFFILIATE_ID = 'pending_referral_affiliate_id';
    private const SESSION_EXPIRES = 'pending_referral_expires_at';

    public static function captureFromRequest(): void
    {
        $code = self::normalizeCode((string) ($_GET['referral_code'] ?? ''));
        if ($code === '') {
            self::pendingReferral();
            return;
        }

        self::persistCode($code, !(new SessionAuth())->check());
    }

    /**
     * @return array{code: string, name: string, affiliate_id: string}|null
     */
    public static function pendingReferral(): ?array
    {
        $intent = self::readValidIntent();
        if ($intent === null) {
            self::clearSession();
            return null;
        }

        return $intent;
    }

    /**
     * @param array<string, mixed> $affiliate
     */
    public static function storeFromAffiliate(array $affiliate, bool $setCookie): ?array
    {
        $code = self::normalizeCode((string) ($affiliate['code'] ?? ''));
        if ($code === '') {
            return null;
        }

        return self::persistCode($code, $setCookie);
    }

    public static function signupUrlForCode(string $code): string
    {
        return app_route('signup', self::trackingQueryForCode($code));
    }

    /**
     * @return array<string, string>
     */
    public static function trackingQueryForCode(string $code): array
    {
        $code = self::normalizeCode($code);
        if ($code === '') {
            return [];
        }

        return [
            'referral_code' => $code,
            'source' => 'referral',
            'pid' => 'referral_code',
            'c' => 'Referral',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function publicQueryParams(): array
    {
        $referral = self::pendingReferral();
        if ($referral === null) {
            return [];
        }

        return self::trackingQueryForCode($referral['code']);
    }

    /**
     * @param array{code: string, name: string, affiliate_id: string} $referral
     * @param array{id: string, email: string, name?: ?string}|null $user
     */
    public static function isOwnLink(array $referral, ?array $user): bool
    {
        if ($user === null) {
            return false;
        }

        $affiliate = RepositoryFactory::affiliates()->findById($referral['affiliate_id']);
        if ($affiliate === null) {
            return false;
        }

        if ((string) ($affiliate['user_id'] ?? '') === (string) ($user['id'] ?? '')) {
            return true;
        }

        return strcasecmp(
            (string) ($affiliate['email'] ?? ''),
            (string) ($user['email'] ?? '')
        ) === 0;
    }

    public static function clear(): void
    {
        self::clearSession();
    }

    /**
     * Drop referral attribution when the visitor opens sign-in (not signup).
     * Prevents stale /r/CODE visits from showing "Referred by …" on login.
     */
    public static function clearForSignIn(): void
    {
        self::clearSession();
        (new AffiliateService())->clearReferralCookie();
    }

    private static function persistCode(string $code, bool $setCookie): ?array
    {
        $affiliateService = new AffiliateService();
        $affiliate = $affiliateService->resolveActiveAffiliate($code);
        if ($affiliate === null) {
            return null;
        }

        $code = strtoupper($code);
        $expiresAt = time() + (AffiliateService::COOKIE_DAYS * 86400);

        $_SESSION[self::SESSION_CODE] = $code;
        $_SESSION[self::SESSION_NAME] = trim((string) ($affiliate['name'] ?? '')) ?: 'a Lead Bird partner';
        $_SESSION[self::SESSION_AFFILIATE_ID] = (string) $affiliate['id'];
        $_SESSION[self::SESSION_EXPIRES] = $expiresAt;

        if ($setCookie) {
            $affiliateService->persistReferralCookie($code);
        }

        return [
            'code' => $code,
            'name' => (string) $_SESSION[self::SESSION_NAME],
            'affiliate_id' => (string) $affiliate['id'],
        ];
    }

    /**
     * @return array{code: string, name: string, affiliate_id: string}|null
     */
    private static function readValidIntent(): ?array
    {
        $code = self::normalizeCode((string) ($_SESSION[self::SESSION_CODE] ?? ''));
        $expires = $_SESSION[self::SESSION_EXPIRES] ?? null;
        $name = trim((string) ($_SESSION[self::SESSION_NAME] ?? ''));
        $affiliateId = trim((string) ($_SESSION[self::SESSION_AFFILIATE_ID] ?? ''));

        if (
            $code !== ''
            && $name !== ''
            && $affiliateId !== ''
            && is_int($expires)
            && time() < $expires
            && (new AffiliateService())->resolveActiveAffiliate($code) !== null
        ) {
            return [
                'code' => $code,
                'name' => $name,
                'affiliate_id' => $affiliateId,
            ];
        }

        $cookieCode = self::normalizeCode((string) ($_COOKIE[AffiliateService::COOKIE_NAME] ?? ''));
        if ($cookieCode !== '' && $code === '') {
            return self::persistCode($cookieCode, false);
        }

        return null;
    }

    private static function clearSession(): void
    {
        unset(
            $_SESSION[self::SESSION_CODE],
            $_SESSION[self::SESSION_NAME],
            $_SESSION[self::SESSION_AFFILIATE_ID],
            $_SESSION[self::SESSION_EXPIRES]
        );
    }

    private static function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }
}
