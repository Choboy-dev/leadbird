<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/require_dashboard.php';

$code = trim((string) ($_GET['code'] ?? ''));
$state = trim((string) ($_GET['state'] ?? ''));
$returnUrl = app_route('onboarding/offerings');

if ($code === '' || $state === '') {
    flash_set('error', 'Google Calendar connection was cancelled.');
    redirect($returnUrl);
}

try {
    $result = (new GoogleCalendarConnectService())->handleCallback($code, $state);
    $returnUrl = trim((string) ($result['return_url'] ?? $returnUrl));
    flash_set('success', 'Google Calendar connected. Consultations can now be booked automatically.');
} catch (Throwable $exception) {
    flash_set('error', 'Could not connect Google Calendar: ' . $exception->getMessage());
}

redirect($returnUrl);
