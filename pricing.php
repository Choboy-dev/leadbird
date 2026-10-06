<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_auth.php';
require_once __DIR__ . '/includes/pricing/cards.php';
require_once __DIR__ . '/includes/pricing/carousel.php';

$appUrl = app_config()['app_url'];
$user = (new SessionAuth())->user();
$userId = (new SessionAuth())->userId();

if ($userId !== null && (new SubscriptionService())->hasActiveSubscription($userId)) {
    redirect(app_route('app'));
}

PlanSelection::captureFromRequest();
$pendingPlan = PlanSelection::pendingSlug();
$pricingTiers = PlanService::list();
$error = flash_get('error');
$success = flash_get('success');
$brandName = app_config()['brand_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose your plan — <?= e($brandName) ?></title>
    <?php
    $metaTitle = 'Choose your plan — ' . $brandName;
    $metaDescription = 'Pick the Lead Bird plan that fits your business. AI salesgirl for WhatsApp, Instagram, and Messenger DMs.';
    $metaUrl = app_route('pricing');
    $metaRobots = 'noindex, nofollow';
    require __DIR__ . '/includes/brand-meta.php';
    ?>
    <?php require __DIR__ . '/includes/brand-fonts.php'; ?>
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
                        },
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/pricing.css">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/pricing-carousel.css?v=4">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/onboarding.css?v=12">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/modal.css?v=2">
</head>
<body class="pricing-page font-sans antialiased">
<div class="pricing-shell min-h-screen bg-brand-blue relative overflow-hidden">
    <div class="pricing-grid absolute inset-0 pointer-events-none" aria-hidden="true"></div>

    <header class="onboarding-header relative z-10 w-full px-6 md:px-10 pt-8 pb-4">
        <div class="onboarding-header-inner">
            <?php $logoSize = 'md'; $logoHref = e($appUrl) . '/'; require __DIR__ . '/includes/brand-logo.php'; ?>
            <div class="onboarding-user-meta">
                <p class="onboarding-user-text">
                    <span class="hidden sm:inline">Signed in as </span>
                    <span class="font-semibold text-white"><?= e($user['email'] ?? '') ?></span>
                </p>
                <a href="<?= e(app_route('auth/logout')) ?>" class="onboarding-signout-btn">Sign out</a>
            </div>
        </div>
    </header>

    <main class="relative z-10 px-6 pt-[50px] pb-16 max-w-6xl mx-auto w-full">
        <div class="text-center mb-10 md:mb-14">
            <p class="text-brand-lime font-heading text-sm uppercase tracking-widest mb-3">Step 1 · Subscription</p>
            <h1 class="font-heading text-3xl md:text-5xl text-white mb-4">Pick the plan that fits your hustle</h1>
            <p class="text-lg text-white/85 max-w-2xl mx-auto">
                <?php if ($pendingPlan !== null): ?>
                    <?php $selected = PlanService::find($pendingPlan); ?>
                    You're almost there — complete payment for the
                    <strong class="text-white"><?= e((string) ($selected['name'] ?? ucfirst($pendingPlan))) ?></strong>
                    plan to continue setup.
                <?php else: ?>
                    Every plan renews monthly. Pick how many businesses you want — each one gets its own Facebook, Instagram, and WhatsApp.
                <?php endif; ?>
            </p>
        </div>

        <?php if ($error !== null): ?>
            <div class="pricing-alert pricing-alert-error mb-8" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success !== null): ?>
            <div class="pricing-alert pricing-alert-success mb-8" role="status"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="flex justify-center mb-8">
            <?php require __DIR__ . '/includes/pricing/billing-cycle-toggle.php'; pricing_billing_cycle_toggle(); ?>
        </div>

        <?php pricing_carousel_open(); ?>
            <?php foreach ($pricingTiers as $index => $tier): ?>
                <div class="pricing-carousel-slide">
                    <?php pricing_render_tier($tier, $index, 'checkout', $pendingPlan, true); ?>
                </div>
            <?php endforeach; ?>
        <?php pricing_carousel_close(); ?>

        <p class="text-center text-sm text-white/70 max-w-xl mx-auto">
            Secure payment powered by Flutterwave. Cancel anytime from your dashboard.
        </p>
    </main>

    <div class="pricing-checkout-overlay hidden" data-checkout-overlay aria-hidden="true">
        <div class="pricing-checkout-panel" role="dialog" aria-modal="true" aria-labelledby="checkout-title">
            <div class="pricing-checkout-spinner" aria-hidden="true"></div>
            <h2 id="checkout-title" class="font-heading text-xl text-zinc-900 mb-2">Opening secure checkout</h2>
            <p class="text-zinc-600" data-checkout-message>Redirecting you to Flutterwave…</p>
        </div>
    </div>
</div>

<script>
    window.LeadBirdCsrf = <?= json_encode(csrf_token(), JSON_THROW_ON_ERROR) ?>;
    window.LeadBirdPricing = {
        initiateUrl: <?= json_encode(app_route('billing/initiate'), JSON_THROW_ON_ERROR) ?>,
        autoPlan: <?= json_encode($pendingPlan, JSON_THROW_ON_ERROR) ?>,
        csrfToken: window.LeadBirdCsrf,
    };
</script>
<script src="<?= e($appUrl) ?>/assets/js/modal.js?v=2"></script>
<script src="<?= e($appUrl) ?>/assets/js/pricing-carousel.js?v=2"></script>
<script src="<?= e($appUrl) ?>/assets/js/pricing.js?v=3"></script>
<?php require __DIR__ . '/includes/site-assistant/embed.php'; ?>
</body>
</html>
