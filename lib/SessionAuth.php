<?php

declare(strict_types=1);

final class SessionAuth
{
    private const USER = 'auth_user';
    private const LAST_ACTIVITY = 'auth_last_activity';
    private const LOGIN_AT = 'auth_login_at';

    public function login(array $user): void
    {
        $now = time();
        $_SESSION[self::USER] = [
            'id' => $user['id'] ?? null,
            'email' => $user['email'] ?? null,
            'name' => $user['name'] ?? null,
        ];
        $_SESSION[self::LAST_ACTIVITY] = $now;
        $_SESSION[self::LOGIN_AT] = $now;
        session_regenerate_id(true);
    }

    public function logout(): void
    {
        unset(
            $_SESSION[self::USER],
            $_SESSION[self::LAST_ACTIVITY],
            $_SESSION[self::LOGIN_AT]
        );
        session_regenerate_id(true);
    }

    public function touchActivity(): void
    {
        if (!is_array($_SESSION[self::USER] ?? null)) {
            return;
        }

        $_SESSION[self::LAST_ACTIVITY] = time();
    }

    /**
     * @return array{idle_timeout_minutes: int, idle_warning_seconds: int, absolute_timeout_hours: int}
     */
    public static function timeoutConfig(): array
    {
        $auth = app_config()['auth'] ?? [];

        return [
            'idle_timeout_minutes' => max(1, (int) ($auth['idle_timeout_minutes'] ?? 30)),
            'idle_warning_seconds' => max(30, (int) ($auth['idle_warning_seconds'] ?? 120)),
            'absolute_timeout_hours' => max(1, (int) ($auth['absolute_timeout_hours'] ?? 12)),
        ];
    }

    public function check(): bool
    {
        $user = $_SESSION[self::USER] ?? null;
        if (!is_array($user) || empty($user['id'])) {
            return false;
        }

        $dbUser = RepositoryFactory::users()->findById((string) $user['id']);
        if ($dbUser === null) {
            $this->logout();
            return false;
        }

        if ($this->isSessionTimedOut()) {
            $this->logout();
            return false;
        }

        if (!empty($dbUser['is_suspended'])) {
            $this->logout();
            return false;
        }

        $_SESSION[self::USER] = [
            'id' => $dbUser['id'],
            'email' => $dbUser['email'],
            'name' => $dbUser['name'] ?? null,
        ];

        return true;
    }

    /**
     * @return array{id: string, email: string, name: ?string}|null
     */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }
        $user = $_SESSION[self::USER] ?? null;
        return is_array($user) ? $user : null;
    }

    public function userId(): ?string
    {
        $user = $this->user();
        $id = $user['id'] ?? null;
        return is_string($id) && $id !== '' ? $id : null;
    }

    public function requireLogin(string $redirectTo = 'login'): void
    {
        if ($this->check()) {
            $this->touchActivity();
            return;
        }

        $path = ltrim($redirectTo, '/');
        if (str_ends_with($path, '.php')) {
            $path = substr($path, 0, -4);
        }

        redirect(app_route($path));
    }

    private function isSessionTimedOut(): bool
    {
        $config = self::timeoutConfig();
        $now = time();
        $lastActivity = (int) ($_SESSION[self::LAST_ACTIVITY] ?? 0);
        $loginAt = (int) ($_SESSION[self::LOGIN_AT] ?? 0);

        if ($lastActivity === 0 || $loginAt === 0) {
            $_SESSION[self::LAST_ACTIVITY] = $now;
            $_SESSION[self::LOGIN_AT] = $now;

            return false;
        }

        $absoluteLimit = $config['absolute_timeout_hours'] * 3600;
        if (($now - $loginAt) > $absoluteLimit) {
            return true;
        }

        $idleLimit = $config['idle_timeout_minutes'] * 60;

        return ($now - $lastActivity) > $idleLimit;
    }
}
