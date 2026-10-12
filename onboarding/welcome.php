<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/onboarding/v2-guard.php';

$firstName = trim(explode(' ', trim((string) ($user['name'] ?? '')))[0] ?? '');
$savedName = (string) ($business['business_name'] ?? '');
$old = [
    'business_name' => $savedName === 'New business' ? '' : $savedName,
    'industry' => (string) ($business['industry'] ?? ''),
    'website' => (string) ($business['website'] ?? ''),
    'owner_role' => (string) ($business['owner_role'] ?? ''),
    'team_size' => (string) ($business['team_size'] ?? ''),
];
$error = flash_get('error');

$pageTitle = 'Welcome';
$obwStep = 1;
require dirname(__DIR__) . '/includes/onboarding/v2-start.php';
?>
        <form class="obw-form" method="post" action="<?= e(app_route('onboarding/save-welcome')) ?>" data-obw-form novalidate>
            <?= csrf_field() ?>

            <p class="obw-hello">Hello<?php if ($firstName !== ''): ?>, <span><?= e($firstName) ?></span><?php endif; ?> 👋</p>
            <h1 class="obw-title">Welcome to <span><?= e($brandName) ?>!</span></h1>
            <p class="obw-lead">Help us set up <?= e($brandName) ?> for your business</p>

            <?php if ($error): ?>
                <div class="obw-alert" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="obw-field">
                <label class="obw-label" for="obw-business-name">What is your business called?</label>
                <input class="obw-input" id="obw-business-name" name="business_name" type="text" maxlength="120" autocomplete="organization" placeholder="e.g. Tolu’s Closet" value="<?= e($old['business_name']) ?>" data-obw-required>
            </div>

            <fieldset class="obw-field">
                <legend class="obw-label">Confirm your industry</legend>
                <div class="obw-chips">
                    <?php foreach (OnboardingService::INDUSTRIES as $value => $label): ?>
                        <label class="obw-chip">
                            <input type="radio" name="industry" value="<?= e($value) ?>"<?= $old['industry'] === $value ? ' checked' : '' ?> data-obw-required>
                            <span><?= e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <div class="obw-field">
                <label class="obw-label" for="obw-website">Confirm your website <small>(optional)</small></label>
                <input class="obw-input" id="obw-website" name="website" type="text" inputmode="url" maxlength="255" autocomplete="url" placeholder="Enter your website or Instagram link" value="<?= e($old['website']) ?>">
                <p class="obw-hint">Hannah can learn about your business from it later.</p>
            </div>

            <div class="obw-field">
                <label class="obw-label" for="obw-role">What best describes you?</label>
                <select class="obw-select" id="obw-role" name="owner_role" data-obw-required>
                    <option value="">Select an option</option>
                    <?php foreach (OnboardingService::OWNER_ROLES as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= $old['owner_role'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <fieldset class="obw-field">
                <legend class="obw-label">How big is your team?</legend>
                <div class="obw-chips">
                    <?php foreach (OnboardingService::TEAM_SIZES as $value => $label): ?>
                        <label class="obw-chip">
                            <input type="radio" name="team_size" value="<?= e($value) ?>"<?= $old['team_size'] === $value ? ' checked' : '' ?> data-obw-required>
                            <span><?= e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <div class="obw-actions">
                <button class="obw-continue" type="submit" data-obw-submit disabled>Continue</button>
            </div>
        </form>
<?php require dirname(__DIR__) . '/includes/onboarding/v2-end.php'; ?>
