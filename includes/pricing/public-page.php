<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var list<array<string, mixed>> $plans */
/** @var list<array{slug: string, name: string, tokens: int, price: int, badge?: string}> $topups */

// Plan names, prices, taglines, and feature lists all come from config/plans.php
// so this page, the homepage section, and checkout never disagree.
$planCtaLabels = [
    'starter' => 'Start with Starter',
    'vendor' => 'Choose Vendor',
    'shop' => 'Choose Shop',
];

$comparison = [
    [
        'title' => 'What you get',
        'rows' => [
            ['label' => 'Businesses you can run', 'values' => ['1', '3', 'As many as you need']],
            ['label' => 'Facebook Pages', 'values' => ['1', '1 for each business', 'As many as you need']],
            ['label' => 'Instagram accounts', 'values' => ['1', '1 for each business', 'As many as you need']],
            ['label' => 'WhatsApp numbers', 'values' => ['1', '1 for each business', 'As many as you need']],
            ['label' => 'Replies each month', 'values' => ['500', '2,000', '8,000']],
            ['label' => 'Buy extra replies', 'values' => [true, true, true]],
        ],
    ],
    [
        'title' => 'What your salesgirl does',
        'rows' => [
            ['label' => 'Answers questions about your products', 'values' => [true, true, true]],
            ['label' => 'Tells customers your prices', 'values' => [true, true, true]],
            ['label' => 'Sends your payment details', 'values' => [true, true, true]],
            ['label' => 'Replies day and night', 'values' => [true, true, true]],
            ['label' => 'Answers in your own tone of voice', 'values' => [true, true, true]],
            ['label' => 'Agrees on a price with the customer', 'values' => [false, true, true]],
            ['label' => 'Moves ready buyers to WhatsApp', 'values' => [false, true, true]],
            ['label' => 'Follows up when a customer goes quiet', 'values' => [false, false, true]],
            ['label' => 'Sends a receipt after payment', 'values' => [false, false, true]],
        ],
    ],
    [
        'title' => 'Social media posts',
        'rows' => [
            ['label' => 'Writes post captions for you', 'values' => [false, true, true]],
            ['label' => 'Plans posts ahead on a calendar', 'values' => [false, true, true]],
            ['label' => 'Publishes to Facebook and Instagram', 'values' => [false, true, true]],
        ],
    ],
    [
        'title' => 'Paying and account',
        'rows' => [
            ['label' => 'Pay with a card', 'values' => [true, true, true]],
            ['label' => 'Pay with a bank transfer', 'values' => [true, true, true]],
            ['label' => 'How often you pay', 'values' => ['Monthly or yearly', 'Monthly or yearly', 'Monthly or yearly']],
            ['label' => 'Cancel whenever you want', 'values' => [true, true, true]],
            ['label' => 'Move up or down a plan any time', 'values' => [true, true, true]],
        ],
    ],
];

$faqGroups = [
    [
        'id' => 'plans',
        'title' => 'Plans and replies',
        'items' => [
            [
                'q' => 'What is a reply?',
                'a' => 'Tokens power AI replies, social AI writing, publishing, and other Lead Bird AI features. Starter includes 500 tokens each month, Vendor 2,000, and Shop 8,000. Unused tokens expire at renewal. You can buy more anytime from Billing.',
            ],
            [
                'q' => 'What happens when my replies finish?',
                'a' => 'Your salesgirl stops answering until the month resets or until you buy more. You can buy more at any time from your dashboard and they show up straight away. Nothing else about your account changes.',
            ],
            [
                'q' => 'Which plan should I pick?',
                'a' => 'Pick Starter if you sell from one shop and you are still testing things out. Pick Vendor if you run two or three shops, or you want help writing and posting on social media. Pick Shop if messages come in all day and you do not want to think about limits.',
            ],
            [
                'q' => 'Can I run more than one business on Starter?',
                'a' => 'No. Starter covers one business. If you need a second one, move up to Vendor and you get three. You keep everything you already set up when you move.',
            ],
            [
                'q' => 'Do I need a Facebook Page to use Instagram?',
                'a' => 'Yes. Instagram asks you to link your account to a Facebook Page before another app can read and answer your messages. Your Instagram account also needs to be a business or creator account, which is free to switch to inside the Instagram app.',
            ],
            [
                'q' => 'Will she answer in my own words?',
                'a' => 'Yes. You tell her about your products, your prices, your delivery rules and how you like to talk to customers. She uses that, so buyers get answers that sound like your shop and not like a robot.',
            ],
        ],
    ],
    [
        'id' => 'paying',
        'title' => 'Paying for your plan',
        'items' => [
            [
                'q' => 'How do I pay?',
                'a' => 'You pay with a card or a bank transfer through Flutterwave. We never see or keep your card details. Prices on this page are in US dollars. Nigerian customers are charged the naira equivalent at checkout.',
            ],
            [
                'q' => 'Is it monthly or yearly?',
                'a' => 'Every plan is monthly by default. You can also pay yearly and get two months free. Cancel whenever you want from your dashboard.',
            ],
            [
                'q' => 'What do extra replies cost?',
                'a' => 'You can buy a pack of extra replies at any time without changing your plan. They sit on top of the replies your plan already gives you each month.',
            ],
            [
                'q' => 'Do you give refunds?',
                'a' => 'If something on our side stops your salesgirl from working and we cannot fix it, write to support@leadbird.app and we will sort it out with you. We do not refund months you have already used.',
            ],
        ],
    ],
    [
        'id' => 'managing',
        'title' => 'Changing your plan',
        'items' => [
            [
                'q' => 'How do I move to a bigger plan?',
                'a' => 'Open Billing in your dashboard and pick the plan you want. The new plan starts right away and your shops, chats and settings stay exactly as they are.',
            ],
            [
                'q' => 'How do I cancel?',
                'a' => 'Open Billing in your dashboard and press cancel. Your plan keeps working until the end of the month you already paid for, then it stops. We do not charge you again.',
            ],
            [
                'q' => 'What happens to my chats if I cancel?',
                'a' => 'Your chats, customers and products stay in your account. Your salesgirl stops answering new messages, and you can start her again later by picking a plan.',
            ],
            [
                'q' => 'Can I delete everything?',
                'a' => 'Yes. Open Settings and choose to delete your account. That wipes your shops, your chats and your customer list for good, and it cannot be undone.',
            ],
        ],
    ],
];

function plans_render_check(bool $included): void
{
    if ($included) {
        ?>
        <span class="plans-cell-yes">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 6 9 17l-5-5"></path>
            </svg>
            <span class="sr-only">Included</span>
        </span>
        <?php

        return;
    }
    ?>
    <span class="plans-cell-no">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true">
            <path d="M5 12h14"></path>
        </svg>
        <span class="sr-only">Not included</span>
    </span>
    <?php
}
?>
<div class="plans-page bg-white">
    <div class="plans-shell min-h-screen flex flex-col">
        <?php require __DIR__ . '/../home/marketing-header.php'; ?>

        <main class="plans-main flex-1">
            <section class="plans-hero">
                <h1 class="plans-hero-title font-heading">Pricing</h1>
                <p class="plans-hero-copy">
                    Every plan gives you an AI salesgirl who answers your customers on Facebook, Instagram and WhatsApp.
                    Pick the one that matches how many shops you run and how many messages you get.
                </p>
            </section>

            <div class="plans-cycle-wrap">
                <?php require __DIR__ . '/billing-cycle-toggle.php'; pricing_billing_cycle_toggle(); ?>
            </div>

            <section class="plans-cards" aria-label="Plans">
                <?php foreach ($plans as $plan): ?>
                    <?php
                    $slug = (string) $plan['slug'];
                    $isPopular = !empty($plan['popular']);
                    ?>
                    <article class="plans-card<?= $isPopular ? ' plans-card--popular' : '' ?>">
                        <?php if ($isPopular): ?>
                            <span class="plans-card-badge font-heading">Most picked</span>
                        <?php endif; ?>

                        <h2 class="plans-card-name font-heading"><?= e((string) $plan['name']) ?></h2>
                        <p class="plans-card-tagline"><?= e((string) $plan['description']) ?></p>

                        <p class="plans-card-price">
                            <span
                                class="plans-card-amount font-heading"
                                data-plan-price
                                data-monthly-amount="<?= e((string) ($plan['price'] ?? 0)) ?>"
                                data-annual-amount="<?= e((string) ($plan['annual_price'] ?? 0)) ?>"
                            ><?= e(money_format((float) $plan['price'])) ?></span>
                            <span class="plans-card-period" data-plan-period>each month</span>
                        </p>

                        <a class="plans-card-cta font-heading" href="<?= e(app_route('signup', ['plan' => $slug])) ?>">
                            <?= e($planCtaLabels[$slug] ?? 'Get started') ?>
                        </a>

                        <ul class="plans-card-list">
                            <?php foreach ($plan['features'] as $feature): ?>
                                <?php if (is_array($feature) && !empty($feature['header'])): ?>
                                    <li class="plans-card-list-header"><?= e((string) $feature['label']) ?></li>
                                <?php else: ?>
                                    <li>
                                        <svg class="plans-card-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg>
                                        <span><?= e(is_array($feature) ? (string) $feature['label'] : (string) $feature) ?></span>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            </section>

            <p class="plans-footnote">
                Prices are in US dollars and renew every month. Pay yearly to get two months free. You can cancel from your dashboard whenever you want.
            </p>

            <section class="plans-topups" aria-labelledby="plans-topups-title">
                <div class="plans-topups-head">
                    <h2 id="plans-topups-title" class="plans-section-title font-heading">Need more replies?</h2>
                    <p class="plans-section-copy">
                        If your replies finish before the month ends, buy a pack and keep selling.
                        This is a one time payment and your monthly plan stays the same.
                    </p>
                </div>
                <ul class="plans-topups-grid">
                    <?php foreach ($topups as $topup): ?>
                        <li class="plans-topup">
                            <span class="plans-topup-count font-heading"><?= e((string) $topup['tokens']) ?> replies</span>
                            <span class="plans-topup-price"><?= e(money_format((float) $topup['price'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="plans-compare" aria-labelledby="plans-compare-title">
                <h2 id="plans-compare-title" class="plans-section-title font-heading">Compare the plans</h2>
                <p class="plans-section-copy">Here is everything each plan gives you, side by side.</p>

                <div class="plans-table-scroll" tabindex="0" role="region" aria-labelledby="plans-compare-title">
                    <table class="plans-table">
                        <thead>
                            <tr>
                                <th scope="col" class="plans-table-feature">Feature</th>
                                <?php foreach ($plans as $plan): ?>
                                    <th scope="col">
                                        <span class="plans-table-plan font-heading"><?= e((string) $plan['name']) ?></span>
                                        <span
                                            class="plans-table-plan-price"
                                            data-plan-price
                                            data-monthly-amount="<?= e((string) ($plan['price'] ?? 0)) ?>"
                                            data-annual-amount="<?= e((string) ($plan['annual_price'] ?? 0)) ?>"
                                        ><?= e(money_format((float) $plan['price'])) ?></span>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comparison as $group): ?>
                                <tr class="plans-table-group">
                                    <th scope="colgroup" colspan="<?= count($plans) + 1 ?>" class="font-heading"><?= e($group['title']) ?></th>
                                </tr>
                                <?php foreach ($group['rows'] as $row): ?>
                                    <tr>
                                        <th scope="row" class="plans-table-feature"><?= e($row['label']) ?></th>
                                        <?php foreach ($row['values'] as $value): ?>
                                            <td>
                                                <?php if (is_bool($value)): ?>
                                                    <?php plans_render_check($value); ?>
                                                <?php else: ?>
                                                    <span class="plans-cell-text"><?= e((string) $value) ?></span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="plans-faq" aria-labelledby="plans-faq-title">
                <h2 id="plans-faq-title" class="plans-section-title font-heading">Questions people ask</h2>

                <div class="plans-faq-tabs" role="tablist" aria-label="Question topics">
                    <?php foreach ($faqGroups as $index => $group): ?>
                        <button
                            type="button"
                            role="tab"
                            id="faq-tab-<?= e($group['id']) ?>"
                            class="plans-faq-tab<?= $index === 0 ? ' is-active' : '' ?>"
                            aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                            aria-controls="faq-panel-<?= e($group['id']) ?>"
                            data-faq-tab="<?= e($group['id']) ?>"
                        >
                            <?= e($group['title']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($faqGroups as $index => $group): ?>
                    <div
                        role="tabpanel"
                        id="faq-panel-<?= e($group['id']) ?>"
                        aria-labelledby="faq-tab-<?= e($group['id']) ?>"
                        class="plans-faq-panel<?= $index === 0 ? ' is-active' : '' ?>"
                        data-faq-panel="<?= e($group['id']) ?>"
                        <?= $index === 0 ? '' : 'hidden' ?>
                    >
                        <?php foreach ($group['items'] as $item): ?>
                            <details class="plans-faq-item">
                                <summary class="plans-faq-question">
                                    <span><?= e($item['q']) ?></span>
                                    <svg class="plans-faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                                        <path d="M5 12h14"></path>
                                        <path class="plans-faq-icon-bar" d="M12 5v14"></path>
                                    </svg>
                                </summary>
                                <p class="plans-faq-answer"><?= e($item['a']) ?></p>
                            </details>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="plans-final">
                <h2 class="plans-final-title font-heading">Ready to stop typing the same answers?</h2>
                <p class="plans-final-copy">
                    Create your account, tell your salesgirl about your shop, and let her handle the messages.
                </p>
                <div class="plans-final-actions">
                    <a class="plans-final-cta font-heading" href="<?= e(app_route('signup')) ?>">Get started</a>
                    <a class="plans-final-link" href="<?= e(app_route('contact')) ?>">Talk to us first</a>
                </div>
            </section>
        </main>
    </div>

    <?php require __DIR__ . '/../home/footer.php'; ?>
</div>
<script src="<?= e($appUrl) ?>/assets/js/plans.js?v=2"></script>
