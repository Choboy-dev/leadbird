<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/dashboard/helpers.php';

$userId = (new SessionAuth())->userId();
if ($userId === null) {
    redirect(app_route('login'));
}

if (!(new SubscriptionService())->hasActiveSubscription((string) $userId)) {
    redirect(app_route('pricing'));
}

$tiktok = new TikTokConnectService();
$userId = (string) $userId;

$businessId = trim((string) ($_GET['business_id'] ?? ''));
$returnTo = safe_return_url(
    trim((string) ($_GET['return_to'] ?? '')),
    $tiktok->defaultReturnTo($userId)
);

if ($businessId === '' || !(new BusinessContext())->userOwnsBusiness($userId, $businessId)) {
    flash_set('error', 'Business not found.');
    redirect($tiktok->defaultReturnTo($userId));
}

if (!$tiktok->isEnabled()) {
    flash_set('error', 'TikTok connections are not available yet.');
    redirect($returnTo);
}

$appUrl = app_config()['app_url'];
$brandName = (string) app_config()['brand_name'];
$connectUrl = app_route('auth/tiktok/connect', [
    'business_id' => $businessId,
    'return_to' => $returnTo,
]);

$checklist = [
    'You are logged in to the TikTok account you want Lead Bird to post from.',
    'The account is a TikTok Business or Creator account, so it can publish from other apps.',
    'You approve every permission TikTok asks for — Lead Bird needs them to schedule and publish posts.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect TikTok · <?= e($brandName) ?></title>
    <?php
    $metaRobots = 'noindex, nofollow';
    require dirname(__DIR__, 2) . '/includes/brand-meta.php';
    ?>
    <?php require dirname(__DIR__, 2) . '/includes/brand-fonts.php'; ?>
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/brand.css">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/onboarding.css?v=22">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/meta-setup.css?v=3">
</head>
<body class="meta-setup-page font-sans antialiased">
<div class="meta-setup-shell">
    <div class="meta-setup-grid" aria-hidden="true"></div>

    <main class="meta-setup-main">
        <div class="meta-setup-logo">
            <?php $logoSize = 'sm'; $logoHref = e($appUrl) . '/'; require dirname(__DIR__, 2) . '/includes/brand-logo.php'; ?>
        </div>

        <article class="meta-setup-card">
            <header class="meta-setup-head">
                <div class="meta-setup-platform meta-setup-platform--tiktok" aria-hidden="true">
                    <?= brand_tiktok_icon_html('', 22, '') ?>
                </div>
                <h1 class="meta-setup-title font-heading">Let's get you set up</h1>
                <p class="meta-setup-lead">
                    Lead Bird needs permission from TikTok to plan and publish videos on your behalf.
                    Before you continue, make sure that:
                </p>
            </header>

            <ul class="meta-setup-checklist">
                <?php foreach ($checklist as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>

            <div class="meta-setup-actions">
                <a href="<?= e($returnTo) ?>" class="onboarding-btn onboarding-btn--ghost">Cancel</a>
                <a href="<?= e($connectUrl) ?>" class="onboarding-btn onboarding-btn--primary">Continue with TikTok</a>
            </div>
        </article>
    </main>
</div>
<?php require dirname(__DIR__, 2) . '/includes/site-assistant/embed.php'; ?>
</body>
</html>
