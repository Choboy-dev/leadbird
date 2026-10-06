<?php

declare(strict_types=1);

final class AdminAuth
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';
    public const DESTINATION_SESSION_KEY = 'auth_destination';
    public const DESTINATION_ADMIN = 'admin';
    public const IMPERSONATOR_SESSION_KEY = 'admin_impersonator';

    /** @return list<string> */
    public static function emailAllowlist(): array
    {
        $raw = env('ADMIN_EMAILS', '') ?? '';
        if (trim($raw) === '') {
            return [];
        }

        $emails = [];
        foreach (explode(',', $raw) as $part) {
            $email = strtolower(trim($part));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }

    public static function isAdminUser(?array $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ((string) ($user['role'] ?? self::ROLE_USER) === self::ROLE_ADMIN) {
            return true;
        }

        $email = strtolower(trim((string) ($user['email'] ?? '')));
        if ($email === '') {
            return false;
        }

        return in_array($email, self::emailAllowlist(), true);
    }

    public static function isAdminUserId(string $userId): bool
    {
        $user = RepositoryFactory::users()->findById($userId);

        return self::isAdminUser($user);
    }

    public static function isSuspended(?array $user): bool
    {
        return $user !== null && !empty($user['is_suspended']);
    }

    public static function setAdminDestination(): void
    {
        $_SESSION[self::DESTINATION_SESSION_KEY] = self::DESTINATION_ADMIN;
    }

    public static function consumeDestination(): ?string
    {
        $destination = trim((string) ($_SESSION[self::DESTINATION_SESSION_KEY] ?? ''));
        unset($_SESSION[self::DESTINATION_SESSION_KEY]);

        return $destination !== '' ? $destination : null;
    }

    public static function postLoginRedirectUrl(string $userId): string
    {
        $destination = self::consumeDestination();
        if ($destination === self::DESTINATION_ADMIN) {
            if (self::isAdminUserId($userId)) {
                return app_route('admin');
            }

            (new SessionAuth())->logout();
            flash_set('error', 'This account does not have admin access.');

            return app_route('admin/login');
        }

        return app_route('app');
    }

    public static function requireAdmin(): void
    {
        $sessionAuth = new SessionAuth();
        if (!$sessionAuth->check()) {
            self::setAdminDestination();
            redirect(app_route('admin/login'));
        }

        $userId = $sessionAuth->userId();
        if ($userId === null || !self::isAdminUserId($userId)) {
            self::logAccessDenied($sessionAuth->user()['email'] ?? 'unknown');
            flash_set('error', 'Admin access required.');
            redirect(app_route('admin/login'));
        }

        $dbUser = RepositoryFactory::users()->findById($userId);
        if (self::isSuspended($dbUser)) {
            $sessionAuth->logout();
            flash_set('error', 'This admin account is suspended.');
            redirect(app_route('admin/login'));
        }

        $sessionAuth->touchActivity();
    }

    public static function logAccessDenied(string $email): void
    {
        error_log('[AdminAuth] Access denied for ' . $email . ' from ' . client_ip());
    }

    /**
     * @return array{id: string, email: string, name?: ?string}|null
     */
    public static function impersonator(): ?array
    {
        $data = $_SESSION[self::IMPERSONATOR_SESSION_KEY] ?? null;

        return is_array($data) ? $data : null;
    }

    public static function isImpersonating(): bool
    {
        return self::impersonator() !== null;
    }
}
