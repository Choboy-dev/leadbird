<?php

declare(strict_types=1);

function auth_normalize_mode(string $mode): string
{
    return $mode === 'signup' ? 'signup' : 'signin';
}

function auth_admin_login_active(): bool
{
    return (string) ($_SESSION[AdminAuth::DESTINATION_SESSION_KEY] ?? '') === AdminAuth::DESTINATION_ADMIN;
}

function auth_set_admin_destination(): void
{
    AdminAuth::setAdminDestination();
}

function auth_clear_admin_destination(): void
{
    unset($_SESSION[AdminAuth::DESTINATION_SESSION_KEY]);
}

function auth_page_path(string $mode): string
{
    if (auth_admin_login_active()) {
        return 'admin/login';
    }

    return auth_normalize_mode($mode) === 'signup' ? 'signup' : 'login';
}

function auth_page_url(string $mode, string $step = 'email'): string
{
    $query = [];

    if ($step === 'verify') {
        $query['step'] = 'verify';
    }

    $plan = PlanSelection::pendingSlug();
    if ($plan !== null) {
        $query['plan'] = $plan;
    }

    $query = array_merge($query, ReferralSelection::publicQueryParams());

    return app_route(auth_page_path($mode), $query);
}

function auth_mode_from_session(): string
{
    return auth_normalize_mode((string) ($_SESSION['auth_mode'] ?? 'signin'));
}

function auth_store_mode(string $mode): void
{
    $_SESSION['auth_mode'] = auth_normalize_mode($mode);
}
