<?php

declare(strict_types=1);

/** @var array{title: string, description: string, min_plan: string} $lockedFeature */
/** @var string $appUrl */
?>
<section class="dash-locked">
    <div class="dash-locked-icon" aria-hidden="true">🔒</div>
    <h2 class="dash-page-title font-heading"><?= e($lockedFeature['title']) ?></h2>
    <p class="dash-page-lead"><?= e($lockedFeature['description']) ?></p>
    <p class="dash-locked-plan">Available on the <strong><?= e($lockedFeature['min_plan']) ?></strong> plan and above.</p>
    <a href="<?= e(app_route('billing')) ?>" class="dash-btn dash-btn--primary">Upgrade to unlock</a>
</section>
