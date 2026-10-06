<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
$user = Mail::requireAdmin();
$error = ''; $message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $result = ($_POST['action'] ?? '') === 'create'
        ? Mail::createUser((string)($_POST['email']??''), (string)($_POST['display_name']??''), (string)($_POST['password']??''), (string)($_POST['role']??'user'))
        : Mail::updateUser((int)($_POST['user_id']??0), ['display_name'=>$_POST['display_name']??'','role'=>$_POST['role']??'user','is_active'=>!empty($_POST['is_active']),'password'=>$_POST['password']??'']);
    if ($result['ok']) $message = 'Mailbox saved.'; else $error = (string) $result['error'];
}
$users = Mail::listUsers(); $folderCounts = Mail::folderCounts((int)$user['id']); $currentFolder = ''; $searchQuery = ''; $pageTitle = 'Mailbox users';
require dirname(__DIR__) . '/partials/layout-start.php';
?>
<section class="mail-admin-panel"><h1>Mailbox users</h1><p class="mail-muted">Manage private @<?= e(Mail::domain()) ?> accounts.</p>
<?php if ($error): ?><div class="mail-alert mail-alert--error"><?= e($error) ?></div><?php endif; ?><?php if ($message): ?><div class="mail-alert mail-alert--success"><?= e($message) ?></div><?php endif; ?>
<div class="mail-admin-grid"><div class="mail-card"><h2>Existing mailboxes</h2><div class="mail-table-wrap"><table class="mail-admin-table"><thead><tr><th>User</th><th>Role</th><th>Status</th></tr></thead><tbody>
<?php foreach ($users as $mailbox): ?><tr><td><strong><?= e((string)$mailbox['display_name']) ?></strong><br><span class="mail-muted"><?= e((string)$mailbox['email']) ?></span></td><td><?= e((string)$mailbox['role']) ?></td><td><?= $mailbox['is_active'] ? 'Active' : 'Disabled' ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<div class="mail-card"><h2>Create mailbox</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create">
<div class="mail-form-group"><label for="new-name">Display name</label><input id="new-name" name="display_name" required></div>
<div class="mail-form-group"><label for="new-email">Email</label><input id="new-email" name="email" type="email" required></div>
<div class="mail-form-group"><label for="new-password">Temporary password</label><input id="new-password" name="password" type="password" minlength="12" required></div>
<div class="mail-form-group"><label for="new-role">Role</label><select id="new-role" name="role"><option value="user">User</option><option value="admin">Admin</option></select></div>
<button class="mail-btn">Create mailbox</button></form></div></div>
<div class="mail-card mail-update-card"><h2>Update mailbox</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="update">
<div class="mail-form-group"><label for="user-id">Mailbox</label><select id="user-id" name="user_id"><?php foreach($users as $mailbox): ?><option value="<?= (int)$mailbox['id'] ?>"><?= e((string)$mailbox['email']) ?></option><?php endforeach; ?></select></div>
<div class="mail-form-group"><label for="edit-name">Display name</label><input id="edit-name" name="display_name" required></div>
<div class="mail-form-group"><label for="edit-role">Role</label><select id="edit-role" name="role"><option value="user">User</option><option value="admin">Admin</option></select></div>
<label class="mail-check-label"><input type="checkbox" name="is_active" value="1" checked> Active</label>
<div class="mail-form-group"><label for="edit-password">New temporary password (optional)</label><input id="edit-password" name="password" type="password" minlength="12"></div>
<button class="mail-btn">Save changes</button></form></div>
</section>
<?php require dirname(__DIR__) . '/partials/layout-end.php'; ?>
