<?php
declare(strict_types=1);
$appUrl = rtrim((string) app_config()['app_url'], '/');
$user = $user ?? Mail::currentUser() ?? [];
$folderCounts = $folderCounts ?? Mail::folderCounts((int) ($user['id'] ?? 0));
$currentFolder = $currentFolder ?? 'inbox';
$searchQuery = $searchQuery ?? '';
$inboxState = Mail::inboxState((int) ($user['id'] ?? 0));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e(mail_page_title($pageTitle ?? 'Mail')) ?></title>
    <?php require __DIR__ . '/head-meta.php'; ?>
    <?php require dirname(__DIR__, 2) . '/includes/brand-fonts.php'; ?>
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/dropdown.css?v=3">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/modal.css?v=3">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/mail.css?v=12">
</head>
<body class="mail-app" data-mail-latest-id="<?= (int) $inboxState['latest_id'] ?>" data-mail-unread="<?= (int) $inboxState['unread'] ?>">
<div class="mail-shell">
<aside class="mail-sidebar" data-mail-sidebar>
    <div class="mail-sidebar__brand">
        <?php $logoHref = Mail::url(); $logoSize = 'sm'; require dirname(__DIR__, 2) . '/includes/brand-logo.php'; ?>
        <strong>Mail</strong>
    </div>
    <a class="mail-compose-btn" href="<?= e(Mail::url('', ['compose' => 'new'])) ?>" data-mail-compose-open><?= mail_icon('compose') ?><span>Compose</span></a>
    <nav class="mail-nav" aria-label="Mail folders" data-mail-nav>
    <?php foreach ([
        'inbox'=>['inbox','Inbox','unread'], 'starred'=>['star','Starred','total'], 'sent'=>['send','Sent','total'],
        'drafts'=>['drafts','Drafts','total'], 'scheduled'=>['clock','Scheduled','total'],
        'archive'=>['archive','Archive','total'], 'spam'=>['spam','Spam','total'], 'trash'=>['trash','Trash','total']
    ] as $key => $item): ?>
        <a class="mail-nav__item<?= $currentFolder === $key ? ' is-active' : '' ?>" href="<?= e(Mail::url('', ['folder' => $key])) ?>">
            <?= mail_icon($item[0]) ?><span><?= e($item[1]) ?></span>
            <?php $count = (int) ($folderCounts[$key][$item[2]] ?? 0); if ($count): ?><span class="mail-nav__count"><?= $count ?></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
    <a class="mail-nav__item<?= ($currentFolder === 'settings') ? ' is-active' : '' ?>" href="<?= e(Mail::url('settings')) ?>"><?= mail_icon('settings') ?><span>Settings</span></a>
    <?php if (($user['role'] ?? '') === 'admin'): ?><a class="mail-nav__item" href="<?= e(Mail::url('admin/users')) ?>"><?= mail_icon('users') ?><span>Users</span></a><?php endif; ?>
    </nav>
</aside>
<main class="mail-main">
<header class="mail-topbar">
    <button class="mail-icon-btn" type="button" data-mail-menu aria-label="Open menu"><?= mail_icon('menu') ?></button>
    <form class="mail-search" method="get" action="<?= e(Mail::url()) ?>"><?= mail_icon('search') ?><input type="search" name="q" value="<?= e($searchQuery) ?>" placeholder="Search mail" aria-label="Search mail"><?php if ($currentFolder !== 'inbox'): ?><input type="hidden" name="folder" value="<?= e($currentFolder) ?>"><?php endif; ?></form>
    <div class="mail-topbar__actions">
        <button class="mail-icon-btn" type="button" data-mail-sound-toggle aria-pressed="true" title="New mail sound" aria-label="New mail sound">
            <span data-mail-sound-on><?= mail_icon('bell') ?></span>
            <span data-mail-sound-off hidden><?= mail_icon('bell-off') ?></span>
        </button>
        <form method="post" action="<?= e(Mail::url('logout')) ?>"><?= csrf_field() ?><?= mail_avatar(Mail::avatarUrl($user), Mail::userInitials($user), 'button', 'type="submit" title="Sign out" aria-label="Sign out"') ?></form>
    </div>
</header>
<p class="mail-live-toast" data-mail-toast role="status" aria-live="polite" hidden></p>
<?php $mailFlash = mail_consume_flash(); if ($mailFlash !== null): ?>
<div class="mail-flash mail-alert mail-alert--<?= e($mailFlash['type'] === 'error' ? 'error' : 'success') ?>" role="status"><?= e((string) $mailFlash['message']) ?></div>
<?php endif; ?>
