<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$user = Mail::requireUser(true);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $password = (string) ($_POST['password'] ?? '');
    if (!hash_equals($password, (string) ($_POST['password_confirmation'] ?? ''))) {
        $error = 'Passwords do not match.';
    } else {
        $result = Mail::changePassword((int) $user['id'], $password);
        if ($result['ok']) redirect(Mail::url());
        $error = (string) $result['error'];
    }
}
$appUrl = rtrim((string) app_config()['app_url'], '/');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(mail_page_title('Change password')) ?></title>
<?php require __DIR__ . '/partials/head-meta.php'; ?>
<?php require dirname(__DIR__) . '/includes/brand-fonts.php'; ?>
<link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/mail.css?v=12"></head>
<body class="mail-app"><main class="mail-login"><section class="mail-login__card">
<?php $logoHref = Mail::url(); require dirname(__DIR__) . '/includes/brand-logo.php'; ?>
<h1>Choose a new password</h1><p class="mail-muted">You must replace your temporary password before using mail.</p>
<?php if ($error): ?><div class="mail-alert mail-alert--error"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<div class="mail-form-group"><label for="password">New password</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required></div>
<div class="mail-form-group"><label for="confirmation">Confirm password</label><input id="confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required></div>
<button class="mail-btn mail-btn--wide">Save password</button></form>
</section></main></body></html>
