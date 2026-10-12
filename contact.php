<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$appUrl = app_config()['app_url'];
$pageTitle = 'Contact — ' . app_config()['brand_name'];
$metaDescription = 'Get in touch with the Lead Bird team. Questions about your AI salesgirl, pricing, or onboarding — we are here to help.';
$metaUrl = app_route('contact');
$extraStylesheets = ['dashboard-help.css?v=7'];
$activeNav = 'contact';
$error = flash_get('error');
$success = flash_get('success');
$_SESSION['contact_form_at'] = time();

require __DIR__ . '/includes/home/layout-start.php';
require __DIR__ . '/includes/home/marketing-header.php';
require __DIR__ . '/includes/contact/page.php';
require __DIR__ . '/includes/home/footer.php';
require __DIR__ . '/includes/home/layout-end.php';
