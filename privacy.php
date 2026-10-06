<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$appUrl = app_config()['app_url'];
$pageTitle = 'Privacy Policy — ' . app_config()['brand_name'];
$metaDescription = 'Lead Bird privacy policy — how we collect, use, and protect your data when you use our AI salesgirl platform.';
$metaUrl = app_route('privacy');
$extraStylesheets = ['contact.css', 'legal.css'];
$activeNav = null;

require __DIR__ . '/includes/home/layout-start.php';
require __DIR__ . '/includes/legal/privacy.php';
require __DIR__ . '/includes/home/layout-end.php';
