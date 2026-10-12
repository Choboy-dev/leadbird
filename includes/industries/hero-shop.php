<?php

declare(strict_types=1);

/** @var array<string, string> $hero */
/** @var string $photoBase */
?>
<div class="chv" aria-hidden="true">
    <span class="chv-blob"></span>

    <div class="chv-browser">
        <div class="chv-bar"><i></i><i></i><i></i><span></span></div>
        <div class="chv-page">
            <div class="chv-cart">
                <svg viewBox="0 0 48 40"><path d="M3 5h7l5 22h24l5-16H13"/><path d="M17 12v13M23 12v13M29 12v13M35 12v13M14 18h28"/><circle cx="18" cy="34" r="3"/><circle cx="36" cy="34" r="3"/></svg>
            </div>
            <span class="chv-row"></span><span class="chv-row short"></span>
        </div>
    </div>

    <svg class="chv-arrow" viewBox="0 0 100 90" preserveAspectRatio="none">
        <path class="o" d="M14 80 44 46 54 56 80 22"/>
        <path class="f" d="M14 80 44 46 54 56 80 22"/>
        <path class="h" d="M71 15 89 29 90 9z"/>
    </svg>

    <svg class="chv-bag" viewBox="0 0 120 130">
        <path class="handle" d="M38 40c0-26 44-26 44 0"/>
        <path class="body" d="M14 36h92l10 90H4z"/>
        <path class="fold" d="M60 64c-24 6-36 28-32 60h62C96 92 84 70 60 64z"/>
        <circle cx="38" cy="48" r="4"/><circle cx="82" cy="48" r="4"/>
    </svg>

    <div class="chv-gift"><i></i></div>

    <svg class="chv-bubble" viewBox="0 0 50 44"><path d="M5 4h40v26H24l-9 10v-10H5z"/></svg>

    <div class="chv-frame">
        <div class="chv-chat">
            <div class="chv-contact"><i></i><b><?= e($hero['store']) ?></b><span>✓</span></div>
            <span class="chv-m out"><?= e($hero['ask']) ?></span>
            <span class="chv-m in">
                <span class="chv-prod"><img src="<?= e($photoBase . $hero['photo']) ?>" alt=""><b><?= e($hero['product']) ?><em><?= e($hero['price']) ?></em></b></span>
                <?= e($hero['reply']) ?>
                <u>Send payment details</u>
            </span>
            <span class="chv-note"><b>🔔 Alert sent to you</b><?= e($hero['alert']) ?></span>
        </div>
    </div>
</div>
