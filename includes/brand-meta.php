<?php

declare(strict_types=1);

$config = app_config();
$appUrl = rtrim((string) ($config['app_url'] ?? ''), '/');
$brandName = (string) ($config['brand_name'] ?? 'Lead Bird');

$defaultDescription = 'Your AI salesgirl that replies to WhatsApp, Instagram, and Messenger DMs automatically — so you never miss another sale.';

$documentTitle = (string) ($pageTitle ?? $brandName);
$metaTitle = (string) ($metaTitle ?? $documentTitle);
$metaDescription = (string) ($metaDescription ?? $defaultDescription);
$metaType = (string) ($metaType ?? 'website');
$metaRobots = (string) ($metaRobots ?? 'index, follow');

$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$metaUrl = (string) ($metaUrl ?? ($appUrl . $requestUri));
$metaImage = (string) ($metaImage ?? ($appUrl . '/assets/images/leadbird-logo.png'));
$metaImageWidth = (int) ($metaImageWidth ?? 250);
$metaImageHeight = (int) ($metaImageHeight ?? 150);
$metaImageAlt = (string) ($metaImageAlt ?? $brandName . ' logo');

$faviconUrl = $appUrl . '/assets/images/leadbird-favicon.png';
$themeColor = '#0038ff';
?>
<meta name="description" content="<?= e($metaDescription) ?>">
<meta name="author" content="<?= e($brandName) ?>">
<meta name="robots" content="<?= e($metaRobots) ?>">
<meta name="theme-color" content="<?= e($themeColor) ?>">
<link rel="canonical" href="<?= e($metaUrl) ?>">
<link rel="icon" href="<?= e($faviconUrl) ?>" type="image/png" sizes="any">
<link rel="icon" href="<?= e($faviconUrl) ?>" type="image/png" sizes="32x32">
<link rel="icon" href="<?= e($faviconUrl) ?>" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="<?= e($faviconUrl) ?>" sizes="180x180">
<link rel="shortcut icon" href="<?= e($faviconUrl) ?>" type="image/png">
<meta property="og:type" content="<?= e($metaType) ?>">
<meta property="og:site_name" content="<?= e($brandName) ?>">
<meta property="og:title" content="<?= e($metaTitle) ?>">
<meta property="og:description" content="<?= e($metaDescription) ?>">
<meta property="og:url" content="<?= e($metaUrl) ?>">
<meta property="og:image" content="<?= e($metaImage) ?>">
<meta property="og:image:secure_url" content="<?= e($metaImage) ?>">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="<?= (int) $metaImageWidth ?>">
<meta property="og:image:height" content="<?= (int) $metaImageHeight ?>">
<meta property="og:image:alt" content="<?= e($metaImageAlt) ?>">
<meta property="og:locale" content="en_NG">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($metaTitle) ?>">
<meta name="twitter:description" content="<?= e($metaDescription) ?>">
<meta name="twitter:image" content="<?= e($metaImage) ?>">
<meta name="twitter:image:alt" content="<?= e($metaImageAlt) ?>">
