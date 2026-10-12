<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$appUrl = app_config()['app_url'];
$pageTitle = 'Terms & Conditions — ' . app_config()['brand_name'];
$metaDescription = 'Lead Bird terms of use — the rules and conditions for using our AI salesgirl for WhatsApp, Instagram, and Messenger.';
$metaUrl = app_route('terms');
$extraStylesheets = ['contact.css', 'legal.css', 'privacy-policy.css?v=3'];
$activeNav = null;

require __DIR__ . '/includes/home/layout-start.php';
require __DIR__ . '/includes/legal/terms.php';
require __DIR__ . '/includes/home/layout-end.php';
