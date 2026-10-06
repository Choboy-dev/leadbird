<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string|null $businessId */
/** @var string $userId */
/** @var string $businessName */
/** @var int $connectedCount */
/** @var list<array{id: string, platform: string, display_name: string, avatar_src: string, platform_label: string, created_at: string, is_active: bool}> $allAccounts */
/** @var array<string, array{label: string, description: string}> $connectPlatforms */
/** @var array<string, bool> $platformCanConnect */
/** @var bool $tiktokEnabled */
/** @var string $guideUrl */
/** @var string|false|null $success */
/** @var string|false|null $error */
/** @var list<array<string, mixed>> $allBusinesses */

$disconnectUrl = e(app_route('connected-accounts/disconnect'));
$billingUrl = e(app_route('billing'));
$returnUrl = app_route('connected-accounts');
$businessSwitchUrl = e(app_route('businesses/switch'));
$currentPath = (string) ($_SERVER['REQUEST_URI'] ?? '/connected-accounts');
$returnTo = e($appUrl) . $currentPath;
?>
<div class="conn-page">
    <header class="conn-page-head">
        <h1 class="conn-page-title font-heading">All social accounts</h1>
        <button type="button" class="conn-btn conn-btn--primary" data-conn-open-add-modal>
            Add social account
        </button>
    </header>

    <?php if ($success): ?>
        <div class="conn-alert conn-alert--success" role="status"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="conn-alert conn-alert--error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="conn-panel" aria-label="Social accounts list">
        <div class="conn-panel-head">
            <h2 class="conn-panel-title font-heading">
                <?= (int) $connectedCount ?> social account<?= $connectedCount === 1 ? '' : 's' ?> in <?= e($businessName) ?>
            </h2>
            <a href="<?= e($guideUrl) ?>" class="conn-panel-link">Learn how to connect social accounts</a>
        </div>

        <div class="conn-toolbar">
            <label class="conn-search">
                <span class="sr-only">Search for social accounts</span>
                <svg class="conn-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.75"/>
                    <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
                <input
                    type="search"
                    class="conn-search-input"
                    placeholder="Search for social accounts"
                    data-conn-search
                    autocomplete="off"
                >
            </label>
            <div class="conn-filters">
                <label class="conn-filter">
                    <span class="sr-only">Social Network</span>
                    <select class="conn-filter-select" data-conn-filter-network>
                        <option value="">Social Network</option>
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="whatsapp">WhatsApp</option>
                        <?php if ($tiktokEnabled): ?>
                            <option value="tiktok">TikTok</option>
                        <?php endif; ?>
                    </select>
                </label>
                <label class="conn-filter">
                    <span class="sr-only">Status</span>
                    <select class="conn-filter-select" data-conn-filter-status>
                        <option value="">Status</option>
                        <option value="connected">Connected</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="conn-table-wrap">
            <table class="conn-table">
                <thead>
                    <tr>
                        <th scope="col" class="conn-th-account">
                            <span class="conn-th-inner">
                                <span class="conn-th-label">Social account</span>
                                <svg class="conn-th-sort" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M8 9l4-4 4 4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8 15l4 4 4-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </th>
                        <th scope="col">Added on</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="conn-th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($allAccounts === []): ?>
                        <tr class="conn-table-empty" data-conn-empty-row>
                            <td colspan="4">No social accounts connected yet. Click <strong>Add social account</strong> to get started.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allAccounts as $account): ?>
                            <?php
                            $platform = (string) $account['platform'];
                            $addedOn = $account['created_at'] !== ''
                                ? date('M j, Y', strtotime($account['created_at']))
                                : '—';
                            $initial = mb_strtoupper(mb_substr(trim((string) $account['display_name']), 0, 1));
                            if ($initial === '') {
                                $initial = mb_strtoupper(mb_substr($platform, 0, 1));
                            }
                            $reconnectUrl = dash_platform_reconnect_url($platform, (string) $businessId, $returnUrl);
                            $searchText = strtolower(trim(
                                (string) $account['display_name'] . ' ' . (string) $account['platform_label'] . ' ' . $platform
                            ));
                            $pictureUrl = trim((string) ($account['avatar_src'] ?? ''));
                            ?>
                            <tr
                                class="conn-table-row"
                                data-conn-row
                                data-platform="<?= e($platform) ?>"
                                data-status="connected"
                                data-search="<?= e($searchText) ?>"
                            >
                                <td>
                                    <div class="conn-account-cell">
                                        <span class="conn-account-avatar conn-account-avatar--<?= e($platform) ?>" aria-hidden="true">
                                            <?php if ($pictureUrl !== ''): ?>
                                                <img
                                                    src="<?= e($pictureUrl) ?>"
                                                    alt=""
                                                    class="conn-account-photo"
                                                    width="48"
                                                    height="48"
                                                    loading="lazy"
                                                    data-conn-avatar-img
                                                    data-initial="<?= e($initial) ?>"
                                                >
                                                <span class="conn-account-initial conn-account-initial--fallback font-heading is-hidden" aria-hidden="true"><?= e($initial) ?></span>
                                            <?php else: ?>
                                                <span class="conn-account-initial font-heading"><?= e($initial) ?></span>
                                            <?php endif; ?>
                                            <span class="conn-account-badge conn-account-badge--<?= e($platform) ?>">
                                                <?= dash_platform_badge_icon($platform, 16) ?>
                                            </span>
                                        </span>
                                        <span class="conn-account-name"><?= e((string) $account['display_name']) ?></span>
                                    </div>
                                </td>
                                <td class="conn-table-date"><?= e($addedOn) ?></td>
                                <td>
                                    <span class="conn-status-pill">Connected</span>
                                </td>
                                <td class="conn-table-actions">
                                    <div class="conn-actions" data-conn-actions>
                                        <button
                                            type="button"
                                            class="conn-actions-trigger"
                                            data-conn-actions-trigger
                                            aria-label="Actions for <?= e((string) $account['display_name']) ?>"
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                        >
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <circle cx="5" cy="12" r="1.75"/><circle cx="12" cy="12" r="1.75"/><circle cx="19" cy="12" r="1.75"/>
                                            </svg>
                                        </button>
                                        <div class="conn-actions-menu" data-conn-actions-menu role="menu" hidden>
                                            <a href="<?= e($reconnectUrl) ?>" class="conn-actions-item" role="menuitem">Reconnect</a>
                                            <form method="post" action="<?= $disconnectUrl ?>" class="conn-actions-form" role="none">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="account_id" value="<?= e((string) $account['id']) ?>">
                                                <button type="submit" class="conn-actions-item conn-actions-item--danger" data-confirm-disconnect role="menuitem">
                                                    Disconnect
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="conn-table-empty is-hidden" data-conn-no-results hidden>
                            <td colspan="4">No social accounts match your search.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="conn-modal-overlay is-hidden" data-conn-add-overlay aria-hidden="true">
    <div
        class="conn-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="conn-add-modal-title"
        data-conn-add-modal
    >
        <button type="button" class="conn-modal-close" data-conn-close-add-modal aria-label="Close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
        <h2 id="conn-add-modal-title" class="conn-modal-title font-heading">Add an account to Lead Bird</h2>
        <p class="conn-modal-lead">
            Connecting your social accounts to Lead Bird is the first step in saving time on social media.
            <a href="<?= e($guideUrl) ?>" class="conn-modal-help">
                Need help?
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 17L17 7M17 7H9M17 7v8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </p>

        <div class="conn-modal-addto">
            <label class="conn-modal-addto-label" for="conn-modal-business">Add to:</label>
            <div class="conn-modal-business-field">
            <?php if (count($allBusinesses) > 1): ?>
                <form method="post" action="<?= $businessSwitchUrl ?>" class="conn-modal-addto-form" data-conn-business-form>
                    <?= csrf_field() ?>
                    <input type="hidden" name="return_to" value="<?= $returnTo ?>">
                    <select id="conn-modal-business" class="conn-modal-business-select" name="business_id" data-conn-business-select>
                        <?php foreach ($allBusinesses as $biz): ?>
                            <?php $bizId = (string) ($biz['id'] ?? ''); ?>
                            <option value="<?= e($bizId) ?>"<?= $bizId === (string) $businessId ? ' selected' : '' ?>>
                                <?= e((string) ($biz['business_name'] ?? 'Business')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php else: ?>
                <div class="conn-modal-business-readonly" id="conn-modal-business">
                    <span class="conn-modal-business-readonly-value"><?= e($businessName) ?></span>
                </div>
            <?php endif; ?>
            </div>
        </div>

        <div class="conn-platform-grid">
            <?php foreach ($connectPlatforms as $platform => $meta): ?>
                <?php
                $canConnect = (bool) ($platformCanConnect[$platform] ?? false);
                $setupUrl = dash_platform_setup_url($platform, (string) $businessId, $returnUrl);
                ?>
                <?php if ($canConnect): ?>
                    <a href="<?= e($setupUrl) ?>" class="conn-platform-tile conn-platform-tile--<?= e($platform) ?>">
                        <span class="conn-platform-tile-icon" aria-hidden="true"><?= dash_platform_tile_icon($platform, 48) ?></span>
                        <span class="conn-platform-tile-label"><?= e($meta['label']) ?></span>
                    </a>
                <?php else: ?>
                    <a href="<?= $billingUrl ?>" class="conn-platform-tile conn-platform-tile--disabled" title="Plan limit reached — upgrade to add more">
                        <span class="conn-platform-tile-icon" aria-hidden="true"><?= dash_platform_tile_icon($platform, 48) ?></span>
                        <span class="conn-platform-tile-label"><?= e($meta['label']) ?></span>
                        <span class="conn-platform-tile-note">Upgrade plan</span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="<?= e($appUrl) ?>/assets/js/dashboard-connected-accounts.js?v=9"></script>
