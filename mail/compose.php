<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$user = Mail::requireUser();

/**
 * Compose lives in a floating panel on every mail page, so this endpoint only
 * handles submissions. A direct visit re-opens the panel over the message list.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $query = ['compose' => 'new'];
    if (!empty($_GET['draft'])) {
        $query = ['folder' => 'drafts', 'compose' => 'draft', 'draft' => (int) $_GET['draft']];
    } elseif (!empty($_GET['reply'])) {
        $query = ['compose' => 'reply', 'source' => (int) $_GET['reply']];
    } elseif (!empty($_GET['forward'])) {
        $query = ['compose' => 'forward', 'source' => (int) $_GET['forward']];
    }
    redirect(Mail::url('', $query));
}

require_csrf();

$action = (string) ($_POST['action'] ?? 'send');
$returnTo = Mail::safeReturnUrl((string) ($_POST['return_to'] ?? '')) ?? Mail::url();

try {
    $payload = [
        'to' => $_POST['to'] ?? '',
        'cc' => $_POST['cc'] ?? '',
        'bcc' => $_POST['bcc'] ?? '',
        'subject' => $_POST['subject'] ?? '',
        'html' => $_POST['html'] ?? '',
        'in_reply_to' => $_POST['in_reply_to'] ?? '',
        'draft_id' => (int) ($_POST['draft_id'] ?? 0),
        'scheduled_at' => $action === 'draft' ? '' : (string) ($_POST['scheduled_at'] ?? ''),
        'attachments' => !empty($_FILES['attachments']) ? Mail::validateUploads($_FILES['attachments']) : [],
    ];

    if ($action === 'draft') {
        $result = Mail::saveDraft($user, $payload);
        if ($result['ok']) {
            mail_flash('success', 'Draft saved.');
            redirect($returnTo);
        }
    } else {
        $result = Mail::sendMessage($user, $payload);
        if ($result['ok']) {
            $scheduled = (string) ($result['scheduled_at'] ?? '') !== '';
            mail_flash('success', $scheduled
                ? 'Send scheduled for ' . Mail::formatScheduledAt((string) $result['scheduled_at']) . '.'
                : 'Message sent.');
            redirect(Mail::url('', ['folder' => $scheduled ? 'scheduled' : 'sent']));
        }
    }
    $error = (string) ($result['error'] ?? 'Could not save this message.');
} catch (Throwable $e) {
    $error = $e->getMessage();
}

// Hand the typed message back to the panel so a failed send is not lost work.
$_SESSION['mail_compose_error'] = $error;
$_SESSION['mail_compose_retry'] = [
    'to' => (string) ($_POST['to'] ?? ''),
    'cc' => (string) ($_POST['cc'] ?? ''),
    'bcc' => (string) ($_POST['bcc'] ?? ''),
    'subject' => (string) ($_POST['subject'] ?? ''),
    'html' => (string) ($_POST['html'] ?? ''),
    'in_reply_to' => (string) ($_POST['in_reply_to'] ?? ''),
    'draft_id' => (int) ($_POST['draft_id'] ?? 0),
];
redirect($returnTo);
