<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';

$userId = (string) (new SessionAuth())->userId();
$appUrl = app_config()['app_url'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId === '') {
    redirect(app_route('businesses'));
}

require_csrf();

$businessId = trim((string) ($_POST['business_id'] ?? ''));

$business = $businessId !== '' ? RepositoryFactory::businesses()->findById($businessId) : null;

if ($business === null || (string) ($business['user_id'] ?? '') !== $userId) {
    flash_set('error', 'Business not found.');
    redirect(app_route('businesses'));
}

try {
    (new BusinessDeletionService())->deleteForUser($userId, $businessId);
} catch (Throwable $e) {
    error_log('[BusinessDeletion] Failed for ' . $businessId . ': ' . $e->getMessage());
    flash_set('error', 'We could not delete that business. Please try again or contact support.');
    redirect(app_route('businesses'));
}

$remaining = RepositoryFactory::businesses()->listByUserId($userId);
if ($remaining === []) {
    redirect(app_route('businesses/start'));
}

redirect(app_route('businesses'));
