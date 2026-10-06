<?php

declare(strict_types=1);

/** @var string|null $dashboardNav */

$sessionAuth = new SessionAuth();
$user = $sessionAuth->user();
$userId = $sessionAuth->userId();
$appUrl = app_config()['app_url'];
$brandName = app_config()['brand_name'];

$businessCtx = new BusinessContext();
$onboarding = new OnboardingService();
$subscription = $userId !== null ? (new SubscriptionService())->getActiveSubscription($userId) : null;
$business = $userId !== null ? $businessCtx->resolveActiveBusiness($userId) : null;
$businessId = $business !== null ? (string) $business['id'] : null;
$allBusinesses = $userId !== null ? $businessCtx->listForUser($userId) : [];
$settings = $businessId !== null
    ? RepositoryFactory::businessSettings()->findByBusinessId($businessId)
    : null;

$planSlug = $subscription !== null ? (string) $subscription['plan_slug'] : 'starter';
$plan = PlanService::find($planSlug);
$monthlyTokens = PlanLimits::monthlyTokens($planSlug);
$planUsage = $userId !== null ? PlanLimits::usageSummaryForUser($userId, $businessId) : [
    'businesses_used' => 0,
    'business_limit' => 1,
    'business_label' => '',
    'channels_label' => '',
    'channels_on_business' => 0,
    'channels_per_platform' => 1,
];
$channelLimit = null;
$tokenService = new TokenService();
$tokenUsage = $userId !== null
    ? $tokenService->getUsageSummary($userId)
    : [
        'balance' => 0,
        'unlimited' => false,
        'monthly_grant' => $monthlyTokens,
        'low_balance' => false,
        'depleted' => true,
        'label' => 'No tokens',
        'percent_remaining' => 0.0,
        'price_per_reply' => null,
    ];

$dashboardService = new DashboardService();
$stats = $dashboardService->getOverviewStats($businessId);
$accountUsage = [
    'used' => (int) ($planUsage['channels_on_business'] ?? 0),
    'limit' => null,
    'label' => (string) ($planUsage['channels_label'] ?? ''),
];

/**
 * Gate a dashboard page by plan feature.
 *
 * @return bool true when allowed; when false, sets $lockedFeature and caller should render feature-locked partial.
 */
function dashboard_require_feature(string $feature): bool
{
    global $planSlug, $lockedFeature;

    if (PlanAccess::allows($planSlug, $feature)) {
        return true;
    }

    $lockedFeature = PlanAccess::lockedMeta($feature);

    return false;
}
