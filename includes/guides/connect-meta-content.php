<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string $returnUrl */
/** @var string $returnLabel */

$checklistItems = [
    'A <strong>Facebook Page</strong> for your business (not a personal profile).',
    'Your Page inside a <strong>Meta Business Portfolio</strong> at business.facebook.com.',
    'Instagram as a <strong>Professional account</strong> linked to that Page.',
    '<strong>WhatsApp Business</strong> added to the same Portfolio.',
    '<strong>Admin access</strong> on the Page and Business account.',
];

$steps = [
    [
        'title' => 'Facebook Page & Business Portfolio',
        'intro' => 'The hub everything else connects to.',
        'items' => [
            'Go to <a href="https://business.facebook.com/" target="_blank" rel="noopener noreferrer">business.facebook.com</a> and sign in.',
            'Open <strong>Settings</strong> and confirm you have a Business Portfolio (create one if needed).',
            'Under <strong>Accounts → Pages</strong>, add or create your Facebook Page.',
            'Verify the Page shows <strong>Full control</strong> or admin access for you.',
        ],
        'tip' => '<strong>Tip:</strong> Selling only on Instagram? You still need a Facebook Page — Meta requires it.',
        'tip_type' => 'tip',
    ],
    [
        'title' => 'Link Instagram to your Page',
        'intro' => 'Instagram DMs route through your Facebook Page.',
        'items' => [
            'On Instagram: <strong>Settings → Account → Switch to professional account</strong> (Business or Creator).',
            'In Meta Business Suite: <strong>Linked accounts → Instagram</strong>.',
            'Tap <strong>Connect account</strong> and sign in to your selling profile.',
            'Confirm your @handle appears under the Page’s Instagram section.',
        ],
        'tip' => '<a href="https://www.facebook.com/business/help/898752960195806" target="_blank" rel="noopener noreferrer">Meta: Connect Instagram to your Page →</a>',
        'tip_type' => 'link',
    ],
    [
        'title' => 'Add WhatsApp to your Portfolio',
        'intro' => 'Your Business number must live in the same Portfolio as your Page.',
        'items' => [
            'In <a href="https://business.facebook.com/settings" target="_blank" rel="noopener noreferrer">Business settings</a>, open <strong>Accounts → WhatsApp accounts</strong>.',
            'Click <strong>Add</strong> and register your WhatsApp Business number.',
            'Complete SMS or voice verification when Meta prompts you.',
            'Assign the account to the same Portfolio as your Facebook Page.',
        ],
        'tip' => '<a href="https://www.facebook.com/business/help/2058515294227817" target="_blank" rel="noopener noreferrer">Meta: Get started with WhatsApp Business →</a>',
        'tip_type' => 'link',
    ],
    [
        'title' => 'Connect in Lead Bird',
        'intro' => 'Takes about a minute once Meta is linked.',
        'items' => [
            'Return to <strong>Connect your channels</strong> in Lead Bird.',
            'Click <strong>Connect Facebook Page</strong> and approve Meta access.',
            'Select the Page that owns your Instagram and WhatsApp.',
            'Connect Instagram and WhatsApp — we detect assets linked to that Page.',
        ],
        'tip' => null,
        'tip_type' => null,
    ],
];

$faqs = [
    [
        'q' => 'Instagram missing during connect?',
        'a' => 'Re-check the link in Meta Business Suite — the Instagram app alone is not enough.',
    ],
    [
        'q' => 'WhatsApp won’t connect?',
        'a' => 'Confirm WhatsApp Business (via Meta), same Portfolio, and that you’re an admin.',
    ],
    [
        'q' => 'Wrong Page listed?',
        'a' => 'Pick the Page tied to the brand you’re setting up — you may manage several.',
    ],
    [
        'q' => 'Still stuck?',
        'a' => '<a href="' . e(app_route('contact')) . '\">Contact support</a> with your Page name and @handle.',
    ],
];
?>
<article class="mg">
    <section class="mg-flow" data-reveal aria-label="Connection flow">
        <div class="mg-flow-track">
            <div class="mg-flow-node mg-flow-node--facebook">
                <span class="mg-flow-icon"><?= brand_messenger_icon_html('', 22) ?></span>
                <span class="mg-flow-label">Facebook</span>
            </div>
            <span class="mg-flow-connector" aria-hidden="true"></span>
            <div class="mg-flow-node mg-flow-node--instagram">
                <span class="mg-flow-icon"><?= brand_instagram_icon_html('', 22) ?></span>
                <span class="mg-flow-label">Instagram</span>
            </div>
            <span class="mg-flow-connector" aria-hidden="true"></span>
            <div class="mg-flow-node mg-flow-node--whatsapp">
                <span class="mg-flow-icon"><?= brand_whatsapp_icon_html('', 22) ?></span>
                <span class="mg-flow-label">WhatsApp</span>
            </div>
        </div>
        <p class="mg-flow-caption">All three must link to the same Facebook Page before you connect in Lead Bird.</p>
    </section>

    <section class="mg-block" data-reveal>
        <header class="mg-block-head">
            <h2 class="mg-block-title font-heading">Before you connect</h2>
            <p class="mg-block-lead">Have these ready in Meta — it saves time when you return to Lead Bird.</p>
        </header>
        <ul class="mg-checklist">
            <?php foreach ($checklistItems as $item): ?>
                <li class="mg-checklist-item">
                    <span class="mg-checklist-mark" aria-hidden="true"></span>
                    <span><?= $item ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="mg-block mg-block--steps" data-reveal>
        <header class="mg-block-head">
            <h2 class="mg-block-title font-heading">Step-by-step</h2>
            <p class="mg-block-lead">Work through each step in order. You can keep this tab open while you use Meta.</p>
        </header>

        <ol class="mg-steps">
            <?php foreach ($steps as $index => $step): ?>
                <li class="mg-step">
                    <div class="mg-step-rail" aria-hidden="true">
                        <span class="mg-step-num font-heading"><?= (int) ($index + 1) ?></span>
                        <?php if ($index < count($steps) - 1): ?>
                            <span class="mg-step-line"></span>
                        <?php endif; ?>
                    </div>
                    <div class="mg-step-card">
                        <header class="mg-step-header">
                            <h3 class="mg-step-title font-heading"><?= e($step['title']) ?></h3>
                            <p class="mg-step-intro"><?= e($step['intro']) ?></p>
                        </header>
                        <ol class="mg-step-list">
                            <?php foreach ($step['items'] as $item): ?>
                                <li><?= $item ?></li>
                            <?php endforeach; ?>
                        </ol>
                        <?php if ($step['tip'] !== null): ?>
                            <p class="mg-callout mg-callout--<?= e((string) $step['tip_type']) ?>"><?= $step['tip'] ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

    <section class="mg-block" data-reveal>
        <header class="mg-block-head">
            <h2 class="mg-block-title font-heading">Troubleshooting</h2>
            <p class="mg-block-lead">Quick fixes for the most common setup issues.</p>
        </header>
        <div class="mg-faq-grid">
            <?php foreach ($faqs as $faq): ?>
                <article class="mg-faq-card">
                    <h3 class="mg-faq-question font-heading"><?= e($faq['q']) ?></h3>
                    <p class="mg-faq-answer"><?= $faq['a'] ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <footer class="mg-footer" data-reveal>
        <a href="<?= e($returnUrl) ?>" class="mg-cta font-heading"><?= e($returnLabel) ?></a>
    </footer>
</article>

<script src="<?= e($appUrl) ?>/assets/js/guide-meta-channels.js?v=2"></script>
