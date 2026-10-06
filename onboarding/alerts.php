<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';

$userId = (new SessionAuth())->userId();
$onboarding = new OnboardingService();
$isEditMode = onboarding_is_edit_mode($userId !== null ? (string) $userId : null);

if ($isEditMode) {
    require_once dirname(__DIR__) . '/includes/require_subscription.php';
} else {
    require_once dirname(__DIR__) . '/includes/require_onboarding.php';
    $onboarding->assertCanAccessStep((string) $userId, OnboardingService::STEP_ALERTS);
}

$onboardingStep = OnboardingService::STEP_ALERTS;
$alertsContext = $onboarding->getAlertsContextForUser((string) $userId);

$appUrl = app_config()['app_url'];
$user = (new SessionAuth())->user();
$settings = $onboarding->getSettingsForUser((string) $userId) ?? [];
$business = $onboarding->getBusinessForUser((string) $userId) ?? [];

$pageTitle = $isEditMode ? 'Alert Settings' : 'Owner Alerts';
$stepHeading = $pageTitle;
$stepLead = $isEditMode
    ? 'Update how Lead Bird reaches you based on your ' . $alertsContext['plan_name'] . ' plan.'
    : 'Choose where Lead Bird should reach you when a customer needs your attention.';
$error = flash_get('error');
$success = flash_get('success');
$hideOnboardingProgress = $isEditMode;

$form = [
    'escalation_phone' => (string) ($settings['escalation_phone'] ?? $business['owner_phone'] ?? ''),
    'escalation_email' => (string) ($settings['escalation_email'] ?? $business['owner_email'] ?? $user['email'] ?? ''),
    'handoff_to_whatsapp' => $alertsContext['show_handoff_to_whatsapp']
        && (bool) ($settings['handoff_to_whatsapp'] ?? true),
    'notify_owner_on_hot_lead' => $alertsContext['show_hot_lead_alerts']
        && (bool) ($settings['notify_owner_on_hot_lead'] ?? true),
    'notify_owner_on_payment' => (bool) ($settings['notify_owner_on_payment'] ?? true),
    'notify_owner_on_complaint' => (bool) ($settings['notify_owner_on_complaint'] ?? true),
];

require dirname(__DIR__) . '/includes/onboarding/layout-start.php';
?>
<div class="onboarding-offers-page">
    <form class="onboarding-form" method="post" action="<?= e(app_route('onboarding/save-alerts')) ?>">
        <section class="onboarding-offer-section" aria-labelledby="alerts-contact-heading">
            <div class="onboarding-offer-form-head">
                <div>
                    <p class="onboarding-offer-kicker">Contact details</p>
                    <h2 id="alerts-contact-heading" class="onboarding-offer-section-title">Where we should reach you</h2>
                </div>
                <span class="onboarding-offer-summary-pill onboarding-offer-summary-pill--soft">Owner alerts</span>
            </div>
            <p class="onboarding-offer-section-lead">Use the contacts you actually monitor so urgent replies do not get missed.</p>

            <div class="onboarding-offer-form-block">
                <div class="onboarding-grid-2<?= $alertsContext['show_whatsapp_number'] ? '' : ' onboarding-grid-2--single' ?>">
                    <?php if ($alertsContext['show_whatsapp_number']): ?>
                        <div class="onboarding-field">
                            <label for="escalation_phone">WhatsApp number</label>
                            <input id="escalation_phone" name="escalation_phone" type="tel" maxlength="50" value="<?= e($form['escalation_phone']) ?>" placeholder="+234 800 000 0000">
                        </div>
                    <?php endif; ?>
                    <div class="onboarding-field">
                        <label for="escalation_email">Preferred email</label>
                        <input id="escalation_email" name="escalation_email" type="email" maxlength="255" value="<?= e($form['escalation_email']) ?>" placeholder="you@company.com">
                    </div>
                </div>
            </div>
        </section>

        <section class="onboarding-offer-section" aria-labelledby="alerts-preferences-heading">
            <div class="onboarding-offer-form-head">
                <div>
                    <p class="onboarding-offer-kicker">Preferences</p>
                    <h2 id="alerts-preferences-heading" class="onboarding-offer-section-title">When Lead Bird should notify you</h2>
                </div>
            </div>
            <p class="onboarding-offer-section-lead">Turn on the alerts that need your eyes, and leave routine replies to your Salesgirl.</p>

            <div class="onboarding-offer-form-block">
                <?php if ($alertsContext['show_handoff_to_whatsapp']): ?>
                    <label class="onboarding-check">
                        <input type="checkbox" name="handoff_to_whatsapp" value="1"<?= $form['handoff_to_whatsapp'] ? ' checked' : '' ?>>
                        <span>Move interested customers to WhatsApp when they are ready to buy</span>
                    </label>
                <?php endif; ?>

                <?php if ($alertsContext['show_hot_lead_alerts']): ?>
                    <label class="onboarding-check">
                        <input type="checkbox" name="notify_owner_on_hot_lead" value="1"<?= $form['notify_owner_on_hot_lead'] ? ' checked' : '' ?>>
                        <span><?= e($alertsContext['hot_lead_label']) ?></span>
                    </label>
                <?php endif; ?>

                <label class="onboarding-check">
                    <input type="checkbox" name="notify_owner_on_payment" value="1"<?= $form['notify_owner_on_payment'] ? ' checked' : '' ?>>
                    <span>Notify me when a customer says they have paid</span>
                </label>

                <label class="onboarding-check">
                    <input type="checkbox" name="notify_owner_on_complaint" value="1"<?= $form['notify_owner_on_complaint'] ? ' checked' : '' ?>>
                    <span>Notify me on complaints or issues I should handle personally</span>
                </label>
            </div>
        </section>

        <?php if ($isEditMode): ?>
            <div class="onboarding-actions">
                <a href="<?= e(app_route('dashboard')) ?>" class="onboarding-btn onboarding-btn--ghost">Back to dashboard</a>
                <button type="submit" class="onboarding-btn onboarding-btn--primary">Save changes</button>
            </div>
        <?php else: ?>
            <div class="onboarding-step-nav onboarding-step-nav--offers">
                <div class="onboarding-actions onboarding-actions--step onboarding-actions--offers-step">
                    <a href="<?= e(onboarding_back_url($onboardingStep)) ?>" class="onboarding-btn onboarding-btn--ghost">Back</a>
                    <button type="submit" class="onboarding-btn onboarding-btn--ghost onboarding-btn--continue">Save &amp; continue</button>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>
<?php require dirname(__DIR__) . '/includes/onboarding/layout-end.php'; ?>
