<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$sessionAuth = new SessionAuth();
$appUrl = app_config()['app_url'];

if (!$sessionAuth->check()) {
    http_response_code(401);
    json_response(['ok' => false, 'error' => 'Please sign in first.', 'redirect' => app_route('login')]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_response(['ok' => false, 'error' => 'Method not allowed.']);
}

require_csrf();

if (!rate_limit_allow('billing_initiate', 20, 3600)) {
    http_response_code(429);
    json_response(['ok' => false, 'error' => 'Too many checkout attempts. Please wait and try again.']);
}

$user = $sessionAuth->user();
$userId = $sessionAuth->userId();
if ($user === null || $userId === null) {
    http_response_code(401);
    json_response(['ok' => false, 'error' => 'Session expired.', 'redirect' => app_route('login')]);
}

$planSlug = strtolower(trim((string) ($_POST['plan'] ?? '')));
$billingCycle = strtolower(trim((string) ($_POST['billing_cycle'] ?? CheckoutPricing::CYCLE_MONTHLY)));
if (!in_array($billingCycle, [CheckoutPricing::CYCLE_MONTHLY, CheckoutPricing::CYCLE_ANNUAL], true)) {
    $billingCycle = CheckoutPricing::CYCLE_MONTHLY;
}
if (!PlanService::isValidSlug($planSlug)) {
    http_response_code(422);
    json_response(['ok' => false, 'error' => 'Please choose a valid plan.']);
}

try {
    $subscriptionService = new SubscriptionService();
    $checkout = $subscriptionService->beginCheckout($userId, $planSlug, $user, $billingCycle);
    json_response(['ok' => true, 'redirect' => $checkout['link'], 'tx_ref' => $checkout['tx_ref']]);
} catch (RuntimeException $e) {
    if ($e->getMessage() === 'You are already on this plan.') {
        http_response_code(409);
        json_response(['ok' => false, 'error' => $e->getMessage()]);
    }

    $message = 'Could not start checkout. Please try again.';
    http_response_code(500);
    json_response(['ok' => false, 'error' => $message]);
} catch (Throwable $e) {
    $message = 'Could not start checkout. Please try again.';

    http_response_code(500);
    json_response(['ok' => false, 'error' => $message]);
}
