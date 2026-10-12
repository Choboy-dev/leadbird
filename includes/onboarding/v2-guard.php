<?php

declare(strict_types=1);

/**
 * Access check for the new onboarding flow. Sets $auth, $userId, $user, $appUrl,
 * $brandName, $ctx and $business (the business being set up).
 */

require_once dirname(__DIR__) . '/require_subscription.php';

$auth = new SessionAuth();
$userId = (string) $auth->userId();
$user = $auth->user();
$appUrl = app_config()['app_url'];
$brandName = (string) (app_config()['brand_name'] ?? 'Lead Bird');

$ctx = new BusinessContext();
if ($ctx->getOnboardingBusiness($userId) === null && $ctx->hasAnyCompleteBusiness($userId)) {
    redirect(app_route('dashboard'));
}
$business = $ctx->ensureOnboardingBusiness($userId);
