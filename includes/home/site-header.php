<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string|null $activeNav */

$activeNav = $activeNav ?? null;
$siteHomeUrl = rtrim($appUrl, '/') . '/';
$siteSignupUrl = app_route('signup');
$siteLoginUrl = app_route('login');
$sitePlansUrl = app_route('plans');
$siteContactUrl = app_route('contact');
$siteHelpUrl = app_route('help-center');
$siteBrandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');

$siteSolutions = [
    ['Teams', [
        ['Lead Bird for Sales', 'Answer every “How much?” in seconds and close deals in the DMs', app_route('solutions/sales')],
        ['Lead Bird for Marketing', 'Plan posts, grow your pages and turn ad clicks and DMs into sales', app_route('solutions/marketing')],
        ['Lead Bird for Support', 'Instant replies on delivery, sizes and orders, day and night', app_route('solutions/support')],
    ]],
    ['Businesses', [
        ['Fashion & beauty', '', app_route('industries/fashion-beauty')],
        ['Food & small vendors', '', app_route('industries/food-vendors')],
        ['Services & bookings', '', app_route('industries/services-bookings')],
        ['Health & wellness', '', app_route('industries/health-wellness')],
        ['Schools & training', '', app_route('industries/schools-training')],
        ['Events & travel', '', app_route('industries/events-travel')],
    ]],
];

$siteProduct = [
    ['Channels', [
        ['WhatsApp', '', app_route('channels/whatsapp')],
        ['Instagram', '', app_route('channels/instagram')],
        ['TikTok', '', app_route('channels/tiktok')],
        ['Messenger', '', app_route('channels/messenger')],
    ]],
    ['Features', [
        ['Bulk WhatsApp Messaging', 'Send bulk messages to all your customers', app_route('features/bulk-whatsapp')],
    ]],
];

$siteResources = [
    ['Help Center', $siteHelpUrl],
    ['Contact us', $siteContactUrl],
    ['Refer & Earn', app_route('affiliates')],
];

$siteFreeTools = [
    ['Chat Link Generator', 'Make a link that opens a chat with your business', app_route('tools/chat-link-generator')],
    ['QR Code Generator', 'Print a code customers scan to message you', app_route('tools/qr-code-generator')],
    ['Chat Button Generator', 'Add a “Chat with us” button to your website', app_route('tools/chat-button-generator')],
];

$sitePartners = [
    ['Refer & Earn', app_route('affiliates')],
    ['Become an affiliate', app_route('affiliates/apply')],
];

$siteCurrent = static fn (string $key): string => $activeNav === $key ? ' aria-current="page"' : '';
?>
<div class="site-util-bar">
    <div class="site-util-inner">
        <a href="<?= e($siteHelpUrl) ?>">Help Center</a>

        <div class="site-has-menu">
            <button type="button" class="site-menu-trigger" aria-haspopup="true" aria-expanded="false">Partners</button>
            <div class="site-mega">
                <div class="site-util-menu">
                    <?php foreach ($sitePartners as [$label, $url]): ?>
                        <a href="<?= e($url) ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="site-util-right">
            <span class="site-lang">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M3 12h18"></path>
                    <path d="M12 3c2.6 2.7 2.6 15.3 0 18"></path>
                    <path d="M12 3c-2.6 2.7-2.6 15.3 0 18"></path>
                </svg>
                English
            </span>
            <a class="site-login" href="<?= e($siteLoginUrl) ?>">Log in</a>
        </div>
    </div>
</div>

<header class="site-nav<?= ($activeNav ?? null) === 'home' ? ' site-nav--overlay' : '' ?>" data-site-nav>
    <div class="site-nav-inner">
        <a class="site-nav-logo-link" href="<?= e($siteHomeUrl) ?>" aria-label="<?= e($siteBrandName) ?> home">
            <img class="site-nav-logo" src="<?= e(rtrim($appUrl, '/')) ?>/assets/images/leadbird-logo.png" alt="<?= e($siteBrandName) ?>" width="250" height="150">
        </a>

        <nav class="site-nav-links" aria-label="Main">
            <div class="site-has-menu">
                <button type="button" class="site-menu-trigger" aria-haspopup="true" aria-expanded="false">Solutions</button>
                <div class="site-mega">
                    <div class="site-mega-cols">
                        <?php foreach ($siteSolutions as [$heading, $links]): ?>
                            <div class="site-mega-col">
                                <h6><?= $heading === '&nbsp;' ? '&nbsp;' : e($heading) ?></h6>
                                <?php foreach ($links as [$label, $desc, $url]): ?>
                                    <a href="<?= e($url) ?>"><?= e($label) ?><?php if ($desc !== ''): ?><span><?= e($desc) ?></span><?php endif; ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="site-has-menu">
                <button type="button" class="site-menu-trigger" aria-haspopup="true" aria-expanded="false">Product</button>
                <div class="site-mega">
                    <div class="site-mega-cols">
                        <?php foreach ($siteProduct as [$heading, $links]): ?>
                            <div class="site-mega-col">
                                <h6><?= $heading === '&nbsp;' ? '&nbsp;' : e($heading) ?></h6>
                                <?php foreach ($links as [$label, $desc, $url]): ?>
                                    <a href="<?= e($url) ?>"><?= e($label) ?><?php if ($desc !== ''): ?><span><?= e($desc) ?></span><?php endif; ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="site-has-menu">
                <button type="button" class="site-menu-trigger" aria-haspopup="true" aria-expanded="false">Resources</button>
                <div class="site-mega">
                    <div class="site-mega-cols">
                        <div class="site-mega-col">
                            <h6>Resources</h6>
                            <?php foreach ($siteResources as [$label, $url]): ?>
                                <a href="<?= e($url) ?>"><?= e($label) ?></a>
                            <?php endforeach; ?>
                        </div>
                        <div class="site-mega-col">
                            <h6>Free tools</h6>
                            <?php foreach ($siteFreeTools as [$label, $desc, $url]): ?>
                                <a href="<?= e($url) ?>"><?= e($label) ?><span><?= e($desc) ?></span></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <a href="<?= e($sitePlansUrl) ?>"<?= $siteCurrent('pricing') ?>>Pricing</a>
        </nav>

        <div class="site-nav-cta">
            <a class="site-btn site-btn-outline site-btn-sm" href="<?= e($siteContactUrl) ?>">Talk to us</a>
            <a class="site-btn site-btn-primary site-btn-sm" href="<?= e($siteSignupUrl) ?>">Get started</a>
        </div>

        <button type="button" class="site-menu-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="site-mobile-menu" data-site-menu-toggle>
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path class="site-menu-icon-open" d="M4 7h16M4 12h16M4 17h16"></path>
                <path class="site-menu-icon-close" d="M6 6l12 12M18 6L6 18"></path>
            </svg>
        </button>
    </div>

    <div class="site-menu-backdrop" data-site-menu-backdrop aria-hidden="true"></div>
    <div class="site-mobile-menu" id="site-mobile-menu" hidden data-site-mobile-menu>
        <nav class="site-mobile-nav" aria-label="Mobile">
            <?php foreach ([['Solutions', $siteSolutions], ['Product', $siteProduct]] as [$groupLabel, $groupCols]): ?>
                <details class="site-mobile-group">
                    <summary><?= e($groupLabel) ?></summary>
                    <div class="site-mobile-links">
                        <?php foreach ($groupCols as [, $links]): ?>
                            <?php foreach ($links as [$label, , $url]): ?>
                                <a href="<?= e($url) ?>"><?= e($label) ?></a>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>
            <details class="site-mobile-group">
                <summary>Resources</summary>
                <div class="site-mobile-links">
                    <?php foreach ($siteResources as [$label, $url]): ?>
                        <a href="<?= e($url) ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </div>
            </details>
            <details class="site-mobile-group">
                <summary>Free tools</summary>
                <div class="site-mobile-links">
                    <?php foreach ($siteFreeTools as [$label, , $url]): ?>
                        <a href="<?= e($url) ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </div>
            </details>
            <details class="site-mobile-group">
                <summary>Partners</summary>
                <div class="site-mobile-links">
                    <?php foreach ($sitePartners as [$label, $url]): ?>
                        <a href="<?= e($url) ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </div>
            </details>
            <a class="site-mobile-link" href="<?= e($sitePlansUrl) ?>">Pricing</a>
        </nav>
        <div class="site-mobile-cta">
            <a class="site-btn site-btn-primary" href="<?= e($siteSignupUrl) ?>">Get started</a>
            <a class="site-btn site-btn-outline" href="<?= e($siteLoginUrl) ?>">Log in</a>
        </div>
    </div>
</header>
