<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$code = trim((string) ($_GET['code'] ?? ''));
$affiliateService = new AffiliateService();
$sessionAuth = new SessionAuth();
$landingPath = $code !== '' ? '/r/' . $code : null;

$affiliate = $affiliateService->trackReferralVisit($code, $landingPath, false);

if ($affiliate === null) {
    flash_set('error', 'This referral link is not valid.');
    redirect(app_route('signup'));
}

$affiliateCode = (string) ($affiliate['code'] ?? '');
$isLoggedIn = $sessionAuth->check();

ReferralSelection::storeFromAffiliate($affiliate, !$isLoggedIn);

redirect(ReferralSelection::signupUrlForCode($affiliateCode));
