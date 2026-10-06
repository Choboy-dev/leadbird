<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Invalid request.'], 405);
}

require_csrf();

$sessionAuth = new SessionAuth();
if (!$sessionAuth->check()) {
    json_response(['ok' => false, 'error' => 'session_expired'], 401);
}

$sessionAuth->touchActivity();
$config = SessionAuth::timeoutConfig();

json_response([
    'ok' => true,
    'idle_timeout_seconds' => $config['idle_timeout_minutes'] * 60,
]);
