<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var array<string, mixed> $affiliate */
/** @var array{clicks: int, signups: int, conversions: int, pending: float, available: float, paid: float} $stats */
/** @var string $referralLink */
/** @var float $withdrawable */
/** @var float $totalBalance */
/** @var list<array{name: string, initial: string, detail: string, amount: float, amount_label: string, status_label: string, status_class: string}> $inviteHistory */
/** @var list<array{date_label: string, plan_label: string, amount_label: string, status_label: string}> $rewardHistory */
/** @var list<array<string, mixed>> $banks */
/** @var bool $canClaim */
/** @var float $pendingUsd */
/** @var bool $hasPendingRewards */
/** @var int $rewardHoldDays */
/** @var string|null $nextAvailableAt */
/** @var bool $hasBank */
/** @var string|false|null $success */
/** @var string|false|null $error */

$fmt = static fn (float $n): string => dash_format_money($n);
$affiliatesUrl = e(app_route('affiliates'));
$helpUrl = e(app_route('help'));
$saveBankUrl = e(app_route('refer/save-bank'));
$withdrawUrl = e(app_route('refer/withdraw'));
$minWithdraw = AffiliateService::MIN_WITHDRAW_USD;
?>
<div class="ref-page">
    <?php if ($success): ?>
        <div class="ref-alert ref-alert--success" role="status"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="ref-alert ref-alert--error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="ref-top-grid">
        <article class="ref-card ref-card--balance">
            <header class="ref-card-head">
                <span class="ref-card-icon ref-card-icon--gift" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="8" width="18" height="13" rx="2"/>
                        <path d="M12 8V21"/>
                        <path d="M3 12h18"/>
                        <path d="M7.5 8C6 8 5 6.5 5 5s1.5-3 3.5-3S12 4 12 8"/>
                        <path d="M16.5 8C18 8 19 6.5 19 5s-1.5-3-3.5-3S12 4 12 8"/>
                    </svg>
                </span>
                <span class="ref-card-kicker">Reward</span>
            </header>
            <div class="ref-balance-main">
                <p class="ref-balance-value" data-ref-balance-value><?= e($fmt($totalBalance)) ?></p>
                <div class="ref-balance-meta">
                    <span class="ref-balance-label">Total balance</span>
                    <button type="button" class="ref-balance-toggle" data-ref-balance-toggle aria-label="Hide balance" aria-pressed="false">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>
            <?php if ($hasPendingRewards && !$canClaim): ?>
                <div class="ref-hold-notice" role="status">
                    <p class="ref-hold-notice-title font-heading">Rewards on hold</p>
                    <p class="ref-hold-notice-copy font-sans">
                        <?= e($fmt($pendingUsd)) ?> is pending while Flutterwave clears the referral payment.
                        We release rewards after a <?= (int) $rewardHoldDays ?>-day hold<?= $nextAvailableAt !== null ? ' — available to claim on ' . e($nextAvailableAt) : '' ?>.
                    </p>
                </div>
                <div class="ref-btn ref-btn--pending" aria-disabled="true">
                    Pending
                </div>
            <?php elseif ($canClaim): ?>
                <button
                    type="button"
                    class="ref-btn ref-btn--claim"
                    data-ref-open-claim
                >
                    Claim reward
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"/><path d="m13 5 7 7-7 7"/>
                    </svg>
                </button>
            <?php else: ?>
                <button type="button" class="ref-btn ref-btn--claim" disabled>
                    Claim reward
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"/><path d="m13 5 7 7-7 7"/>
                    </svg>
                </button>
                <p class="ref-claim-hint font-sans">Earn rewards when friends subscribe. Minimum withdrawal <?= e($fmt((float) $minWithdraw)) ?>.</p>
            <?php endif; ?>
        </article>

        <article class="ref-card ref-card--promo">
            <header class="ref-card-head">
                <span class="ref-card-icon ref-card-icon--referral" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 11v6"/><path d="M19 14h6"/>
                    </svg>
                </span>
                <span class="ref-card-kicker">Referral</span>
            </header>
            <h2 class="ref-promo-title">Invite friends and earn up to <?= e($fmt(200)) ?></h2>
            <p class="ref-promo-copy">
                Earn <?= (int) (AffiliateService::COMMISSION_RATE * 100) ?>% when someone you refer pays for their first Lead Bird plan.
                Share your link and track signups here.
            </p>
        </article>
    </div>

    <section class="ref-card ref-link-card" aria-label="Referral link">
        <label class="ref-link-label" for="ref-link-input">Referral link</label>
        <div class="ref-link-row">
            <div class="ref-link-field">
                <input id="ref-link-input" type="text" readonly class="ref-link-input" value="<?= e($referralLink) ?>">
                <button type="button" class="ref-link-copy" data-ref-copy>Copy</button>
            </div>
            <button type="button" class="ref-btn ref-btn--share" data-ref-share>Share</button>
        </div>
    </section>

    <div class="ref-bottom-grid">
        <article class="ref-card ref-card--earnings">
            <h2 class="ref-section-title font-heading">Earnings</h2>
            <ul class="ref-earnings-list">
                <li class="ref-earnings-row">
                    <span class="ref-earnings-icon ref-earnings-icon--money" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2v20"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </span>
                    <span class="ref-earnings-label">Current earnings</span>
                    <span class="ref-earnings-value ref-earnings-value--money"><?= e($fmt($withdrawable)) ?></span>
                </li>
                <li class="ref-earnings-row">
                    <span class="ref-earnings-icon ref-earnings-icon--invited" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M19 8v6"/>
                            <path d="M22 11h-6"/>
                        </svg>
                    </span>
                    <span class="ref-earnings-label">Invited</span>
                    <span class="ref-earnings-value"><?= (int) $stats['clicks'] ?></span>
                </li>
                <li class="ref-earnings-row">
                    <span class="ref-earnings-icon ref-earnings-icon--onboarded" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="m16 11 2 2 4-4"/>
                        </svg>
                    </span>
                    <span class="ref-earnings-label">Onboarded</span>
                    <span class="ref-earnings-value"><?= (int) $stats['signups'] ?></span>
                </li>
                <li class="ref-earnings-row">
                    <span class="ref-earnings-icon ref-earnings-icon--rewarded" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="8" width="18" height="13" rx="2"/>
                            <path d="M12 8V21"/>
                            <path d="M3 12h18"/>
                            <path d="M7.5 8C6 8 5 6.5 5 5s1.5-3 3.5-3S12 4 12 8"/>
                            <path d="M16.5 8C18 8 19 6.5 19 5s-1.5-3-3.5-3S12 4 12 8"/>
                        </svg>
                    </span>
                    <span class="ref-earnings-label">Rewarded</span>
                    <span class="ref-earnings-value"><?= (int) $stats['conversions'] ?></span>
                </li>
            </ul>
            <div class="ref-foot-links">
                <a href="<?= $affiliatesUrl ?>" class="ref-foot-link">Terms and conditions</a>
                <a href="<?= $helpUrl ?>" class="ref-foot-link">FAQs</a>
            </div>
        </article>

        <article class="ref-card ref-card--history">
            <div class="ref-history-head">
                <h2 class="ref-section-title font-heading">Invite History</h2>
                <button type="button" class="ref-history-link" data-ref-toggle-rewards aria-expanded="false">View reward history</button>
            </div>

            <?php if ($inviteHistory === []): ?>
                <p class="ref-empty">No invites yet. Share your link to start earning.</p>
            <?php else: ?>
                <ul class="ref-invite-list">
                    <?php foreach ($inviteHistory as $invite): ?>
                        <li class="ref-invite-row">
                            <span class="ref-invite-avatar font-heading"><?= e((string) $invite['initial']) ?></span>
                            <div class="ref-invite-copy">
                                <p class="ref-invite-name"><?= e((string) $invite['name']) ?></p>
                                <p class="ref-invite-detail"><?= e((string) $invite['detail']) ?></p>
                            </div>
                            <div class="ref-invite-side">
                                <?php if ((float) $invite['amount'] > 0): ?>
                                    <span class="ref-invite-amount"><?= e((string) $invite['amount_label']) ?></span>
                                <?php endif; ?>
                                <span class="ref-status-pill <?= e((string) $invite['status_class']) ?>"><?= e((string) $invite['status_label']) ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="ref-pagination">Showing 1 – <?= count($inviteHistory) ?> of <?= count($inviteHistory) ?></p>
            <?php endif; ?>

            <div class="ref-reward-history" data-ref-reward-history hidden>
                <?php if ($rewardHistory === []): ?>
                    <p class="ref-empty">No rewards recorded yet.</p>
                <?php else: ?>
                    <ul class="ref-reward-list">
                        <?php foreach ($rewardHistory as $reward): ?>
                            <li class="ref-reward-row">
                                <span class="ref-reward-date"><?= e((string) $reward['date_label']) ?></span>
                                <span class="ref-reward-plan"><?= e((string) $reward['plan_label']) ?></span>
                                <span class="ref-reward-amount"><?= e((string) $reward['amount_label']) ?></span>
                                <span class="ref-reward-status"><?= e((string) $reward['status_label']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </article>
    </div>
</div>

<div class="ref-claim-overlay" data-ref-claim-overlay hidden aria-hidden="true">
    <div class="ref-claim-modal" role="dialog" aria-modal="true" aria-labelledby="ref-claim-title">
        <header class="ref-claim-head">
            <h2 id="ref-claim-title" class="ref-claim-title font-heading">Claim reward</h2>
            <button type="button" class="ref-claim-close" data-ref-close-claim aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                </svg>
            </button>
        </header>

        <p class="ref-claim-lead">
            Available: <strong><?= e($fmt($withdrawable)) ?></strong>.
            Minimum withdrawal <?= e($fmt((float) $minWithdraw)) ?>.
        </p>

        <?php if ($hasBank): ?>
            <p class="ref-bank-preview">
                <?= e((string) $affiliate['account_name']) ?><br>
                <?= e((string) $affiliate['account_number']) ?> · <?= e((string) ($affiliate['bank_name'] ?? $affiliate['bank_code'])) ?>
            </p>
        <?php else: ?>
            <p class="ref-muted">Verify a bank account before withdrawing.</p>
        <?php endif; ?>

        <form method="post" action="<?= $saveBankUrl ?>" class="ref-form">
            <?= csrf_field() ?>
            <label class="ref-form-label" for="ref-bank-code">Bank</label>
            <select id="ref-bank-code" name="bank_code" class="ref-select" required>
                <option value="">Select bank</option>
                <?php foreach ($banks as $bank): ?>
                    <?php
                    $code = (string) ($bank['code'] ?? '');
                    $name = (string) ($bank['name'] ?? $code);
                    $selected = $code !== '' && $code === (string) ($affiliate['bank_code'] ?? '');
                    ?>
                    <option value="<?= e($code) ?>" <?= $selected ? 'selected' : '' ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ref-form-label" for="ref-account-number">Account number</label>
            <input
                id="ref-account-number"
                name="account_number"
                class="ref-input"
                required
                pattern="[0-9]{10}"
                maxlength="10"
                inputmode="numeric"
                autocomplete="off"
                value="<?= e((string) ($affiliate['account_number'] ?? '')) ?>"
            >

            <label class="ref-form-label" for="ref-phone">Phone (for wallet)</label>
            <input
                id="ref-phone"
                name="phone"
                class="ref-input"
                autocomplete="tel"
                value="<?= e((string) ($affiliate['phone'] ?? '')) ?>"
            >

            <button type="submit" class="ref-btn ref-btn--secondary">Verify &amp; save bank</button>
        </form>

        <form method="post" action="<?= $withdrawUrl ?>" class="ref-form ref-form--withdraw">
            <?= csrf_field() ?>
            <label class="ref-form-label" for="ref-withdraw-amount">Amount (USD)</label>
            <input
                id="ref-withdraw-amount"
                name="amount"
                type="number"
                class="ref-input"
                min="<?= $minWithdraw ?>"
                step="0.01"
                required
                value="<?= $canClaim ? number_format($withdrawable, 2, '.', '') : '' ?>"
            >
            <button
                type="submit"
                class="ref-btn ref-btn--primary"
                <?= $canClaim ? '' : 'disabled' ?>
                data-lb-confirm="We will send this amount to your saved bank account."
                data-lb-confirm-title="Withdraw now?"
                data-lb-confirm-label="Withdraw"
            >
                Withdraw to bank
            </button>
        </form>
    </div>
</div>
