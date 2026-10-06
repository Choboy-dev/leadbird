<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('refer'));
}

require_csrf();

if (!PlanAccess::allows($planSlug, 'refer_earn')) {
    flash_set('error', 'Not allowed.');
    redirect(app_route('dashboard'));
}

$affiliate = (new AffiliateService())->ensureForUser((string) $userId, $user ?? []);
$amount = (float) ($_POST['amount'] ?? 0);
$result = (new PayoutService())->withdrawAffiliate($affiliate, $amount);

if (!($result['ok'] ?? false)) {
    flash_set('error', (string) ($result['error'] ?? 'Withdrawal failed.'));
    redirect(app_route('refer'));
}

$ngnPaid = (int) ($result['amount_ngn'] ?? 0);
flash_set(
    'success',
    'Withdrawal of ' . money_format($amount)
        . ' is on the way'
        . ($ngnPaid > 0 ? ' (' . money_format((float) $ngnPaid, 'NGN') . ' to your bank)' : '')
        . '.'
);
redirect(app_route('refer'));
