<?php

declare(strict_types=1);

/** @var array<string, string> $hero */
?>
<div class="bhv" aria-hidden="true">
    <i class="bhv-sq a"></i><i class="bhv-sq b"></i><i class="bhv-sq c"></i><i class="bhv-sq d"></i><i class="bhv-sq e"></i><i class="bhv-sq f"></i>
    <svg class="bhv-plus" viewBox="0 0 40 40"><path d="M10 4v12M4 10h12M28 6v12M22 12h12M16 22v12M10 28h12"/></svg>

    <div class="bhv-phone">
        <div class="bhv-head"><i></i><b><?= e($hero['store']) ?></b><span>✓</span></div>
        <div class="bhv-msgs">
            <span class="bhv-m out"><?= e($hero['ask']) ?></span>
            <span class="bhv-m in"><?= e($hero['reply']) ?></span>
            <span class="bhv-slots">
                <?php foreach (explode('|', $hero['slots']) as $slot): ?>
                    <u><?= e($slot) ?></u>
                <?php endforeach; ?>
            </span>
            <span class="bhv-m out"><?= e($hero['pick']) ?></span>
            <span class="bhv-m in"><?= e($hero['booked']) ?></span>
        </div>
        <span class="bhv-input"><i></i></span>
    </div>

    <?php if (($hero['icon'] ?? 'heart') === 'ticket'): ?>
        <span class="bhv-heart case"><svg viewBox="0 0 24 24"><path d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-2a2 2 0 0 0 0-4z"/><path d="M14 6.5v11" stroke-dasharray="2 2"/></svg></span>
    <?php elseif (($hero['icon'] ?? 'heart') === 'case'): ?>
        <span class="bhv-heart case"><svg viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8.5 7V5a1.5 1.5 0 0 1 1.5-1.5h4A1.5 1.5 0 0 1 15.5 5v2M3 12.5h18M10.5 12.5v2h3v-2"/></svg></span>
    <?php else: ?>
        <span class="bhv-heart"><svg viewBox="0 0 24 24"><path d="M12 20s-8-5-8-11a4.5 4.5 0 0 1 8-2.5A4.5 4.5 0 0 1 20 9c0 6-8 11-8 11z"/><path d="M5 12h4l1.5-3 2 5 1.5-2H19"/></svg></span>
    <?php endif; ?>
    <span class="bhv-ask"><em></em><em class="s"></em><b>?</b></span>
    <svg class="bhv-bubble" viewBox="0 0 50 44"><path d="M5 4h40v26H30l-6 9-6-9H5z"/></svg>

    <div class="bhv-cal">
        <span class="bhv-cal-top"><i></i><i></i></span>
        <b><?= e($hero['day']) ?></b>
        <small><?= e($hero['time']) ?></small>
        <em><?= e($hero['badge'] ?? 'Booked ✓') ?></em>
    </div>

    <span class="bhv-note"><b>🔔 Alert sent to you</b><?= e($hero['alert']) ?></span>
</div>
