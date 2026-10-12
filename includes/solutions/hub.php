<?php

declare(strict_types=1);

/**
 * Channels-and-tools hub with Lead Bird in the middle. Needs includes/solutions/icons.php.
 *
 * @var string $brandName
 * @var string $mark Lead Bird logo <img>
 */

$hubLeft = [
    ['WhatsApp', 'whatsapp', true],
    ['Instagram', 'instagram', false],
    ['Messenger', 'messenger', false],
    ['TikTok', 'tiktok', true],
    ['Facebook', 'facebook', false],
];
$hubRight = [
    ['Catalog', 'C', 'blue', true],
    ['Knowledge', 'K', 'ink', false],
    ['Payments', '₦', 'lime', false],
    ['Owner alerts', 'A', 'red', true],
    ['Planner', 'P', 'blue', false],
];
?>
<div class="hub" aria-label="Channels and tools">
    <svg class="hub-lines" viewBox="0 0 1000 640" preserveAspectRatio="none" aria-hidden="true">
        <path d="M60 35 H320 L430 268"/>
        <path d="M940 35 H680 L570 268"/>
        <path d="M60 177.5 H310 L430 294"/>
        <path d="M940 177.5 H690 L570 294"/>
        <path d="M60 320 H940"/>
        <path d="M60 462.5 H310 L430 346"/>
        <path d="M940 462.5 H690 L570 346"/>
        <path d="M60 605 H320 L430 372"/>
        <path d="M940 605 H680 L570 372"/>
    </svg>
    <div class="hub-col left">
        <?php foreach ($hubLeft as [$name, $key, $on]): ?>
            <span class="hub-tile<?= $on ? ' on' : '' ?>"><?= sol_icon($key) ?><b><?= e($name) ?></b></span>
        <?php endforeach; ?>
    </div>
    <div class="hub-mid">
        <span class="hub-sq l1" title="Instagram"><?= sol_icon('instagram') ?></span>
        <span class="hub-sq l2 on" title="Messenger"><?= sol_icon('messenger') ?></span>
        <span class="hub-sq r1" title="WhatsApp"><?= sol_icon('whatsapp') ?></span>
        <span class="hub-sq r2" title="TikTok"><?= sol_icon('tiktok') ?></span>
        <div class="hub-core"><?= $mark ?><b><?= e($brandName) ?></b></div>
    </div>
    <div class="hub-col right">
        <?php foreach ($hubRight as [$name, $letter, $tone, $on]): ?>
            <span class="hub-tile<?= $on ? ' on' : '' ?>"><i class="hub-i <?= $tone ?>"><?= e($letter) ?></i><b><?= e($name) ?></b></span>
        <?php endforeach; ?>
    </div>
</div>
