<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string|null $activeNav */

$activeNav = $activeNav ?? null;
$loginUrl = e(app_route('login'));
$signupUrl = e(app_route('signup'));
$homeUrl = e($appUrl) . '/';
$pricingUrl = e(app_route('plans'));
$contactUrl = e(app_route('contact'));

$navLinkClass = static function (string $page) use ($activeNav): string {
    $base = 'contact-nav-link';
    return $activeNav === $page ? $base . ' contact-nav-link--active' : $base;
};
?>
<header class="contact-header shrink-0 w-full bg-brand-blue">
    <div class="brand-grid-bg absolute inset-0 pointer-events-none opacity-60" aria-hidden="true"></div>
    <div class="relative px-6 py-4 md:px-10 md:py-5 max-w-[1440px] mx-auto w-full flex items-center justify-between gap-4">
        <?php $logoSize = 'md'; $logoHref = $homeUrl; require __DIR__ . '/../brand-logo.php'; ?>

        <nav class="hidden md:flex items-center gap-2" aria-label="Main">
            <a href="<?= $homeUrl ?>" class="<?= e($navLinkClass('home')) ?>"<?= $activeNav === 'home' ? ' aria-current="page"' : '' ?>>Home</a>
            <a href="<?= $pricingUrl ?>" class="<?= e($navLinkClass('pricing')) ?>"<?= $activeNav === 'pricing' ? ' aria-current="page"' : '' ?>>Pricing</a>
            <a href="<?= $contactUrl ?>" class="<?= e($navLinkClass('contact')) ?>"<?= $activeNav === 'contact' ? ' aria-current="page"' : '' ?>>Contact</a>
        </nav>

        <div class="flex items-center gap-2 shrink-0">
            <a href="<?= $loginUrl ?>" class="contact-nav-auth contact-nav-auth--outline">Sign in</a>
            <a href="<?= $signupUrl ?>" class="contact-nav-auth contact-nav-auth--solid">Sign up</a>
        </div>
    </div>
</header>
