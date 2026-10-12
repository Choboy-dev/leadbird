<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/helpers.php';
require_once __DIR__ . '/lib/InboxReplyService.php';

$pageTitle = 'Inbox — ' . $brandName;
$dashboardNav = 'inbox';
$pageHeading = 'Inbox';
$dashboardNavCollapsed = true;
$dashboardHideTopbar = true;
$extraStylesheets = ['dashboard-inbox.css?v=30'];

$formatTimestamp = static function (?string $value, bool $includeDate = true): string {
    return app_format_datetime($value, 'M j, g:ia', $includeDate);
};

$formatRelativeTime = static function (?string $value): string {
    return app_relative_datetime($value);
};

$messagePreview = static function (?string $text, int $limit = 84): string {
    $normalized = trim(preg_replace('/\s+/', ' ', (string) $text));
    if ($normalized === '') {
        return 'No message content yet';
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($normalized, 0, $limit, '...');
    }

    return strlen($normalized) > $limit
        ? substr($normalized, 0, $limit - 3) . '...'
        : $normalized;
};

$initialsFor = static fn (string $name): string => dash_initials_for_name($name);

$conversations = $businessId !== null
    ? RepositoryFactory::conversations()->listByBusinessId($businessId)
    : [];

$totalChats = count($conversations);
$activePlatforms = [];
foreach ($conversations as $conversationRow) {
    $platform = trim((string) ($conversationRow['platform'] ?? ''));
    if ($platform !== '') {
        $activePlatforms[$platform] = true;
    }
}
$platformCount = count($activePlatforms);
$aiReplyCount = $businessId !== null
    ? RepositoryFactory::messages()->countBySenderType($businessId, 'ai')
    : 0;

$selectedConversationId = trim((string) ($_GET['conversation'] ?? ''));
$selectedConversation = null;
foreach ($conversations as $conversationRow) {
    if ((string) ($conversationRow['id'] ?? '') === $selectedConversationId) {
        $selectedConversation = $conversationRow;
        break;
    }
}

$hasThreadSelection = $selectedConversation !== null;

$inboxLabelService = new InboxLabelService();
$inboxLabels = [];
$favoritesLabel = null;
$customInboxLabels = [];
$favoritesLabelId = '';
$labelCounts = [];
$conversationLabelMap = [];
$selectedLabelId = trim((string) ($_GET['label'] ?? ''));

if ($businessId !== null) {
    $inboxLabelService->ensureDefaults($businessId);
    $labelCounts = RepositoryFactory::inboxLabels()->countConversationsByLabelId($businessId);
    foreach (RepositoryFactory::inboxLabels()->listByBusinessId($businessId) as $labelRow) {
        $labelId = (string) ($labelRow['id'] ?? '');
        $formatted = $inboxLabelService->formatLabel($labelRow, $labelCounts[$labelId] ?? 0);
        $inboxLabels[] = $formatted;
        if ((string) ($labelRow['slug'] ?? '') === InboxLabelRepository::FAVORITES_SLUG) {
            $favoritesLabel = $formatted;
            $favoritesLabelId = $labelId;
        } else {
            $customInboxLabels[] = $formatted;
        }
    }

    $conversationIds = [];
    foreach ($conversations as $conversationRow) {
        $conversationIds[] = (string) ($conversationRow['id'] ?? '');
    }
    $conversationLabelMap = RepositoryFactory::inboxLabels()->mapLabelIdsByConversationIds($conversationIds);
}

$selectedConversationLabelIds = $selectedConversationId !== ''
    ? ($conversationLabelMap[$selectedConversationId] ?? [])
    : [];

$inboxLabelsForMenu = array_map(
    static fn (array $label): array => [
        'id' => (string) ($label['id'] ?? ''),
        'name' => (string) ($label['name'] ?? ''),
        'slug' => (string) ($label['slug'] ?? ''),
    ],
    $inboxLabels
);

$inboxNavParams = static function (array $params = []): array {
    return $params;
};

$recentMessageByConversation = [];
if ($businessId !== null) {
    foreach (RepositoryFactory::messages()->listRecentByBusinessId($businessId, 250) as $messageRow) {
        $conversationId = (string) ($messageRow['conversation_id'] ?? '');
        if ($conversationId !== '' && !isset($recentMessageByConversation[$conversationId])) {
            $recentMessageByConversation[$conversationId] = $messageRow;
        }
    }
}

$selectedCustomer = null;
$selectedMessages = [];
$selectedCustomerId = '';
$selectedLastMessage = null;

if ($selectedConversation !== null && $businessId !== null) {
    $selectedCustomerId = (string) ($selectedConversation['customer_id'] ?? '');
    $selectedCustomer = $selectedCustomerId !== ''
        ? RepositoryFactory::customers()->findById($selectedCustomerId)
        : null;
    $selectedMessages = RepositoryFactory::messages()->listByConversationId($selectedConversationId, 200);
    $selectedLastMessage = $selectedMessages !== [] ? $selectedMessages[count($selectedMessages) - 1] : null;
}

$allChatsActive = $selectedLabelId === '';
$dashboardNavContext = [
    'inbox' => [
        'nav_params' => $inboxNavParams,
        'all_chats_active' => $allChatsActive,
        'selected_label_id' => $selectedLabelId,
        'total_chats' => $totalChats,
        'favorites_label' => $favoritesLabel,
        'custom_labels' => $customInboxLabels,
    ],
];

require __DIR__ . '/includes/dashboard/layout-start.php';
?>
<div
    class="inbox-app<?= $hasThreadSelection ? ' inbox-app--thread-open' : '' ?><?= $conversations === [] ? ' inbox-app--empty' : '' ?>"
    data-selected-label="<?= e($selectedLabelId) ?>"
    data-favorites-label-id="<?= e($favoritesLabelId) ?>"
    data-inbox-labels-json="<?= e(json_encode($inboxLabelsForMenu, JSON_THROW_ON_ERROR)) ?>"
>
    <header class="inbox-header">
        <h1 class="inbox-header-title">Inbox</h1>
        <div class="inbox-header-search-wrap">
            <label class="inbox-header-search" for="inbox-search-input">
                <span class="inbox-header-search-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m20 20-3.5-3.5"/>
                    </svg>
                </span>
                <input
                    id="inbox-search-input"
                    class="inbox-header-search-input"
                    type="search"
                    placeholder="Search through Inbox conversations"
                    autocomplete="off"
                    data-inbox-search
                >
            </label>
        </div>
    </header>

    <div class="inbox-frame">
        <nav class="inbox-nav" aria-label="Inbox folders">
            <a
                href="<?= e(app_route('inbox', $inboxNavParams())) ?>"
                class="inbox-nav-item<?= $allChatsActive ? ' is-active' : '' ?>"
                <?= $allChatsActive ? 'aria-current="page"' : '' ?>
            >
                <span class="inbox-nav-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </span>
                <span class="inbox-nav-label">All chats</span>
                <span class="inbox-nav-count"><?= $totalChats ?></span>
            </a>

            <button type="button" class="inbox-nav-item" disabled aria-disabled="true">
                <span class="inbox-nav-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                </span>
                <span class="inbox-nav-label">Reminders</span>
            </button>

            <div class="inbox-nav-section">
                <div class="inbox-nav-section-head">
                    <button type="button" class="inbox-nav-section-toggle" aria-expanded="true" aria-controls="inbox-labels-panel">
                        <svg class="inbox-nav-section-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                        <span>Labels</span>
                    </button>
                    <button type="button" class="inbox-nav-add" data-inbox-label-add aria-label="Add label">+</button>
                </div>
                <div class="inbox-nav-section-panel" id="inbox-labels-panel">
                    <?php if ($favoritesLabel !== null): ?>
                        <?php $favoritesActive = $selectedLabelId === (string) ($favoritesLabel['id'] ?? ''); ?>
                        <a
                            href="<?= e(app_route('inbox', $inboxNavParams(['label' => (string) $favoritesLabel['id']]))) ?>"
                            class="inbox-nav-item inbox-nav-item--nested<?= $favoritesActive ? ' is-active' : '' ?>"
                            data-inbox-nav-label
                            data-label-id="<?= e((string) $favoritesLabel['id']) ?>"
                            <?= $favoritesActive ? 'aria-current="page"' : '' ?>
                        >
                            <span class="inbox-nav-icon inbox-nav-icon--heart" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                            </span>
                            <span class="inbox-nav-label"><?= e((string) $favoritesLabel['name']) ?></span>
                            <span class="inbox-nav-count" data-inbox-label-count="<?= e((string) $favoritesLabel['id']) ?>"><?= (int) ($favoritesLabel['chat_count'] ?? 0) ?></span>
                        </a>
                    <?php endif; ?>

                    <?php foreach ($customInboxLabels as $label): ?>
                        <?php
                        $labelId = (string) ($label['id'] ?? '');
                        $labelActive = $selectedLabelId === $labelId;
                        ?>
                        <div class="inbox-nav-label-row">
                            <a
                                href="<?= e(app_route('inbox', $inboxNavParams(['label' => $labelId]))) ?>"
                                class="inbox-nav-item inbox-nav-item--nested<?= $labelActive ? ' is-active' : '' ?>"
                                data-inbox-nav-label
                                data-label-id="<?= e($labelId) ?>"
                                <?= $labelActive ? 'aria-current="page"' : '' ?>
                            >
                                <span class="inbox-nav-icon" aria-hidden="true">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                                        <circle cx="7" cy="7" r="1.5" fill="currentColor" stroke="none"/>
                                    </svg>
                                </span>
                                <span class="inbox-nav-label"><?= e((string) ($label['name'] ?? '')) ?></span>
                                <span class="inbox-nav-count" data-inbox-label-count="<?= e($labelId) ?>"><?= (int) ($label['chat_count'] ?? 0) ?></span>
                            </a>
                            <button
                                type="button"
                                class="inbox-nav-label-delete"
                                data-inbox-label-delete
                                data-label-id="<?= e($labelId) ?>"
                                data-label-name="<?= e((string) ($label['name'] ?? '')) ?>"
                                aria-label="Delete label <?= e((string) ($label['name'] ?? '')) ?>"
                            >
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </nav>

        <div class="inbox-main">
            <div class="inbox-toolbar" aria-label="Inbox filters">
                <div class="inbox-toolbar-row inbox-toolbar-row--secondary">
                    <select class="inbox-toolbar-select inbox-toolbar-select--sort" data-inbox-sort aria-label="Sort conversations">
                        <option value="newest" selected>Newest first</option>
                        <option value="oldest">Oldest first</option>
                    </select>

                    <select class="inbox-toolbar-select inbox-toolbar-select--channels" data-inbox-channel-filter aria-label="Filter by channel">
                        <option value="">All channels</option>
                        <?php foreach ($activePlatforms as $platformKey => $_): ?>
                            <option value="<?= e($platformKey) ?>"><?= e(dash_platform_label($platformKey)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="inbox-main-body<?= $hasThreadSelection ? ' has-thread' : '' ?><?= $conversations !== [] ? ' has-list' : '' ?>">
                <?php if ($conversations !== []): ?>
                <aside class="inbox-thread-panel">
                    <div class="inbox-thread-list" role="list" data-inbox-thread-list>
                        <?php foreach ($conversations as $index => $convo): ?>
                            <?php
                            $conversationId = (string) ($convo['id'] ?? '');
                            $customer = !empty($convo['customer_id'])
                                ? RepositoryFactory::customers()->findById((string) $convo['customer_id'])
                                : null;
                            $name = $customer !== null && !empty($customer['customer_name'])
                                ? (string) $customer['customer_name']
                                : 'Customer';
                            $platform = (string) ($convo['platform'] ?? '');
                            $recentMessage = $recentMessageByConversation[$conversationId] ?? null;
                            $isSelected = $conversationId === $selectedConversationId;
                            $lastSender = (string) ($recentMessage['sender_type'] ?? '');
                            $convoLabelIds = $conversationLabelMap[$conversationId] ?? [];
                            $convoIsFavorite = $favoritesLabelId !== '' && in_array($favoritesLabelId, $convoLabelIds, true);
                            ?>
                            <div
                                class="inbox-thread-row<?= $isSelected ? ' is-active' : '' ?><?= $convoIsFavorite ? ' is-favorite' : '' ?>"
                                role="listitem"
                                data-inbox-thread
                                data-conversation-id="<?= e($conversationId) ?>"
                                data-customer-id="<?= e((string) ($convo['customer_id'] ?? '')) ?>"
                                data-customer-name="<?= e($name) ?>"
                                data-search-text="<?= e(strtolower($name . ' ' . dash_platform_label($platform) . ' ' . ($recentMessage['message_text'] ?? ''))) ?>"
                                data-platform="<?= e($platform) ?>"
                                data-timestamp="<?= e((string) ($convo['last_message'] ?? $convo['created_at'] ?? '')) ?>"
                                data-label-ids="<?= e(implode(',', $convoLabelIds)) ?>"
                                <?= $isSelected ? 'data-current="page"' : '' ?>
                            >
                                <a
                                    href="<?= e(app_route('inbox', array_merge($inboxNavParams($selectedLabelId !== '' ? ['label' => $selectedLabelId] : []), ['conversation' => $conversationId])) . '#selected-thread') ?>"
                                    class="inbox-thread-row-link"
                                    <?= $isSelected ? 'aria-current="page"' : '' ?>
                                >
                                    <?= dash_customer_avatar_html($customer, $name, 'inbox-thread-row-avatar') ?>
                                    <span class="inbox-thread-row-body">
                                        <span class="inbox-thread-row-top">
                                            <strong class="inbox-thread-row-name"><?= e($name) ?></strong>
                                            <span class="inbox-thread-row-time"><?= e($formatRelativeTime((string) ($convo['last_message'] ?? ''))) ?></span>
                                        </span>
                                        <span class="inbox-thread-row-preview">
                                            <?php if ($lastSender === 'ai'): ?>
                                                <span class="inbox-thread-row-tag">AI · </span>
                                            <?php elseif ($lastSender === 'owner'): ?>
                                                <span class="inbox-thread-row-tag">You · </span>
                                            <?php endif; ?>
                                            <?= e($messagePreview($recentMessage['message_text'] ?? null, 56)) ?>
                                        </span>
                                    </span>
                                    <span class="inbox-thread-row-channel" aria-hidden="true"><?= dash_platform_icon($platform) ?></span>
                                </a>
                                <button
                                    type="button"
                                    class="inbox-thread-row-menu"
                                    data-inbox-thread-menu-trigger
                                    aria-haspopup="menu"
                                    aria-expanded="false"
                                    aria-label="Actions for chat with <?= e($name) ?>"
                                >
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
                                        <circle cx="12" cy="5" r="1.75"/>
                                        <circle cx="12" cy="12" r="1.75"/>
                                        <circle cx="12" cy="19" r="1.75"/>
                                    </svg>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="inbox-thread-empty-search is-hidden" data-inbox-search-empty hidden>No conversations match your search.</p>
                </aside>
                <?php endif; ?>

                <?php if ($hasThreadSelection): ?>
                <?php
                $selectedCustomerName = $selectedCustomer !== null && !empty($selectedCustomer['customer_name'])
                    ? (string) $selectedCustomer['customer_name']
                    : 'Customer';
                $selectedPlatform = (string) ($selectedConversation['platform'] ?? '');
                $ownerDisplayName = trim((string) ($business['owner_name'] ?? ''));
                if ($ownerDisplayName === '') {
                    $ownerDisplayName = 'You';
                }
                $aiAutoReply = (string) ($selectedConversation['status'] ?? 'open') !== 'human';
                $isWhatsAppThread = $selectedPlatform === 'whatsapp' && $businessId !== null;
                $listPickers = $isWhatsAppThread ? (new InboxReplyService())->listPickers($businessId) : [];
                $listWindowOpen = $isWhatsAppThread && RepositoryFactory::messages()->customerWroteWithin24Hours($selectedConversationId);
                ?>
                <section class="inbox-chat-panel" id="selected-thread">
                    <header class="inbox-chat-topbar">
                        <a href="<?= e(app_route('inbox')) ?>" class="inbox-chat-back" aria-label="Back to conversations">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m15 18-6-6 6-6"></path>
                            </svg>
                        </a>

                        <div class="inbox-chat-contact">
                            <?= dash_customer_avatar_html($selectedCustomer, $selectedCustomerName, 'inbox-chat-avatar') ?>
                            <div class="inbox-chat-contact-body">
                                <h2 class="inbox-chat-name"><?= e($selectedCustomerName) ?></h2>
                                <div class="inbox-chat-meta">
                                    <span class="inbox-platform-pill">
                                        <span class="inbox-platform-icon" aria-hidden="true"><?= dash_platform_icon($selectedPlatform) ?></span>
                                        <span class="inbox-platform-pill-label"><?= e(dash_platform_label($selectedPlatform)) ?></span>
                                    </span>
                                    <span class="inbox-chat-meta-time"><?= e($formatRelativeTime((string) ($selectedLastMessage['created_at'] ?? $selectedConversation['last_message'] ?? ''))) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="inbox-chat-toolbar">
                            <button
                                type="button"
                                class="inbox-chat-details-btn"
                                data-inbox-details-toggle
                                aria-expanded="false"
                                aria-controls="inbox-customer-details"
                            >
                                <span class="sr-only">Customer details</span>
                                <span class="inbox-chat-details-label">Details</span>
                                <svg class="inbox-chat-details-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 16v-4M12 8h.01"></path>
                                </svg>
                            </button>
                        </div>
                    </header>

                    <div class="inbox-message-rail" data-inbox-message-rail>
                        <?php if ($selectedMessages === []): ?>
                            <div class="inbox-thread-empty">
                                <p>When this customer sends a message, the thread will appear here.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($selectedMessages as $message): ?>
                                <?php
                                $senderType = (string) ($message['sender_type'] ?? 'customer');
                                $isAi = $senderType === 'ai';
                                $isOwner = $senderType === 'owner';
                                $isOutbound = $isAi || $isOwner;
                                $senderLabel = match (true) {
                                    $isAi => 'Lead Bird AI',
                                    $isOwner => $ownerDisplayName,
                                    default => $selectedCustomerName,
                                };
                                $messageText = trim((string) ($message['message_text'] ?? ''));
                                ?>
                                <article
                                    class="inbox-message-row<?= $isOutbound ? ' is-outbound' : '' ?><?= $isAi ? ' is-ai' : '' ?><?= $isOwner ? ' is-owner' : '' ?>"
                                    data-message-id="<?= e((string) ($message['id'] ?? '')) ?>"
                                    data-created-at="<?= e((string) ($message['created_at'] ?? '')) ?>"
                                >
                                    <?php if (!$isOutbound): ?>
                                        <?= dash_customer_avatar_html($selectedCustomer, $selectedCustomerName, 'inbox-message-avatar') ?>
                                    <?php endif; ?>
                                    <div class="inbox-message-bubble-wrap">
                                        <div class="inbox-message-meta">
                                            <strong><?= e($senderLabel) ?></strong>
                                            <span><?= e($formatTimestamp((string) ($message['created_at'] ?? ''), false)) ?></span>
                                        </div>
                                        <div class="inbox-message-bubble<?= $isAi ? ' inbox-message-bubble--ai' : '' ?><?= $isOwner ? ' inbox-message-bubble--owner' : '' ?>">
                                            <p><?= $messageText !== '' ? chat_format_message_html($messageText) : 'Message unavailable.' ?></p>
                                        </div>
                                    </div>
                                    <?php if ($isAi): ?>
                                        <span class="inbox-message-avatar inbox-message-avatar--ai" aria-hidden="true">
                                            <?= brand_favicon_icon_html('inbox-ai-avatar-icon', 20, 'Lead Bird AI') ?>
                                        </span>
                                    <?php elseif ($isOwner): ?>
                                        <span class="inbox-message-avatar inbox-message-avatar--owner" aria-hidden="true">
                                            <?= e($initialsFor($ownerDisplayName)) ?>
                                        </span>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <footer
                        class="inbox-compose"
                        data-inbox-compose
                        data-conversation-id="<?= e($selectedConversationId) ?>"
                        data-ai-auto-reply="<?= $aiAutoReply ? '1' : '0' ?>"
                        data-owner-name="<?= e($ownerDisplayName) ?>"
                        data-owner-initials="<?= e($initialsFor($ownerDisplayName)) ?>"
                    >
                        <div class="inbox-compose-toolbar">
                            <button
                                type="button"
                                class="inbox-ai-toggle"
                                data-inbox-ai-toggle
                                aria-pressed="<?= $aiAutoReply ? 'true' : 'false' ?>"
                                aria-label="<?= $aiAutoReply ? 'Turn off AI replies for this chat' : 'Turn on AI replies for this chat' ?>"
                            >
                                <span class="inbox-ai-toggle-track" aria-hidden="true">
                                    <span class="inbox-ai-toggle-thumb"></span>
                                </span>
                                <span class="inbox-ai-toggle-label">AI replies</span>
                            </button>
                            <p class="inbox-compose-status" data-inbox-compose-status>
                                <?php if ($aiAutoReply): ?>
                                    Hannah replies automatically. You can jump in any time.
                                <?php else: ?>
                                    AI is paused for this chat. Your replies go straight to the customer.
                                <?php endif; ?>
                            </p>
                            <?php if ($isWhatsAppThread): ?>
                                <button type="button" class="inbox-list-btn" data-inbox-list-open aria-haspopup="dialog">
                                    <svg viewBox="0 0 16 16" width="15" height="15" aria-hidden="true">
                                        <path d="M6 4.5h7.5M6 8h7.5M6 11.5h7.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                        <circle cx="3" cy="4.5" r="0.9" fill="currentColor"/><circle cx="3" cy="8" r="0.9" fill="currentColor"/><circle cx="3" cy="11.5" r="0.9" fill="currentColor"/>
                                    </svg>
                                    Send a list
                                </button>
                            <?php endif; ?>
                        </div>

                        <form class="inbox-compose-form" data-inbox-compose-form novalidate>
                            <label class="inbox-compose-field">
                                <span class="sr-only">Reply to customer</span>
                                <textarea
                                    class="inbox-compose-input"
                                    data-inbox-compose-input
                                    rows="1"
                                    maxlength="4000"
                                    placeholder="Type your reply..."
                                    required
                                ></textarea>
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

                    <aside
                        class="inbox-details-drawer"
                        id="inbox-customer-details"
                        data-inbox-details-panel
                        hidden
                    >
                        <div class="inbox-details-head">
                            <h3>Customer profile</h3>
                            <button type="button" class="inbox-details-close" data-inbox-details-close aria-label="Close details">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="inbox-details-body">
                            <div class="inbox-profile-avatar-row">
                                <?= dash_customer_avatar_html($selectedCustomer, $selectedCustomerName, 'inbox-profile-avatar') ?>
                                <div>
                                    <p class="inbox-details-name"><?= e($selectedCustomerName) ?></p>
                                    <p class="inbox-details-platform"><?= e(dash_platform_label($selectedPlatform)) ?></p>
                                </div>
                            </div>

                            <div class="inbox-details-labels">
                                <div class="inbox-details-labels-head">
                                    <p class="inbox-details-labels-title">Labels</p>
                                    <button type="button" class="inbox-details-labels-add" data-inbox-label-add>Add label</button>
                                </div>
                                <?php if ($inboxLabels === []): ?>
                                    <p class="inbox-profile-note">No labels yet.</p>
                                <?php else: ?>
                                    <div class="inbox-label-pills" data-inbox-label-pills>
                                        <?php foreach ($inboxLabels as $label): ?>
                                            <?php
                                            $labelId = (string) ($label['id'] ?? '');
                                            $labelAssigned = in_array($labelId, $selectedConversationLabelIds, true);
                                            $isFavoritesLabel = (string) ($label['slug'] ?? '') === InboxLabelRepository::FAVORITES_SLUG;
                                            ?>
                                            <button
                                                type="button"
                                                class="inbox-label-pill<?= $labelAssigned ? ' is-active' : '' ?><?= $isFavoritesLabel ? ' inbox-label-pill--favorite' : '' ?>"
                                                data-inbox-label-toggle
                                                data-label-id="<?= e($labelId) ?>"
                                                data-conversation-id="<?= e($selectedConversationId) ?>"
                                                aria-pressed="<?= $labelAssigned ? 'true' : 'false' ?>"
                                            >
                                                <?php if ($isFavoritesLabel): ?>
                                                    <span class="inbox-label-pill-icon" aria-hidden="true">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                                    </span>
                                                <?php endif; ?>
                                                <?= e((string) ($label['name'] ?? '')) ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <dl class="inbox-profile-list">
                                <div>
                                    <dt>Phone</dt>
                                    <dd><?= e((string) ($selectedCustomer['phone'] ?? 'Not added')) ?></dd>
                                </div>
                                <div>
                                    <dt>Email</dt>
                                    <dd><?= e((string) ($selectedCustomer['email'] ?? 'Not added')) ?></dd>
                                </div>
                                <div>
                                    <dt>First message</dt>
                                    <dd><?= e($formatTimestamp((string) ($selectedConversation['created_at'] ?? ''), true)) ?></dd>
                                </div>
                                <div>
                                    <dt>Last message</dt>
                                    <dd><?= e($formatTimestamp((string) ($selectedLastMessage['created_at'] ?? $selectedConversation['last_message'] ?? ''), true)) ?></dd>
                                </div>
                            </dl>

                            <div class="inbox-profile-actions">
                                <a href="<?= e(app_route('customers')) ?>" class="inbox-btn inbox-btn--primary">All customers</a>
                            </div>
                        </div>
                    </aside>
                </section>
                <?php else: ?>
                <div class="inbox-empty-viewport">
                    <div class="inbox-empty-illustration" aria-hidden="true">
                        <img
                            src="<?= e(brand_inbox_empty_illustration_url()) ?>"
                            class="inbox-empty-art"
                            width="200"
                            height="200"
                            alt=""
                            loading="lazy"
                            decoding="async"
                        >
                    </div>
                    <?php if ($conversations === []): ?>
                        <p class="inbox-empty-message">Send a message to your bot to try Inbox</p>
                    <?php else: ?>
                        <p class="inbox-empty-message">Select a conversation to read messages and reply</p>
                    <?php endif; ?>

                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="inbox-label-modal" data-inbox-label-modal hidden>
    <button type="button" class="inbox-label-modal-backdrop" data-inbox-label-modal-close aria-label="Close"></button>
    <div class="inbox-label-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="inbox-label-modal-title">
        <div class="inbox-label-modal-head">
            <h2 id="inbox-label-modal-title" class="inbox-label-modal-title">New label</h2>
            <button type="button" class="inbox-label-modal-close" data-inbox-label-modal-close aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form class="inbox-label-modal-form" data-inbox-label-form novalidate>
            <label class="inbox-label-modal-field" for="inbox-label-name-input">
                <span class="inbox-label-modal-label">Label name</span>
                <input
                    id="inbox-label-name-input"
                    class="inbox-label-modal-input"
                    type="text"
                    name="name"
                    maxlength="100"
                    placeholder="e.g. VIP, Support, Hot lead"
                    required
                    data-inbox-label-name-input
                >
            </label>
            <p class="inbox-label-modal-error is-hidden" data-inbox-label-form-error hidden></p>
            <div class="inbox-label-modal-actions">
                <button type="button" class="inbox-btn inbox-btn--ghost" data-inbox-label-modal-close>Cancel</button>
                <button type="submit" class="inbox-btn inbox-btn--primary">Create label</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($isWhatsAppThread)): ?>
<div class="inbox-label-modal inbox-list-modal" data-inbox-list-modal hidden>
    <button type="button" class="inbox-label-modal-backdrop" data-inbox-list-close aria-label="Close"></button>
    <div class="inbox-label-modal-dialog inbox-list-dialog" role="dialog" aria-modal="true" aria-labelledby="inbox-list-modal-title">
        <div class="inbox-label-modal-head">
            <h2 id="inbox-list-modal-title" class="inbox-label-modal-title">Send a list</h2>
            <button type="button" class="inbox-label-modal-close" data-inbox-list-close aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <?php if (!$listWindowOpen): ?>
            <p class="inbox-list-note">This customer last wrote more than 24 hours ago. WhatsApp only lets you send a list within 24 hours of their last message.</p>
        <?php endif; ?>
        <?php if ($listPickers === []): ?>
            <div class="inbox-list-empty">
                <p>You have no saved lists yet. Create a template with the List Picker content type, then send it from here.</p>
                <a class="inbox-btn inbox-btn--primary" href="<?= e(app_route('broadcasts/template')) ?>">Create a list</a>
            </div>
        <?php else: ?>
            <form data-inbox-list-form novalidate>
                <fieldset class="inbox-list-options">
                    <legend class="sr-only">Choose a list</legend>
                    <?php foreach ($listPickers as $index => $picker): ?>
                        <label class="inbox-list-option">
                            <input type="radio" name="inbox-list" value="<?= e($picker['id']) ?>"<?= $index === 0 ? ' checked' : '' ?>>
                            <span class="inbox-list-option-body">
                                <strong><?= e($picker['name']) ?></strong>
                                <span class="inbox-list-option-text"><?= e($picker['body']) ?></span>
                                <span class="inbox-list-option-meta">
                                    <svg viewBox="0 0 16 16" width="13" height="13" aria-hidden="true">
                                        <path d="M6 4.5h7.5M6 8h7.5M6 11.5h7.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                        <circle cx="3" cy="4.5" r="0.9" fill="currentColor"/><circle cx="3" cy="8" r="0.9" fill="currentColor"/><circle cx="3" cy="11.5" r="0.9" fill="currentColor"/>
                                    </svg>
                                    <?= e($picker['button']) ?> · <?= count($picker['items']) ?> <?= count($picker['items']) === 1 ? 'option' : 'options' ?>
                                </span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <div class="inbox-label-modal-actions">
                    <button type="button" class="inbox-btn inbox-btn--ghost" data-inbox-list-close>Cancel</button>
                    <button type="submit" class="inbox-btn inbox-btn--primary" data-inbox-list-send<?= $listWindowOpen ? '' : ' disabled' ?>>Send list</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div
    class="inbox-thread-menu"
    data-inbox-thread-menu
    hidden
    role="menu"
    aria-labelledby="inbox-thread-menu-title"
>
    <div class="inbox-thread-menu-head">
        <p class="inbox-thread-menu-title font-heading" id="inbox-thread-menu-title" data-inbox-thread-menu-title>Conversation</p>
    </div>

    <div class="inbox-thread-menu-body">
        <p class="inbox-thread-menu-section-label" id="inbox-thread-menu-labels-title">Add to label</p>
        <div
            class="inbox-thread-menu-labels"
            data-inbox-thread-menu-labels
            role="group"
            aria-labelledby="inbox-thread-menu-labels-title"
        ></div>
        <button type="button" class="inbox-thread-menu-add-label" data-inbox-thread-menu-add-label>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            New label
        </button>
    </div>

    <div class="inbox-thread-menu-divider" role="separator" aria-hidden="true"></div>

    <button
        type="button"
        class="inbox-thread-menu-action inbox-thread-menu-action--danger"
        data-inbox-thread-menu-delete
        role="menuitem"
    >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/>
        </svg>
        Delete chat
    </button>
</div>

<script src="<?= e($appUrl) ?>/assets/js/chat-message-format.js?v=1"></script>
<script src="<?= e($appUrl) ?>/assets/js/dashboard-inbox.js?v=19"></script>
<?php require __DIR__ . '/includes/dashboard/layout-end.php'; ?>
