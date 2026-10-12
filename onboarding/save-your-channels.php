<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_subscription.php';

$stepUrl = app_route('onboarding/your-channels');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($stepUrl);
}
if (!csrf_verify()) {
    flash_set('error', 'Your session expired. Please try again.');
    redirect($stepUrl);
}

$userId = (string) (new SessionAuth())->userId();
$business = (new BusinessContext())->getOnboardingBusiness($userId);
if ($business === null) {
    redirect(app_route('dashboard'));
}

$businessId = (string) $business['id'];
if (RepositoryFactory::connectedAccounts()->countActiveByBusinessId($businessId) < 1) {
    flash_set('error', 'Connect an account before you continue. Hannah needs a page or number to reply on.');
    redirect($stepUrl);
}

$onboarding = new OnboardingService();
$onboarding->ensureSettings($businessId);
if ($onboarding->getCurrentStepForBusiness($businessId) < OnboardingService::STEP_OFFERINGS) {
    RepositoryFactory::businesses()->updateOnboardingStep($businessId, OnboardingService::STEP_OFFERINGS);
}

redirect($onboarding->stepUrl(OnboardingService::STEP_OFFERINGS));
