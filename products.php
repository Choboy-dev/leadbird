<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$userId = (new SessionAuth())->userId();
if ($userId === null) {
    redirect(app_route('login'));
}

redirect(app_route('offers'));
