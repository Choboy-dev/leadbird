<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var string $brandName */
/** @var string $pageTitle */
/** @var int $obwStep */

$obwTotalSteps = 6;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e($brandName) ?></title>
    <?php
    $metaRobots = 'noindex, nofollow';
    require dirname(__DIR__) . '/brand-meta.php';
    require dirname(__DIR__) . '/brand-fonts.php';
    ?>
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/dropdown.css?v=7">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/onboarding-welcome.css?v=5">
</head>
<body class="obw-page">
    <header class="obw-top">
        <?php $logoSize = 'sm'; require dirname(__DIR__) . '/brand-logo.php'; ?>
        <a class="obw-signout" href="<?= e(app_route('auth/logout')) ?>">Sign out</a>
        <div class="obw-progress" role="progressbar" aria-label="Setup progress" aria-valuemin="1" aria-valuemax="<?= $obwTotalSteps ?>" aria-valuenow="<?= (int) $obwStep ?>">
            <span style="width: <?= round(100 * $obwStep / $obwTotalSteps) ?>%"></span>
        </div>
    </header>

    <main class="obw-main">
