<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string $planName */
/** @var int $planPrice */
/** @var string|null $renewal */
/** @var string $status */
/** @var list<array<string, mixed>> $billingHistory */
/** @var list<array<string, mixed>> $allPlans */
/** @var string $planSlug */
/** @var string|false|null $success */
/** @var string|false|null $error */
/** @var list<array<string, mixed>> $paymentMethods */
/** @var array{address_line1: string, city: string, state: string, country: string, postal_code: string, phone: string} $billingDefaults */
/** @var array{balance: int, unlimited: bool, monthly_grant: int|null, low_balance: bool, depleted: bool, label: string, percent_remaining: float|null, price_per_reply: string|null} $tokenUsage */
/** @var list<array{slug: string, name: string, tokens: int, price: int, badge?: string}> $tokenPackages */
/** @var string $userDisplayName */
/** @var string $userEmail */
/** @var string $userInitials */
/** @var array{attempts: int, next_attempt_at: ?string, final_attempt_at: ?string, grace_until: ?string}|null $pastDue */

$hasCards = $paymentMethods !== [];
$pastDueNext = is_array($pastDue) && !empty($pastDue['next_attempt_at'])
    ? date('M j', strtotime((string) $pastDue['next_attempt_at']))
    : null;
$pastDueDeadline = is_array($pastDue) && !empty($pastDue['final_attempt_at'])
    ? date('M j', strtotime((string) $pastDue['final_attempt_at']))
    : null;

$statusLabel = is_array($pastDue) ? 'Payment due' : ucfirst(strtolower($status));
$showBillingBanner = !$hasCards && !is_array($pastDue);
$tokenBalanceLabel = ($tokenUsage['unlimited'] ?? false)
    ? 'Unlimited'
    : (string) (int) ($tokenUsage['balance'] ?? 0);
?>
<div class="bill-page">
    <?php if (is_array($pastDue)): ?>
        <div class="bill-banner bill-banner--alert" data-reveal role="alert">
            <div class="bill-banner-main">
                <span class="bill-banner-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M10.3 4.2 2.6 17.5A2 2 0 0 0 4.3 20.5h15.4a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                        <path d="M12 9.5v4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                        <circle cx="12" cy="16.75" r="1" fill="currentColor"/>
                    </svg>
                </span>
                <div class="bill-banner-copy">
                    <p class="bill-banner-lead">We couldn't renew your plan</p>
                    <p class="bill-banner-renewal">
                        <?php if ($hasCards): ?>
                            Your last payment didn't go through. Your Salesgirl keeps replying for now<?= $pastDueNext !== null ? ', and we will try again on ' . e($pastDueNext) : '' ?>.
                        <?php else: ?>
                            There's no card on your account to charge. Your Salesgirl keeps replying for now.
                        <?php endif; ?>
                        <?php if ($pastDueDeadline !== null): ?>
                            <strong><?= $hasCards ? 'Update your card' : 'Add a card' ?> before <?= e($pastDueDeadline) ?></strong> to avoid any interruption.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="bill-banner-actions<?= $hasCards ? '' : ' bill-banner-actions--single' ?>">
                <button type="button" class="bill-btn bill-btn--primary" data-billing-add-card>
                    <?= $hasCards ? 'Use a different card' : 'Add a card' ?>
                </button>
                <?php if ($hasCards): ?>
                    <form method="post" action="<?= e(app_route('billing/retry-renewal')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="bill-btn bill-btn--outline" data-billing-pending-label="Charging your card…">
                            Retry payment now
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($showBillingBanner): ?>
        <div class="bill-banner" data-reveal role="status">
            <div class="bill-banner-main">
                <span class="bill-banner-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/>
                        <path d="M12 8v5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                        <circle cx="12" cy="16.5" r="1" fill="currentColor"/>
                    </svg>
                </span>
                <div class="bill-banner-copy">
                    <p class="bill-banner-lead">Add a payment method so your plan renews without interruption.</p>
                    <?php if ($renewal !== null): ?>
                        <p class="bill-banner-renewal">Next renewal: <?= e($renewal) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <button type="button" class="bill-btn bill-btn--primary bill-banner-action" data-billing-add-card>
                Add billing details
            </button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bill-alert bill-alert--success" role="status" data-reveal><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bill-alert bill-alert--error" role="alert" data-reveal><?= e($error) ?></div>
    <?php endif; ?>

    <section class="bill-profile" data-reveal aria-label="Account profile">
        <div class="bill-profile-avatar font-heading" aria-hidden="true"><?= e($userInitials) ?></div>
        <div class="bill-profile-copy">
            <h1 class="bill-profile-name font-heading"><?= e($userDisplayName) ?></h1>
            <p class="bill-profile-email"><?= e($userEmail) ?></p>
        </div>
        <span class="bill-profile-plan"><?= e($planName) ?> plan</span>
    </section>

    <section class="bill-panel" data-reveal aria-label="Billing settings">
        <div class="bill-acc is-open" data-billing-accordion>
            <button
                type="button"
                class="bill-acc-trigger"
                data-billing-accordion-trigger
                aria-expanded="true"
                aria-controls="bill-acc-billing-info"
            >
                <span class="bill-acc-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.75"/>
                        <path d="M3 10h18" stroke="currentColor" stroke-width="1.75"/>
                    </svg>
                </span>
                <span class="bill-acc-title font-heading">Billing information</span>
                <span class="bill-acc-chevron" aria-hidden="true"></span>
            </button>
            <div class="bill-acc-body" id="bill-acc-billing-info" data-billing-accordion-panel>
                <div class="bill-field">
                    <span class="bill-field-label">Billing cycle</span>
                    <span class="bill-field-value">Monthly</span>
                </div>
                <div class="bill-cards">
                    <div class="bill-cards-head">
                        <span class="bill-field-label">Saved cards</span>
                        <?php if (count($paymentMethods) > 1): ?>
                            <span class="bill-cards-hint">Renewals charge your default card first. If that fails, we try your other cards.</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!$hasCards): ?>
                        <p class="bill-empty bill-cards-empty">No card saved yet. Add one so your plan renews automatically.</p>
                    <?php else: ?>
                        <ul class="bill-card-list">
                            <?php foreach ($paymentMethods as $method): ?>
                                <?php
                                $isDefaultCard = !empty($method['is_default']);
                                $cardLabel = (string) ($method['brand_name'] ?? 'Card') . ' •••• ' . (string) ($method['last4'] ?? '');
                                ?>
                                <li class="bill-card-item<?= $isDefaultCard ? ' is-default' : '' ?>">
                                    <span class="bill-card-brand font-heading" aria-hidden="true"><?= e((string) ($method['brand_short'] ?? 'CARD')) ?></span>
                                    <div class="bill-card-copy">
                                        <span class="bill-card-number"><?= e($cardLabel) ?></span>
                                        <span class="bill-card-meta<?= !empty($method['is_expired']) ? ' is-expired' : '' ?>">
                                            <?= !empty($method['is_expired']) ? 'Expired' : 'Expires' ?> <?= e((string) ($method['expires'] ?? '')) ?>
                                        </span>
                                    </div>
                                    <div class="bill-card-actions">
                                        <?php if ($isDefaultCard): ?>
                                            <span class="bill-card-default">Default</span>
                                        <?php else: ?>
                                            <form method="post" action="<?= e(app_route('billing/set-default-payment-method')) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="method_id" value="<?= e((string) $method['id']) ?>">
                                                <button type="submit" class="bill-link" data-billing-pending-label="Saving…" aria-label="Make <?= e($cardLabel) ?> the default card">Make default</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="<?= e(app_route('billing/remove-payment-method')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="method_id" value="<?= e((string) $method['id']) ?>">
                                            <button
                                                type="submit"
                                                class="bill-link bill-link--danger"
                                                data-confirm-remove-card
                                                data-card-label="<?= e($cardLabel) ?>"
                                                data-card-only="<?= count($paymentMethods) === 1 ? '1' : '0' ?>"
                                                data-card-default="<?= $isDefaultCard ? '1' : '0' ?>"
                                                aria-label="Remove <?= e($cardLabel) ?>"
                                            >Remove</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <button type="button" class="bill-btn bill-btn--outline" data-billing-add-card>
                    <?= $hasCards ? 'Add another card' : 'Add payment method' ?>
                </button>
            </div>
        </div>

        <div class="bill-acc" data-billing-accordion>
            <button
                type="button"
                class="bill-acc-trigger"
                data-billing-accordion-trigger
                aria-expanded="false"
                aria-controls="bill-acc-history"
            >
                <span class="bill-acc-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M8 6h12M8 12h12M8 18h7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                        <circle cx="5" cy="6" r="1.25" fill="currentColor"/>
                        <circle cx="5" cy="12" r="1.25" fill="currentColor"/>
                        <circle cx="5" cy="18" r="1.25" fill="currentColor"/>
                    </svg>
                </span>
                <span class="bill-acc-title font-heading">Billing history</span>
                <span class="bill-acc-chevron" aria-hidden="true"></span>
            </button>
            <div class="bill-acc-body is-hidden" id="bill-acc-history" data-billing-accordion-panel hidden>
                <?php if ($billingHistory === []): ?>
                    <p class="bill-empty">You will see your invoices here after your first payment.</p>
                <?php else: ?>
                    <ul class="bill-history-list">
                        <?php foreach ($billingHistory as $row): ?>
                            <?php
                            $rowStatus = ucfirst((string) ($row['status'] ?? ''));
                            ?>
                            <li class="bill-history-item">
                                <div class="bill-history-main">
                                    <strong><?= e(ucfirst((string) ($row['plan_slug'] ?? 'Plan'))) ?></strong>
                                    <span><?= e(date('M j, Y', strtotime((string) ($row['created_at'] ?? 'now')))) ?></span>
                                </div>
                                <div class="bill-history-meta">
                                    <span><?= e(money_format((float) ($row['amount'] ?? 0), (string) ($row['currency'] ?? 'USD'))) ?></span>
                                    <span class="bill-history-status"><?= e($rowStatus) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="bill-acc" data-billing-accordion>
            <button
                type="button"
                class="bill-acc-trigger"
                data-billing-accordion-trigger
                aria-expanded="false"
                aria-controls="bill-acc-subscription"
            >
                <span class="bill-acc-icon" aria-hidden="true">
                    <img
                        src="<?= e($appUrl) ?>/assets/images/icons/manage-subscription.png"
                        alt=""
                        width="20"
                        height="20"
                        class="bill-acc-icon-img"
                        loading="lazy"
                    >
                </span>
                <span class="bill-acc-title font-heading">Manage subscription</span>
                <span class="bill-acc-chevron" aria-hidden="true"></span>
            </button>
            <div class="bill-acc-body is-hidden" id="bill-acc-subscription" data-billing-accordion-panel hidden>
                <div class="bill-plan-row">
                    <div>
                        <span class="bill-field-label">Your plan</span>
                        <p class="bill-plan-name font-heading"><?= e($planName) ?></p>
                    </div>
                    <p class="bill-plan-price font-heading"><?= e(money_format((float) $planPrice)) ?>/month</p>
                </div>
                <div class="bill-field">
                    <span class="bill-field-label">Status</span>
                    <span class="bill-field-value"><?= e($statusLabel) ?></span>
                </div>
                <div class="bill-field">
                    <span class="bill-field-label">Renews</span>
                    <span class="bill-field-value"><?php if (is_array($pastDue)): ?>Payment failed<?= $pastDueNext !== null ? ' · retrying ' . e($pastDueNext) : '' ?><?php else: ?><?= $renewal !== null ? e($renewal) : '—' ?><?php endif; ?></span>
                </div>
                <?php if (!($tokenUsage['unlimited'] ?? false)): ?>
                    <div class="bill-field" id="tokens">
                        <span class="bill-field-label">Tokens</span>
                        <span class="bill-field-value"><?= e($tokenBalanceLabel) ?> remaining</span>
                    </div>
                <?php endif; ?>

                <div class="bill-plan-picker" id="bill-plan-picker" data-bill-plan-picker hidden>
                    <p class="bill-plan-picker-lead">Choose a plan</p>
                    <?php require dirname(__DIR__) . '/pricing/billing-cycle-toggle.php'; pricing_billing_cycle_toggle(); ?>
                    <ul class="bill-plan-options">
                        <?php foreach ($allPlans as $tier): ?>
                            <?php
                            $isCurrent = ($tier['slug'] ?? '') === $planSlug;
                            $tierPrice = (float) ($tier['price'] ?? 0);
                            if ($tierPrice > $planPrice) {
                                $actionLabel = 'Upgrade';
                            } elseif ($tierPrice < $planPrice) {
                                $actionLabel = 'Downgrade';
                            } else {
                                $actionLabel = 'Switch';
                            }
                            ?>
                            <li class="bill-plan-option<?= $isCurrent ? ' is-current' : '' ?>">
                                <div class="bill-plan-option-copy">
                                    <strong class="font-heading"><?= e((string) ($tier['name'] ?? '')) ?></strong>
                                    <span data-plan-price data-monthly-amount="<?= e((string) ($tier['price'] ?? 0)) ?>" data-annual-amount="<?= e((string) ($tier['annual_price'] ?? 0)) ?>"><?= e(money_format($tierPrice)) ?></span><span data-plan-period>/month</span>
                                </div>
                                <?php if ($isCurrent): ?>
                                    <span class="bill-plan-current">Current</span>
                                <?php else: ?>
                                    <button
                                        type="button"
                                        class="bill-btn bill-btn--outline bill-btn--sm"
                                        data-plan-select="<?= e((string) ($tier['slug'] ?? '')) ?>"
                                    ><?= e($actionLabel) ?></button>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (!($tokenUsage['unlimited'] ?? false) && $tokenPackages !== []): ?>
                        <p class="bill-plan-picker-lead" id="reply-tokens">Top up tokens</p>
                        <ul class="bill-topup-list">
                            <?php foreach ($tokenPackages as $package): ?>
                                <li class="bill-topup-item">
                                    <div>
                                        <strong><?= (int) ($package['tokens'] ?? 0) ?> tokens</strong>
                                        <span><?= e(money_format((float) ($package['price'] ?? 0))) ?></span>
                                    </div>
                                    <form method="post" action="<?= e(app_route('billing/topup-initiate')) ?>" class="bill-topup-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="package" value="<?= e((string) ($package['slug'] ?? '')) ?>">
                                        <button type="submit" class="bill-btn bill-btn--outline bill-btn--sm" data-topup-submit>Top up</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <button type="button" class="bill-btn bill-btn--outline" data-bill-toggle-plans aria-expanded="false" aria-controls="bill-plan-picker">
                    Update subscription
                </button>
            </div>
        </div>

        <div class="bill-acc bill-acc--danger" data-billing-accordion>
            <button
                type="button"
                class="bill-acc-trigger"
                data-billing-accordion-trigger
                aria-expanded="false"
                aria-controls="bill-acc-cancel"
            >
                <span class="bill-acc-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m2 0v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7h12Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="bill-acc-title font-heading">Cancel subscription</span>
                <span class="bill-acc-chevron" aria-hidden="true"></span>
            </button>
            <div class="bill-acc-body is-hidden" id="bill-acc-cancel" data-billing-accordion-panel hidden>
                <p class="bill-cancel-copy">
                    If Lead Bird is not the right fit, you can cancel your subscription. Your Salesgirl will stop replying when the current billing period ends.
                </p>
                <form method="post" action="<?= e(app_route('billing/cancel-subscription')) ?>" class="bill-cancel-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="bill-btn bill-btn--outline" data-confirm-cancel-subscription>
                        Cancel subscription
                    </button>
                </form>
            </div>
        </div>
    </section>
</div>

<?php $cardVerifyAmountLabel = $billingDefaults['country'] === 'NG' ? '₦100' : '$1'; ?>
<div class="billing-card-overlay is-hidden" data-billing-card-overlay aria-hidden="true">
    <div class="billing-card-modal" role="dialog" aria-modal="true" aria-labelledby="billing-card-modal-title">
        <button type="button" class="billing-card-modal-close" data-billing-card-close aria-label="Close">×</button>
        <h2 id="billing-card-modal-title" class="billing-card-modal-title font-heading">Add payment method</h2>
        <p class="billing-card-modal-copy">
            Confirm your billing details, then enter your card on Flutterwave's secure page. A
            <strong data-card-verify-amount><?= e($cardVerifyAmountLabel) ?></strong>
            verification charge confirms the card for automatic renewals. Your card details never touch our servers.
        </p>

        <form class="billing-card-form" data-billing-card-form novalidate>
            <?= csrf_field() ?>
            <h3 class="billing-card-form-section-title font-heading">Billing details</h3>
            <div class="billing-card-form-grid">
                <label class="billing-card-field billing-card-field--full">
                    <span class="billing-card-label">Street address</span>
                    <input class="billing-card-input" type="text" name="address_line1" autocomplete="street-address" value="<?= e($billingDefaults['address_line1']) ?>" required>
                </label>
                <label class="billing-card-field">
                    <span class="billing-card-label">City</span>
                    <input class="billing-card-input" type="text" name="city" autocomplete="address-level2" value="<?= e($billingDefaults['city']) ?>" required>
                </label>
                <label class="billing-card-field">
                    <span class="billing-card-label">State</span>
                    <input class="billing-card-input" type="text" name="state" autocomplete="address-level1" value="<?= e($billingDefaults['state']) ?>" required>
                </label>
                <label class="billing-card-field">
                    <span class="billing-card-label">Postal code</span>
                    <input class="billing-card-input" type="text" name="postal_code" autocomplete="postal-code" value="<?= e($billingDefaults['postal_code']) ?>" required>
                </label>
                <label class="billing-card-field">
                    <span class="billing-card-label">Country</span>
                    <select class="billing-card-input billing-card-select" name="country" autocomplete="country" required data-card-verify-country>
                        <option value="NG"<?= $billingDefaults['country'] === 'NG' ? ' selected' : '' ?>>Nigeria</option>
                        <option value="GH"<?= $billingDefaults['country'] === 'GH' ? ' selected' : '' ?>>Ghana</option>
                        <option value="KE"<?= $billingDefaults['country'] === 'KE' ? ' selected' : '' ?>>Kenya</option>
                        <option value="ZA"<?= $billingDefaults['country'] === 'ZA' ? ' selected' : '' ?>>South Africa</option>
                    </select>
                </label>
                <label class="billing-card-field billing-card-field--full">
                    <span class="billing-card-label">Phone</span>
                    <input class="billing-card-input" type="tel" name="phone" autocomplete="tel" value="<?= e($billingDefaults['phone']) ?>" required>
                </label>
            </div>

            <p class="billing-card-error is-hidden" data-billing-card-error role="alert"></p>

            <div class="billing-card-actions">
                <button type="submit" class="bill-btn bill-btn--primary bill-btn--block" data-billing-card-submit>
                    Continue to Flutterwave
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.LeadBirdBilling = {
        addCardUrl: <?= json_encode(app_route('billing/add-payment-method'), JSON_THROW_ON_ERROR) ?>,
        topupSectionId: 'tokens',
        csrfToken: window.LeadBirdCsrf || <?= json_encode(csrf_token(), JSON_THROW_ON_ERROR) ?>,
    };
    window.LeadBirdPricing = {
        initiateUrl: <?= json_encode(app_route('billing/initiate'), JSON_THROW_ON_ERROR) ?>,
        autoPlan: null,
        csrfToken: window.LeadBirdCsrf || <?= json_encode(csrf_token(), JSON_THROW_ON_ERROR) ?>,
    };
</script>
<script src="<?= e($appUrl) ?>/assets/js/dashboard-billing.js?v=18"></script>
<script src="<?= e($appUrl) ?>/assets/js/pricing.js?v=3"></script>
