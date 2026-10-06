<?php

declare(strict_types=1);

/**
 * @return array<string, array{label: string, url: string, icon: string, group?: string}>
 */
function admin_nav_items(): array
{
    return [
        'overview' => ['label' => 'Overview', 'url' => app_route('admin'), 'icon' => 'overview', 'group' => 'core'],
        'users' => ['label' => 'Users', 'url' => app_route('admin/users'), 'icon' => 'customers', 'group' => 'core'],
        'businesses' => ['label' => 'Businesses', 'url' => app_route('admin/businesses'), 'icon' => 'my_business', 'group' => 'core'],
        'subscriptions' => ['label' => 'Subscriptions', 'url' => app_route('admin/subscriptions'), 'icon' => 'billing', 'group' => 'revenue'],
        'tokens' => ['label' => 'Tokens & AI', 'url' => app_route('admin/tokens'), 'icon' => 'ai', 'group' => 'revenue'],
        'openai' => ['label' => 'OpenAI usage', 'url' => app_route('admin/openai-usage'), 'icon' => 'ai', 'group' => 'revenue'],
        'inbox' => ['label' => 'Inbox viewer', 'url' => app_route('admin/inbox'), 'icon' => 'inbox', 'group' => 'ops'],
        'channels' => ['label' => 'Channels', 'url' => app_route('admin/channels'), 'icon' => 'connected_accounts', 'group' => 'ops'],
        'automation' => ['label' => 'Automation queue', 'url' => app_route('admin/automation'), 'icon' => 'social_media', 'group' => 'ops'],
        'affiliates' => ['label' => 'Affiliates', 'url' => app_route('admin/affiliates'), 'icon' => 'billing', 'group' => 'ops'],
        'disputes' => ['label' => 'Disputes', 'url' => app_route('admin/disputes'), 'icon' => 'billing', 'group' => 'ops'],
        'meta_deletion' => ['label' => 'Meta deletion', 'url' => app_route('admin/meta-deletion'), 'icon' => 'connected_accounts', 'group' => 'compliance'],
        'audit' => ['label' => 'Audit log', 'url' => app_route('admin/audit'), 'icon' => 'settings', 'group' => 'compliance'],
        'admins' => ['label' => 'Admin users', 'url' => app_route('admin/admins'), 'icon' => 'settings', 'group' => 'power'],
        'system' => ['label' => 'System health', 'url' => app_route('admin/system'), 'icon' => 'overview', 'group' => 'power'],
        'webhooks' => ['label' => 'Webhook logs', 'url' => app_route('admin/webhooks'), 'icon' => 'connected_accounts', 'group' => 'power'],
        'feature_flags' => ['label' => 'Config & flags', 'url' => app_route('admin/feature-flags'), 'icon' => 'settings', 'group' => 'power'],
        'reports' => ['label' => 'Reports', 'url' => app_route('admin/reports'), 'icon' => 'billing', 'group' => 'power'],
    ];
}

/** @return list<array{id: string, label: string, icon: string, items: list<string>}> */
function admin_nav_groups(): array
{
    return [
        ['id' => 'core', 'label' => 'Core', 'icon' => 'overview', 'items' => ['overview', 'users', 'businesses']],
        ['id' => 'revenue', 'label' => 'Revenue', 'icon' => 'billing', 'items' => ['subscriptions', 'tokens', 'openai']],
        ['id' => 'ops', 'label' => 'Operations', 'icon' => 'connected_accounts', 'items' => ['inbox', 'channels', 'automation', 'affiliates', 'disputes']],
        ['id' => 'compliance', 'label' => 'Compliance', 'icon' => 'settings', 'items' => ['meta_deletion', 'audit']],
        ['id' => 'power', 'label' => 'Power tools', 'icon' => 'settings', 'items' => ['admins', 'system', 'webhooks', 'feature_flags', 'reports']],
    ];
}

/** @param list<string> $itemKeys */
function admin_nav_group_is_active(array $itemKeys, string $activeNav): bool
{
    return in_array($activeNav, $itemKeys, true);
}

function admin_page(int $page): int
{
    return max(1, $page);
}

function admin_per_page(int $perPage = 25): int
{
    return max(1, min(100, $perPage));
}

function admin_format_datetime(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '—';
    }

    $ts = strtotime($value);

    return $ts !== false ? date('M j, Y g:i A', $ts) : $value;
}

function admin_format_money(float $amount, ?string $currency = null): string
{
    $sourceCurrency = strtoupper(trim($currency ?? 'USD'));
    $usd = money_to_usd($amount, $sourceCurrency);

    if (function_exists('dash_format_money')) {
        return dash_format_money($usd, 'USD');
    }

    return money_format($usd, 'USD');
}

function admin_format_openai_usd(float $amount): string
{
    if ($amount >= 1) {
        return '$' . number_format($amount, 2);
    }

    if ($amount >= 0.01) {
        return '$' . number_format($amount, 2);
    }

    return '$' . number_format($amount, 4);
}

function admin_status_class(string $status): string
{
    $status = strtolower(trim($status));

    return match ($status) {
        'active', 'completed', 'paid', 'successful', 'available' => 'admin-badge admin-badge--success',
        'pending', 'processing', 'open' => 'admin-badge admin-badge--warning',
        'failed', 'cancelled', 'canceled', 'suspended', 'danger' => 'admin-badge admin-badge--danger',
        default => 'admin-badge',
    };
}

function admin_render_pagination(string $baseUrl, int $page, int $total, int $perPage): void
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($totalPages <= 1) {
        return;
    }

    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    echo '<nav class="admin-pagination" aria-label="Pagination">';
    if ($page > 1) {
        echo '<a class="admin-pagination-link" href="' . e($baseUrl . $sep . 'page=' . ($page - 1)) . '">Previous</a>';
    }
    echo '<span class="admin-pagination-meta font-sans">Page ' . (int) $page . ' of ' . $totalPages . '</span>';
    if ($page < $totalPages) {
        echo '<a class="admin-pagination-link" href="' . e($baseUrl . $sep . 'page=' . ($page + 1)) . '">Next</a>';
    }
    echo '</nav>';
}

function admin_render_flash(?string $success, ?string $error): void
{
    if ($success): ?>
        <div class="dashboard-alert dashboard-alert--success admin-alert" role="status"><?= e($success) ?></div>
    <?php endif;
    if ($error): ?>
        <div class="dashboard-alert admin-alert admin-alert--error" role="alert"><?= e($error) ?></div>
    <?php endif;
}

function admin_render_table_start(array $headers): void
{
    echo '<div class="admin-table-wrap"><table class="admin-table"><thead><tr>';
    foreach ($headers as $header) {
        echo '<th scope="col">' . e($header) . '</th>';
    }
    echo '</tr></thead><tbody>';
}

function admin_render_table_end(): void
{
    echo '</tbody></table></div>';
}

function admin_render_empty(string $message): void
{
    echo '<div class="admin-empty"><p class="font-sans">' . e($message) . '</p></div>';
}

function admin_render_kpi_cards(array $cards): void
{
    echo '<div class="admin-kpi-grid">';
    foreach ($cards as $card) {
        $url = isset($card['url']) ? (string) $card['url'] : '';
        $tag = $url !== '' ? 'a' : 'div';
        $href = $url !== '' ? ' href="' . e($url) . '"' : '';
        echo '<' . $tag . ' class="admin-kpi-card"' . $href . '>';
        echo '<p class="admin-kpi-label font-sans">' . e((string) ($card['label'] ?? '')) . '</p>';
        echo '<p class="admin-kpi-value font-heading">' . e((string) ($card['value'] ?? '')) . '</p>';
        if (!empty($card['hint'])) {
            echo '<p class="admin-kpi-hint font-sans">' . e((string) $card['hint']) . '</p>';
        }
        echo '</' . $tag . '>';
    }
    echo '</div>';
}

function admin_toolbar_search(string $placeholder, ?string $value = null): void
{
    ?>
    <form class="admin-toolbar-search" method="get" role="search">
        <label class="sr-only" for="admin-search">Search</label>
        <input
            id="admin-search"
            type="search"
            name="q"
            class="admin-search-input"
            placeholder="<?= e($placeholder) ?>"
            value="<?= e((string) ($value ?? '')) ?>"
            autocomplete="off"
        >
        <button type="submit" class="dash-btn admin-search-btn">Search</button>
    </form>
    <?php
}

/**
 * @param list<array{label: string, url: string, active?: bool}> $links
 */
function admin_render_filter_links(array $links): void
{
    if ($links === []) {
        return;
    }

    echo '<div class="dash-filters admin-filters" role="group" aria-label="Filter">';
    foreach ($links as $link) {
        $active = !empty($link['active']);
        $class = 'dash-filter-btn font-sans' . ($active ? ' is-active' : '');
        echo '<a class="' . $class . '" href="' . e((string) $link['url']) . '"';
        if ($active) {
            echo ' aria-current="page"';
        }
        echo '>' . e((string) $link['label']) . '</a>';
    }
    echo '</div>';
}

function admin_system_health(): array
{
    $checks = [];
    try {
        Database::connection()->query('SELECT 1');
        $checks[] = ['label' => 'Database', 'status' => 'ok', 'detail' => 'Connected'];
    } catch (Throwable $e) {
        $checks[] = ['label' => 'Database', 'status' => 'error', 'detail' => 'Connection failed'];
    }

    $checks[] = [
        'label' => 'OpenAI API key',
        'status' => trim((string) (app_config()['openai']['api_key'] ?? '')) !== '' ? 'ok' : 'error',
        'detail' => trim((string) (app_config()['openai']['api_key'] ?? '')) !== '' ? 'Configured' : 'Missing',
    ];
    $checks[] = [
        'label' => 'Flutterwave',
        'status' => trim((string) (app_config()['flutterwave']['secret_key'] ?? '')) !== '' ? 'ok' : 'warning',
        'detail' => trim((string) (app_config()['flutterwave']['secret_key'] ?? '')) !== '' ? 'Configured' : 'Missing secret key',
    ];
    $checks[] = [
        'label' => 'Meta app',
        'status' => trim((string) (app_config()['meta']['app_id'] ?? '')) !== '' ? 'ok' : 'warning',
        'detail' => trim((string) (app_config()['meta']['app_id'] ?? '')) !== '' ? 'Configured' : 'Missing app ID',
    ];

    try {
        $fx = (new FxRateService())->ngnPerUsd();
        $checks[] = ['label' => 'FX rate (NGN/USD)', 'status' => 'ok', 'detail' => number_format($fx, 2)];
    } catch (Throwable $e) {
        $checks[] = ['label' => 'FX rate', 'status' => 'warning', 'detail' => 'Unavailable'];
    }

    $logPath = dirname(__DIR__, 2) . '/logs/meta-webhook.log';
    $checks[] = [
        'label' => 'Meta webhook log',
        'status' => is_readable($logPath) ? 'ok' : 'warning',
        'detail' => is_readable($logPath) ? 'Readable' : 'Not found',
    ];

    return $checks;
}

function admin_read_webhook_log(int $lines = 200): array
{
    $logPath = dirname(__DIR__, 2) . '/logs/meta-webhook.log';
    if (!is_readable($logPath)) {
        return [];
    }

    $content = file($logPath, FILE_IGNORE_NEW_LINES);
    if ($content === false) {
        return [];
    }

    return array_slice($content, -max(1, min(500, $lines)));
}
