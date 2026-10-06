<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string $pageTitle */
/** @var string $stepLead */
/** @var int $onboardingStep */
/** @var array{id: string, email: string, name: ?string}|null $user */
/** @var string|false|null $error */
/** @var string|false|null $success */

$brandName = app_config()['brand_name'];
$homeUrl = e($appUrl) . '/';
$logoutUrl = e(app_route('auth/logout'));
$stepMeta = OnboardingService::STEPS[$onboardingStep] ?? OnboardingService::STEPS[OnboardingService::STEP_PROFILE];
$stepLabel = (string) $stepMeta['label'];
$totalSteps = count(OnboardingService::STEPS);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e($brandName) ?></title>
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
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/onboarding.css?v=22">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/dropdown.css?v=6">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/modal.css?v=3">
    <?php foreach ($extraStylesheets ?? [] as $stylesheet): ?>
        <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/<?= e($stylesheet) ?>">
    <?php endforeach; ?>
</head>
<body class="onboarding-page font-sans antialiased<?= isset($onboardingBodyClass) && $onboardingBodyClass !== '' ? ' ' . e($onboardingBodyClass) : '' ?>">
<div class="onboarding-shell min-h-screen bg-brand-blue relative overflow-hidden">
    <div class="onboarding-grid absolute inset-0 pointer-events-none" aria-hidden="true"></div>

    <header class="onboarding-header relative z-10 w-full px-6 md:px-10 pt-8 pb-4">
        <div class="onboarding-header-inner">
            <?php $logoSize = 'md'; require __DIR__ . '/../brand-logo.php'; ?>
            <div class="onboarding-user-meta">
                <p class="onboarding-user-text">
                    <span class="hidden sm:inline">Signed in as </span>
                    <span class="font-semibold text-white"><?= e($user['email'] ?? '') ?></span>
                </p>
                <a href="<?= $logoutUrl ?>" class="onboarding-signout-btn">Sign out</a>
            </div>
        </div>
    </header>

    <main class="relative z-10 px-6 pt-[50px] pb-16 max-w-3xl mx-auto w-full">
        <?php if (empty($hideOnboardingProgress)): ?>
        <nav class="onboarding-progress" aria-label="Onboarding progress">
            <ol class="onboarding-progress-list">
                <?php foreach (OnboardingService::STEPS as $num => $step): ?>
                    <?php
                    $state = $num < $onboardingStep ? 'done' : ($num === $onboardingStep ? 'current' : 'upcoming');
                    ?>
                    <li class="onboarding-progress-item onboarding-progress-item--<?= e($state) ?>">
                        <span class="onboarding-progress-dot" aria-hidden="true"><?= $num < $onboardingStep ? '✓' : (string) $num ?></span>
                        <span class="onboarding-progress-label"><?= e((string) $step['label']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>

        <div class="onboarding-card">
            <div class="onboarding-card-head">
                <?php if (empty($hideOnboardingProgress)): ?>
                    <p class="onboarding-eyebrow">Step <?= (int) $onboardingStep ?> of <?= (int) $totalSteps ?></p>
                <?php endif; ?>
                <h1 class="onboarding-title font-heading"><?= e($stepHeading ?? $stepLabel) ?></h1>
                <?php if (($stepLead ?? '') !== ''): ?>
                    <p class="onboarding-lead"><?= e($stepLead) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($error): ?>
                <div class="onboarding-alert onboarding-alert--error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="onboarding-alert onboarding-alert--success" role="status"><?= e($success) ?></div>
            <?php endif; ?>
