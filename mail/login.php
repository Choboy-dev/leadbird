<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (Mail::currentUser() !== null) redirect(Mail::url());
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $ip = client_ip();
    if (!rate_limit_allow('mail_login:' . $ip, 8, 900)) {
        $error = 'Too many sign-in attempts. Try again in 15 minutes.';
    } else {
        $email = normalize_email((string) ($_POST['email'] ?? ''));
        $user = Mail::authenticate($email, (string) ($_POST['password'] ?? ''));
        if ($user === null) {
            usleep(random_int(150000, 350000));
            $error = 'Invalid email or password.';
        } else {
            Mail::login($user);
            redirect(!empty($user['must_change_password']) ? Mail::url('change-password') : Mail::url());
        }
    }
}
$appUrl = rtrim((string) app_config()['app_url'], '/');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e(mail_page_title('Sign in')) ?></title>
    <?php require __DIR__ . '/partials/head-meta.php'; ?>
    <?php require dirname(__DIR__) . '/includes/brand-fonts.php'; ?>
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/mail.css?v=12">
</head>
<body class="mail-app">
<main class="mail-login">
    <section class="mail-login__card">
        <?php $logoHref = Mail::url('login'); $logoSize = 'md'; require dirname(__DIR__) . '/includes/brand-logo.php'; ?>
        <h1>Sign in to Lead Bird Mail</h1>
        <p class="mail-muted">Private email for @<?= e(Mail::domain()) ?></p>
        <?php if ($error !== ''): ?><div class="mail-alert mail-alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="mail-form-group"><label for="email">Email</label><input id="email" type="email" name="email" autocomplete="username" required value="<?= e($email) ?>"></div>
            <div class="mail-form-group"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" required></div>
            <button class="mail-btn mail-btn--wide" type="submit">Sign in</button>
        </form>
    </section>
</main>
</body>
</html>
