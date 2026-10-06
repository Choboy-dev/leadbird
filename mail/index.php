<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$user = Mail::requireUser();
$folder = in_array($_GET['folder'] ?? 'inbox', Mail::FOLDERS, true) ? (string) ($_GET['folder'] ?? 'inbox') : 'inbox';
$category = in_array($_GET['category'] ?? 'primary', Mail::CATEGORIES, true) ? (string) ($_GET['category'] ?? 'primary') : 'primary';
$searchQuery = trim((string) ($_GET['q'] ?? ''));
$result = Mail::listMessages((int) $user['id'], $folder, $category, $searchQuery, max(1, (int) ($_GET['page'] ?? 1)));
$folderCounts = Mail::folderCounts((int) $user['id']);
$currentFolder = $folder;
$pageTitle = ucfirst($folder);
require __DIR__ . '/partials/layout-start.php';
?>
<form method="post" action="<?= e(Mail::url('actions')) ?>" data-mail-bulk-form>
<?= csrf_field() ?><input type="hidden" name="folder" value="<?= e($folder) ?>">
<div class="mail-toolbar"><div><label class="mail-icon-btn"><input type="checkbox" class="mail-checkbox" data-mail-select-all aria-label="Select all"></label>
<a class="mail-icon-btn" href="<?= e(Mail::url('', ['folder'=>$folder])) ?>" title="Refresh"><?= mail_icon('refresh') ?></a>
<?php if ($folder !== 'trash'): ?><button class="mail-icon-btn" name="action" value="archive" title="Archive"><?= mail_icon('archive') ?></button><button class="mail-icon-btn" name="action" value="trash" title="Move to trash"><?= mail_icon('trash') ?></button>
<?php else: ?><button class="mail-icon-btn" name="action" value="delete" title="Delete forever"><?= mail_icon('trash') ?></button><?php endif; ?></div>
<span class="mail-muted" data-mail-total><?= (int) $result['total'] ?> messages</span></div>
<?php if ($folder === 'inbox' && $searchQuery === ''): ?><nav class="mail-tabs" aria-label="Inbox categories">
<?php foreach (Mail::CATEGORIES as $tab): ?><a class="mail-tabs__item<?= $category === $tab ? ' is-active' : '' ?>" href="<?= e(Mail::url('', ['folder'=>'inbox','category'=>$tab])) ?>"><?= mail_category_icon($tab) ?><span><?= e(ucfirst($tab)) ?></span></a><?php endforeach; ?>
</nav><?php endif; ?>
<div class="mail-list" data-mail-list>
<?php if ($result['messages'] === []): ?><div class="mail-empty"><?= mail_icon('inbox') ?><h2>No messages here</h2><p><?= $searchQuery ? 'Try another search.' : 'New messages will appear here.' ?></p></div>
<?php else: foreach ($result['messages'] as $message): $starred = !empty($message['is_starred']);
    $rowHref = match ($folder) {
        'drafts' => Mail::url('', ['folder'=>'drafts','compose'=>'draft','draft'=>(int)$message['id']]),
        'scheduled' => Mail::url('', ['folder'=>'scheduled']),
        default => Mail::url('message', ['id'=>(int)$message['id']]),
    }; ?>
<article class="mail-row<?= empty($message['is_read']) ? ' is-unread' : '' ?>" data-mail-row data-href="<?= e($rowHref) ?>">
<label><input class="mail-checkbox" type="checkbox" name="message_ids[]" value="<?= (int) $message['id'] ?>" data-mail-checkbox aria-label="Select message"></label>
<?php if ($folder === 'scheduled'): ?>
<button type="button" class="mail-icon-btn" data-mail-cancel-schedule="<?= (int) $message['id'] ?>" data-csrf="<?= e(csrf_token()) ?>" title="Cancel scheduled send" aria-label="Cancel scheduled send"><?= mail_icon('close') ?></button>
<?php else: ?>
<button type="button" class="mail-icon-btn<?= $starred ? ' is-starred' : '' ?>" data-mail-star="<?= (int) $message['id'] ?>" data-csrf="<?= e(csrf_token()) ?>" aria-label="Toggle star"><?= mail_icon('star') ?></button>
<?php endif; ?>
<div class="mail-row__sender"><?= e(Mail::displaySender($message)) ?></div>
<div class="mail-row__subject"><strong><?= e((string) ($message['subject'] ?: '(no subject)')) ?></strong><span>— <?= e((string) ($message['snippet'] ?? '')) ?></span></div>
<?php if ($folder === 'scheduled'): ?><time class="mail-row__scheduled"><?= mail_icon('clock') ?><?= e(Mail::formatScheduledAt((string) ($message['scheduled_at'] ?? ''))) ?></time>
<?php else: ?><time><?= e(Mail::formatListDate($message['received_at'] ?: $message['sent_at'] ?: $message['created_at'])) ?></time><?php endif; ?>
</article>
<?php endforeach; endif; ?>
</div></form>
<?php require __DIR__ . '/partials/layout-end.php'; ?>
