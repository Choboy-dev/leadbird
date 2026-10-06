<?php

declare(strict_types=1);

$faviconUrl = rtrim((string) (app_config()['app_url'] ?? ''), '/') . '/assets/images/leadbird-favicon.png?v=1';
?>
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#0038FF">
<link rel="icon" href="<?= e($faviconUrl) ?>" type="image/png" sizes="any">
<link rel="icon" href="<?= e($faviconUrl) ?>" type="image/png" sizes="32x32">
<link rel="icon" href="<?= e($faviconUrl) ?>" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="<?= e($faviconUrl) ?>" sizes="180x180">
<link rel="shortcut icon" href="<?= e($faviconUrl) ?>" type="image/png">
