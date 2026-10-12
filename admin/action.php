<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('admin'));
}

require_csrf();

$adminUserId = (string) ($adminUserId ?? '');
$action = trim((string) ($_POST['action'] ?? ''));
$redirectTo = safe_return_url($_POST['redirect'] ?? null, app_route('admin'));

try {
    switch ($action) {
        case 'grant_tokens':
            $userId = trim((string) ($_POST['user_id'] ?? ''));
            $amount = max(1, (int) ($_POST['amount'] ?? 0));
            $note = trim((string) ($_POST['note'] ?? 'Admin grant'));
            $adminService->grantTokens($adminUserId, $userId, $amount, $note);
            flash_set('success', 'Granted ' . $amount . ' tokens.');
            $redirectTo = app_route('admin/user', ['id' => $userId]);
            break;

        case 'suspend_user':
            $userId = trim((string) ($_POST['user_id'] ?? ''));
            $suspend = (string) ($_POST['suspend'] ?? '1') === '1';
            $adminService->suspendUser($adminUserId, $userId, $suspend);
            flash_set('success', $suspend ? 'User suspended.' : 'User reactivated.');
            $redirectTo = app_route('admin/user', ['id' => $userId]);
            break;

        case 'set_role':
            $userId = trim((string) ($_POST['user_id'] ?? ''));
            $role = trim((string) ($_POST['role'] ?? AdminAuth::ROLE_USER));
            $adminService->setUserRole($adminUserId, $userId, $role);
            flash_set('success', 'Role updated.');
            $redirectTo = app_route('admin/user', ['id' => $userId]);
            break;

        case 'cancel_subscription':
            $subscriptionId = trim((string) ($_POST['subscription_id'] ?? ''));
            $adminService->cancelSubscription($adminUserId, $subscriptionId);
            flash_set('success', 'Subscription cancelled.');
            $redirectTo = app_route('admin/subscriptions');
            break;

        case 'override_subscription':
            $userId = trim((string) ($_POST['user_id'] ?? ''));
            $planSlug = trim((string) ($_POST['plan_slug'] ?? ''));
            $days = max(1, (int) ($_POST['days'] ?? 30));
            $adminService->overrideSubscription($adminUserId, $userId, $planSlug, $days);
            flash_set('success', 'Subscription override applied.');
            $redirectTo = app_route('admin/user', ['id' => $userId]);
            break;

        case 'retry_job':
            $jobId = trim((string) ($_POST['job_id'] ?? ''));
            if (!$adminService->repository()->retryAutomationJob($jobId)) {
                throw new InvalidArgumentException('Job not found or not failed.');
            }
            (new AdminAuditService())->log($adminUserId, 'retry_automation_job', 'automation_queue', $jobId);
            flash_set('success', 'Job queued for retry.');
            $redirectTo = app_route('admin/automation', ['status' => 'pending']);
            break;

        case 'disconnect_channel':
            $accountId = trim((string) ($_POST['account_id'] ?? ''));
            $adminService->disconnectChannel($adminUserId, $accountId);
            flash_set('success', 'Channel disconnected.');
            $redirectTo = app_route('admin/channels');
            break;

        case 'update_dispute':
            $disputeId = trim((string) ($_POST['dispute_id'] ?? ''));
            $status = trim((string) ($_POST['status'] ?? 'open'));
            $notes = trim((string) ($_POST['admin_notes'] ?? ''));
            RepositoryFactory::walletDisputes()->updateStatus($disputeId, $status, $notes !== '' ? $notes : null);
            (new AdminAuditService())->log($adminUserId, 'update_dispute', 'wallet_dispute', $disputeId, [
                'status' => $status,
            ]);
            flash_set('success', 'Dispute updated.');
            $redirectTo = app_route('admin/disputes');
            break;

        case 'update_feature_flag':
            $key = trim((string) ($_POST['flag_key'] ?? ''));
            $value = $_POST['flag_value'] ?? '';
            if (in_array($value, ['true', 'false', '1', '0'], true)) {
                $decoded = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } else {
                $decoded = $value;
            }
            $adminService->repository()->setFeatureFlag($key, $decoded, $adminUserId);
            (new AdminAuditService())->log($adminUserId, 'update_feature_flag', 'feature_flag', $key, [
                'value' => $decoded,
            ]);
            flash_set('success', 'Feature flag saved.');
            $redirectTo = app_route('admin/feature-flags');
            break;

        case 'approve_payout':
            $payoutId = trim((string) ($_POST['payout_id'] ?? ''));
            $outcome = (new PayoutService())->approve($payoutId, $adminUserId);
            (new AdminAuditService())->log($adminUserId, 'approve_affiliate_payout', 'affiliate_payout', $payoutId, [
                'ok' => $outcome['ok'],
            ]);
            if (!$outcome['ok']) {
                throw new RuntimeException((string) ($outcome['error'] ?? 'Could not send this payout.'));
            }
            flash_set('success', 'Payout sent to Flutterwave. It will show as paid once the bank confirms.');
            $redirectTo = app_route('admin/affiliates');
            break;

        case 'reject_payout':
            $payoutId = trim((string) ($_POST['payout_id'] ?? ''));
            $reason = mb_substr(trim((string) ($_POST['reason'] ?? '')), 0, 255);
            $outcome = (new PayoutService())->reject($payoutId, $adminUserId, $reason);
            if (!$outcome['ok']) {
                throw new RuntimeException((string) ($outcome['error'] ?? 'Could not decline this payout.'));
            }
            (new AdminAuditService())->log($adminUserId, 'reject_affiliate_payout', 'affiliate_payout', $payoutId, [
                'reason' => $reason,
            ]);
            flash_set('success', 'Payout declined. The money is back in the partner\'s balance.');
            $redirectTo = app_route('admin/affiliates');
            break;

        case 'clawback_commission':
            $commissionId = trim((string) ($_POST['commission_id'] ?? ''));
            if (!RepositoryFactory::affiliateCommissions()->clawback($commissionId)) {
                throw new RuntimeException('This commission can no longer be cancelled.');
            }
            (new AdminAuditService())->log($adminUserId, 'clawback_affiliate_commission', 'affiliate_commission', $commissionId);
            flash_set('success', 'Commission cancelled.');
            $redirectTo = app_route('admin/affiliates');
            break;

        case 'impersonate':
            $userId = trim((string) ($_POST['user_id'] ?? ''));
            $adminService->startImpersonation($adminUserId, $userId);
            redirect(app_route('dashboard'));
            break;

        default:
            throw new InvalidArgumentException('Unknown action.');
    }
} catch (Throwable $e) {
    flash_set('error', $e->getMessage());
}

redirect($redirectTo);
