<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_subscription.php';

$userId = (string) (new SessionAuth())->userId();
$appUrl = app_config()['app_url'];
$ctx = new BusinessContext();

if (!$ctx->canAddBusiness($userId)) {
    flash_set('error', 'Your plan business limit has been reached. Upgrade to add more businesses.');
    redirect(app_route('businesses'));
}

$business = $ctx->ensureOnboardingBusiness($userId);
$ctx->setOnboardingBusinessId((string) $business['id']);

redirect(app_route('onboarding/channels'));
