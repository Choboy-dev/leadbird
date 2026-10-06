<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var list<array{id: string, customer: array<string, mixed>, name: string, initial: string, avatar_src: string, platform: string, platform_label: string, last_message: string, inbox_url: string, search_text: string}> $allCustomersList */

$inboxUrl = e(app_route('inbox'));
$connectedAccountsUrl = e(app_route('connected-accounts'));
?>
<div class="conn-page conn-page--customers">
    <header class="conn-page-head">
        <h1 class="conn-page-title font-heading">All customers</h1>
        <a href="<?= $inboxUrl ?>" class="conn-btn conn-btn--primary">Open inbox</a>
    </header>

    <section class="conn-panel" aria-label="Customers list">
        <div class="conn-toolbar">
            <label class="conn-search">
                <span class="sr-only">Search for customers</span>
                <svg class="conn-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.75"/>
                    <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
                <input
                    type="search"
                    class="conn-search-input"
                    data-conn-search
                    placeholder="Search for customers"
                    autocomplete="off"
                >
            </label>
            <div class="conn-filters">
                <label class="conn-filter">
                    <span class="sr-only">Channel</span>
                    <select class="conn-filter-select" data-conn-filter-network>
                        <option value="">Channel</option>
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="whatsapp">WhatsApp</option>
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
                                <span class="conn-th-label">Customer</span>
                                <svg class="conn-th-sort" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M8 9l4-4 4 4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8 15l4 4 4-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </th>
                        <th scope="col">Last message</th>
                        <th scope="col">Channel</th>
                        <th scope="col" class="conn-th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($allCustomersList === []): ?>
                        <tr class="conn-table-empty" data-conn-empty-row>
                            <td colspan="4">
                                No customers yet. Contacts appear here when people message your connected channels.
                                <a href="<?= $connectedAccountsUrl ?>" class="conn-panel-link conn-table-empty-link">Connect social accounts</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allCustomersList as $row): ?>
                            <?php
                            $platform = (string) $row['platform'];
                            $avatarSrc = trim((string) $row['avatar_src']);
                            $initial = (string) $row['initial'];
                            ?>
                            <tr
                                class="conn-table-row"
                                data-conn-row
                                data-platform="<?= e($platform) ?>"
                                data-search="<?= e((string) $row['search_text']) ?>"
                            >
                                <td>
                                    <div class="conn-account-cell">
                                        <span class="conn-account-avatar conn-account-avatar--<?= e($platform !== '' ? $platform : 'business') ?>" aria-hidden="true">
                                            <?php if ($avatarSrc !== ''): ?>
                                                <img
                                                    src="<?= e($avatarSrc) ?>"
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
                                            <?php if ($platform !== ''): ?>
                                                <span class="conn-account-badge conn-account-badge--<?= e($platform) ?>">
                                                    <?= dash_platform_badge_icon($platform, 16) ?>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="conn-account-name"><?= e((string) $row['name']) ?></span>
                                    </div>
                                </td>
                                <td class="conn-table-message"><?= e((string) $row['last_message']) ?></td>
                                <td><?= e((string) $row['platform_label']) ?></td>
                                <td class="conn-table-actions">
                                    <div class="conn-actions" data-conn-actions>
                                        <button
                                            type="button"
                                            class="conn-actions-trigger"
                                            data-conn-actions-trigger
                                            aria-label="Actions for <?= e((string) $row['name']) ?>"
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                        >
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <circle cx="5" cy="12" r="1.75"/><circle cx="12" cy="12" r="1.75"/><circle cx="19" cy="12" r="1.75"/>
                                            </svg>
                                        </button>
                                        <div class="conn-actions-menu" data-conn-actions-menu role="menu" hidden>
                                            <a href="<?= e((string) $row['inbox_url']) ?>" class="conn-actions-item" role="menuitem">Open chat</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="conn-table-empty is-hidden" data-conn-no-results hidden>
                            <td colspan="4">No customers match your search.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script src="<?= e($appUrl) ?>/assets/js/dashboard-connected-accounts.js?v=10"></script>
