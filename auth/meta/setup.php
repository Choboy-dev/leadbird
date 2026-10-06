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

$metaConnect = new MetaConnectService();
$userId = (string) $userId;

$platform = strtolower(trim((string) ($_GET['platform'] ?? '')));
$businessId = trim((string) ($_GET['business_id'] ?? ''));
$returnTo = safe_return_url(
    trim((string) ($_GET['return_to'] ?? '')),
    $metaConnect->defaultReturnTo($userId)
);

if ($businessId === '' || !(new BusinessContext())->userOwnsBusiness($userId, $businessId)) {
    flash_set('error', 'Business not found.');
    redirect($metaConnect->defaultReturnTo($userId));
}

if (!in_array($platform, ['whatsapp', 'instagram', 'facebook'], true)) {
    flash_set('error', 'Unsupported channel.');
    redirect($returnTo);
}

$appUrl = app_config()['app_url'];
$brandName = (string) app_config()['brand_name'];

$connectUrl = app_route('auth/meta/connect', [
    'platform' => $platform,
    'business_id' => $businessId,
    'return_to' => $returnTo,
]);

$platformMeta = match ($platform) {
    'instagram' => [
        'title' => "Let's get you set up",
        'lead' => 'Lead Bird needs permission from Facebook to access and publish content to Instagram on your behalf. Before you continue, make sure that:',
        'checklist' => [
            'You have converted your Instagram profile to an Instagram Business account.',
            'Your Instagram Business account is linked to a Facebook Page.',
            'You have administrator privileges on that Facebook Page.',
        ],
        'login_label' => 'Log in to Facebook',
    ],
    'facebook' => [
        'title' => "Let's get you set up",
        'lead' => 'Lead Bird needs permission from Facebook to reply to Messenger on your behalf. Before you continue, make sure that:',
        'checklist' => [
            'You manage the Facebook Page you want to connect.',
            'You have administrator privileges on that Page.',
        ],
        'login_label' => 'Log in to Facebook',
    ],
    'whatsapp' => [
        'title' => "Let's get you set up",
        'lead' => 'Lead Bird needs permission from Meta to reply on WhatsApp on your behalf. Before you continue, make sure that:',
        'checklist' => [
            'Your WhatsApp Business number is set up in Meta Business Manager.',
            'You have admin access to the WhatsApp Business account.',
        ],
        'login_label' => 'Log in to Facebook',
    ],
    default => [
        'title' => "Let's get you set up",
        'lead' => 'Lead Bird needs permission from Meta to connect your account.',
        'checklist' => [],
        'login_label' => 'Continue with Meta',
    ],
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($platformMeta['title']) ?> · <?= e($brandName) ?></title>
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
                <div class="meta-setup-platform" aria-hidden="true"><?= dash_platform_icon($platform) ?></div>
                <h1 class="meta-setup-title font-heading"><?= e($platformMeta['title']) ?></h1>
                <p class="meta-setup-lead"><?= e($platformMeta['lead']) ?></p>
            </header>

            <?php if (($platformMeta['checklist'] ?? []) !== []): ?>
                <ul class="meta-setup-checklist">
                    <?php foreach ($platformMeta['checklist'] as $item): ?>
                        <li><?= e($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="meta-setup-actions">
                <a href="<?= e($returnTo) ?>" class="onboarding-btn onboarding-btn--ghost">Cancel</a>
                <a href="<?= e($connectUrl) ?>" class="onboarding-btn onboarding-btn--primary"><?= e($platformMeta['login_label']) ?></a>
            </div>
        </article>
    </main>
</div>
<?php require dirname(__DIR__, 2) . '/includes/site-assistant/embed.php'; ?>
</body>
</html>
