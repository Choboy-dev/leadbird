<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$sessionAuth = new SessionAuth();
$appUrl = app_config()['app_url'];

if (!$sessionAuth->check()) {
    redirect(app_route('login'));
}

$userId = $sessionAuth->userId();
if ($userId === null) {
    redirect(app_route('login'));
}

redirect(PostAuthRedirect::urlForUser($userId));
