<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string $pageTitle */
/** @var string $adminNav */
/** @var string $pageHeading */
/** @var array{id: string, email: string, name?: ?string}|null $adminUser */

require_once __DIR__ . '/nav.php';
require_once __DIR__ . '/helpers.php';

$homeUrl = e($appUrl) . '/';
$adminUrl = e(app_route('admin'));
$logoutUrl = e(app_route('auth/logout'));
$navItems = admin_nav_items();
$pageHeading = $pageHeading ?? ($navItems[$adminNav]['label'] ?? 'Admin');
$sidebarUserName = trim((string) ($adminUser['name'] ?? ''));
if ($sidebarUserName === '') {
    $sidebarUserEmail = trim((string) ($adminUser['email'] ?? ''));
    $sidebarUserName = $sidebarUserEmail !== '' ? (string) (explode('@', $sidebarUserEmail)[0] ?? 'Admin') : 'Admin';
}
$sidebarUserInitials = function_exists('dash_initials_for_name')
    ? dash_initials_for_name($sidebarUserName)
    : strtoupper(substr($sidebarUserName, 0, 2));
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
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/brand.css?v=18">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/dashboard.css?v=36">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/dashboard-cta.css?v=7">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/dropdown.css?v=6">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/modal.css?v=3">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/admin-dashboard.css?v=6">
    <?php foreach ($extraStylesheets ?? [] as $stylesheet): ?>
        <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/<?= e($stylesheet) ?>">
    <?php endforeach; ?>
</head>
<body class="dashboard-page admin-page font-sans antialiased" data-admin-nav="<?= e($adminNav) ?>">
<?php if (AdminAuth::isImpersonating()): ?>
    <div class="admin-impersonation-banner" role="status">
        <span class="font-sans">You are viewing the app as another user.</span>
        <a href="<?= e(app_route('admin/stop-impersonate')) ?>" class="admin-impersonation-stop font-heading">Stop impersonating</a>
    </div>
<?php endif; ?>
<div class="dashboard-shell bg-brand-surface flex">
    <div class="dashboard-overlay" data-dashboard-overlay hidden></div>

    <aside class="dashboard-sidebar admin-sidebar" data-dashboard-sidebar id="dashboard-sidebar" aria-label="Admin menu">
        <div class="dashboard-sidebar-head">
            <div class="dashboard-sidebar-brand">
                <?php $logoSize = 'sidebar'; $logoHref = $adminUrl; require __DIR__ . '/../brand-logo.php'; ?>
            </div>
            <p class="admin-sidebar-eyebrow font-heading">Admin portal</p>
            <button type="button" class="dashboard-sidebar-close" data-dashboard-close aria-label="Close menu">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>

        <nav class="dashboard-nav admin-nav" aria-label="Admin" data-admin-nav-accordion>
            <?php foreach (admin_nav_groups() as $group): ?>
                <?php admin_render_nav_group($group, $navItems, $adminNav); ?>
            <?php endforeach; ?>
        </nav>

        <div class="dashboard-sidebar-foot">
            <div class="dashboard-user-card" data-dashboard-user-card>
                <span class="dashboard-user-avatar" aria-hidden="true"><?= e($sidebarUserInitials) ?></span>
                <div class="dashboard-user-meta">
                    <p class="dashboard-user-name"><?= e($sidebarUserName) ?></p>
                    <p class="dashboard-user-plan">Administrator</p>
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
                        <a href="<?= $logoutUrl ?>" class="dashboard-user-menu-link dashboard-user-menu-link--danger">Sign out</a>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <div class="dashboard-main flex-1 flex flex-col min-w-0">
        <header class="dashboard-topbar">
            <div class="dashboard-topbar-inner">
                <h1 class="dashboard-topbar-title"><?= e($pageHeading) ?></h1>
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
        <div class="dashboard-sections admin-sections">
