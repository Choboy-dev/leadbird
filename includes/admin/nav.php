<?php

declare(strict_types=1);

/** @var array{id: string, label: string, url: string, icon: string} */
function admin_render_nav_link(string $key, array $item, string $activeNav): void
{
    $isActive = $key === $activeNav;
    $iconHtml = brand_dashboard_nav_icon_html((string) ($item['icon'] ?? ''));
    ?>
    <a
        href="<?= e((string) $item['url']) ?>"
        class="dashboard-nav-link<?= $isActive ? ' is-active' : '' ?>"
        data-nav-key="<?= e($key) ?>"
        <?= $isActive ? 'aria-current="page"' : '' ?>
    >
        <span class="dashboard-nav-icon" aria-hidden="true"><?= $iconHtml ?? '' ?></span>
        <span class="dashboard-nav-text"><?= e((string) $item['label']) ?></span>
    </a>
    <?php
}

/**
 * @param array{id: string, label: string, icon?: string, items: list<string>} $group
 * @param array<string, array{label: string, url: string, icon: string}> $navItems
 */
function admin_render_nav_group(array $group, array $navItems, string $activeNav): void
{
    $groupId = (string) ($group['id'] ?? '');
    $groupLabel = (string) ($group['label'] ?? '');
    $groupIcon = (string) ($group['icon'] ?? 'settings');
    $itemKeys = is_array($group['items'] ?? null) ? $group['items'] : [];
    $groupIsActive = admin_nav_group_is_active($itemKeys, $activeNav);
    $panelId = 'admin-nav-group-' . $groupId;
    $groupIconHtml = brand_dashboard_nav_icon_html($groupIcon);
    ?>
    <div
        class="dashboard-nav-group<?= $groupIsActive ? ' is-open is-active-group' : '' ?>"
        data-dashboard-nav-group
        data-admin-nav-group="<?= e($groupId) ?>"
    >
        <button
            type="button"
            class="dashboard-nav-group-toggle"
            aria-expanded="<?= $groupIsActive ? 'true' : 'false' ?>"
            aria-controls="<?= e($panelId) ?>"
        >
            <span class="dashboard-nav-icon" aria-hidden="true"><?= $groupIconHtml ?? '' ?></span>
            <span class="dashboard-nav-group-label"><?= e($groupLabel) ?></span>
            <svg class="dashboard-nav-group-chevron" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true">
                <path d="M5 7.5 10 12.5 15 7.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="dashboard-nav-group-panel" id="<?= e($panelId) ?>"<?= $groupIsActive ? '' : ' hidden' ?>>
            <?php foreach ($itemKeys as $key): ?>
                <?php if (isset($navItems[$key])) {
                    admin_render_nav_link($key, $navItems[$key], $activeNav);
                } ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
