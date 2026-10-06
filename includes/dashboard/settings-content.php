<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string|false|null $success */
/** @var string|false|null $error */
/** @var string $profileTitle */
/** @var string $profileSubtitle */
/** @var string $profileInitials */
/** @var string $planLabel */
/** @var int $channelsConnected */
/** @var list<array{id: string, title: string, open: bool, icon: string, items: list<array{title: string, desc: string, action: string, cta: string, href?: string, modal?: string}>}> $settingsSections */

$billingUrl = e(app_route('billing'));
$helpUrl = e(app_route('help'));
$deleteAccountUrl = e(app_route('settings/delete-account'));
?>
<div class="bill-page">
    <?php if ($success): ?>
        <div class="bill-alert bill-alert--success" role="status" data-reveal><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bill-alert bill-alert--error" role="alert" data-reveal><?= e($error) ?></div>
    <?php endif; ?>

    <section class="bill-profile" data-reveal aria-label="Account profile">
        <div class="bill-profile-avatar font-heading" aria-hidden="true"><?= e($profileInitials) ?></div>
        <div class="bill-profile-copy">
            <h1 class="bill-profile-name font-heading"><?= e($profileTitle) ?></h1>
            <p class="bill-profile-email"><?= e($profileSubtitle) ?></p>
        </div>
        <span class="bill-profile-plan"><?= e($planLabel) ?> plan</span>
    </section>

    <section class="bill-panel" data-reveal aria-label="Settings">
        <?php foreach ($settingsSections as $section): ?>
            <?php
            $sectionId = 'settings-acc-' . (string) $section['id'];
            $isOpen = (bool) ($section['open'] ?? false);
            ?>
            <div class="bill-acc<?= $isOpen ? ' is-open' : '' ?>" data-billing-accordion>
                <button
                    type="button"
                    class="bill-acc-trigger"
                    data-billing-accordion-trigger
                    aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
                    aria-controls="<?= e($sectionId) ?>"
                >
                    <span class="bill-acc-icon" aria-hidden="true"><?= $section['icon'] ?></span>
                    <span class="bill-acc-title font-heading"><?= e($section['title']) ?></span>
                    <span class="bill-acc-chevron" aria-hidden="true"></span>
                </button>
                <div
                    class="bill-acc-body<?= $isOpen ? '' : ' is-hidden' ?>"
                    id="<?= e($sectionId) ?>"
                    data-billing-accordion-panel
                    <?= $isOpen ? '' : 'hidden' ?>
                >
                    <ul class="bill-plan-options settings-options">
                        <?php foreach ($section['items'] as $item): ?>
                            <li class="bill-plan-option">
                                <div class="bill-plan-option-copy">
                                    <strong class="font-heading"><?= e($item['title']) ?></strong>
                                    <span><?= e($item['desc']) ?></span>
                                </div>
                                <?php if (($item['action'] ?? 'link') === 'modal'): ?>
                                    <button
                                        type="button"
                                        class="bill-btn bill-btn--outline bill-btn--sm"
                                        data-settings-open="<?= e((string) ($item['modal'] ?? '')) ?>"
                                    ><?= e($item['cta']) ?></button>
                                <?php else: ?>
                                    <a href="<?= e((string) ($item['href'] ?? '#')) ?>" class="bill-btn bill-btn--outline bill-btn--sm"><?= e($item['cta']) ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="bill-acc bill-acc--danger" data-billing-accordion>
            <button
                type="button"
                class="bill-acc-trigger"
                data-billing-accordion-trigger
                aria-expanded="false"
                aria-controls="settings-acc-delete"
            >
                <span class="bill-acc-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m2 0v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7h12Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="bill-acc-title font-heading">Delete account</span>
                <span class="bill-acc-chevron" aria-hidden="true"></span>
            </button>
            <div class="bill-acc-body is-hidden" id="settings-acc-delete" data-billing-accordion-panel hidden>
                <p class="bill-cancel-copy">
                    Permanently remove your account, businesses, channels, messages, and billing data.
                    You are on the <?= e($planLabel) ?> plan with <?= (int) $channelsConnected ?> connected channel<?= $channelsConnected === 1 ? '' : 's' ?>.
                </p>
                <div class="settings-delete-form" data-delete-form-wrap>
                    <label class="bill-field-label" for="settings-delete-confirm">Type <strong>Delete</strong> to confirm</label>
                    <form method="post" action="<?= $deleteAccountUrl ?>" class="settings-delete-form-inner" data-delete-form>
                        <?= csrf_field() ?>
                        <input
                            id="settings-delete-confirm"
                            class="settings-delete-input"
                            type="text"
                            name="confirmation"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="Type: Delete"
                            data-delete-confirm-input
                        >
                        <div class="settings-delete-actions">
                            <button type="submit" class="bill-btn bill-btn--outline" data-delete-confirm-submit disabled>
                                Delete permanently
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <p class="settings-footnote" data-reveal>
        Need help? <a href="<?= $helpUrl ?>" class="bill-link">Visit the Help Center</a>
        or manage your plan on <a href="<?= $billingUrl ?>" class="bill-link">Billing</a>.
    </p>
</div>
