<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/plans.php';

$appUrl = app_config()['app_url'];
$brandName = app_config()['brand_name'];
$pageTitle = 'Pricing — ' . $brandName;
$metaDescription = 'Lead Bird plans and prices. See what each plan gives you, compare them side by side, and read answers to common questions.';
$metaUrl = app_route('plans');
$extraStylesheets = ['contact.css', 'plans.css?v=5'];
$activeNav = 'pricing';

$plans = plans_list();
$topups = token_topup_packages_list();

require __DIR__ . '/includes/home/layout-start.php';
require __DIR__ . '/includes/pricing/public-page.php';
require __DIR__ . '/includes/home/layout-end.php';
