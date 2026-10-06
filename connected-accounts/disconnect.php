<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

$userId = (new SessionAuth())->userId();
$ctx = new BusinessContext();
$business = $userId !== null ? $ctx->getActiveBusiness($userId) : null;
$appUrl = app_config()['app_url'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $business === null) {
    redirect(app_route('connected-accounts'));
}

require_csrf();

$accountId = trim((string) ($_POST['account_id'] ?? ''));
$account = $accountId !== '' ? RepositoryFactory::connectedAccounts()->findById($accountId) : null;

if ($account === null || (string) ($account['business_id'] ?? '') !== (string) $business['id']) {
    flash_set('error', 'Account not found.');
    redirect(app_route('connected-accounts'));
}

$platform = (string) ($account['platform'] ?? '');

if ($platform === TikTokConnectService::PLATFORM) {
    (new TikTokConnectService())->disconnectAccount($account);
} else {
    (new MetaConnectService())->disconnectAccount($account);
}

flash_set('success', dash_platform_label($platform) . ' disconnected.');
redirect(app_route('connected-accounts'));
