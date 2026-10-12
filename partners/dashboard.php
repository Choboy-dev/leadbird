<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

$sessionAuth = new SessionAuth();
$userId = $sessionAuth->userId();
if ($userId === null) {
    flash_set('success', 'Sign in with the email you used for your partner account.');
    redirect(app_route('login'));
}

$subscriptions = new SubscriptionService();
if ($subscriptions->hasActiveSubscription($userId) && (new OnboardingService())->userCanAccessDashboard($userId)) {
    redirect(app_route('refer'));
}

$sessionUser = $sessionAuth->user() ?? [];
$affiliate = (new AffiliateService())->findForUser($userId, (string) ($sessionUser['email'] ?? ''));
if ($affiliate === null) {
    flash_set('error', 'There is no partner account for ' . (string) ($sessionUser['email'] ?? 'this email') . '. Create one to get your referral link.');
    redirect(app_route('affiliates/apply'));
}

$appUrl = app_config()['app_url'];
$brandName = app_config()['brand_name'] ?? 'Lead Bird';
$referReturn = 'partners/dashboard';
require dirname(__DIR__) . '/includes/refer/view-data.php';

$success = flash_get('success');
$error = flash_get('error');
$partnerName = trim((string) ($affiliate['name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner dashboard — <?= e($brandName) ?></title>
    <?php
    $metaRobots = 'noindex, nofollow';
    require dirname(__DIR__) . '/includes/brand-meta.php';
    require dirname(__DIR__) . '/includes/brand-fonts.php';
    ?>
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/dropdown.css?v=6">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/modal.css?v=3">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/dashboard-refer.css?v=12">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/partners-dashboard.css?v=2">
</head>
<body class="pd-page">
    <header class="pd-top">
        <?php $logoSize = 'sm'; require dirname(__DIR__) . '/includes/brand-logo.php'; ?>
        <nav class="pd-top-links" aria-label="Partner links">
            <a href="<?= e(app_route('affiliates')) ?>">How it works</a>
            <a href="<?= e(app_route('pricing')) ?>">Use Lead Bird</a>
            <a href="<?= e(app_route('auth/logout')) ?>">Sign out</a>
        </nav>
    </header>

    <main class="pd-main">
        <div class="pd-intro">
            <p class="pd-kicker">Lead Bird Partners</p>
            <h1 class="pd-title"><?= $partnerName !== '' ? 'Hi ' . e(explode(' ', $partnerName)[0]) . ', here are your earnings' : 'Your partner earnings' ?></h1>
            <p class="pd-lead">Share your link. When someone you refer pays for their first Lead Bird plan, you earn <?= (int) (AffiliateService::COMMISSION_RATE * 100) ?>% of it.</p>
        </div>

        <?php require dirname(__DIR__) . '/includes/dashboard/refer-content.php'; ?>
    </main>

    <script src="<?= e($appUrl) ?>/assets/js/modal.js?v=2"></script>
    <script src="<?= e($appUrl) ?>/assets/js/dropdown.js?v=6"></script>
    <script src="<?= e($appUrl) ?>/assets/js/dashboard-refer.js?v=1"></script>
</body>
</html>
