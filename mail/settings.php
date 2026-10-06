<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$user = Mail::requireUser();
$userId = (int) $user['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'save_signature') {
        $result = Mail::saveSignature($userId, [
            'id' => (int) ($_POST['signature_id'] ?? 0),
            'name' => (string) ($_POST['name'] ?? ''),
            'body_html' => (string) ($_POST['body_html'] ?? ''),
            'is_default' => !empty($_POST['is_default']),
        ]);
        if ($result['ok']) {
            mail_flash('success', 'Signature saved.');
            redirect(Mail::url('settings'));
        }
        $error = (string) $result['error'];
    } elseif ($action === 'delete_signature') {
        Mail::deleteSignature($userId, (int) ($_POST['signature_id'] ?? 0));
        mail_flash('success', 'Signature deleted.');
        redirect(Mail::url('settings'));
    } elseif ($action === 'set_default') {
        Mail::setDefaultSignature($userId, (int) ($_POST['signature_id'] ?? 0));
        mail_flash('success', 'Default signature updated.');
        redirect(Mail::url('settings'));
    } elseif ($action === 'save_preferences') {
        Mail::updateSignaturePreferences($userId, !empty($_POST['signature_on_new']), !empty($_POST['signature_on_reply']));
        mail_flash('success', 'Signature preferences saved.');
        redirect(Mail::url('settings'));
    } elseif ($action === 'save_avatar') {
        $file = $_FILES['avatar'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            $error = 'Choose an image to upload.';
        } else {
            try {
                Mail::storeAvatar($userId, (string) $file['tmp_name'], (string) ($file['type'] ?? ''));
                mail_flash('success', 'Profile picture updated.');
                redirect(Mail::url('settings'));
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }
    } elseif ($action === 'remove_avatar') {
        Mail::removeAvatar($userId);
        mail_flash('success', 'Profile picture removed.');
        redirect(Mail::url('settings'));
    }
}

$user = Mail::findUserById($userId) ?? $user;
$avatarUrl = Mail::avatarUrl($user);
$signatures = Mail::listSignatures($userId);
$editing = null;
$editingId = (int) ($_GET['signature'] ?? 0);
if ($editingId > 0) {
    $editing = Mail::getSignature($userId, $editingId);
}
$folderCounts = Mail::folderCounts($userId);
$currentFolder = 'settings';
$searchQuery = '';
$pageTitle = 'Settings';
$editorId = 'mail-signature-editor';
require __DIR__ . '/partials/layout-start.php';
?>
<div class="mail-settings">
    <header class="mail-settings__head">
        <h1>Settings</h1>
        <p class="mail-muted">Your profile picture and sign-offs. Create a signature once, then add it to any message — or let Lead Bird Mail append it for you.</p>
    </header>

    <?php if ($error !== ''): ?><div class="mail-alert mail-alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>

    <div class="mail-settings__grid">
        <section class="mail-card">
            <h2><?= $editing ? 'Edit signature' : 'New signature' ?></h2>
            <form method="post" data-mail-signature-form>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_signature">
                <input type="hidden" name="signature_id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                <input type="hidden" name="body_html" value="" data-mail-signature-field>

                <div class="mail-form-group">
                    <label for="signature-name">Signature name</label>
                    <input id="signature-name" name="name" maxlength="120" required value="<?= e((string) ($editing['name'] ?? '')) ?>" placeholder="Work sign-off">
                </div>

                <div class="mail-form-group">
                    <span class="mail-form-label">Signature content</span>
                    <div class="mail-signature-editor">
                        <div id="<?= e($editorId) ?>" class="mail-editor mail-editor--signature" contenteditable="true" role="textbox" aria-multiline="true"
                             aria-label="Signature content" data-mail-editor data-placeholder="Your name, role and contact details"><?= (string) ($editing['body_html'] ?? '') ?></div>
                        <?php require __DIR__ . '/partials/editor-toolbar.php'; ?>
                    </div>
                </div>

                <label class="mail-check-label">
                    <input type="checkbox" name="is_default" value="1" <?= !empty($editing['is_default']) || $signatures === [] ? 'checked' : '' ?>>
                    <span>Use as my default signature</span>
                </label>

                <div class="mail-settings__actions">
                    <button class="mail-btn" type="submit"><?= $editing ? 'Save changes' : 'Create signature' ?></button>
                    <?php if ($editing): ?><a class="mail-btn mail-btn--ghost" href="<?= e(Mail::url('settings')) ?>">Cancel</a><?php endif; ?>
                </div>
            </form>
        </section>

        <div class="mail-settings__side">
            <section class="mail-card">
                <h2>Profile picture</h2>
                <p class="mail-muted mail-card__hint">Shown beside your name inside Lead Bird Mail. Gmail and Apple Mail only show a sender logo through BIMI, which needs a certificate on the domain.</p>
                <form class="mail-avatar-form" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_avatar">
                    <span class="mail-avatar-preview" data-mail-avatar-preview>
                        <?= mail_avatar($avatarUrl, Mail::userInitials($user)) ?>
                    </span>
                    <div class="mail-avatar-form__controls">
                        <label class="mail-btn mail-btn--ghost mail-btn--sm" for="mail-avatar-input">Choose image</label>
                        <input id="mail-avatar-input" class="mail-visually-hidden" type="file" name="avatar"
                               accept="image/png,image/jpeg,image/gif,image/webp" data-mail-avatar-input>
                        <p class="mail-muted mail-avatar-form__note" data-mail-avatar-note>PNG, JPEG, GIF or WebP, up to 3 MB. Square images look best.</p>
                        <div class="mail-settings__actions">
                            <button class="mail-btn mail-btn--sm" type="submit">Save picture</button>
                            <?php if ($avatarUrl !== ''): ?>
                                <button class="mail-btn mail-btn--ghost mail-btn--sm mail-btn--danger" type="button" data-mail-remove-avatar>Remove</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                <?php if ($avatarUrl !== ''): ?>
                    <form method="post" data-mail-remove-avatar-form hidden>
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove_avatar">
                    </form>
                <?php endif; ?>
            </section>

            <section class="mail-card">
                <h2>When to add signatures</h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_preferences">
                    <label class="mail-check-label">
                        <input type="checkbox" name="signature_on_new" value="1" <?= !empty($user['signature_on_new']) ? 'checked' : '' ?>>
                        <span>Add my default signature to new messages</span>
                    </label>
                    <label class="mail-check-label">
                        <input type="checkbox" name="signature_on_reply" value="1" <?= !empty($user['signature_on_reply']) ? 'checked' : '' ?>>
                        <span>Add it to replies and forwards</span>
                    </label>
                    <button class="mail-btn mail-btn--sm" type="submit">Save preferences</button>
                </form>
            </section>

            <section class="mail-card">
                <h2>Your signatures</h2>
                <?php if ($signatures === []): ?>
                    <p class="mail-muted">No signatures yet. Create one on the left.</p>
                <?php else: ?>
                    <ul class="mail-signature-list">
                        <?php foreach ($signatures as $signature): ?>
                            <li class="mail-signature-item">
                                <div class="mail-signature-item__head">
                                    <strong><?= e((string) $signature['name']) ?></strong>
                                    <?php if (!empty($signature['is_default'])): ?><span class="mail-badge">Default</span><?php endif; ?>
                                </div>
                                <div class="mail-signature-item__preview"><?= (string) $signature['body_html'] ?></div>
                                <div class="mail-signature-item__actions">
                                    <a class="mail-btn mail-btn--ghost mail-btn--sm" href="<?= e(Mail::url('settings', ['signature' => (int) $signature['id']])) ?>">Edit</a>
                                    <?php if (empty($signature['is_default'])): ?>
                                        <form method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="set_default">
                                            <input type="hidden" name="signature_id" value="<?= (int) $signature['id'] ?>">
                                            <button class="mail-btn mail-btn--ghost mail-btn--sm" type="submit">Make default</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_signature">
                                        <input type="hidden" name="signature_id" value="<?= (int) $signature['id'] ?>">
                                        <button class="mail-btn mail-btn--ghost mail-btn--sm mail-btn--danger" type="button" data-mail-delete-signature>Delete</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/layout-end.php'; ?>
