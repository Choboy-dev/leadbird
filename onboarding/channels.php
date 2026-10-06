<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_onboarding.php';
require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

$onboardingStep = OnboardingService::STEP_CHANNELS;
$userId = (string) (new SessionAuth())->userId();
$onboarding = new OnboardingService();
$onboarding->assertCanAccessStep($userId, $onboardingStep);

$ctx = new BusinessContext();
$business = $ctx->ensureOnboardingBusiness($userId);
$businessId = (string) $business['id'];

$appUrl = app_config()['app_url'];
$returnTo = app_route('onboarding/channels');

$connected = [];
foreach (RepositoryFactory::connectedAccounts()->listByBusinessId($businessId, true) as $account) {
    $connected[(string) ($account['platform'] ?? '')] = $account;
}

$channels = [
    'facebook' => ['label' => 'Facebook'],
    'instagram' => ['label' => 'Instagram'],
    'whatsapp' => ['label' => 'WhatsApp'],
    'tiktok' => ['label' => 'TikTok', 'disabled' => !tiktok_is_enabled()],
];

$pageTitle = 'Connect your channels';
$stepHeading = 'Connect your channels';
$stepLead = '';
$onboardingBodyClass = 'onboarding-step--channels';
$error = flash_get('error');
$success = flash_get('success');

require dirname(__DIR__) . '/includes/onboarding/layout-start.php';
?>
<div class="onboarding-offers-page onboarding-channels-page">
    <section class="onboarding-offer-section" aria-label="Social channels">
        <div class="onboarding-channel-grid">
            <?php foreach ($channels as $platform => $channel): ?>
                <?php
                $label = (string) ($channel['label'] ?? ucfirst($platform));
                $isDisabled = !empty($channel['disabled']);
                $isConnected = !$isDisabled && isset($connected[$platform]);
                $connectUrl = dash_platform_setup_url($platform, $businessId, $returnTo);
                $reconnectUrl = dash_platform_reconnect_url($platform, $businessId, $returnTo);
                $platformBlocked = !$isDisabled && !$isConnected && !PlanLimits::canConnectPlatform($userId, $businessId, $platform);
                $targetUrl = $isConnected ? $reconnectUrl : $connectUrl;
                $tileClass = 'onboarding-channel-tile onboarding-channel-tile--' . $platform;
                if ($isConnected) {
                    $tileClass .= ' is-connected';
                }
                if ($isDisabled || $platformBlocked) {
                    $tileClass .= ' is-unavailable';
                }
                $iconHtml = $platform === 'tiktok'
                    ? brand_tiktok_icon_html('onboarding-channel-tile-icon-svg', 44, '')
                    : dash_platform_tile_icon($platform, 44);
                $actionLabel = $isConnected
                    ? 'Reconnect ' . $label
                    : ($platformBlocked ? $label . ' unavailable on your plan' : 'Connect ' . $label);
                ?>
                <?php if ($isDisabled): ?>
                    <span class="<?= e($tileClass) ?>" aria-disabled="true" title="Coming soon">
                        <span class="onboarding-channel-tile-icon" aria-hidden="true"><?= $iconHtml ?></span>
                        <span class="onboarding-channel-tile-soon">Soon</span>
                    </span>
                <?php elseif ($platformBlocked): ?>
                    <span class="<?= e($tileClass) ?>" aria-disabled="true" title="Already connected on another business">
                        <span class="onboarding-channel-tile-icon" aria-hidden="true"><?= $iconHtml ?></span>
                    </span>
                <?php else: ?>
                    <a
                        href="<?= e($targetUrl) ?>"
                        class="<?= e($tileClass) ?>"
                        aria-label="<?= e($actionLabel) ?>"
                    >
                        <span class="onboarding-channel-tile-icon" aria-hidden="true"><?= $iconHtml ?></span>
                        <?php if ($isConnected): ?>
                            <span class="onboarding-channel-tile-status" aria-hidden="true">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6 9 17l-5-5"/>
                                </svg>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <form class="onboarding-form" method="post" action="<?= e(app_route('onboarding/advance-channels')) ?>">
        <?php if ($connected !== []): ?>
        <div class="onboarding-step-nav onboarding-step-nav--offers">
            <div class="onboarding-actions onboarding-actions--step onboarding-actions--offers-step">
                <?php if (onboarding_has_back_button($onboardingStep)): ?>
                    <a href="<?= e(onboarding_back_url($onboardingStep)) ?>" class="onboarding-btn onboarding-btn--ghost">Back</a>
                <?php endif; ?>
                <button type="submit" class="onboarding-btn onboarding-btn--ghost onboarding-btn--continue">
                    Continue
                </button>
            </div>
        </div>
        <?php endif; ?>
    </form>
</div>
<?php require dirname(__DIR__) . '/includes/onboarding/layout-end.php'; ?>
