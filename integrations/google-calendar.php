<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/require_dashboard.php';

$userId = (new SessionAuth())->userId();
$business = $userId !== null ? (new BusinessContext())->resolveActiveBusiness((string) $userId) : null;
$businessId = $business !== null ? (string) $business['id'] : null;
if ($businessId === null) {
    redirect(app_route('dashboard'));
}

$returnUrl = trim((string) ($_GET['return'] ?? app_route('onboarding/offerings')));
if (!str_starts_with($returnUrl, app_config()['app_url'])) {
    $returnUrl = app_route('onboarding/offerings');
}

try {
    $url = (new GoogleCalendarConnectService())->beginConnect($businessId, $returnUrl);
    redirect($url);
} catch (Throwable $exception) {
    flash_set('error', $exception->getMessage());
    redirect($returnUrl);
}
