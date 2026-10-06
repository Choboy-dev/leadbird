<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
require_csrf();
Mail::logout();
redirect(Mail::url('login'));
