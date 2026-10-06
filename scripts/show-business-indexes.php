<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_cli();

$db = Database::connection();
foreach ($db->query('SHOW INDEX FROM businesses') as $row) {
    echo json_encode($row) . "\n";
}
