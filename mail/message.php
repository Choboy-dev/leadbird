<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$user = Mail::requireUser();
$message = Mail::getMessage((int) $user['id'], (int) ($_GET['id'] ?? 0));
if ($message === null) redirect(Mail::url());
Mail::markRead((int) $user['id'], (int) $message['id'], true);
$attachments = Mail::getAttachments((int) $message['id']);
$folderCounts = Mail::folderCounts((int) $user['id']);
$currentFolder = (string) $message['folder'];
$searchQuery = '';
$pageTitle = (string) ($message['subject'] ?: '(no subject)');
require __DIR__ . '/partials/layout-start.php';
?>
<div class="mail-toolbar"><a class="mail-icon-btn" href="<?= e(Mail::url('', ['folder'=>$currentFolder])) ?>" title="Back"><?= mail_icon('back') ?></a>
<div><a class="mail-btn mail-btn--ghost" href="<?= e(Mail::url('message', ['id'=>(int)$message['id'],'compose'=>'reply','source'=>(int)$message['id']])) ?>">Reply</a>
<a class="mail-btn mail-btn--ghost" href="<?= e(Mail::url('message', ['id'=>(int)$message['id'],'compose'=>'reply-all','source'=>(int)$message['id']])) ?>">Reply all</a>
<a class="mail-btn mail-btn--ghost" href="<?= e(Mail::url('message', ['id'=>(int)$message['id'],'compose'=>'forward','source'=>(int)$message['id']])) ?>">Forward</a></div></div>
<article class="mail-message-view">
<h1><?= e((string) ($message['subject'] ?: '(no subject)')) ?></h1>
<?php
$senderName = trim((string) ($message['from_name'] ?? ''));
$senderEmail = (string) ($message['from_email'] ?? '');
$senderLabel = $senderName !== '' ? $senderName : $senderEmail;
$recipients = array_values(array_filter(array_map('trim', array_merge(
    Mail::decodeJsonList($message['to_emails'] ?? ''),
    Mail::decodeJsonList($message['cc_emails'] ?? ''),
    Mail::decodeJsonList($message['bcc_emails'] ?? '')
))));
$shownRecipients = array_slice($recipients, 0, 2);
$restRecipients = array_slice($recipients, 2);
$senderAvatar = Mail::avatarUrlForEmail($senderEmail);
?>
<header class="mail-message-meta"><?= mail_avatar($senderAvatar, strtoupper(substr($senderLabel, 0, 1))) ?>
<div><strong><?= e($senderLabel) ?></strong><?php if ($senderName !== '' && $senderEmail !== ''): ?><span class="mail-message-meta__email">&lt;<?= e($senderEmail) ?>&gt;</span><?php endif; ?>
<div class="mail-muted" data-mail-recipient-list>
<?php if ($recipients === []): ?>no recipients
<?php else: ?>to <?= e(implode(', ', $shownRecipients)) ?><?php if ($restRecipients !== []): ?><span data-mail-recipient-rest hidden>, <?= e(implode(', ', $restRecipients)) ?></span>
<button class="mail-inline-link" type="button" data-mail-recipient-toggle data-more-label="(<?= count($restRecipients) ?> more)" aria-expanded="false">(<?= count($restRecipients) ?> more)</button>
<?php endif; endif; ?>
</div></div>
<time><?= e(Mail::formatListDate($message['received_at'] ?: $message['sent_at'] ?: $message['created_at'])) ?></time></header>
<?php if (!empty($message['body_html'])): ?>
<iframe class="mail-body-frame" sandbox="allow-same-origin allow-popups" referrerpolicy="no-referrer" title="Email body" srcdoc="<?= e(Mail::htmlSandboxSrcdoc((string) $message['body_html'])) ?>"></iframe>
<?php else: ?><div class="mail-message-body"><?= nl2br(e((string) ($message['body_text'] ?? ''))) ?></div><?php endif; ?>
<?php if ($attachments): ?><div class="mail-attachments"><?php foreach ($attachments as $attachment): ?><a class="mail-attachment" href="<?= e(Mail::url('download', ['id'=>(int)$attachment['id']])) ?>"><?= mail_icon('attach') ?><?= e((string) $attachment['filename']) ?></a><?php endforeach; ?></div><?php endif; ?>
</article>
<?php require __DIR__ . '/partials/layout-end.php'; ?>
