<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_onboarding.php';
require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';

$onboardingStep = OnboardingService::STEP_PROFILE;
$userId = (new SessionAuth())->userId();
$onboarding = new OnboardingService();
$onboarding->assertCanAccessStep((string) $userId, $onboardingStep);

$appUrl = app_config()['app_url'];
$user = (new SessionAuth())->user();
$business = (new BusinessContext())->ensureOnboardingBusiness((string) $userId);
$settings = $onboarding->getSettingsForBusiness((string) $business['id']);

$connectedNames = [];
foreach (RepositoryFactory::connectedAccounts()->listByBusinessId((string) $business['id'], true) as $account) {
    $connectedNames[] = (string) ($account['platform'] ?? '');
}

$pageTitle = 'Business Profile';
$stepHeading = $pageTitle;
$stepLead = 'Add the business details your Salesgirl should represent'
    . ($connectedNames !== [] ? ' across ' . implode(', ', array_map('ucfirst', $connectedNames)) : '')
    . '.';
$error = flash_get('error');
$success = flash_get('success');

$form = [
    'business_name' => (string) ($business['business_name'] ?? '') === 'New business' ? '' : (string) ($business['business_name'] ?? ''),
    'owner_name' => (string) ($business['owner_name'] ?? $user['name'] ?? ''),
    'owner_email' => (string) ($business['owner_email'] ?? $user['email'] ?? ''),
    'owner_phone' => (string) ($business['owner_phone'] ?? ''),
    'ai_name' => (string) ($settings['ai_name'] ?? 'Sales Girl'),
    'business_description' => (string) ($settings['business_description'] ?? ''),
];

require dirname(__DIR__) . '/includes/onboarding/layout-start.php';
?>
<div class="onboarding-offers-page">
    <form class="onboarding-form" method="post" action="<?= e(app_route('onboarding/save-profile')) ?>">
        <section class="onboarding-offer-section" aria-labelledby="business-profile-section">
            <div class="onboarding-offer-form-head">
                <div>
                    <p class="onboarding-offer-kicker">Business setup</p>
                    <h2 id="business-profile-section" class="onboarding-offer-section-title">Who your Salesgirl represents</h2>
                </div>
                <span class="onboarding-offer-summary-pill onboarding-offer-summary-pill--soft">Required</span>
            </div>
            <p class="onboarding-offer-section-lead">Keep this short and clear so customers instantly understand the brand behind every reply.</p>

            <div class="onboarding-offer-form-block">
                <div class="onboarding-field">
                    <label for="business_name">Business name</label>
                    <input id="business_name" name="business_name" type="text" required maxlength="255" value="<?= e($form['business_name']) ?>" placeholder="e.g. Adaeze Fabrics">
                </div>
            </div>

            <div class="onboarding-offer-form-block">
                <div class="onboarding-grid-2">
                    <div class="onboarding-field">
                        <label for="owner_name">Your name</label>
                        <input id="owner_name" name="owner_name" type="text" maxlength="255" value="<?= e($form['owner_name']) ?>" placeholder="Owner / contact name">
                    </div>
                    <div class="onboarding-field">
                        <label for="owner_phone">Phone number</label>
                        <input id="owner_phone" name="owner_phone" type="tel" maxlength="50" value="<?= e($form['owner_phone']) ?>" placeholder="+234 800 000 0000">
                    </div>
                </div>

                <div class="onboarding-field">
                    <label for="owner_email">Business email</label>
                    <input id="owner_email" name="owner_email" type="email" maxlength="255" value="<?= e($form['owner_email']) ?>" placeholder="hello@yourbusiness.com">
                </div>
            </div>

            <div class="onboarding-offer-form-block">
                <div class="onboarding-field">
                    <label for="ai_name">Name your salesgirl</label>
                    <input id="ai_name" name="ai_name" type="text" maxlength="100" value="<?= e($form['ai_name']) ?>" placeholder="Sales Girl">
                    <p class="onboarding-field-hint">This is the name your AI uses when it introduces itself in chats.</p>
                </div>

                <div class="onboarding-field">
                    <label for="business_description">What does your business do?</label>
                    <textarea id="business_description" name="business_description" placeholder="Briefly describe what you sell and who you serve."><?= e($form['business_description']) ?></textarea>
                </div>
            </div>
        </section>

        <div class="onboarding-step-nav onboarding-step-nav--offers">
            <div class="onboarding-actions onboarding-actions--step onboarding-actions--offers-step">
                <a href="<?= e(onboarding_back_url($onboardingStep)) ?>" class="onboarding-btn onboarding-btn--ghost">Back</a>
                <button type="submit" class="onboarding-btn onboarding-btn--ghost onboarding-btn--continue">Save &amp; continue</button>
            </div>
        </div>
    </form>
</div>
<?php require dirname(__DIR__) . '/includes/onboarding/layout-end.php'; ?>
