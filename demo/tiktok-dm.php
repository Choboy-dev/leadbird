<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/bootstrap.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

$pageTitle = 'TikTok DMs prototype — ' . $brandName;
$dashboardNav = 'inbox';
$dashboardNavCollapsed = true;
$dashboardHideTopbar = true;
$extraStylesheets = ['dashboard-inbox.css?v=29', 'demo-tiktok-dm.css?v=3'];
$extraScripts = ['demo-tiktok-dm.js?v=1'];

$threads = [
    ['name' => 'Ada Okafor', 'handle' => '@ada.styles', 'time' => 'now', 'tag' => '', 'preview' => 'New chat from your TikTok video', 'active' => true],
    ['name' => 'Chidi Eze', 'handle' => '@chidi.e', 'time' => '12m', 'tag' => 'AI · ', 'preview' => 'Your order is on its way to Ikeja 🚚', 'active' => false],
    ['name' => 'Bisi Adeyemi', 'handle' => '@bisi.a', 'time' => '1h', 'tag' => 'You · ', 'preview' => 'Thank you! See you on Saturday', 'active' => false],
    ['name' => 'Musa Bello', 'handle' => '@musabello', 'time' => '3h', 'tag' => 'AI · ', 'preview' => 'Size 10 and 12 are available in black', 'active' => false],
];

$dashboardNavContext = [
    'inbox' => [
        'nav_params' => static fn (array $params = []): array => $params,
        'all_chats_active' => true,
        'selected_label_id' => '',
        'total_chats' => count($threads),
        'favorites_label' => null,
        'custom_labels' => [],
    ],
];

require dirname(__DIR__) . '/includes/dashboard/layout-start.php';
?>
<div class="inbox-app inbox-app--thread-open tkd-demo" data-tkd-demo>
    <header class="inbox-header">
        <h1 class="inbox-header-title">Inbox</h1>
        <p class="tkd-proto-pill"><b>Prototype</b> TikTok DM replies are not live yet. This shows the planned flow.</p>
        <div class="tkd-controls" role="group" aria-label="Prototype controls">
            <span class="tkd-controls-step" data-tkd-step>Step 0 of 6</span>
            <button type="button" class="tkd-controls-btn tkd-controls-btn--ghost" data-tkd-restart>Restart</button>
            <button type="button" class="tkd-controls-btn" data-tkd-next>Next ▶</button>
        </div>
    </header>

    <div class="inbox-frame">
        <nav class="inbox-nav" aria-label="Inbox folders">
            <span class="inbox-nav-item is-active" aria-current="page">
                <span class="inbox-nav-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </span>
                <span class="inbox-nav-label">All chats</span>
                <span class="inbox-nav-count"><?= count($threads) ?></span>
            </span>
            <span class="inbox-nav-item">
                <span class="inbox-nav-icon" aria-hidden="true"><?= dash_platform_icon('tiktok') ?></span>
                <span class="inbox-nav-label">TikTok</span>
                <span class="inbox-nav-count"><?= count($threads) ?></span>
            </span>
        </nav>

        <div class="inbox-main">
            <div class="inbox-main-body has-thread has-list">
                <aside class="inbox-thread-panel">
                    <div class="inbox-thread-list" role="list">
                        <?php foreach ($threads as $thread): ?>
                            <div class="inbox-thread-row<?= $thread['active'] ? ' is-active' : '' ?>" role="listitem"<?= $thread['active'] ? ' data-tkd-active-row' : '' ?>>
                                <span class="inbox-thread-row-link">
                                    <?= dash_customer_avatar_html(null, $thread['name'], 'inbox-thread-row-avatar') ?>
                                    <span class="inbox-thread-row-body">
                                        <span class="inbox-thread-row-top">
                                            <strong class="inbox-thread-row-name"><?= e($thread['name']) ?></strong>
                                            <span class="inbox-thread-row-time" data-tkd-row-time><?= e($thread['time']) ?></span>
                                        </span>
                                        <span class="inbox-thread-row-preview">
                                            <span class="inbox-thread-row-tag" data-tkd-row-tag><?= e($thread['tag']) ?></span><span data-tkd-row-preview><?= e($thread['preview']) ?></span>
                                        </span>
                                    </span>
                                    <span class="inbox-thread-row-channel" aria-hidden="true"><?= dash_platform_icon('tiktok') ?></span>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </aside>

                <section class="inbox-chat-panel">
                    <header class="inbox-chat-topbar">
                        <div class="inbox-chat-contact">
                            <?= dash_customer_avatar_html(null, 'Ada Okafor', 'inbox-chat-avatar') ?>
                            <div class="inbox-chat-contact-body">
                                <h2 class="inbox-chat-name">Ada Okafor</h2>
                                <div class="inbox-chat-meta">
                                    <span class="inbox-platform-pill">
                                        <span class="inbox-platform-icon" aria-hidden="true"><?= dash_platform_icon('tiktok') ?></span>
                                        <span class="inbox-platform-pill-label">TikTok</span>
                                    </span>
                                    <span class="inbox-chat-meta-time">@ada.styles</span>
                                </div>
                            </div>
                        </div>
                    </header>

                    <div class="inbox-message-rail" data-tkd-rail>
                        <p class="tkd-system">Ada messaged <b>@toluscloset</b> on TikTok after watching your “Red satin gown” video.</p>
                    </div>

                    <footer class="inbox-compose">
                        <div class="inbox-compose-toolbar">
                            <button type="button" class="inbox-ai-toggle" data-tkd-ai-toggle aria-pressed="true" aria-label="Turn off AI replies for this chat">
                                <span class="inbox-ai-toggle-track" aria-hidden="true"><span class="inbox-ai-toggle-thumb"></span></span>
                                <span class="inbox-ai-toggle-label">AI replies</span>
                            </button>
                            <p class="inbox-compose-status" data-tkd-status>Hannah replies automatically. You can jump in any time.</p>
                        </div>
                        <form class="inbox-compose-form" data-tkd-compose novalidate>
                            <label class="inbox-compose-field">
                                <span class="sr-only">Reply to customer</span>
                                <textarea class="inbox-compose-input" data-tkd-input rows="1" maxlength="1000" placeholder="Type your reply..."></textarea>
                                <button type="submit" class="inbox-compose-send" aria-label="Send reply">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m22 2-7 20-4-9-9-4Z"/>
                                        <path d="M22 2 11 13"/>
                                    </svg>
                                    <span>Send</span>
                                </button>
                            </label>
                        </form>
                    </footer>
                </section>
            </div>
        </div>
    </div>

    <div class="tkd-toast" data-tkd-toast role="status" hidden></div>

    <template data-tkd-tpl-customer>
        <article class="inbox-message-row">
            <?= dash_customer_avatar_html(null, 'Ada Okafor', 'inbox-message-avatar') ?>
            <div class="inbox-message-bubble-wrap">
                <div class="inbox-message-meta"><strong>Ada Okafor</strong><span data-tkd-time></span></div>
                <div class="inbox-message-bubble"><p data-tkd-text></p></div>
            </div>
        </article>
    </template>
    <template data-tkd-tpl-ai>
        <article class="inbox-message-row is-outbound is-ai">
            <div class="inbox-message-bubble-wrap">
                <div class="inbox-message-meta"><strong>Hannah · Lead Bird AI</strong><span data-tkd-time></span></div>
                <div class="inbox-message-bubble inbox-message-bubble--ai"><p data-tkd-text></p></div>
            </div>
            <span class="inbox-message-avatar inbox-message-avatar--ai" aria-hidden="true"><?= brand_favicon_icon_html('inbox-ai-avatar-icon', 20, 'Lead Bird AI') ?></span>
        </article>
    </template>
    <template data-tkd-tpl-owner>
        <article class="inbox-message-row is-outbound is-owner">
            <div class="inbox-message-bubble-wrap">
                <div class="inbox-message-meta"><strong>Tolu (you)</strong><span data-tkd-time></span></div>
                <div class="inbox-message-bubble inbox-message-bubble--owner"><p data-tkd-text></p></div>
            </div>
            <span class="inbox-message-avatar inbox-message-avatar--owner" aria-hidden="true">TO</span>
        </article>
    </template>
</div>
<?php require dirname(__DIR__) . '/includes/dashboard/layout-end.php'; ?>
