<?php

declare(strict_types=1);

/**
 * @param array<string, mixed>|null $businessSettings
 */
function dashboard_ai_assistant_label(?array $businessSettings = null): string
{
    $aiName = trim((string) ($businessSettings['ai_name'] ?? ''));
    if ($aiName === '') {
        $aiName = 'Salesgirl';
    }

    return 'Ask ' . $aiName;
}

/**
 * @param array<string, mixed>|null $businessSettings
 * @return array<string, array{label: string, url: string, icon: string, feature: string}>
 */
function dashboard_nav_items(?array $businessSettings = null): array
{
    return [
        'overview' => ['label' => 'Overview', 'url' => app_route('dashboard'), 'icon' => 'overview', 'feature' => 'overview'],
        'inbox' => ['label' => 'Inbox', 'url' => app_route('inbox'), 'icon' => 'inbox', 'feature' => 'inbox'],
        'businesses' => ['label' => 'My Businesses', 'url' => app_route('businesses'), 'icon' => 'my_business', 'feature' => 'overview'],
        'customers' => ['label' => 'Customers', 'url' => app_route('customers'), 'icon' => 'customers', 'feature' => 'customers'],
        'my_business' => ['label' => 'Offers & Catalog', 'url' => app_route('offers'), 'icon' => 'offers', 'feature' => 'offers'],
        'ai_salesgirl' => [
            'label' => dashboard_ai_assistant_label($businessSettings),
            'url' => app_route('ai-salesgirl'),
            'icon' => 'ai',
            'feature' => 'ai_salesgirl',
        ],
        'social_media' => ['label' => 'Planner', 'url' => app_route('social-media'), 'icon' => 'social_media', 'feature' => 'social_media'],
        'refer' => ['label' => 'Refer & Earn', 'url' => app_route('refer'), 'icon' => 'billing', 'feature' => 'refer_earn'],
        'connected_accounts' => ['label' => 'Social Accounts', 'url' => app_route('connected-accounts'), 'icon' => 'connected_accounts', 'feature' => 'connected_accounts'],
        'billing' => ['label' => 'Billing', 'url' => app_route('billing'), 'icon' => 'billing', 'feature' => 'billing'],
        'settings' => ['label' => 'Settings', 'url' => app_route('settings'), 'icon' => 'settings', 'feature' => 'settings'],
        'help' => ['label' => 'Help', 'url' => app_route('help'), 'icon' => 'help', 'feature' => 'help'],
    ];
}

/**
 * Sidebar groups: top-level links plus collapsible sections.
 *
 * @return list<array{id: string, label?: string, icon?: string, items: list<string>}>
 */
function dashboard_nav_groups(): array
{
    return [
        [
            'id' => 'primary',
            'items' => ['overview', 'inbox', 'ai_salesgirl', 'social_media', 'customers', 'my_business', 'businesses'],
        ],
        [
            'id' => 'refer',
            'items' => ['refer'],
        ],
        [
            'id' => 'help',
            'items' => ['help'],
        ],
        [
            'id' => 'account',
            'label' => 'Account',
            'icon' => 'settings',
            'items' => ['connected_accounts', 'billing', 'settings'],
        ],
    ];
}

/**
 * @param list<string> $itemKeys
 */
function dashboard_nav_group_is_active(array $itemKeys, string $activeNav): bool
{
    return in_array($activeNav, $itemKeys, true);
}

/**
 * Mobile sub-navigation shown under parent items in the main sidebar drawer.
 *
 * @param array<string, mixed> $context
 * @return list<array<string, mixed>>
 */
function dashboard_nav_sub_items(string $parentKey, array $context = []): array
{
    return match ($parentKey) {
        'social_media' => dashboard_nav_planner_sub_items($context),
        'ai_salesgirl' => dashboard_nav_ai_salesgirl_sub_items($context),
        'inbox' => dashboard_nav_inbox_sub_items(is_array($context['inbox'] ?? null) ? $context['inbox'] : []),
        default => [],
    };
}

/**
 * @param array<string, mixed> $context
 * @return list<array<string, mixed>>
 */
function dashboard_nav_planner_sub_items(array $context = []): array
{
    $activeView = (string) ($context['planner_view'] ?? 'calendar');
    if (!in_array($activeView, ['calendar', 'drafts', 'content'], true)) {
        $activeView = 'calendar';
    }

    return [
        [
            'type' => 'link',
            'label' => 'Create a post',
            'url' => app_route('social-media', ['compose' => '1']),
            'active' => false,
        ],
        [
            'type' => 'link',
            'label' => 'Calendar',
            'url' => app_route('social-media', ['view' => 'calendar']),
            'active' => $activeView === 'calendar',
        ],
        [
            'type' => 'link',
            'label' => 'Drafts',
            'url' => app_route('social-media', ['view' => 'drafts']),
            'active' => $activeView === 'drafts',
        ],
        [
            'type' => 'link',
            'label' => 'My Posts',
            'url' => app_route('social-media', ['view' => 'content']),
            'active' => $activeView === 'content',
        ],
    ];
}

/**
 * @param array<string, mixed> $context
 * @return list<array<string, mixed>>
 */
function dashboard_nav_ai_salesgirl_sub_items(array $context = []): array
{
    $activeView = (string) ($context['ai_salesgirl_view'] ?? 'chat');
    if (!in_array($activeView, ['chat', 'knowledge', 'alerts', 'selling'], true)) {
        $activeView = 'chat';
    }

    return [
        [
            'type' => 'link',
            'label' => 'Chat',
            'url' => app_route('ai-salesgirl', ['view' => 'chat']),
            'active' => $activeView === 'chat',
        ],
        [
            'type' => 'link',
            'label' => 'Knowledge',
            'url' => app_route('ai-salesgirl', ['view' => 'knowledge']),
            'active' => $activeView === 'knowledge',
        ],
        [
            'type' => 'link',
            'label' => 'Selling',
            'url' => app_route('ai-salesgirl', ['view' => 'selling']),
            'active' => $activeView === 'selling',
        ],
        [
            'type' => 'link',
            'label' => 'Owner alerts',
            'url' => app_route('ai-salesgirl', ['view' => 'alerts']),
            'active' => $activeView === 'alerts',
        ],
        [
            'type' => 'link',
            'label' => 'Brand voice',
            'url' => app_route('ai-salesgirl', ['profile' => '1']),
            'active' => false,
        ],
    ];
}

/**
 * @param array<string, mixed> $inboxContext
 * @return list<array<string, mixed>>
 */
function dashboard_nav_inbox_sub_items(array $inboxContext): array
{
    if ($inboxContext === []) {
        return [];
    }

    $inboxNavParams = is_callable($inboxContext['nav_params'] ?? null)
        ? $inboxContext['nav_params']
        : static fn (array $params = []): array => $params;

    $allChatsActive = !empty($inboxContext['all_chats_active']);
    $selectedLabelId = trim((string) ($inboxContext['selected_label_id'] ?? ''));
    $totalChats = (int) ($inboxContext['total_chats'] ?? 0);

    $items = [
        [
            'type' => 'link',
            'label' => 'All chats',
            'url' => app_route('inbox', $inboxNavParams()),
            'active' => $allChatsActive,
            'count' => $totalChats,
        ],
    ];

    $favoritesLabel = is_array($inboxContext['favorites_label'] ?? null) ? $inboxContext['favorites_label'] : null;
    if ($favoritesLabel !== null) {
        $favoritesId = (string) ($favoritesLabel['id'] ?? '');
        $items[] = [
            'type' => 'link',
            'label' => (string) ($favoritesLabel['name'] ?? 'Favorites'),
            'url' => app_route('inbox', $inboxNavParams(['label' => $favoritesId])),
            'active' => $selectedLabelId === $favoritesId,
            'count' => (int) ($favoritesLabel['chat_count'] ?? 0),
        ];
    }

    $customLabels = is_array($inboxContext['custom_labels'] ?? null) ? $inboxContext['custom_labels'] : [];
    if ($customLabels !== []) {
        $items[] = [
            'type' => 'heading',
            'label' => 'Labels',
        ];
        foreach ($customLabels as $label) {
            if (!is_array($label)) {
                continue;
            }
            $labelId = (string) ($label['id'] ?? '');
            if ($labelId === '') {
                continue;
            }
            $items[] = [
                'type' => 'link',
                'label' => (string) ($label['name'] ?? 'Label'),
                'url' => app_route('inbox', $inboxNavParams(['label' => $labelId])),
                'active' => $selectedLabelId === $labelId,
                'count' => (int) ($label['chat_count'] ?? 0),
            ];
        }
    }

    return $items;
}

/**
 * @param list<array<string, mixed>> $subItems
 */
function dashboard_nav_sub_item_is_active(array $subItems): bool
{
    foreach ($subItems as $subItem) {
        if (!empty($subItem['active'])) {
            return true;
        }
    }

    return false;
}

/**
 * @param array{label: string, url: string, icon: string, feature: string} $item
 */
function dashboard_render_nav_link(string $key, array $item, string $dashboardNav, string $planSlug): void
{
    $allowed = PlanAccess::allows($planSlug, $item['feature']);
    $isActive = $dashboardNav === $key;
    ?>
    <a
        href="<?= e($item['url']) ?>"
        class="dashboard-nav-link<?= $isActive ? ' is-active' : '' ?><?= !$allowed ? ' is-locked' : '' ?>"
        data-nav-key="<?= e($key) ?>"
        <?= $isActive ? 'aria-current="page"' : '' ?>
    >
        <span class="dashboard-nav-icon" aria-hidden="true">
        <?php $navIconHtml = brand_dashboard_nav_icon_html((string) ($item['icon'] ?? '')); ?>
        <?php if ($navIconHtml !== null): ?>
            <?= $navIconHtml ?>
        <?php else: ?>
            <?= e((string) ($item['icon'] ?? '')) ?>
        <?php endif; ?>
        </span>
        <span class="dashboard-nav-text"><?= e($item['label']) ?></span>
        <?php if (!$allowed): ?>
            <span class="dashboard-nav-lock" aria-label="Upgrade required">🔒</span>
        <?php endif; ?>
    </a>
    <?php
}

/**
 * @param list<array<string, mixed>> $subItems
 * @param array{label: string, url: string, icon: string, feature: string} $item
 */
function dashboard_render_nav_item_with_subs(string $key, array $item, string $dashboardNav, string $planSlug, array $subItems): void
{
    $allowed = PlanAccess::allows($planSlug, $item['feature']);
    $isActive = $dashboardNav === $key;
    $hasActiveChild = dashboard_nav_sub_item_is_active($subItems);
    $groupOpen = $isActive || $hasActiveChild;
    $panelId = 'dashboard-nav-sub-' . $key;
    ?>
    <a
        href="<?= e($item['url']) ?>"
        class="dashboard-nav-link dashboard-nav-link--section<?= $isActive ? ' is-active' : '' ?><?= !$allowed ? ' is-locked' : '' ?>"
        <?= $isActive ? 'aria-current="page"' : '' ?>
    >
        <span class="dashboard-nav-icon" aria-hidden="true">
        <?php $navIconHtml = brand_dashboard_nav_icon_html((string) ($item['icon'] ?? '')); ?>
        <?php if ($navIconHtml !== null): ?>
            <?= $navIconHtml ?>
        <?php else: ?>
            <?= e((string) ($item['icon'] ?? '')) ?>
        <?php endif; ?>
        </span>
        <span class="dashboard-nav-text"><?= e($item['label']) ?></span>
        <?php if (!$allowed): ?>
            <span class="dashboard-nav-lock" aria-label="Upgrade required">🔒</span>
        <?php endif; ?>
    </a>

    <div
        class="dashboard-nav-group dashboard-nav-group--section<?= $groupOpen ? ' is-open is-active-group' : '' ?>"
        data-dashboard-nav-group
        data-dashboard-nav-section="<?= e($key) ?>"
    >
        <button
            type="button"
            class="dashboard-nav-group-toggle"
            aria-expanded="<?= $groupOpen ? 'true' : 'false' ?>"
            aria-controls="<?= e($panelId) ?>"
        >
            <span class="dashboard-nav-icon" aria-hidden="true">
            <?php if ($navIconHtml !== null): ?>
                <?= $navIconHtml ?>
            <?php endif; ?>
            </span>
            <span class="dashboard-nav-group-label"><?= e($item['label']) ?></span>
            <svg class="dashboard-nav-group-chevron" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true">
                <path d="M5 7.5 10 12.5 15 7.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="dashboard-nav-group-panel" id="<?= e($panelId) ?>"<?= $groupOpen ? '' : ' hidden' ?>>
            <?php foreach ($subItems as $subItem): ?>
                <?php dashboard_render_nav_sub_item($subItem); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * @param array<string, mixed> $subItem
 */
function dashboard_render_nav_sub_item(array $subItem): void
{
    $type = (string) ($subItem['type'] ?? 'link');
    $label = (string) ($subItem['label'] ?? '');
    $active = !empty($subItem['active']);
    $disabled = !empty($subItem['disabled']);
    $count = isset($subItem['count']) ? (int) $subItem['count'] : null;
    $attrs = is_array($subItem['attrs'] ?? null) ? $subItem['attrs'] : [];

    if ($type === 'heading') {
        ?>
        <p class="dashboard-nav-sub-heading"><?= e($label) ?></p>
        <?php
        return;
    }

    $attrString = '';
    foreach ($attrs as $attrName => $attrValue) {
        $attrString .= ' ' . e((string) $attrName);
        if ($attrValue !== '') {
            $attrString .= '="' . e((string) $attrValue) . '"';
        }
    }

    if ($type === 'button') {
        ?>
        <button
            type="button"
            class="dashboard-nav-link dashboard-nav-sub-link dashboard-nav-sub-link--button<?= $active ? ' is-active' : '' ?>"
            <?= $disabled ? 'disabled aria-disabled="true"' : '' ?>
            <?= $attrString ?>
        >
            <span class="dashboard-nav-text"><?= e($label) ?></span>
        </button>
        <?php
        return;
    }

    $url = (string) ($subItem['url'] ?? '#');
    ?>
    <a
        href="<?= e($url) ?>"
        class="dashboard-nav-link dashboard-nav-sub-link<?= $active ? ' is-active' : '' ?>"
        <?= $active ? 'aria-current="page" data-nav-sub-active="true"' : '' ?>
        <?= $attrString ?>
    >
        <span class="dashboard-nav-text"><?= e($label) ?></span>
        <?php if ($count !== null): ?>
            <span class="dashboard-nav-count"><?= $count ?></span>
        <?php endif; ?>
    </a>
    <?php
}
