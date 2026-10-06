<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_auth.php';
require_once dirname(__DIR__) . '/includes/brand-ai-icon.php';

$appUrl = rtrim((string) app_config()['app_url'], '/');
$brandName = (string) app_config()['brand_name'];
$user = (new SessionAuth())->user();

$returnUrl = safe_return_url(
    trim((string) ($_GET['return_to'] ?? '')),
    app_route('onboarding/channels')
);

$returnLabel = str_contains($returnUrl, 'connected-accounts')
    ? 'Back to connected accounts'
    : 'Back to connect channels';

$pageTitle = 'Link Facebook, Instagram & WhatsApp';
$homeUrl = e($appUrl) . '/';
$logoutUrl = e(app_route('auth/logout'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e($brandName) ?></title>
    <?php
    $metaTitle = $pageTitle . ' — ' . $brandName;
    $metaDescription = 'Step-by-step guide to link your Instagram and WhatsApp to Facebook so Lead Bird can reply in your DMs.';
    $metaRobots = 'noindex, nofollow';
    require __DIR__ . '/../includes/brand-meta.php';
    ?>
    <?php require __DIR__ . '/../includes/brand-fonts.php'; ?>
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
                            lime: 'var(--brand-lime)',
                            'lime-hover': 'var(--brand-lime-hover)',
                            surface: 'var(--brand-surface)',
                        },
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/onboarding.css?v=12">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/guide-meta-channels.css?v=4">
</head>
<body class="onboarding-page mg-page font-sans antialiased">
<div class="onboarding-shell min-h-screen bg-brand-blue relative overflow-hidden">
    <div class="onboarding-grid absolute inset-0 pointer-events-none" aria-hidden="true"></div>

    <header class="onboarding-header relative z-10 w-full px-5 md:px-10 pt-6 md:pt-8 pb-2">
        <div class="onboarding-header-inner">
            <?php $logoSize = 'md'; $logoHref = $homeUrl; require __DIR__ . '/../includes/brand-logo.php'; ?>
            <div class="onboarding-user-meta">
                <p class="onboarding-user-text">
                    <span class="hidden sm:inline">Signed in as </span>
                    <span class="font-semibold text-white"><?= e($user['email'] ?? '') ?></span>
                </p>
                <a href="<?= e($returnUrl) ?>" class="mg-back-btn"><?= e($returnLabel) ?></a>
            </div>
        </div>
    </header>

    <main class="mg-main relative z-10 w-full max-w-3xl mx-auto px-5 pt-6 pb-20 md:pb-24">
        <header class="mg-hero" data-reveal>
            <p class="mg-eyebrow">Setup guide</p>
            <h1 class="mg-hero-title font-heading"><?= e($pageTitle) ?></h1>
            <p class="mg-hero-lead">
                Link your accounts in Meta first — then one login in Lead Bird connects your Salesgirl to every DM.
            </p>
        </header>

        <div class="mg-sheet">
            <?php require __DIR__ . '/../includes/guides/connect-meta-content.php'; ?>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/site-assistant/embed.php'; ?>
</body>
</html>
