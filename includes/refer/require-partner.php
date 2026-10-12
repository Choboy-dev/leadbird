<?php

declare(strict_types=1);

/**
 * Resolves the signed-in user's partner account for Refer & Earn actions.
 * Customers on a paid plan get one created automatically; public partners need no plan.
 *
 * Sets: $userId, $affiliate, $referHome (URL to send the user back to).
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$sessionAuth = new SessionAuth();
$userId = $sessionAuth->userId();
if ($userId === null) {
    redirect(app_route('login'));
}

$referHome = app_route(($_POST['return'] ?? '') === 'partners/dashboard' ? 'partners/dashboard' : 'refer');

$sessionUser = $sessionAuth->user() ?? [];
$affiliateService = new AffiliateService();
$affiliate = $affiliateService->findForUser((string) $userId, (string) ($sessionUser['email'] ?? ''));
if ($affiliate === null && (new SubscriptionService())->hasActiveSubscription((string) $userId)) {
    $affiliate = $affiliateService->ensureForUser((string) $userId, $sessionUser);
}

if ($affiliate === null) {
    flash_set('error', 'Create a partner account to get your referral link.');
    redirect(app_route('affiliates/apply'));
}
