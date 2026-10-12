<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_subscription.php';

$userId = (new SessionAuth())->userId();
if ($userId === null) {
    redirect(app_route('login'));
}

$user = (new SessionAuth())->user();

$onboarding = new OnboardingService();
if (!OnboardingService::legacyWizardEnabled()) {
    // The new welcome and channel screens replace the wizard's first two steps.
    $inProgress = (new BusinessContext())->getOnboardingBusiness($userId);
    $replacedStep = in_array(basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')), ['channels.php', 'advance-channels.php', 'profile.php', 'save-profile.php'], true);
    if ($inProgress !== null && $replacedStep) {
        redirect(app_route('onboarding/your-channels'));
    }
    if ($inProgress === null || $onboarding->getCurrentStepForBusiness((string) $inProgress['id']) < OnboardingService::STEP_OFFERINGS) {
        redirect($onboarding->welcomeUrl($userId, 'settings'));
    }
}

$ctx = new BusinessContext();
$business = $ctx->ensureOnboardingBusiness($userId);

if ($onboarding->isBusinessComplete((string) $business['id'])) {
    redirect(app_route('businesses'));
}
