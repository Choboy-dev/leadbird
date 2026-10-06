<?php

declare(strict_types=1);

/** Polled by open mail tabs so new messages appear without a manual refresh. */

require_once __DIR__ . '/bootstrap.php';

$user = Mail::requireUser();

header('Cache-Control: no-store');
json_response(['ok' => true] + Mail::inboxState((int) $user['id']));
