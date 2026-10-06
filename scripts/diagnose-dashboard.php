<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

echo "bootstrap OK\n";

if (!class_exists('PlanAccess')) {
    echo "MISSING: PlanAccess\n";
}

try {
    require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';
    echo "helpers OK\n";
    $fn = 'dash_token_ring_offset';
    if (!function_exists($fn)) {
        echo "MISSING: dash_token_ring_offset\n";
    }
    $tokenService = new TokenService();
    echo "TokenService OK\n";
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n";
}
