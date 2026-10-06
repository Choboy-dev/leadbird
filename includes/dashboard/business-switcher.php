<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var array<string, mixed>|null $business */
/** @var list<array<string, mixed>> $allBusinesses */

$activeId = is_array($business) ? (string) ($business['id'] ?? '') : '';
$activeName = is_array($business) ? (string) ($business['business_name'] ?? '') : '';
if ($activeName === '') {
    return;
}

$switchUrl = e(app_route('businesses/switch'));
$currentPath = (string) ($_SERVER['REQUEST_URI'] ?? '/dashboard');
$returnTo = e($appUrl) . $currentPath;
$activeInitial = strtoupper(mb_substr(trim($activeName), 0, 1, 'UTF-8') ?: 'B');
$hasMultiple = count($allBusinesses) > 1;
$switcherId = (string) ($switcherId ?? 'dash-business-switcher');
$switcherCompact = (bool) ($switcherCompact ?? false);
$userId = (new SessionAuth())->userId();
$canAddBusiness = $userId !== null && (new BusinessContext())->canAddBusiness((string) $userId);
$businessCtx = new BusinessContext();
?>

<div class="dash-business-switcher<?= $switcherCompact ? ' dash-business-switcher--compact' : '' ?>" data-business-switcher>
    <?php if ($switcherCompact): ?>
        <span class="sr-only" id="<?= e($switcherId) ?>-label">Active business</span>
    <?php else: ?>
        <p class="dash-business-switcher-eyebrow" id="<?= e($switcherId) ?>-label">Active business</p>
    <?php endif; ?>

    <form
        method="post"
        action="<?= $switchUrl ?>"
        class="dash-business-switcher-form"
        data-business-switcher-form
    >
        <input type="hidden" name="business_id" value="<?= e($activeId) ?>" data-business-switcher-input>
        <input type="hidden" name="return_to" value="<?= $returnTo ?>">
        <?= csrf_field() ?>

        <button
            type="button"
            class="dash-business-switcher-trigger"
            id="<?= e($switcherId) ?>-trigger"
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-labelledby="<?= e($switcherId) ?>-label <?= e($switcherId) ?>-trigger"
            aria-controls="<?= e($switcherId) ?>-menu"
        >
            <span class="dash-business-switcher-avatar font-heading" aria-hidden="true"><?= e($activeInitial) ?></span>
            <span class="dash-business-switcher-value">
                <span class="dash-business-switcher-name font-heading"><?= e($activeName) ?></span>
            </span>
            <svg class="dash-business-switcher-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m6 9 6 6 6-6"></path>
            </svg>
        </button>

        <ul
            class="dash-business-switcher-menu"
            id="<?= e($switcherId) ?>-menu"
            role="listbox"
            aria-labelledby="<?= e($switcherId) ?>-label"
            hidden
        >
            <?php foreach ($allBusinesses as $item): ?>
                <?php
                $itemId = (string) ($item['id'] ?? '');
                $itemName = (string) ($item['business_name'] ?? 'Business');
                $itemInitial = strtoupper(mb_substr(trim($itemName), 0, 1, 'UTF-8') ?: 'B');
                $isSelected = $itemId === $activeId;
                $isComplete = $businessCtx->isBusinessComplete($item);
                ?>
                <li role="none">
                    <button
                        type="button"
                        class="dash-business-switcher-option<?= $isSelected ? ' is-selected' : '' ?>"
                        role="option"
                        aria-selected="<?= $isSelected ? 'true' : 'false' ?>"
                        data-business-id="<?= e($itemId) ?>"
                    >
                        <span class="dash-business-switcher-option-avatar font-heading" aria-hidden="true"><?= e($itemInitial) ?></span>
                        <span class="dash-business-switcher-option-copy">
                            <span class="dash-business-switcher-option-name"><?= e($itemName) ?></span>
                            <?php if (!$isComplete): ?>
                                <span class="dash-business-switcher-option-meta">Setup in progress</span>
                            <?php endif; ?>
                        </span>
                        <span class="dash-business-switcher-check" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5"></path>
                            </svg>
                        </span>
                    </button>
                </li>
            <?php endforeach; ?>
            <li class="dash-business-switcher-footer" role="none">
                <a href="<?= e(app_route('businesses')) ?>" class="dash-business-switcher-manage">Manage businesses</a>
                <?php if ($canAddBusiness): ?>
                    <a href="<?= e(app_route('businesses/start')) ?>" class="dash-business-switcher-add">+ Add business</a>
                <?php elseif (!$hasMultiple): ?>
                    <a href="<?= e(app_route('billing')) ?>" class="dash-business-switcher-manage dash-business-switcher-manage--muted">Upgrade to add more</a>
                <?php endif; ?>
            </li>
        </ul>
    </form>
</div>
