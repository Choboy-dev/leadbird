<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$result = (new CommissionService())->releaseHolds();
echo 'Released: ' . $result['released'] . PHP_EOL;
