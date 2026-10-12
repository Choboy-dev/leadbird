<?php

declare(strict_types=1);

/** @var string $appUrl */

$footerBrandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');
$footerAssetBase = rtrim($appUrl, '/') . '/assets/images';

$footerColumns = [
    [
        ['Product', [
            ['WhatsApp salesgirl', app_route('channels/whatsapp')],
            ['Instagram salesgirl', app_route('channels/instagram')],
            ['TikTok for sellers', app_route('channels/tiktok')],
            ['Messenger salesgirl', app_route('channels/messenger')],
            ['Bulk WhatsApp Messaging', app_route('features/bulk-whatsapp')],
        ]],
    ],
    [
        ['Company', [
            ['Home', rtrim($appUrl, '/') . '/'],
            ['Contact us', app_route('contact')],
            ['Pricing', app_route('plans')],
            ['Help Center', app_route('help-center')],
            ['FAQs', app_route('faqs')],
        ]],
    ],
    [
        ['Partners', [
            ['Refer & Earn', app_route('affiliates')],
            ['Become an affiliate', app_route('affiliates/apply')],
        ]],
    ],
    [
        ['Free tools', [
            ['Chat Link Generator', app_route('tools/chat-link-generator')],
            ['QR Code Generator', app_route('tools/qr-code-generator')],
            ['Chat Button Generator', app_route('tools/chat-button-generator')],
        ]],
    ],
    [
        ['Legal', [
            ['Terms of Use', app_route('terms')],
            ['Privacy Policy', app_route('privacy')],
            ['Delete My Data', app_route('data-deletion')],
        ]],
    ],
];

$footerSocials = [
    ['Facebook', '<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>'],
    ['X', '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>'],
    ['LinkedIn', '<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 114.126 0 2.063 2.063 0 01-2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>'],
    ['Instagram', '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>'],
    ['TikTok', '<path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>'],
];

$footerAwards = [
    ['g2-leader-chatbots.svg', 'G2 Leader, Chatbots'],
    ['g2-momentum-leader.svg', 'G2 Momentum Leader, Chatbots'],
    ['g2-most-implementable.svg', 'G2 Most Implementable, WhatsApp Marketing'],
    ['g2-leader-mid-market.svg', 'G2 Leader Mid-Market, Chatbots'],
];
?>
<footer class="landing-ft">
    <div class="landing-ft-wrap">

        <div class="landing-ft-top">
            <div class="landing-ft-brand">
                <a href="<?= e(rtrim($appUrl, '/') . '/') ?>" class="landing-ft-logo-link" aria-label="<?= e($footerBrandName) ?> home">
                    <img class="landing-ft-logo" src="<?= e($footerAssetBase . '/leadbird-logo.png') ?>" alt="<?= e($footerBrandName) ?>" width="250" height="150" loading="lazy" decoding="async">
                </a>
                <p>Your AI salesgirl for WhatsApp, Instagram, Facebook and TikTok.</p>
                <div class="landing-ft-social">
                    <?php foreach ($footerSocials as [$label, $path]): ?>
                        <a href="#" aria-label="<?= e($label) ?>">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><?= $path ?></svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <nav class="landing-ft-cols" aria-label="Footer">
                <?php foreach ($footerColumns as $column): ?>
                    <div>
                        <?php foreach ($column as [$heading, $links]): ?>
                            <h6><?= e($heading) ?></h6>
                            <?php foreach ($links as [$label, $url]): ?>
                                <a href="<?= e($url) ?>"><?= e($label) ?></a>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="landing-ft-trust">
            <div class="landing-ft-trust-left">
                <img src="<?= e($footerAssetBase . '/ratings/meta-business-partner.svg') ?>" alt="Meta Business Partner" width="196" height="62" loading="lazy">
            </div>
            <div class="landing-ft-trust-row">
                <?php foreach ($footerAwards as [$file, $alt]): ?>
                    <img src="<?= e($footerAssetBase . '/ratings/' . $file) ?>" alt="<?= e($alt) ?>" width="100" height="118" loading="lazy">
                <?php endforeach; ?>
            </div>
        </div>

        <div class="landing-ft-bottom">
            <div class="landing-ft-legal">
                <span>&copy; <?= e($footerBrandName) ?> <?= date('Y') ?>. All rights reserved.</span>
                <a href="<?= e(app_route('terms')) ?>">Terms of Use</a>
                <a href="<?= e(app_route('privacy')) ?>">Privacy Policy</a>
                <a href="<?= e(app_route('data-deletion')) ?>">Delete My Data</a>
            </div>
        </div>

    </div>
</footer>
