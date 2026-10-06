<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$user = Mail::requireUser();
require_csrf();
$action = (string) ($_POST['action'] ?? '');
if ($action === 'star') {
    Mail::toggleStar((int) $user['id'], (int) ($_POST['message_id'] ?? 0));
    json_response(['ok'=>true]);
}
if ($action === 'discard_draft') {
    Mail::deleteMessages((int) $user['id'], [(int) ($_POST['message_id'] ?? 0)], true);
    json_response(['ok'=>true]);
}
if ($action === 'cancel_schedule') {
    $result = Mail::cancelScheduled((int) $user['id'], (int) ($_POST['message_id'] ?? 0));
    json_response($result, $result['ok'] ? 200 : 422);
}
$ids = array_map('intval', (array) ($_POST['message_ids'] ?? []));
match ($action) {
    'archive' => Mail::moveMessages((int)$user['id'], $ids, 'archive'),
    'trash' => Mail::deleteMessages((int)$user['id'], $ids, false),
    'delete' => Mail::deleteMessages((int)$user['id'], $ids, true),
    'spam' => Mail::moveMessages((int)$user['id'], $ids, 'spam'),
    'mark_read' => array_map(fn(int $id) => Mail::markRead((int)$user['id'], $id, true), $ids),
    'mark_unread' => array_map(fn(int $id) => Mail::markRead((int)$user['id'], $id, false), $ids),
    default => null,
};
redirect(Mail::url('', ['folder'=>(string)($_POST['folder'] ?? 'inbox')]));
