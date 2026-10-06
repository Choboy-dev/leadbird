<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_subscription.php';

$userId = (new SessionAuth())->userId();
if ($userId === null) {
    redirect(app_route('login'));
}

$user = (new SessionAuth())->user();

$onboarding = new OnboardingService();
$ctx = new BusinessContext();
$business = $ctx->ensureOnboardingBusiness($userId);

if ($onboarding->isBusinessComplete((string) $business['id'])) {
    redirect(app_route('businesses'));
}
