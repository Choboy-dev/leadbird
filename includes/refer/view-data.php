<?php

declare(strict_types=1);

/**
 * Builds the variables used by includes/dashboard/refer-content.php.
 *
 * @var array<string, mixed> $affiliate
 */

$affiliateService = new AffiliateService();
$affiliateId = (string) ($affiliate['id'] ?? '');
$stats = $affiliateService->dashboardStats($affiliateId);
$referralLink = $affiliateService->referralUrl((string) ($affiliate['code']));
$withdrawable = $affiliateService->withdrawableBalance($affiliateId);
$withdrawingUsd = (float) ($stats['withdrawing'] ?? 0);
$totalBalance = $withdrawable + (float) ($stats['pending'] ?? 0) + $withdrawingUsd;
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
    $isRewarded = in_array($commissionStatus, ['available', 'withdrawing', 'paid_out'], true) && $commissionUsd > 0;
    $isCancelled = $commissionStatus === 'clawed_back';
    $statusLabel = $isCancelled ? 'Cancelled' : ($isRewarded ? 'Rewarded' : ($commissionUsd > 0 ? 'Pending' : 'Onboarded'));
    $statusClass = $isRewarded
        ? 'ref-status-pill--rewarded'
        : ($commissionUsd > 0 && !$isCancelled ? 'ref-status-pill--pending' : 'ref-status-pill--onboarded');

    $createdAt = (string) ($row['created_at'] ?? '');
    $createdTs = $createdAt !== '' ? strtotime($createdAt) : false;
    $dateLabel = $createdTs !== false ? date('M j, Y', $createdTs) : '';

    $detail = 'Signed up with your referral link' . ($dateLabel !== '' ? ' · ' . $dateLabel : '');
    if ($isCancelled) {
        $detail = 'Reward cancelled because their payment was refunded';
    } elseif ($isRewarded) {
        $detail = 'Earned from their first plan payment';
    } elseif ($commissionUsd > 0) {
        $detail = 'First plan payment recorded · reward on hold';
    }

    $inviteHistory[] = [
        'name' => $displayName,
        'initial' => dash_initials_for_name($displayName),
        'detail' => $detail,
        'amount' => $isCancelled ? 0.0 : $commissionUsd,
        'amount_label' => $commissionUsd > 0 && !$isCancelled
            ? money_format($commissionUsd, 'USD') . ($isRewarded ? '' : ' pending')
            : '',
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
    ];
}

$commissionStatusLabels = [
    'pending' => 'On hold',
    'available' => 'Available',
    'withdrawing' => 'Withdrawal requested',
    'paid_out' => 'Paid',
    'clawed_back' => 'Cancelled',
];
$payoutStatusLabels = [
    PayoutService::STATUS_PENDING_REVIEW => 'Under review',
    PayoutService::STATUS_PROCESSING => 'Sending to bank',
    PayoutService::STATUS_SUCCESSFUL => 'Paid',
    PayoutService::STATUS_FAILED => 'Failed, returned to balance',
    PayoutService::STATUS_REJECTED => 'Declined, returned to balance',
];

$rewardHistory = [];
foreach ($commissions as $row) {
    $createdAt = (string) ($row['created_at'] ?? '');
    $createdTs = $createdAt !== '' ? strtotime($createdAt) : false;
    $status = (string) ($row['status'] ?? '');
    $rewardHistory[] = [
        'sort' => $createdTs !== false ? $createdTs : 0,
        'date_label' => $createdTs !== false ? date('M j, Y', $createdTs) : '—',
        'plan_label' => ucfirst((string) ($row['plan_slug'] ?? 'Plan')),
        'amount_label' => money_format(money_to_usd((float) ($row['commission_amount'] ?? 0), (string) ($row['currency'] ?? 'USD'))),
        'status_label' => $commissionStatusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)),
    ];
}
foreach ($payouts as $row) {
    $createdAt = (string) ($row['created_at'] ?? '');
    $createdTs = $createdAt !== '' ? strtotime($createdAt) : false;
    $status = (string) ($row['status'] ?? '');
    $amountUsd = ($row['amount_usd'] ?? null) !== null
        ? (float) $row['amount_usd']
        : money_to_usd((float) ($row['amount'] ?? 0), (string) ($row['currency'] ?? 'NGN'));
    $rewardHistory[] = [
        'sort' => $createdTs !== false ? $createdTs : 0,
        'date_label' => $createdTs !== false ? date('M j, Y', $createdTs) : '—',
        'plan_label' => 'Withdrawal',
        'amount_label' => money_format($amountUsd),
        'status_label' => $payoutStatusLabels[$status] ?? ucfirst($status),
    ];
}

usort($rewardHistory, static fn (array $a, array $b): int => $b['sort'] <=> $a['sort']);
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
