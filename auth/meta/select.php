<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/dashboard/helpers.php';

$userId = (new SessionAuth())->userId();
if ($userId === null) {
    redirect(app_route('login'));
}

$metaConnect = new MetaConnectService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $metaConnect->finalizeFromSelection((string) ($_POST['option_key'] ?? ''));
    } catch (Throwable $e) {
        flash_set('error', 'Could not save your channel selection. Please try again.');
        redirect(app_route('auth/meta/select'));
    }
}

$pending = $metaConnect->getPendingSelection();
$userId = (string) $userId;
if ($pending === null) {
    flash_set('error', 'Channel selection expired. Please connect again.');
    redirect($metaConnect->defaultReturnTo($userId));
}

$platform = (string) ($pending['platform'] ?? 'channel');
$options = is_array($pending['options'] ?? null) ? $pending['options'] : [];
$appUrl = app_config()['app_url'];
$brandName = (string) app_config()['brand_name'];
$returnTo = (string) ($pending['return_to'] ?? app_route('onboarding/channels'));

$platformMeta = match ($platform) {
    'facebook' => [
        'title' => 'Choose a Facebook Page',
        'lead' => 'We found more than one Page on your Meta account. Pick the one Lead Bird should reply on.',
        'field' => 'Facebook Page',
    ],
    'instagram' => [
        'title' => 'Choose Instagram',
        'lead' => 'We found more than one Instagram account. Pick the one Lead Bird should manage.',
        'field' => 'Instagram account',
    ],
    'whatsapp' => [
        'title' => 'Choose WhatsApp',
        'lead' => 'We found more than one WhatsApp number. Pick the one Lead Bird should use.',
        'field' => 'WhatsApp number',
    ],
    default => [
        'title' => 'Choose ' . ucfirst($platform) . ' account',
        'lead' => 'Multiple accounts were found. Choose the one Lead Bird should manage.',
        'field' => 'Account',
    ],
};

$connectMode = (string) ($pending['connect_mode'] ?? 'connect');
if ($connectMode === 'add') {
    $platformMeta['lead'] = match ($platform) {
        'facebook' => 'Pick the Facebook Page you want to add to this business.',
        'instagram' => 'Pick the Instagram account you want to add to this business.',
        'whatsapp' => 'Pick the WhatsApp number you want to add to this business.',
        default => 'Pick the account you want to add to this business.',
    };
} elseif ($connectMode === 'reconnect') {
    $platformMeta['lead'] = match ($platform) {
        'facebook' => 'Pick the Facebook Page Lead Bird should reply on.',
        'instagram' => 'Pick the Instagram account Lead Bird should reply on.',
        'whatsapp' => 'Pick the WhatsApp number Lead Bird should reply on.',
        default => 'Pick the account Lead Bird should use.',
    };
}

$firstKey = '';
foreach ($options as $key => $option) {
    if (is_array($option)) {
        $firstKey = (string) $key;
        break;
    }
}

$retryMetaUrl = app_route('auth/meta/connect', [
    'platform' => $platform,
    'business_id' => (string) ($pending['business_id'] ?? ''),
    'return_to' => $returnTo,
    'mode' => $connectMode === 'add' ? 'add' : 'reconnect',
    'refresh' => '1',
]);
$optionCount = count(array_filter($options, 'is_array'));
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
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/onboarding.css?v=12">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/dropdown.css?v=4">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/meta-select.css?v=3">
</head>
<body class="meta-select-page font-sans antialiased">
<div class="meta-select-shell">
    <div class="meta-select-grid" aria-hidden="true"></div>

    <main class="meta-select-main">
        <div class="meta-select-logo">
            <?php $logoSize = 'sm'; $logoHref = e($appUrl) . '/'; require dirname(__DIR__, 2) . '/includes/brand-logo.php'; ?>
        </div>

        <article class="meta-select-card">
            <header class="meta-select-head">
                <div class="meta-select-platform" aria-hidden="true"><?= dash_platform_icon($platform) ?></div>
                <p class="meta-select-eyebrow">Meta connect</p>
                <h1 class="meta-select-title font-heading"><?= e($platformMeta['title']) ?></h1>
                <p class="meta-select-lead"><?= e($platformMeta['lead']) ?></p>
                <?php if ($optionCount <= 1): ?>
                    <p class="meta-select-hint">
                        Missing an account? <a href="<?= e($retryMetaUrl) ?>">Connect again</a> to pick a different one on Meta.
                    </p>
                <?php endif; ?>
            </header>

            <form class="meta-select-form" method="post" action="<?= e(app_route('auth/meta/select')) ?>">
                <div class="meta-select-field onboarding-field">
                    <label for="option_key"><?= e($platformMeta['field']) ?></label>
                    <select id="option_key" name="option_key" required>
                        <?php foreach ($options as $key => $option): ?>
                            <?php if (!is_array($option)) {
                                continue;
                            } ?>
                            <option value="<?= e((string) $key) ?>"<?= (string) $key === $firstKey ? ' selected' : '' ?>>
                                <?= e((string) ($option['label'] ?? $key)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="meta-select-actions">
                    <a href="<?= e($returnTo) ?>" class="onboarding-btn onboarding-btn--ghost">Cancel</a>
                    <button type="submit" class="onboarding-btn onboarding-btn--primary">Connect account</button>
                </div>
            </form>
        </article>
    </main>
</div>
<script src="<?= e($appUrl) ?>/assets/js/dropdown.js?v=4"></script>
<?php require dirname(__DIR__, 2) . '/includes/site-assistant/embed.php'; ?>
</body>
</html>
