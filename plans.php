<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/plans.php';

$appUrl = app_config()['app_url'];
$brandName = app_config()['brand_name'];
$pageTitle = 'Pricing: Plans for every stage of growth — ' . $brandName;
$metaDescription = 'Lead Bird pricing: no setup fees, simple plans for every size of shop, and two months free when you pay yearly.';
$metaUrl = app_route('plans');
$extraStylesheets = ['pricing-page.css?v=3'];
$activeNav = 'pricing';

$plans = plans_list();
$topups = token_topup_packages_list();

require __DIR__ . '/includes/home/layout-start.php';
require __DIR__ . '/includes/pricing/public-page.php';
require __DIR__ . '/includes/home/layout-end.php';
