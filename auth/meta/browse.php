<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/dashboard/helpers.php';

$userId = (new SessionAuth())->userId();
if ($userId === null) {
    redirect(app_route('login'));
}

if (!(new SubscriptionService())->hasActiveSubscription((string) $userId)) {
    redirect(app_route('pricing'));
}

$metaConnect = new MetaConnectService();
$userId = (string) $userId;

$platform = strtolower(trim((string) ($_GET['platform'] ?? '')));
$businessId = trim((string) ($_GET['business_id'] ?? ''));
$returnTo = safe_return_url(
    trim((string) ($_GET['return_to'] ?? '')),
    $metaConnect->defaultReturnTo($userId)
);

if ($businessId === '' || !(new BusinessContext())->userOwnsBusiness($userId, $businessId)) {
    flash_set('error', 'Business not found.');
    redirect($metaConnect->defaultReturnTo($userId));
}

if (!in_array($platform, ['whatsapp', 'instagram', 'facebook'], true)) {
    flash_set('error', 'Unsupported channel.');
    redirect($returnTo);
}

$appUrl = app_config()['app_url'];
$brandName = (string) app_config()['brand_name'];

$browse = $metaConnect->getBrowseAccounts($userId, $businessId, $platform);
$accounts = $browse['accounts'];
$canAddMore = (bool) ($browse['can_add_more'] ?? false);
$hasMetaToken = (bool) ($browse['has_meta_token'] ?? false);

if (!$hasMetaToken) {
    redirect($metaConnect->buildSetupUrl($platform, $businessId, $returnTo));
}

$alreadyConnectedCount = 0;
foreach ($accounts as $account) {
    if (!empty($account['is_connected'])) {
        $alreadyConnectedCount++;
    }
}

$platformMeta = match ($platform) {
    'facebook' => [
        'title' => 'Which Facebook Pages do you want to add?',
        'lead' => 'Choose the Pages you want Lead Bird to reply on.',
        'empty' => 'No Facebook Pages were found on your Meta account.',
        'search' => 'Search by name or keyword',
        'type_label' => 'Facebook Page',
    ],
    'instagram' => [
        'title' => 'Which Instagram profiles do you want to add?',
        'lead' => 'Choose the Instagram Business profiles you want Lead Bird to manage.',
        'empty' => 'No Instagram Business profiles were found on your Meta account.',
        'search' => 'Search by name or keyword',
        'type_label' => 'Instagram business',
    ],
    'whatsapp' => [
        'title' => 'Which WhatsApp numbers do you want to add?',
        'lead' => 'Choose the WhatsApp numbers you want Lead Bird to use.',
        'empty' => 'No WhatsApp numbers were found on your Meta account.',
        'search' => 'Search by name or keyword',
        'type_label' => 'WhatsApp Business',
    ],
    default => [
        'title' => 'Which accounts do you want to add?',
        'lead' => 'Choose the accounts you want Lead Bird to manage.',
        'empty' => 'No accounts were found on your Meta account.',
        'search' => 'Search accounts',
        'type_label' => 'Account',
    ],
};

$refreshUrl = app_route('auth/meta/connect', [
    'platform' => $platform,
    'business_id' => $businessId,
    'return_to' => $returnTo,
    'refresh' => '1',
]);
$addUrl = app_route('auth/meta/add');
$csrfToken = csrf_token();
$isInstagramLayout = $platform === 'instagram';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($platformMeta['title']) ?> · <?= e($brandName) ?></title>
    <?php
    $metaRobots = 'noindex, nofollow';
    require dirname(__DIR__, 2) . '/includes/brand-meta.php';
    ?>
    <?php require dirname(__DIR__, 2) . '/includes/brand-fonts.php'; ?>
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/brand.css">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/onboarding.css?v=12">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/modal.css?v=2">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/meta-browse.css?v=3">
</head>
<body class="meta-browse-page font-sans antialiased<?= $isInstagramLayout ? ' meta-browse-page--instagram' : '' ?>">
<div class="meta-browse-shell">
    <div class="meta-browse-grid" aria-hidden="true"></div>

    <main class="meta-browse-main">
        <div class="meta-browse-logo">
            <?php $logoSize = 'sm'; $logoHref = e($appUrl) . '/'; require dirname(__DIR__, 2) . '/includes/brand-logo.php'; ?>
        </div>

        <article
            class="meta-browse-card<?= $isInstagramLayout ? ' meta-browse-card--instagram' : '' ?>"
            data-meta-browse
            data-add-url="<?= e($addUrl) ?>"
            data-csrf="<?= e($csrfToken) ?>"
            data-business-id="<?= e($businessId) ?>"
            data-platform="<?= e($platform) ?>"
            data-can-add-more="<?= $canAddMore ? '1' : '0' ?>"
            data-initial-added="<?= (int) $alreadyConnectedCount ?>"
        >
            <header class="meta-browse-head">
                <h1 class="meta-browse-title font-heading"><?= e($platformMeta['title']) ?></h1>
                <p class="meta-browse-lead"><?= e($platformMeta['lead']) ?></p>
            </header>

            <div class="meta-browse-toolbar">
                <label class="meta-browse-search-wrap">
                    <span class="sr-only">Search accounts</span>
                    <input
                        type="search"
                        class="meta-browse-search"
                        placeholder="<?= e($platformMeta['search']) ?>"
                        autocomplete="off"
                        data-meta-browse-search
                    >
                </label>
            </div>

            <?php if ($accounts === []): ?>
                <div class="meta-browse-empty" role="status">
                    <p><?= e($platformMeta['empty']) ?></p>
                    <p class="meta-browse-empty-hint">
                        <a href="<?= e($refreshUrl) ?>">Try again</a> to grant access to more accounts on Meta.
                    </p>
                </div>
            <?php else: ?>
                <ul class="meta-browse-list" data-meta-browse-list>
                    <?php foreach ($accounts as $account): ?>
                        <?php
                        $isConnected = (bool) ($account['is_connected'] ?? false);
                        $label = (string) ($account['label'] ?? '');
                        $subtitle = (string) ($account['subtitle'] ?? '');
                        $optionKey = (string) ($account['key'] ?? '');
                        $pageName = (string) ($account['page_name'] ?? '');
                        $username = (string) ($account['username'] ?? '');
                        $profilePicture = (string) ($account['profile_picture_url'] ?? '');
                        $searchText = (string) ($account['search_text'] ?? strtolower($label . ' ' . $subtitle));
                        $handle = $username !== '' ? '@' . ltrim($username, '@') : $label;
                        ?>
                        <li
                            class="meta-browse-item<?= $isConnected ? ' is-added' : '' ?><?= $isInstagramLayout ? ' meta-browse-item--instagram' : '' ?>"
                            data-meta-browse-item
                            data-search="<?= e($searchText) ?>"
                        >
                            <?php if ($isInstagramLayout): ?>
                                <div class="meta-browse-ig-card">
                                    <div class="meta-browse-ig-head">
                                        <span class="meta-browse-ig-page font-heading"><?= e($pageName !== '' ? $pageName : 'Instagram') ?></span>
                                        <?php if ($isConnected): ?>
                                            <button type="button" class="meta-browse-btn meta-browse-btn--added" disabled aria-disabled="true">Added</button>
                                        <?php elseif ($canAddMore): ?>
                                            <button
                                                type="button"
                                                class="meta-browse-btn meta-browse-btn--add"
                                                data-meta-browse-add
                                                data-option-key="<?= e($optionKey) ?>"
                                            >Add</button>
                                        <?php else: ?>
                                            <button type="button" class="meta-browse-btn meta-browse-btn--disabled" disabled aria-disabled="true" title="Plan limit reached">Limit reached</button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="meta-browse-ig-profile">
                                        <?php if ($profilePicture !== ''): ?>
                                            <img src="<?= e($profilePicture) ?>" alt="" class="meta-browse-ig-avatar" width="36" height="36" loading="lazy">
                                        <?php else: ?>
                                            <span class="meta-browse-ig-avatar meta-browse-ig-avatar--placeholder" aria-hidden="true"><?= dash_platform_icon('instagram') ?></span>
                                        <?php endif; ?>
                                        <div class="meta-browse-ig-copy">
                                            <span class="meta-browse-ig-handle"><?= e($handle) ?></span>
                                            <span class="meta-browse-ig-type"><?= e($platformMeta['type_label']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="meta-browse-item-copy">
                                    <span class="meta-browse-item-name"><?= e($label) ?></span>
                                    <?php if ($subtitle !== '' && $subtitle !== $label): ?>
                                        <span class="meta-browse-item-sub"><?= e($subtitle) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($isConnected): ?>
                                    <button type="button" class="meta-browse-btn meta-browse-btn--added" disabled aria-disabled="true">Added</button>
                                <?php elseif ($canAddMore): ?>
                                    <button
                                        type="button"
                                        class="meta-browse-btn meta-browse-btn--add"
                                        data-meta-browse-add
                                        data-option-key="<?= e($optionKey) ?>"
                                    >Add</button>
                                <?php else: ?>
                                    <button type="button" class="meta-browse-btn meta-browse-btn--disabled" disabled aria-disabled="true" title="Plan limit reached">Limit reached</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="meta-browse-footer">
                <p class="meta-browse-counter" data-meta-browse-counter>
                    Accounts added: <span data-meta-browse-counter-value><?= (int) $alreadyConnectedCount ?></span>
                </p>
                <div class="meta-browse-actions">
                    <a href="<?= e($returnTo) ?>" class="meta-browse-link meta-browse-link--cancel">Cancel</a>
                    <div class="meta-browse-actions-right">
                        <a href="<?= e($refreshUrl) ?>" class="onboarding-btn onboarding-btn--ghost meta-browse-retry">Try again</a>
                        <a href="<?= e($returnTo) ?>" class="onboarding-btn onboarding-btn--primary meta-browse-done">Done</a>
                    </div>
                </div>
            </div>
        </article>
    </main>
</div>
<script src="<?= e($appUrl) ?>/assets/js/modal.js?v=2"></script>
<script src="<?= e($appUrl) ?>/assets/js/meta-browse.js?v=3"></script>
<?php require dirname(__DIR__, 2) . '/includes/site-assistant/embed.php'; ?>
</body>
</html>
