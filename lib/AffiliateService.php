<?php

declare(strict_types=1);

/**
 * Affiliate identity, referral codes, and attribution cookies.
 */
final class AffiliateService
{
    public const COOKIE_NAME = 'lb_ref';
    public const COOKIE_DAYS = 30;
    public const COMMISSION_RATE = 0.10;
    public const HOLD_DAYS = 7;
    public const MIN_WITHDRAW_USD = 10.0;

    public function ensureForUser(string $userId, array $user): array
    {
        $existing = RepositoryFactory::affiliates()->findByUserId($userId);
        if ($existing !== null) {
            return $existing;
        }

        $email = normalize_email((string) ($user['email'] ?? ''));
        $byEmail = $email !== '' ? RepositoryFactory::affiliates()->findByEmail($email) : null;
        if ($byEmail !== null) {
            RepositoryFactory::affiliates()->linkUser((string) $byEmail['id'], $userId);
            return RepositoryFactory::affiliates()->findById((string) $byEmail['id']) ?? $byEmail;
        }

        $name = trim((string) ($user['name'] ?? ''));
        if ($name === '') {
            $name = explode('@', $email)[0] ?: 'Partner';
        }

        return RepositoryFactory::affiliates()->create([
            'user_id' => $userId,
            'code' => $this->generateUniqueCode($name),
            'name' => $name,
            'email' => $email !== '' ? $email : ($userId . '@users.leadbird.local'),
            'phone' => null,
            'kyc_status' => 'none',
        ]);
    }

    /**
     * @return array{ok: bool, affiliate?: array<string, mixed>, error?: string}
     */
    public function applyPublic(string $name, string $email, ?string $phone = null): array
    {
        $name = trim($name);
        $email = normalize_email($email);
        $phone = $phone !== null ? trim($phone) : null;

        if ($name === '' || !is_valid_email($email)) {
            return ['ok' => false, 'error' => 'Enter a valid name and email.'];
        }

        if (RepositoryFactory::affiliates()->findByEmail($email) !== null) {
            return ['ok' => false, 'error' => 'An affiliate account already exists for this email. Sign in to continue.'];
        }

        $user = RepositoryFactory::users()->findByEmail($email);
        $affiliate = RepositoryFactory::affiliates()->create([
            'user_id' => $user !== null ? (string) $user['id'] : null,
            'code' => $this->generateUniqueCode($name),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'kyc_status' => 'none',
        ]);

        return ['ok' => true, 'affiliate' => $affiliate];
    }

    public function captureReferralCode(?string $code, ?string $landingPath = null): ?array
    {
        return $this->trackReferralVisit($code, $landingPath, true);
    }

    public function trackReferralVisit(?string $code, ?string $landingPath = null, bool $setCookie = false): ?array
    {
        $affiliate = $this->resolveActiveAffiliate($code);
        if ($affiliate === null) {
            return null;
        }

        if ($setCookie) {
            $this->persistReferralCookie((string) $affiliate['code']);
        }

        RepositoryFactory::affiliates()->recordAttribution(
            (string) $affiliate['id'],
            $this->visitorKey(),
            (new SessionAuth())->userId(),
            $landingPath
        );

        return $affiliate;
    }

    public function persistReferralCookie(string $code): bool
    {
        $affiliate = $this->resolveActiveAffiliate($code);
        if ($affiliate === null) {
            return false;
        }

        $this->setReferralCookie((string) $affiliate['code']);

        return true;
    }

    public function clearReferralCookie(): void
    {
        if (!isset($_COOKIE[self::COOKIE_NAME])) {
            return;
        }

        $secure = str_starts_with((string) app_config()['app_url'], 'https://');
        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::COOKIE_NAME]);
    }

    public function resolveActiveAffiliate(?string $code): ?array
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        $affiliate = RepositoryFactory::affiliates()->findByCode($code);
        if ($affiliate === null || (string) ($affiliate['status'] ?? '') !== 'active') {
            return null;
        }

        return $affiliate;
    }

    public function referredAffiliateIdFromCookie(): ?string
    {
        $code = strtoupper(trim((string) ($_COOKIE[self::COOKIE_NAME] ?? '')));
        if ($code === '') {
            return null;
        }

        $affiliate = RepositoryFactory::affiliates()->findByCode($code);
        if ($affiliate === null || (string) ($affiliate['status'] ?? '') !== 'active') {
            return null;
        }

        return (string) $affiliate['id'];
    }

    public function bindUserReferral(string $userId): void
    {
        $user = RepositoryFactory::users()->findById($userId);
        if ($user === null || !empty($user['referred_by_affiliate_id'])) {
            return;
        }

        $affiliateId = $this->referredAffiliateIdFromCookie();
        if ($affiliateId === null) {
            return;
        }

        $affiliate = RepositoryFactory::affiliates()->findById($affiliateId);
        if ($affiliate === null) {
            return;
        }

        // Block self-referral
        if ((string) ($affiliate['user_id'] ?? '') === $userId) {
            return;
        }
        if (strcasecmp((string) ($affiliate['email'] ?? ''), (string) ($user['email'] ?? '')) === 0) {
            return;
        }

        RepositoryFactory::users()->setReferredBy($userId, $affiliateId);
    }

    public function referralUrl(string $code): string
    {
        return rtrim((string) app_config()['app_url'], '/') . '/r/' . rawurlencode(strtoupper($code));
    }

    /**
     * Find the partner record for a signed-in user without creating one. Emails are verified at
     * sign-in, so a partner who applied before having an account is linked by email.
     */
    public function findForUser(string $userId, string $email): ?array
    {
        $existing = RepositoryFactory::affiliates()->findByUserId($userId);
        if ($existing !== null) {
            return $existing;
        }

        $email = normalize_email($email);
        $byEmail = $email !== '' ? RepositoryFactory::affiliates()->findByEmail($email) : null;
        if ($byEmail === null || !empty($byEmail['user_id'])) {
            return null;
        }

        RepositoryFactory::affiliates()->linkUser((string) $byEmail['id'], $userId);

        return RepositoryFactory::affiliates()->findById((string) $byEmail['id']);
    }

    /**
     * @return array{clicks: int, signups: int, conversions: int, pending: float, available: float, withdrawing: float, paid: float}
     */
    public function dashboardStats(string $affiliateId): array
    {
        $repo = RepositoryFactory::affiliates();
        $commissions = RepositoryFactory::affiliateCommissions();

        return [
            'clicks' => $repo->countClicks($affiliateId),
            'signups' => $repo->countSignups($affiliateId),
            'conversions' => $commissions->countPaidConversions($affiliateId),
            'pending' => $this->sumCommissionsUsd($affiliateId, 'pending'),
            'available' => $this->sumCommissionsUsd($affiliateId, 'available'),
            'withdrawing' => $this->sumCommissionsUsd($affiliateId, 'withdrawing'),
            'paid' => $this->sumCommissionsUsd($affiliateId, 'paid_out'),
        ];
    }

    public function withdrawableBalance(string $affiliateId): float
    {
        return max(0.0, $this->sumCommissionsUsd($affiliateId, 'available'));
    }

    private function sumCommissionsUsd(string $affiliateId, string $status): float
    {
        $total = 0.0;
        foreach (RepositoryFactory::affiliateCommissions()->listByAffiliateId($affiliateId, 250) as $row) {
            if ((string) ($row['status'] ?? '') !== $status) {
                continue;
            }
            $total += money_to_usd(
                (float) ($row['commission_amount'] ?? 0),
                (string) ($row['currency'] ?? 'USD')
            );
        }

        return round($total, 2);
    }

    private function setReferralCookie(string $code): void
    {
        $secure = str_starts_with((string) app_config()['app_url'], 'https://');
        setcookie(self::COOKIE_NAME, $code, [
            'expires' => time() + (self::COOKIE_DAYS * 86400),
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[self::COOKIE_NAME] = $code;
    }

    private function visitorKey(): string
    {
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['_lb_vid'])) {
            return (string) $_SESSION['_lb_vid'];
        }

        $key = bin2hex(random_bytes(16));
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['_lb_vid'] = $key;
        }

        return $key;
    }

    private function generateUniqueCode(string $name): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name) ?? '');
        $base = substr($base, 0, 8);
        if ($base === '') {
            $base = 'LBIRD';
        }

        for ($i = 0; $i < 12; $i++) {
            $suffix = $i === 0 ? '' : (string) random_int(10, 99);
            $code = substr($base . $suffix, 0, 12);
            if (RepositoryFactory::affiliates()->findByCode($code) === null) {
                return $code;
            }
        }

        return 'LB' . strtoupper(bin2hex(random_bytes(4)));
    }
}
