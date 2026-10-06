<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string $userId */
/** @var int $businessCount */
/** @var string $panelTitle */
/** @var list<array{id: string, name: string, created_at: string, status: string, status_label: string, is_complete: bool, is_active: bool, channel_count: int, platforms: list<string>, search_text: string}> $allBusinessesList */
/** @var bool $canAdd */
/** @var int|null $businessLimit */
/** @var string|false|null $success */
/** @var string|false|null $error */

$switchUrl = e(app_route('businesses/switch'));
$deleteUrl = e(app_route('businesses/delete'));
$billingUrl = e(app_route('billing'));
$addBusinessUrl = e(app_route('businesses/start'));
$connectedAccountsUrl = app_route('connected-accounts');
$dashboardUrl = app_route('dashboard');
?>
<div class="conn-page conn-page--businesses">
    <header class="conn-page-head">
        <h1 class="conn-page-title font-heading">All businesses</h1>
        <button type="button" class="conn-btn conn-btn--primary" data-conn-open-add-modal>
            Add business
        </button>
    </header>

    <?php if ($success): ?>
        <div class="conn-alert conn-alert--success" role="status"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="conn-alert conn-alert--error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="conn-panel" aria-label="Businesses list">
        <div class="conn-panel-head">
            <h2 class="conn-panel-title font-heading"><?= e($panelTitle) ?></h2>
            <a href="<?= e(app_route('connected-accounts')) ?>" class="conn-panel-link">Manage social accounts</a>
        </div>

        <div class="conn-toolbar">
            <label class="conn-search">
                <span class="sr-only">Search for businesses</span>
                <svg class="conn-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.75"/>
                    <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
                <input
                    type="search"
                    class="conn-search-input"
                    placeholder="Search for businesses"
                    data-conn-search
                    autocomplete="off"
                >
            </label>
            <div class="conn-filters">
                <label class="conn-filter">
                    <span class="sr-only">Channels</span>
                    <select class="conn-filter-select" data-conn-filter-network>
                        <option value="">Channels</option>
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="none">No channels</option>
                    </select>
                </label>
                <label class="conn-filter">
                    <span class="sr-only">Status</span>
                    <select class="conn-filter-select" data-conn-filter-status>
                        <option value="">Status</option>
                        <option value="active">Active</option>
                        <option value="setup">In setup</option>
                        <option value="ready">Ready</option>
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
                                <span class="conn-th-label">Business</span>
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
                    <?php if ($allBusinessesList === []): ?>
                        <tr class="conn-table-empty" data-conn-empty-row>
                            <td colspan="4">No businesses yet. Click <strong>Add business</strong> to get started.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allBusinessesList as $businessRow): ?>
                            <?php
                            $businessId = (string) $businessRow['id'];
                            $businessName = (string) $businessRow['name'];
                            $addedOn = $businessRow['created_at'] !== ''
                                ? date('M j, Y', strtotime($businessRow['created_at']))
                                : '—';
                            $initial = mb_strtoupper(mb_substr(trim($businessName), 0, 1));
                            if ($initial === '') {
                                $initial = 'B';
                            }
                            $platforms = is_array($businessRow['platforms']) ? $businessRow['platforms'] : [];
                            $platformsAttr = implode(',', $platforms);
                            $status = (string) $businessRow['status'];
                            $statusLabel = (string) $businessRow['status_label'];
                            $statusClass = match ($status) {
                                'active' => 'conn-status-pill',
                                'setup' => 'conn-status-pill conn-status-pill--setup',
                                default => 'conn-status-pill conn-status-pill--idle',
                            };
                            $channelSummary = (int) $businessRow['channel_count'] > 0
                                ? (int) $businessRow['channel_count'] . ' channel' . ((int) $businessRow['channel_count'] === 1 ? '' : 's')
                                : 'No channels';
                            ?>
                            <tr
                                class="conn-table-row"
                                data-conn-row
                                data-platforms="<?= e($platformsAttr) ?>"
                                data-status="<?= e($status) ?>"
                                data-search="<?= e((string) $businessRow['search_text']) ?>"
                            >
                                <td>
                                    <div class="conn-account-cell">
                                        <span class="conn-account-avatar conn-account-avatar--business" aria-hidden="true">
                                            <span class="conn-account-initial font-heading"><?= e($initial) ?></span>
                                        </span>
                                        <span class="conn-account-copy">
                                            <span class="conn-account-name"><?= e($businessName) ?></span>
                                            <span class="conn-account-meta"><?= e($channelSummary) ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="conn-table-date"><?= e($addedOn) ?></td>
                                <td>
                                    <span class="<?= e($statusClass) ?>"><?= e($statusLabel) ?></span>
                                </td>
                                <td class="conn-table-actions">
                                    <div class="conn-actions" data-conn-actions>
                                        <button
                                            type="button"
                                            class="conn-actions-trigger"
                                            data-conn-actions-trigger
                                            aria-label="Actions for <?= e($businessName) ?>"
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                        >
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <circle cx="5" cy="12" r="1.75"/><circle cx="12" cy="12" r="1.75"/><circle cx="19" cy="12" r="1.75"/>
                                            </svg>
                                        </button>
                                        <div class="conn-actions-menu" data-conn-actions-menu role="menu" hidden>
                                            <form method="post" action="<?= $switchUrl ?>" class="conn-actions-form" role="none">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="business_id" value="<?= e($businessId) ?>">
                                                <input type="hidden" name="return_to" value="<?= e($dashboardUrl) ?>">
                                                <button type="submit" class="conn-actions-item" role="menuitem">
                                                    <?= $businessRow['is_complete']
                                                        ? ($businessRow['is_active'] ? 'Open dashboard' : 'Switch to this business')
                                                        : 'Continue setup' ?>
                                                </button>
                                            </form>
                                            <?php if ($businessRow['is_complete']): ?>
                                                <form method="post" action="<?= $switchUrl ?>" class="conn-actions-form" role="none">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="business_id" value="<?= e($businessId) ?>">
                                                    <input type="hidden" name="return_to" value="<?= e($connectedAccountsUrl) ?>">
                                                    <button type="submit" class="conn-actions-item" role="menuitem">Social accounts</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="post" action="<?= $deleteUrl ?>" class="conn-actions-form" role="none">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="business_id" value="<?= e($businessId) ?>">
                                                <button
                                                    type="submit"
                                                    class="conn-actions-item conn-actions-item--danger"
                                                    data-confirm-delete-business
                                                    data-business-name="<?= e($businessName) ?>"
                                                    role="menuitem"
                                                >
                                                    Delete business
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="conn-table-empty is-hidden" data-conn-no-results hidden>
                            <td colspan="4">No businesses match your search.</td>
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
        aria-labelledby="conn-add-business-title"
        data-conn-add-modal
    >
        <button type="button" class="conn-modal-close" data-conn-close-add-modal aria-label="Close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
        <h2 id="conn-add-business-title" class="conn-modal-title font-heading">Add a business to Lead Bird</h2>
        <p class="conn-modal-lead">
            Each business gets its own AI Salesgirl, inbox, offers, and social channels.
            <?php if ($businessLimit !== null): ?>
                You have <?= (int) $businessCount ?> of <?= (int) $businessLimit ?> businesses on your plan.
            <?php endif; ?>
        </p>

        <div class="conn-modal-cta">
            <?php if ($canAdd): ?>
                <a href="<?= $addBusinessUrl ?>" class="conn-btn conn-btn--primary conn-modal-cta-btn">Add business</a>
            <?php else: ?>
                <a href="<?= $billingUrl ?>" class="conn-btn conn-btn--primary conn-modal-cta-btn">Upgrade plan</a>
                <p class="conn-modal-cta-note">Your plan business limit has been reached.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?= e($appUrl) ?>/assets/js/dashboard-connected-accounts.js?v=9"></script>
