<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('settings'));
}

require_csrf();

$userId = (string) (new SessionAuth())->userId();
$ctx = new BusinessContext();
$business = $ctx->resolveActiveBusiness($userId);

if ($business === null) {
    flash_set('error', 'We could not find your active business.');
    redirect(app_route('settings'));
}

$ownerName = trim((string) ($_POST['owner_name'] ?? ''));
$ownerEmail = trim((string) ($_POST['owner_email'] ?? ''));
$ownerPhone = trim((string) ($_POST['owner_phone'] ?? ''));

$businessId = (string) $business['id'];

RepositoryFactory::businesses()->update($businessId, [
    'owner_name' => $ownerName !== '' ? $ownerName : null,
    'owner_email' => $ownerEmail !== '' ? $ownerEmail : null,
    'owner_phone' => $ownerPhone !== '' ? $ownerPhone : null,
]);

flash_set('success', 'Owner profile saved.');
redirect(app_route('settings'));
