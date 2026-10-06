<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/lib/AppReviewAccessService.php';

$review = new AppReviewAccessService();

if (!$review->isEnabled()) {
    http_response_code(404);
    exit('Not found');
}

$sessionAuth = new SessionAuth();
$userId = $sessionAuth->userId();
if ($userId !== null && $review->isReviewUser($userId) && (new SubscriptionService())->hasActiveSubscription($userId)) {
    redirect(app_route('dashboard'));
}

$appUrl = app_config()['app_url'];
$brandName = app_config()['brand_name'] ?? 'Lead Bird';
$error = flash_get('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!rate_limit_allow('app_review_login', 12, 900)) {
        flash_set('error', 'Too many attempts. Wait a few minutes and try again.');
        redirect(app_route('review/login'));
    }

    $email = normalize_email(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!$review->credentialsMatch($email, $password)) {
        flash_set('error', 'Invalid email or password.');
        redirect(app_route('review/login'));
    }

    try {
        $sessionAuth->login($review->provisionSessionUser());
    } catch (Throwable $e) {
        error_log('[AppReviewAccess] login failed: ' . $e->getMessage());
        flash_set('error', 'Could not open the review account. Please try again.');
        redirect(app_route('review/login'));
    }

    redirect(app_route('dashboard'));
}

$pageTitle = 'App Review sign in — ' . $brandName;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle) ?></title>
    <?php require dirname(__DIR__) . '/includes/brand-fonts.php'; ?>
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/brand.css?v=18">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/review-login.css?v=1">
</head>
<body class="font-sans review-login-page">
    <main class="review-login-card">
        <span class="review-login-badge font-heading">Meta App Review</span>
        <h1 class="review-login-title font-heading">Reviewer sign in</h1>
        <p class="review-login-lead">Sign in to test Lead Bird with the Shop plan. Connect channels, use Inbox, and try messaging features.</p>

        <?php if ($error): ?>
            <div class="review-login-error" role="alert"><?= e((string) $error) ?></div>
        <?php endif; ?>

        <form class="review-login-form" method="post" action="<?= e(app_route('review/login')) ?>" autocomplete="on">
            <div class="review-login-field">
                <label for="review-email">Email</label>
                <input
                    id="review-email"
                    name="email"
                    type="email"
                    autocomplete="username"
                    required
                    value="<?= e($review->configuredEmail()) ?>"
                >
            </div>
            <div class="review-login-field">
                <label for="review-password">Password</label>
                <input
                    id="review-password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                >
            </div>
            <button type="submit" class="review-login-submit font-heading">Enter dashboard</button>
        </form>
    </main>
</body>
</html>
