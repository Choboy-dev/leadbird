<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/helpers.php';

if (!PlanAccess::allows($planSlug, 'refer_earn')) {
    flash_set('error', 'Refer & Earn is not available on your plan.');
    redirect(app_route('dashboard'));
}

$pageTitle = 'Refer & Earn — ' . $brandName;
$dashboardNav = 'refer';
$pageHeading = 'Refer & Earn';
$extraStylesheets = ['dashboard-refer.css?v=11'];
$extraScripts = ['dashboard-refer.js?v=1'];

$affiliateService = new AffiliateService();
$affiliate = $affiliateService->ensureForUser((string) $userId, $user ?? []);
$affiliateId = (string) ($affiliate['id'] ?? '');
$stats = $affiliateService->dashboardStats($affiliateId);
$referralLink = $affiliateService->referralUrl((string) ($affiliate['code']));
$withdrawable = $affiliateService->withdrawableBalance($affiliateId);
$totalBalance = $withdrawable + (float) ($stats['pending'] ?? 0);
$commissions = RepositoryFactory::affiliateCommissions()->listByAffiliateId($affiliateId, 50);
$payouts = RepositoryFactory::affiliatePayouts()->listByAffiliateId($affiliateId, 20);

$inviteHistory = [];
foreach (RepositoryFactory::affiliates()->listReferredUsers($affiliateId, 20) as $row) {
    $displayName = trim((string) ($row['name'] ?? ''));
    if ($displayName === '') {
        $email = trim((string) ($row['email'] ?? ''));
        $displayName = $email !== '' ? (string) (explode('@', $email)[0] ?? 'Invite') : 'Invite';
    }

    $commissionAmount = (float) ($row['commission_amount'] ?? 0);
    $commissionCurrency = strtoupper(trim((string) ($row['commission_currency'] ?? 'USD')));
    if ($commissionCurrency === '') {
        $commissionCurrency = 'USD';
    }
    $commissionUsd = money_to_usd($commissionAmount, $commissionCurrency);
    $commissionStatus = (string) ($row['commission_status'] ?? '');
    $isRewarded = in_array($commissionStatus, ['available', 'funding', 'paid_out'], true) && $commissionUsd > 0;
    $statusLabel = $isRewarded ? 'Rewarded' : ($commissionUsd > 0 ? 'Pending' : 'Onboarded');
    $statusClass = $isRewarded
        ? 'ref-status-pill--rewarded'
        : ($commissionUsd > 0 ? 'ref-status-pill--pending' : 'ref-status-pill--onboarded');

    $createdAt = (string) ($row['created_at'] ?? '');
    $createdTs = $createdAt !== '' ? strtotime($createdAt) : false;
    $dateLabel = $createdTs !== false ? date('M j, Y', $createdTs) : '';

    $detail = 'Signed up with your referral link' . ($dateLabel !== '' ? ' · ' . $dateLabel : '');
    if ($isRewarded) {
        $detail = 'Earned from their first plan payment';
    } elseif ($commissionUsd > 0) {
        $detail = 'First plan payment recorded · reward on hold';
    }

    $inviteHistory[] = [
        'name' => $displayName,
        'initial' => dash_initials_for_name($displayName),
        'detail' => $detail,
        'amount' => $commissionUsd,
        'amount_label' => $commissionUsd > 0
            ? money_format($commissionUsd, 'USD') . ($isRewarded ? '' : ' pending')
            : '',
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
    ];
}

$rewardHistory = [];
foreach ($commissions as $row) {
    $createdAt = (string) ($row['created_at'] ?? '');
    $createdTs = $createdAt !== '' ? strtotime($createdAt) : false;
    $rewardHistory[] = [
        'date_label' => $createdTs !== false ? date('M j, Y', $createdTs) : '—',
        'plan_label' => ucfirst((string) ($row['plan_slug'] ?? 'Plan')),
        'amount_label' => money_format(money_to_usd((float) ($row['commission_amount'] ?? 0), (string) ($row['currency'] ?? 'USD'))),
        'status_label' => ucfirst(str_replace('_', ' ', (string) ($row['status'] ?? ''))),
    ];
}
foreach ($payouts as $row) {
    $createdAt = (string) ($row['created_at'] ?? '');
    $createdTs = $createdAt !== '' ? strtotime($createdAt) : false;
    $rewardHistory[] = [
        'date_label' => $createdTs !== false ? date('M j, Y', $createdTs) : '—',
        'plan_label' => 'Withdrawal',
        'amount_label' => money_format(money_to_usd((float) ($row['amount'] ?? 0), (string) ($row['currency'] ?? 'NGN'))),
        'status_label' => ucfirst((string) ($row['status'] ?? '')),
    ];
}

usort($rewardHistory, static function (array $a, array $b): int {
    return strcmp((string) $b['date_label'], (string) $a['date_label']);
});
$rewardHistory = array_slice($rewardHistory, 0, 30);

$banks = [];
try {
    $banks = (new FlutterwaveService())->listBanks('NG');
} catch (Throwable $e) {
    $banks = [];
}

$canClaim = $withdrawable >= AffiliateService::MIN_WITHDRAW_USD;
$pendingUsd = (float) ($stats['pending'] ?? 0);
$hasPendingRewards = $pendingUsd >= 0.01;
$rewardHoldDays = AffiliateService::HOLD_DAYS;
$nextAvailableAt = null;
$nextAvailableTs = null;
foreach ($commissions as $row) {
    if ((string) ($row['status'] ?? '') !== 'pending') {
        continue;
    }
    $holdUntil = trim((string) ($row['hold_until'] ?? ''));
    if ($holdUntil === '') {
        continue;
    }
    $ts = strtotime($holdUntil);
    if ($ts === false) {
        continue;
    }
    if ($nextAvailableTs === null || $ts < $nextAvailableTs) {
        $nextAvailableTs = $ts;
        $nextAvailableAt = date('M j, Y', $ts);
    }
}
$hasBank = trim((string) ($affiliate['account_name'] ?? '')) !== ''
    && trim((string) ($affiliate['account_number'] ?? '')) !== '';

$success = flash_get('success');
$error = flash_get('error');

require __DIR__ . '/includes/dashboard/layout-start.php';
require __DIR__ . '/includes/dashboard/refer-content.php';
require __DIR__ . '/includes/dashboard/layout-end.php';
