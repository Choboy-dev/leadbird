<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$appUrl = app_config()['app_url'];
$pageTitle = 'Delete Your Data — ' . app_config()['brand_name'];
$metaDescription = 'How to delete the Facebook, Instagram, and WhatsApp data Lead Bird holds for you, and how to check the status of a deletion request.';
$metaUrl = app_route('data-deletion');
$extraStylesheets = ['contact.css', 'legal.css'];
$activeNav = null;

$requestedCode = trim((string) ($_GET['code'] ?? ''));
$deletionRequest = $requestedCode === ''
    ? null
    : (new MetaDataDeletionService())->findStatus($requestedCode);

require __DIR__ . '/includes/home/layout-start.php';
require __DIR__ . '/includes/legal/data-deletion.php';
require __DIR__ . '/includes/home/layout-end.php';
