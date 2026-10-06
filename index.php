<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

// Leaving the pricing/signup funnel for the marketing home clears checkout intent,
// so Sign in / Sign up from here stay plain unless they pick a plan again.
PlanSelection::clear();

$appUrl = app_config()['app_url'];
$pageTitle = 'Lead Bird — Never Miss Another DM';
$metaTitle = 'Lead Bird — Never Miss Another DM';
$metaDescription = 'Your AI salesgirl replies to WhatsApp, Instagram, and Messenger DMs automatically — so you never miss another sale.';
$metaUrl = $appUrl . '/';

require __DIR__ . '/includes/home/layout-start.php';
require __DIR__ . '/includes/home/landing.php';
require __DIR__ . '/includes/home/layout-end.php';
