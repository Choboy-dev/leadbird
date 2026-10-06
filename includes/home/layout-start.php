<?php

declare(strict_types=1);

/** @var string $appUrl */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Lead Bird') ?></title>
    <?php require __DIR__ . '/../brand-meta.php'; ?>
    <?php require __DIR__ . '/../brand-fonts.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Google Sans Flex"', '"Google Sans"', 'sans-serif'],
                        heading: ['"Arial Black"', 'Impact', 'sans-serif'],
                        body: ['"Google Sans Flex"', '"Google Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            blue: 'var(--brand-blue)',
                            'blue-dark': 'var(--brand-blue-dark)',
                            lime: 'var(--brand-lime)',
                            'lime-hover': 'var(--brand-lime-hover)',
                            red: 'var(--brand-red)',
                            surface: 'var(--brand-surface)',
                            avatar: 'var(--brand-avatar)',
                            instagram: {
                                purple: 'var(--brand-instagram-purple)',
                                red: 'var(--brand-instagram-red)',
                                orange: 'var(--brand-instagram-orange)',
                            },
                            whatsapp: {
                                bg: 'var(--brand-whatsapp-bg)',
                                DEFAULT: 'var(--brand-whatsapp)',
                            },
                            'instagram-icon': {
                                bg: 'var(--brand-instagram-icon-bg)',
                                DEFAULT: 'var(--brand-instagram-icon)',
                            },
                            tiktok: {
                                bg: 'var(--brand-tiktok-icon-bg)',
                                DEFAULT: 'var(--brand-tiktok-icon)',
                            },
                        },
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/landing.css?v=8">
    <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/pricing-carousel.css?v=4">
    <?php foreach ($extraStylesheets ?? [] as $stylesheet): ?>
        <link rel="stylesheet" href="<?= e($appUrl) ?>/assets/css/<?= e($stylesheet) ?>">
    <?php endforeach; ?>
</head>
<body class="font-sans antialiased">
