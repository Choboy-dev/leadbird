<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_onboarding.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('onboarding/channels'));
}

$userId = (string) (new SessionAuth())->userId();
$onboarding = new OnboardingService();
$ctx = new BusinessContext();
$appUrl = app_config()['app_url'];

$business = $ctx->ensureOnboardingBusiness($userId);
$businessId = (string) $business['id'];

$connectedCount = RepositoryFactory::connectedAccounts()->countActiveByBusinessId($businessId);
if ($connectedCount < 1) {
    flash_set('error', 'Connect at least one channel before continuing — your AI needs a page or number to reply on.');
    redirect(app_route('onboarding/channels'));
}

$onboarding->advanceAfterStep($businessId, OnboardingService::STEP_CHANNELS);
redirect(app_route('onboarding/profile'));
