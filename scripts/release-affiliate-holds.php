<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$result = (new CommissionService())->releaseHoldsAndFund();
echo 'Released: ' . $result['released'] . ', funded: ' . $result['funded'] . ', failed: ' . $result['failed'] . PHP_EOL;
