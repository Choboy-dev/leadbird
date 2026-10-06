<?php

declare(strict_types=1);

/** @var array{id: string, email: string, name?: ?string}|null $user */

require_once __DIR__ . '/../require_admin.php';

$appUrl = app_config()['app_url'];
$brandName = app_config()['brand_name'] ?? 'Lead Bird';
$adminService = new AdminService();
$adminUser = (new SessionAuth())->user();
$adminUserId = $adminUser['id'] ?? null;
