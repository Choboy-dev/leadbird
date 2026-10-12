<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string $pageTitle */
/** @var string $dashboardNav */
/** @var array{id: string, email: string, name?: ?string}|null $user */
/** @var array<string, mixed>|null $business */
/** @var list<array<string, mixed>> $allBusinesses */

/** @var array<string, mixed>|null $settings */

require_once __DIR__ . '/nav.php';

if (!function_exists('dash_initials_for_name')) {
    require_once __DIR__ . '/helpers.php';
}

$homeUrl = e($appUrl) . '/';
$dashboardUrl = e(app_route('dashboard'));
$logoutUrl = e(app_route('auth/logout'));
$sidebarSettingsUrl = e(app_route('settings'));
$sidebarBillingUrl = e(app_route('billing'));
$navItems = dashboard_nav_items($settings);
$pageHeading = $pageHeading ?? ($navItems[$dashboardNav]['label'] ?? 'Dashboard');
$dashboardNavCollapsed = $dashboardNavCollapsed ?? false;
$dashboardHideTopbar = $dashboardHideTopbar ?? false;
$dashboardNavContext = is_array($dashboardNavContext ?? null) ? $dashboardNavContext : [];

$sidebarUserName = trim((string) ($user['name'] ?? ''));
if ($sidebarUserName === '' && is_array($business ?? null)) {
    $sidebarUserName = trim((string) ($business['owner_name'] ?? ''));
}
if ($sidebarUserName === '') {
    $sidebarUserEmail = trim((string) ($user['email'] ?? ''));
    $sidebarUserName = $sidebarUserEmail !== '' ? (string) (explode('@', $sidebarUserEmail)[0] ?? 'Account') : 'Account';
}
$sidebarUserInitials = dash_initials_for_name($sidebarUserName);
$sidebarPlanLabel = is_array($plan ?? null) ? (string) ($plan['name'] ?? 'Active plan') : 'Active plan';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <?php
    $metaRobots = 'noindex, nofollow';
    require __DIR__ . '/../brand-meta.php';
    ?>
    <?php require __DIR__ . '/../brand-fonts.php'; ?>
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <script>
        window.LeadBirdCsrf = <?= json_encode(csrf_token(), JSON_THROW_ON_ERROR) ?>;
        (function () {
            var token = window.LeadBirdCsrf;
            if (!token || !window.fetch) return;
            var originalFetch = window.fetch.bind(window);
            window.fetch = function (input, init) {
                init = init || {};
                var method = (init.method || 'GET').toUpperCase();
                if (method !== 'GET' && method !== 'HEAD') {
                    var headers = new Headers(init.headers || {});
                    if (!headers.has('X-CSRF-Token')) {
                        headers.set('X-CSRF-Token', token);
                    }
                    init.headers = headers;
                }
                return originalFetch(input, init);
            };
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Google Sans Flex"', '"Google Sans"', 'sans-serif'],
                        heading: ['"Arial Black"', 'Impact', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            blue: 'var(--brand-blue)',
                            'blue-dark': 'var(--brand-blue-dark)',
                            lime: 'var(--brand-lime)',
                            'lime-hover': 'var(--brand-lime-hover)',
                            surface: 'var(--brand-surface)',
                        },
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/dashboard.css?v=40">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/dashboard-cta.css?v=7">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/dropdown.css?v=7">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/modal.css?v=3">
    <?php foreach ($extraStylesheets ?? [] as $stylesheet): ?>
        <?php if ($stylesheet !== 'dashboard.css'): ?>
            <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/<?= e($stylesheet) ?>">
        <?php endif; ?>
    <?php endforeach; ?>
</head>
<body class="dashboard-page font-sans antialiased<?= $dashboardHideTopbar ? ' dashboard-page--flush' : '' ?>" data-dashboard-nav="<?= e($dashboardNav) ?>">
<div class="dashboard-shell bg-brand-surface flex<?= $dashboardNavCollapsed ? ' dashboard-shell--nav-collapsed' : '' ?>">
    <div class="dashboard-overlay" data-dashboard-overlay hidden></div>

    <aside class="dashboard-sidebar" data-dashboard-sidebar id="dashboard-sidebar" aria-label="Dashboard menu">
        <div class="dashboard-sidebar-head">
            <div class="dashboard-sidebar-brand">
                <?php $logoSize = 'sidebar'; $logoHref = $dashboardUrl; require __DIR__ . '/../brand-logo.php'; ?>
            </div>
            <p class="dashboard-drawer-eyebrow font-heading">Navigation</p>
            <button type="button" class="dashboard-sidebar-close" data-dashboard-close aria-label="Close menu">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>

        <?php if (!empty($business['business_name'])): ?>
            <div class="dashboard-sidebar-business">
                <?php $switcherId = 'dash-business-switcher-drawer'; require __DIR__ . '/business-switcher.php'; ?>
            </div>
        <?php endif; ?>

        <nav class="dashboard-nav" aria-label="Dashboard">
            <?php
            $allNavItems = dashboard_nav_items($settings);
            foreach (dashboard_nav_groups() as $group):
                $groupId = (string) ($group['id'] ?? '');
                $groupLabel = isset($group['label']) ? (string) $group['label'] : '';
                $groupIcon = isset($group['icon']) ? (string) $group['icon'] : '';
                $itemKeys = is_array($group['items'] ?? null) ? $group['items'] : [];
                $groupIsActive = dashboard_nav_group_is_active($itemKeys, $dashboardNav);
                $panelId = 'dashboard-nav-group-' . $groupId;
                $groupIsPlain = $itemKeys !== [] && array_filter(
                    $itemKeys,
                    static fn (string $key): bool => (string) ($allNavItems[$key]['icon'] ?? '') !== ''
                ) === [];

                if ($groupLabel === ''):
                    foreach ($itemKeys as $key):
                        if (!isset($allNavItems[$key])) {
                            continue;
                        }
                        $subItems = dashboard_nav_sub_items($key, $dashboardNavContext);
                        if ($subItems !== []) {
                            dashboard_render_nav_item_with_subs($key, $allNavItems[$key], $dashboardNav, $planSlug, $subItems);
                        } else {
                            dashboard_render_nav_link($key, $allNavItems[$key], $dashboardNav, $planSlug);
                        }
                    endforeach;
                    continue;
                endif;
            ?>
                <div
                    class="dashboard-nav-group<?= $groupIsActive ? ' is-open is-active-group' : '' ?>"
                    data-dashboard-nav-group
                >
                    <button
                        type="button"
                        class="dashboard-nav-group-toggle"
                        aria-expanded="<?= $groupIsActive ? 'true' : 'false' ?>"
                        aria-controls="<?= e($panelId) ?>"
                    >
                        <span class="dashboard-nav-icon" aria-hidden="true">
                        <?php
                        $groupIconHtml = $groupIcon !== '' ? brand_dashboard_nav_icon_html($groupIcon) : null;
                        if ($groupIconHtml !== null):
                            echo $groupIconHtml;
                        endif;
                        ?>
                        </span>
                        <span class="dashboard-nav-group-label"><?= e($groupLabel) ?></span>
                        <svg class="dashboard-nav-group-chevron" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true">
                            <path d="M5 7.5 10 12.5 15 7.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="dashboard-nav-group-panel" id="<?= e($panelId) ?>"<?= $groupIsActive ? '' : ' hidden' ?>>
                        <?php foreach ($itemKeys as $key): ?>
                            <?php
                            if (!isset($allNavItems[$key])) {
                                continue;
                            }
                            dashboard_render_nav_link($key, $allNavItems[$key], $dashboardNav, $planSlug);
                            ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>

        <div class="dashboard-sidebar-foot">
            <div class="dashboard-user-card" data-dashboard-user-card>
                <span class="dashboard-user-avatar" aria-hidden="true"><?= e($sidebarUserInitials) ?></span>
                <div class="dashboard-user-meta">
                    <p class="dashboard-user-name"><?= e($sidebarUserName) ?></p>
                    <p class="dashboard-user-plan"><?= e($sidebarPlanLabel) ?></p>
                </div>
                <div class="dashboard-user-menu-wrap">
                    <button
                        type="button"
                        class="dashboard-user-menu-btn"
                        data-dashboard-user-menu-toggle
                        aria-label="Account menu"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <circle cx="5" cy="12" r="1.75"/>
                            <circle cx="12" cy="12" r="1.75"/>
                            <circle cx="19" cy="12" r="1.75"/>
                        </svg>
                    </button>
                    <div class="dashboard-user-menu" data-dashboard-user-menu hidden>
                        <a href="<?= $sidebarSettingsUrl ?>" class="dashboard-user-menu-link">Settings</a>
                        <a href="<?= $sidebarBillingUrl ?>" class="dashboard-user-menu-link">Billing</a>
                        <a href="<?= $logoutUrl ?>" class="dashboard-user-menu-link dashboard-user-menu-link--danger">Sign out</a>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <div class="dashboard-main flex-1 flex flex-col min-w-0">
        <header class="dashboard-topbar<?= $dashboardHideTopbar ? ' dashboard-topbar--hidden' : '' ?>">
            <div class="dashboard-topbar-inner">
                <?php if (!empty($pageBackUrl)): ?>
                    <div class="dashboard-topbar-start">
                        <a class="dashboard-topbar-back" href="<?= e($pageBackUrl) ?>" aria-label="Back"><svg viewBox="0 0 16 16" width="18" height="18" aria-hidden="true"><path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                        <h1 class="dashboard-topbar-title"><?= e($pageHeading) ?></h1>
                    </div>
                <?php else: ?>
                    <h1 class="dashboard-topbar-title"><?= e($pageHeading) ?></h1>
                <?php endif; ?>
                <?php if (!empty($business['business_name'])): ?>
                    <div class="dashboard-topbar-switcher">
                        <?php
                        $switcherId = 'dash-business-switcher-topbar';
                        $switcherCompact = true;
                        require __DIR__ . '/business-switcher.php';
                        ?>
                    </div>
                <?php endif; ?>

                <button
                    type="button"
                    class="dashboard-menu-btn"
                    data-dashboard-open
                    aria-label="Open menu"
                    aria-expanded="false"
                    aria-controls="dashboard-sidebar"
                >
                    <span class="dashboard-menu-btn-box" aria-hidden="true">
                        <span class="dashboard-menu-btn-line"></span>
                        <span class="dashboard-menu-btn-line"></span>
                        <span class="dashboard-menu-btn-line"></span>
                    </span>
                </button>
            </div>
        </header>

        <main class="dashboard-content flex-1">
        <div class="dashboard-sections">
