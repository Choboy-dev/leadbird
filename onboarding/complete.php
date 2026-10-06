<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_onboarding.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('onboarding/channels'));
}

$userId = (string) (new SessionAuth())->userId();
$onboarding = new OnboardingService();
$appUrl = app_config()['app_url'];
$ctx = new BusinessContext();
$business = $ctx->ensureOnboardingBusiness($userId);

if ($business === null) {
    redirect(app_route('onboarding/channels'));
}

$businessId = (string) $business['id'];

$onboarding->completeOnboarding($businessId);
flash_set('success', 'Welcome to Lead Bird! Your business is ready.');
redirect(app_route('dashboard'));
