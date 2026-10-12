<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/helpers.php';

if (!PlanAccess::allows($planSlug, 'refer_earn')) {
    flash_set('error', 'Refer & Earn is not available on your plan.');
    redirect(app_route('dashboard'));
}

$pageTitle = 'Refer & Earn — ' . $brandName;
$dashboardNav = 'refer';
$pageHeading = 'Refer & Earn';
$extraStylesheets = ['dashboard-refer.css?v=12'];
$extraScripts = ['dashboard-refer.js?v=1'];

$affiliate = (new AffiliateService())->ensureForUser((string) $userId, $user ?? []);
$referReturn = 'refer';
require __DIR__ . '/includes/refer/view-data.php';

$success = flash_get('success');
$error = flash_get('error');

require __DIR__ . '/includes/dashboard/layout-start.php';
require __DIR__ . '/includes/dashboard/refer-content.php';
require __DIR__ . '/includes/dashboard/layout-end.php';
