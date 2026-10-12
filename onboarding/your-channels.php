<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/onboarding/v2-guard.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';
require_once dirname(__DIR__) . '/includes/solutions/icons.php';
require_once dirname(__DIR__) . '/includes/tiktok.php';

if (($business['industry'] ?? null) === null) {
    redirect(app_route('onboarding/welcome'));
}

$businessId = (string) $business['id'];
$returnTo = app_route('onboarding/your-channels');

$connected = [];
foreach (RepositoryFactory::connectedAccounts()->listByBusinessId($businessId, true) as $account) {
    $connected[(string) ($account['platform'] ?? '')] = $account;
}

/** Platform key used by the connect flow => label and icon. */
$channels = [
    'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'whatsapp'],
    'instagram' => ['label' => 'Instagram', 'icon' => 'instagram'],
    'facebook' => ['label' => 'Messenger', 'icon' => 'messenger'],
    'tiktok' => ['label' => 'TikTok', 'icon' => 'tiktok', 'disabled' => !tiktok_is_enabled()],
];

$error = flash_get('error');
$success = flash_get('success');

$pageTitle = 'Connect your channels';
$obwStep = 2;
require dirname(__DIR__) . '/includes/onboarding/v2-start.php';
?>
        <div class="obw-form">
            <a class="obw-back" href="<?= e(app_route('onboarding/welcome')) ?>">← Back</a>
            <h1 class="obw-title">How do you connect with <span>your customers?</span></h1>
            <p class="obw-lead">Connect the account your customers message most. You can add your other accounts later, one at a time.</p>

            <?php if ($error): ?>
                <div class="obw-alert" role="alert"><?= e($error) ?></div>
            <?php elseif ($success): ?>
                <div class="obw-alert obw-alert--ok" role="status"><?= e($success) ?></div>
            <?php endif; ?>

            <div class="obw-tiles">
                <?php foreach ($channels as $platform => $channel): ?>
                    <?php
                    $label = $channel['label'];
                    $isDisabled = !empty($channel['disabled']);
                    $isConnected = !$isDisabled && isset($connected[$platform]);
                    $isBlocked = !$isDisabled && !$isConnected && !PlanLimits::canConnectPlatform($userId, $businessId, $platform);
                    $tileClass = 'obw-tile' . ($isConnected ? ' is-connected' : '') . ($isDisabled || $isBlocked ? ' is-unavailable' : '');
                    $tileIcon = '<span class="obw-tile-icon">' . sol_icon($channel['icon']) . '</span>';
                    ?>
                    <?php if ($isDisabled || $isBlocked): ?>
                        <span class="<?= e($tileClass) ?>" aria-disabled="true" aria-label="<?= e($label . ($isDisabled ? ': coming soon' : ': not on your plan')) ?>">
                            <?= $tileIcon ?>
                            <small><?= $isDisabled ? 'Coming soon' : 'Not on your plan' ?></small>
                        </span>
                    <?php else: ?>
                        <a class="<?= e($tileClass) ?>" href="<?= e($isConnected ? dash_platform_reconnect_url($platform, $businessId, $returnTo) : dash_platform_setup_url($platform, $businessId, $returnTo)) ?>" aria-label="<?= e(($isConnected ? 'Reconnect ' : 'Connect ') . $label) ?>">
                            <?= $tileIcon ?>
                            <span class="obw-tile-btn"><?= $isConnected ? 'Connected' : 'Connect' ?></span>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <form class="obw-actions" method="post" action="<?= e(app_route('onboarding/save-your-channels')) ?>">
                <?= csrf_field() ?>
                <button class="obw-continue" type="submit"<?= $connected === [] ? ' disabled' : '' ?>>Continue</button>
            </form>
            <?php if ($connected === []): ?>
                <p class="obw-hint obw-hint--center">Connect an account to continue.</p>
            <?php endif; ?>
        </div>
<?php require dirname(__DIR__) . '/includes/onboarding/v2-end.php'; ?>
