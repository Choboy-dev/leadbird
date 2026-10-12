<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

$adminNav = 'affiliates';
$pageTitle = 'Affiliates — Admin';
$pageHeading = 'Affiliates & payouts';
$success = flash_get('success');
$error = flash_get('error');
$page = admin_page((int) ($_GET['page'] ?? 1));
$search = trim((string) ($_GET['q'] ?? ''));
$result = $adminService->repository()->listAffiliates($page, 25, $search !== '' ? $search : null);
$baseUrl = app_route('admin/affiliates', array_filter(['q' => $search ?: null]));
$payoutRows = RepositoryFactory::affiliatePayouts()->listForAdmin(30);
$openCommissions = RepositoryFactory::affiliateCommissions()->listOpenForAdmin(50);
$actionUrl = app_route('admin/action');
$payoutStatusLabels = [
    PayoutService::STATUS_PENDING_REVIEW => 'Needs review',
    PayoutService::STATUS_PROCESSING => 'Sending',
    PayoutService::STATUS_SUCCESSFUL => 'Paid',
    PayoutService::STATUS_FAILED => 'Failed',
    PayoutService::STATUS_REJECTED => 'Declined',
];

require dirname(__DIR__) . '/includes/admin/layout-start.php';
admin_render_flash($success, $error);
?>
<div class="admin-panel">
    <h2 class="admin-panel-title font-heading">Withdrawal requests</h2>
    <p class="font-sans" style="font-size:0.875rem;color:rgb(0 0 0 / 0.58)">Nothing leaves your Flutterwave balance until you approve. Declined or failed requests go back to the partner's balance.</p>
</div>
<?php if ($payoutRows === []): ?>
    <?php admin_render_empty('No withdrawal requests yet.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['Partner', 'Amount', 'Bank account', 'Status', 'Requested', '']); ?>
    <?php foreach ($payoutRows as $row): ?>
        <?php $payoutStatus = (string) ($row['status'] ?? ''); ?>
        <tr>
            <td><?= e((string) ($row['affiliate_name'] ?? '')) ?><br><span style="font-size:0.8125rem;color:rgb(0 0 0 / 0.55)"><?= e((string) ($row['affiliate_email'] ?? '')) ?> · <?= e((string) ($row['affiliate_code'] ?? '')) ?></span></td>
            <td>
                <?= isset($row['amount_usd']) ? e(money_format((float) $row['amount_usd'], 'USD')) : '—' ?>
                <br><span style="font-size:0.8125rem;color:rgb(0 0 0 / 0.55)"><?= e(money_format((float) ($row['amount'] ?? 0), 'NGN')) ?></span>
            </td>
            <td><?= e((string) ($row['account_name'] ?? '')) ?><br><span style="font-size:0.8125rem;color:rgb(0 0 0 / 0.55)"><?= e((string) ($row['bank_name'] ?? '')) ?> · <?= e((string) ($row['account_number'] ?? '')) ?></span></td>
            <td>
                <span class="<?= admin_status_class($payoutStatus) ?>"><?= e($payoutStatusLabels[$payoutStatus] ?? $payoutStatus) ?></span>
                <?php if (trim((string) ($row['failure_reason'] ?? '')) !== ''): ?>
                    <br><span style="font-size:0.8125rem;color:rgb(0 0 0 / 0.55)"><?= e((string) $row['failure_reason']) ?></span>
                <?php endif; ?>
            </td>
            <td><?= e(admin_format_datetime((string) ($row['created_at'] ?? ''))) ?></td>
            <td>
                <?php if ($payoutStatus === PayoutService::STATUS_PENDING_REVIEW): ?>
                    <div class="admin-actions">
                        <form method="post" action="<?= e($actionUrl) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="approve_payout">
                            <input type="hidden" name="payout_id" value="<?= e((string) $row['id']) ?>">
                            <button type="submit" class="dash-btn" data-admin-confirm="Flutterwave will send <?= e(money_format((float) ($row['amount'] ?? 0), 'NGN')) ?> to <?= e((string) ($row['account_name'] ?? '')) ?>. The final naira amount uses today's rate." data-admin-confirm-title="Send this payout?" data-admin-confirm-label="Send money" data-admin-confirm-tone="default">Approve</button>
                        </form>
                        <form method="post" action="<?= e($actionUrl) ?>" class="admin-form-card" style="padding:0.65rem">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="reject_payout">
                            <input type="hidden" name="payout_id" value="<?= e((string) $row['id']) ?>">
                            <input type="text" name="reason" maxlength="255" placeholder="Reason (internal note)" aria-label="Reason for declining">
                            <button type="submit" class="dash-btn dash-btn--ghost" style="margin-top:0.5rem" data-admin-confirm="The money goes back to the partner's balance." data-admin-confirm-title="Decline this payout?" data-admin-confirm-label="Decline" data-admin-confirm-tone="danger">Decline</button>
                        </form>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
<?php endif; ?>

<div class="admin-panel">
    <h2 class="admin-panel-title font-heading">Open commissions</h2>
    <p class="font-sans" style="font-size:0.875rem;color:rgb(0 0 0 / 0.58)">If you refund a customer, cancel the commission their partner earned so it can't be withdrawn.</p>
</div>
<?php if ($openCommissions === []): ?>
    <?php admin_render_empty('No open commissions.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['Partner', 'Customer', 'Commission', 'Status', 'Available from', '']); ?>
    <?php foreach ($openCommissions as $row): ?>
        <tr>
            <td><?= e((string) ($row['affiliate_name'] ?? '')) ?><br><span style="font-size:0.8125rem;color:rgb(0 0 0 / 0.55)"><?= e((string) ($row['affiliate_code'] ?? '')) ?></span></td>
            <td><?= e((string) ($row['referred_email'] ?? '—')) ?></td>
            <td><?= e(admin_format_money((float) ($row['commission_amount'] ?? 0), (string) ($row['currency'] ?? 'USD'))) ?></td>
            <td><span class="<?= admin_status_class((string) ($row['status'] ?? '')) ?>"><?= e((string) ($row['status'] ?? '') === 'pending' ? 'On hold' : 'Available') ?></span></td>
            <td><?= e(admin_format_datetime((string) ($row['available_at'] ?? $row['hold_until'] ?? ''))) ?></td>
            <td>
                <form method="post" action="<?= e($actionUrl) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="clawback_commission">
                    <input type="hidden" name="commission_id" value="<?= e((string) $row['id']) ?>">
                    <button type="submit" class="dash-btn dash-btn--ghost" data-admin-confirm="The partner will no longer be able to withdraw this commission." data-admin-confirm-title="Cancel this commission?" data-admin-confirm-label="Cancel commission" data-admin-confirm-tone="danger">Cancel</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
<?php endif; ?>

<div class="admin-panel">
    <h2 class="admin-panel-title font-heading">Partners</h2>
</div>
<div class="admin-toolbar"><?php admin_toolbar_search('Search code, name, or email…', $search); ?></div>
<?php if ($result['rows'] === []): ?>
    <?php admin_render_empty('No affiliates found.'); ?>
<?php else: ?>
    <?php admin_render_table_start(['Affiliate', 'Code', 'Status', 'Commissions', 'Paid total', 'Joined']); ?>
    <?php foreach ($result['rows'] as $row): ?>
        <tr>
            <td><?= e((string) ($row['name'] ?? '')) ?><br><span style="font-size:0.8125rem;color:rgb(0 0 0 / 0.55)"><?= e((string) ($row['email'] ?? '')) ?></span></td>
            <td><?= e((string) ($row['code'] ?? '')) ?></td>
            <td><span class="<?= admin_status_class((string) ($row['status'] ?? '')) ?>"><?= e((string) ($row['status'] ?? '')) ?></span></td>
            <td><?= e((string) ($row['commission_count'] ?? '0')) ?></td>
            <td><?= e(admin_format_money((float) ($row['paid_total'] ?? 0))) ?></td>
            <td><?= e(admin_format_datetime((string) ($row['created_at'] ?? ''))) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php admin_render_table_end(); ?>
    <?php admin_render_pagination($baseUrl, $page, $result['total'], 25); ?>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin/layout-end.php'; ?>
