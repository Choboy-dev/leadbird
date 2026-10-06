<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/helpers.php';

$pageTitle = 'Billing — ' . $brandName;
$dashboardNav = 'billing';
$pageHeading = 'Billing';
$extraStylesheets = ['dashboard-billing.css?v=20'];

$userEmail = trim((string) ($user['email'] ?? ''));
$userDisplayName = trim((string) ($user['name'] ?? ''));
if ($userDisplayName === '') {
    $userDisplayName = $userEmail !== '' ? (strstr($userEmail, '@', true) ?: $userEmail) : 'Account';
}
$nameParts = preg_split('/\s+/u', $userDisplayName, 2) ?: [];
$firstInitial = mb_strtoupper(mb_substr((string) ($nameParts[0] ?? 'A'), 0, 1));
$secondInitial = mb_strtoupper(mb_substr((string) ($nameParts[1] ?? $nameParts[0] ?? ''), 0, 1));
$userInitials = $firstInitial . $secondInitial;

$renewal = is_array($subscription) && !empty($subscription['current_period_end'])
    ? date('M j, Y', strtotime((string) $subscription['current_period_end']))
    : null;
$planName = is_array($plan) ? (string) ($plan['name'] ?? 'Active plan') : 'Active plan';
$planPrice = is_array($plan) ? (int) ($plan['price'] ?? 0) : 0;
$planAllowanceLabel = PlanLimits::planAllowanceLabel($planSlug);
$tokenPackages = token_topup_packages_list();
$tokenActivity = $userId !== null ? $tokenService->recentActivity($userId, 6) : [];
$status = is_array($subscription) ? ucfirst((string) ($subscription['status'] ?? 'active')) : 'Active';

$billingHistory = [];
if ($userId !== null && PlanAccess::allows($planSlug, 'billing_history')) {
    $billingHistory = RepositoryFactory::subscriptions()->listByUserId($userId);
}

$allPlans = PlanService::list();
$success = flash_get('success');
$error = flash_get('error');

$paymentMethods = [];
$billingDefaults = [
    'address_line1' => '',
    'city' => '',
    'state' => '',
    'country' => 'NG',
    'postal_code' => '',
    'phone' => '',
];
if ($userId !== null) {
    $paymentMethodService = new PaymentMethodService();
    $paymentMethodService->syncFromActiveSubscription($userId);
    foreach ($paymentMethodService->listForUser($userId) as $method) {
        $paymentMethods[] = $paymentMethodService->presentMethod($method);
    }
    $billingDefaults = (new BillingProfileService())->defaultsForUser($userId, $business, $user);
}
$pastDue = $userId !== null ? (new SubscriptionService())->pastDueState($userId) : null;

require __DIR__ . '/includes/dashboard/layout-start.php';
require __DIR__ . '/includes/dashboard/billing-content.php';
require __DIR__ . '/includes/dashboard/layout-end.php';
