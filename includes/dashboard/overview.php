<?php

declare(strict_types=1);

/** @var string|false|null $success */
/** @var array<string, mixed>|null $subscription */
/** @var array<string, mixed>|null $plan */
/** @var array<string, mixed>|null $business */
/** @var string $appUrl */
/** @var string $userName */
/** @var string $planName */
/** @var int $planPrice */
/** @var string|null $renewal */
/** @var array<string, int|float|null> $stats */
/** @var array{balance: int, unlimited: bool, monthly_grant: int|null, low_balance: bool, depleted: bool, label: string, percent_remaining: float|null, price_per_reply: string|null} $tokenUsage */
/** @var array{used: int, limit: int|null, label: string} $accountUsage */
/** @var array{businesses_used: int, business_limit: int|null, business_label: string, channels_label: string, channels_on_business: int, channels_per_platform: int} $planUsage */
/** @var list<array<string, mixed>> $channelPerformance */
/** @var list<array<string, mixed>> $catalogOfferings */
/** @var int $catalogTotal */
/** @var array<string, mixed>|null $settings */
/** @var list<array<string, mixed>> $recentTransactions */
/** @var list<array{platform: string, platform_label: string, display_name: string, is_active: bool, href: string}> $connectedChannelCards */
/** @var string $planSlug */

$tokensUnlimited = ($tokenUsage['unlimited'] ?? false);
$tokensLeftDisplay = $tokensUnlimited
    ? '∞'
    : number_format((int) ($tokenUsage['balance'] ?? 0));
$channelsPerPlatform = PlanLimits::channelsPerPlatform($planSlug);
$planSummaryText = $channelsPerPlatform === null
    ? 'You\'re on the ' . $planName . ' plan with unlimited social accounts'
    : 'You\'re on the ' . $planName . ' plan with Facebook, Instagram & WhatsApp';
$planRenewalText = $renewal !== null ? 'Plan renews ' . $renewal : null;
$askAiLabel = dashboard_ai_assistant_label($settings ?? null);
?>
<div class="ov-page">
    <header class="ov-topbar">
        <div class="ov-topbar-inner">
            <h1 class="ov-greeting">Hi, <?= e($userName !== '' ? $userName : 'there') ?></h1>
        </div>
    </header>

    <div class="ov-body">
    <?php if ($success): ?>
        <div class="ov-alert ov-alert--success" role="status"><?= e($success) ?></div>
    <?php endif; ?>

    <section class="ov-hero" aria-label="Account summary">
        <div class="ov-balance-row">
            <div class="ov-balance-block">
                <p class="ov-balance-label">Tokens left</p>
                <div class="ov-balance-value-wrap">
                    <?php if ($tokensUnlimited): ?>
                        <strong class="ov-balance-value font-heading ov-balance-value--unlimited" aria-hidden="true">∞</strong>
                        <span class="ov-balance-unlimited-label font-heading">Unlimited</span>
                    <?php else: ?>
                        <strong class="ov-balance-value font-heading"><?= e($tokensLeftDisplay) ?></strong>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ov-actions" aria-label="Quick actions">
                <a href="<?= e(app_route('businesses/start')) ?>" class="ov-btn ov-btn--primary">
                    <svg class="ov-btn-icon ov-btn-icon--plus" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Add new business
                </a>
                <a href="<?= e(app_route('connected-accounts')) ?>" class="ov-btn ov-btn--soft">
                    <svg class="ov-btn-icon ov-btn-icon--plus" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Add social account
                </a>
                <a href="<?= e(app_route('inbox')) ?>" class="ov-btn ov-btn--soft">
                    <svg class="ov-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                    </svg>
                    View Inbox
                </a>
                <a href="<?= e(app_route('social-media')) ?>" class="ov-btn ov-btn--soft">
                    <svg class="ov-btn-icon ov-btn-icon--plus" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Create a post
                </a>
            </div>
        </div>
    </section>

    <section class="ov-panel ov-panel--channels" aria-labelledby="ov-channels-heading">
        <div class="ov-section-head">
            <h2 id="ov-channels-heading" class="ov-section-title font-heading">Connected channels</h2>
            <a href="<?= e(app_route('connected-accounts')) ?>" class="ov-section-link">View all channels</a>
        </div>

        <div class="ov-accounts-scroll" tabindex="0" aria-label="Connected channels">
            <?php foreach ($connectedChannelCards as $card): ?>
                <?php
                $platform = (string) $card['platform'];
                $isActive = (bool) $card['is_active'];
                $metric = '—';
                foreach ($channelPerformance as $row) {
                    if (($row['platform'] ?? '') === $platform) {
                        $metric = number_format((int) ($row['messages'] ?? 0)) . ' messages';
                        break;
                    }
                }
                ?>
                <a href="<?= e((string) $card['href']) ?>" class="ov-account-card<?= $isActive ? '' : ' ov-account-card--empty' ?>">
                    <span class="ov-account-icon" aria-hidden="true">
                        <?= dash_platform_badge_icon($platform, 22) ?>
                    </span>
                    <span class="ov-account-amount font-heading">
                        <?= $isActive ? e($metric) : '—' ?>
                    </span>
                    <span class="ov-account-meta">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 10.5 12 4l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                        </svg>
                        <?php if ($isActive): ?>
                            <?= e((string) $card['display_name']) ?>
                        <?php else: ?>
                            Connect <?= e((string) $card['platform_label']) ?>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="ov-promo-grid">
        <article class="ov-promo-card ov-promo-card--plan">
            <span class="ov-promo-icon" aria-hidden="true">
                <img
                    src="<?= e(brand_overview_plan_icon_url()) ?>"
                    class="ov-promo-icon-img"
                    width="56"
                    height="56"
                    alt=""
                    loading="lazy"
                    decoding="async"
                >
            </span>
            <div class="ov-promo-copy">
                <p class="ov-promo-text"><?= e($planSummaryText) ?></p>
                <?php if ($planRenewalText !== null): ?>
                    <p class="ov-promo-sub"><?= e($planRenewalText) ?></p>
                <?php endif; ?>
                <a href="<?= e(app_route('billing')) ?>" class="ov-btn ov-btn--soft ov-btn--compact">Change Plan</a>
            </div>
        </article>

        <article class="ov-promo-card ov-promo-card--refer">
            <div class="ov-promo-copy">
                <p class="ov-promo-text">Earn rewards when you invite friends to Lead Bird.</p>
                <a href="<?= e(app_route('refer')) ?>" class="ov-btn ov-btn--primary ov-btn--compact">Invite friends</a>
            </div>
            <span class="ov-promo-art" aria-hidden="true">
                <img
                    src="<?= e(brand_overview_refer_icon_url()) ?>"
                    class="ov-promo-art-img"
                    width="72"
                    height="72"
                    alt=""
                    loading="lazy"
                    decoding="async"
                >
            </span>
        </article>
    </div>

    <div class="ov-widgets-grid">
        <section class="ov-widget-card" aria-labelledby="ov-catalog-heading">
            <div class="ov-section-head ov-section-head--inset">
                <h2 id="ov-catalog-heading" class="ov-section-title font-heading">Offers &amp; catalog</h2>
                <a href="<?= e(app_route('offers')) ?>" class="ov-section-link">Manage catalog</a>
            </div>

            <?php if ($catalogTotal === 0): ?>
                <div class="ov-catalog-empty">
                    <p class="ov-catalog-empty-copy">Add products, services, or memberships so your Salesgirl can quote and recommend them in DMs.</p>
                    <a href="<?= e(app_route('offers')) ?>" class="ov-btn ov-btn--primary ov-btn--compact">Add your first offer</a>
                </div>
            <?php else: ?>
                <ul class="ov-catalog-list">
                    <?php foreach ($catalogOfferings as $offering): ?>
                        <?php
                        $offeringId = (string) ($offering['id'] ?? '');
                        $type = (string) ($offering['offering_type'] ?? 'product');
                        $typeMeta = dash_offering_type_meta($type);
                        $price = $offering['price'] !== null
                            ? dash_format_catalog_money(
                                (float) $offering['price'],
                                (string) ($offering['currency'] ?? money_default_catalog_currency())
                            )
                            : 'No price set';
                        $billing = dash_billing_label((string) ($offering['billing_type'] ?? 'one_time'));
                        $imageUrl = dash_offering_image_url((string) ($offering['image_url'] ?? ''));
                        $editUrl = app_route('offers') . ($offeringId !== '' ? '?edit=' . rawurlencode($offeringId) : '');
                        ?>
                        <li>
                            <a href="<?= e($editUrl) ?>" class="ov-catalog-link">
                                <?php if ($imageUrl !== null): ?>
                                    <span class="ov-catalog-link-thumb">
                                        <img src="<?= e($imageUrl) ?>" alt="" width="40" height="40" loading="lazy" decoding="async">
                                    </span>
                                <?php else: ?>
                                    <span class="ov-catalog-link-icon ov-catalog-link-icon--<?= e((string) $typeMeta['class']) ?>" aria-hidden="true">
                                        <img src="<?= e((string) $typeMeta['icon']) ?>" alt="" width="18" height="18" loading="lazy" decoding="async">
                                    </span>
                                <?php endif; ?>
                                <span class="ov-catalog-link-copy">
                                    <span class="ov-catalog-link-title"><?= e((string) ($offering['name'] ?? 'Untitled')) ?></span>
                                    <span class="ov-catalog-link-desc"><?= e((string) $typeMeta['label']) ?> · <?= e($billing) ?></span>
                                </span>
                                <span class="ov-catalog-link-price font-heading"><?= e($price) ?></span>
                                <svg class="ov-catalog-link-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="ov-widget-card" aria-labelledby="ov-help-heading">
            <div class="ov-section-head ov-section-head--inset">
                <h2 id="ov-help-heading" class="ov-section-title font-heading">Help Center</h2>
                <a href="<?= e(app_route('help')) ?>" class="ov-section-link">View all help</a>
            </div>

            <ul class="ov-help-list">
                <li>
                    <a href="<?= e(app_route('contact')) ?>" class="ov-help-link">
                        <span class="ov-help-link-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a4 4 0 0 1-4 4H7l-4 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8z"/>
                            </svg>
                        </span>
                        <span class="ov-help-link-copy">
                            <span class="ov-help-link-title">Contact us</span>
                            <span class="ov-help-link-desc">Billing, connections, or setup questions</span>
                        </span>
                        <svg class="ov-help-link-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="<?= e(app_route('help')) ?>" class="ov-help-link">
                        <span class="ov-help-link-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M9.5 9.5a2.5 2.5 0 1 1 4.2 1.8c-.8.7-1.2 1.1-1.2 2.2"/>
                                <circle cx="12" cy="17" r="0.75" fill="currentColor" stroke="none"/>
                            </svg>
                        </span>
                        <span class="ov-help-link-copy">
                            <span class="ov-help-link-title">FAQs</span>
                            <span class="ov-help-link-desc">Answers on channels, AI, and billing</span>
                        </span>
                        <svg class="ov-help-link-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="<?= e(app_route('ai-salesgirl')) ?>" class="ov-help-link">
                        <span class="ov-help-link-icon ov-help-link-icon--ai" aria-hidden="true">
                            <?= brand_favicon_icon_html('ov-help-link-bird', 18, '') ?>
                        </span>
                        <span class="ov-help-link-copy">
                            <span class="ov-help-link-title"><?= e($askAiLabel) ?></span>
                            <span class="ov-help-link-desc">Test replies and tune your Salesgirl</span>
                        </span>
                        <svg class="ov-help-link-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </a>
                </li>
            </ul>
        </section>
    </div>

    <section class="ov-transactions" aria-labelledby="ov-transactions-heading">
        <div class="ov-section-head ov-section-head--inset">
            <h2 id="ov-transactions-heading" class="ov-section-title font-heading">Recent messages</h2>
            <a href="<?= e(app_route('inbox')) ?>" class="ov-section-link">View all</a>
        </div>

        <?php if ($recentTransactions === []): ?>
            <p class="ov-widget-empty ov-widget-empty--table">No messages yet. Connect a channel and your Salesgirl will start chatting with customers.</p>
        <?php else: ?>
            <div class="ov-txn-table-wrap">
                <table class="ov-txn-table">
                    <thead class="sr-only">
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Contact</th>
                            <th scope="col">Description</th>
                            <th scope="col">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions as $txn): ?>
                            <tr class="ov-txn-row">
                                <td class="ov-txn-date"><?= e((string) $txn['date_short']) ?></td>
                                <td class="ov-txn-entity">
                                    <span class="ov-txn-avatar" aria-hidden="true">
                                        <?php if (($txn['platform'] ?? '') !== ''): ?>
                                            <?= dash_platform_badge_icon((string) $txn['platform'], 18) ?>
                                        <?php else: ?>
                                            <span class="ov-txn-avatar-fallback"><?= e(mb_strtoupper(mb_substr((string) $txn['entity_name'], 0, 1))) ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="ov-txn-name">
                                        <?= e((string) $txn['entity_name']) ?>
                                        <?php if (!empty($txn['status_badge'])): ?>
                                            <span class="ov-txn-badge"><?= e((string) $txn['status_badge']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="ov-txn-desc"><?= e((string) $txn['description']) ?></td>
                                <td class="ov-txn-category"><?= e((string) $txn['category']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    </div>
</div>
