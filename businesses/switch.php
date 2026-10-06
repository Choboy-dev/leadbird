<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('businesses'));
}

require_csrf();

$userId = (string) (new SessionAuth())->userId();
$businessId = trim((string) ($_POST['business_id'] ?? ''));
$appUrl = app_config()['app_url'];
$ctx = new BusinessContext();

if ($businessId === '' || !$ctx->userOwnsBusiness($userId, $businessId)) {
    flash_set('error', 'Business not found.');
    redirect(app_route('businesses'));
}

$business = RepositoryFactory::businesses()->findById($businessId);
if ($business === null) {
    flash_set('error', 'Business not found.');
    redirect(app_route('businesses'));
}

$onboarding = new OnboardingService();
if (!$ctx->isBusinessComplete($business)) {
    $ctx->setOnboardingBusinessId($businessId);
    redirect($onboarding->stepUrl($onboarding->getCurrentStepForBusiness($businessId)));
}

$ctx->setActiveBusinessId($businessId);
flash_set('success', 'Switched to ' . (string) ($business['business_name'] ?? 'business') . '.');

$returnTo = trim((string) ($_POST['return_to'] ?? ''));
$returnTo = safe_return_url(
    $returnTo !== '' ? $returnTo : (string) ($_SERVER['HTTP_REFERER'] ?? ''),
    app_route('dashboard')
);

redirect($returnTo);
