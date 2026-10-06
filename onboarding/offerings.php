<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

$userId = (new SessionAuth())->userId();
$onboarding = new OnboardingService();
$isEditMode = onboarding_is_edit_mode($userId !== null ? (string) $userId : null);

if ($isEditMode) {
    require_once dirname(__DIR__) . '/includes/require_subscription.php';
} else {
    require_once dirname(__DIR__) . '/includes/require_onboarding.php';
    $onboarding->assertCanAccessStep((string) $userId, OnboardingService::STEP_OFFERINGS);
}

$onboardingStep = OnboardingService::STEP_OFFERINGS;

$appUrl = app_config()['app_url'];
$user = (new SessionAuth())->user();
$offerings = $onboarding->getOfferingsForUser((string) $userId);
$business = $onboarding->getBusinessForUser((string) $userId);
$businessId = $business !== null ? (string) $business['id'] : '';

$editingOffering = null;
$editingId = trim((string) ($_GET['edit'] ?? ''));
if ($editingId !== '' && $business !== null) {
    $candidate = RepositoryFactory::offerings()->findById($editingId);
    if ($candidate !== null && (string) ($candidate['business_id'] ?? '') === (string) $business['id']) {
        $editingOffering = $candidate;
    }
}

$pageTitle = $isEditMode ? 'Manage Offers' : 'Offers & Services';
$stepHeading = $pageTitle;
$stepLead = $isEditMode
    ? 'Update what you sell — your Salesgirl uses type-specific details to answer and act in DMs.'
    : 'Add what you sell in a few quick steps.';
$error = flash_get('error');
$success = flash_get('success');
$hideOnboardingProgress = $isEditMode;

$formAction = $editingOffering !== null ? 'update' : 'add';
$formState = OfferingInputParser::formState($editingOffering);
if ($editingOffering === null && $businessId !== '') {
    $bizSettings = RepositoryFactory::businessSettings()->findByBusinessId($businessId);
    $formState['currency'] = money_business_catalog_currency($bizSettings);
}
$offeringsCount = count($offerings);
$showOfferForm = $offerings === [] || $editingOffering !== null || (string) ($_GET['new'] ?? '') === '1';
$showOfferFormCancel = $offerings !== [] && $editingOffering === null;
$saveUrl = app_route('onboarding/save-offerings');
$cancelUrl = app_route('onboarding/offerings');
$formContext = 'onboarding';
$googleCalendarConnected = $businessId !== ''
    && RepositoryFactory::businessGoogleCalendar()->findByBusinessId($businessId) !== null;
$extraStylesheets = ['onboarding-offers.css?v=12'];
$extraScripts = ['onboarding-offers.js?v=5'];

require dirname(__DIR__) . '/includes/onboarding/layout-start.php';
?>
<div class="onboarding-offers-page" data-offer-form-visible="<?= $showOfferForm ? 'true' : 'false' ?>">
    <?php if ($offerings !== []): ?>
        <section class="onboarding-offer-catalog onboarding-offer-catalog--mobile" aria-labelledby="your-offers-heading">
            <div class="onboarding-offer-mobile-head">
                <div>
                    <p class="onboarding-offer-kicker">Your catalog</p>
                    <h2 id="your-offers-heading" class="onboarding-offer-section-title">
                        Your offers <span class="onboarding-offer-count">(<?= $offeringsCount ?>)</span>
                    </h2>
                </div>
                <span class="onboarding-offer-summary-pill"><?= $offeringsCount ?> saved</span>
            </div>

            <ul class="onboarding-offering-list">
                <?php foreach ($offerings as $offering): ?>
                    <?php
                    $isBeingEdited = $editingOffering !== null && (string) $offering['id'] === (string) $editingOffering['id'];
                    $typeMeta = dash_offering_type_meta((string) ($offering['offering_type'] ?? 'product'));
                    ?>
                    <li class="onboarding-offering-item<?= $isBeingEdited ? ' is-editing' : '' ?>">
                        <?php $listImageUrl = dash_offering_image_url((string) ($offering['image_url'] ?? '')); ?>
                        <div class="onboarding-offering-item-main">
                            <?php if ($listImageUrl !== null): ?>
                                <img src="<?= e($listImageUrl) ?>" alt="" class="onboarding-offering-thumb" loading="lazy">
                            <?php endif; ?>
                            <div class="onboarding-offering-copy">
                                <div class="onboarding-offering-topline">
                                    <strong><?= e((string) $offering['name']) ?></strong>
                                    <span class="onboarding-offering-tag"><?= e($typeMeta['label']) ?></span>
                                    <?php if ($isBeingEdited): ?>
                                        <span class="onboarding-offering-editing-label onboarding-offering-editing-label--inline" aria-current="true">Editing</span>
                                    <?php endif; ?>
                                </div>
                                <?php
                                $offeringMeta = [OfferingTypeRegistry::formatPrice($offering)];
                                $offeringMeta[] = dash_billing_label((string) ($offering['billing_type'] ?? 'one_time'));
                                ?>
                                <p class="onboarding-offering-meta"><?= e(implode(' · ', $offeringMeta)) ?></p>
                            </div>
                        </div>
                        <div class="onboarding-offering-actions">
                            <?php if (!$isBeingEdited): ?>
                                <a href="<?= e(app_route('onboarding/offerings', ['edit' => (string) $offering['id']])) ?>" class="onboarding-offering-edit">Edit</a>
                            <?php endif; ?>
                            <form method="post" action="<?= e($saveUrl) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="offering_id" value="<?= e((string) $offering['id']) ?>">
                                <button type="submit" class="onboarding-offering-delete">Remove</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if (!$showOfferForm): ?>
                <div class="onboarding-offer-catalog-actions">
                    <a href="<?= e(app_route('onboarding/offerings', ['new' => '1'])) ?>" class="onboarding-btn onboarding-btn--primary onboarding-btn--add-offer">Add new offer</a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($showOfferForm): ?>
    <section class="onboarding-offer-section<?= $editingOffering !== null ? ' is-editing-offer' : '' ?>" aria-labelledby="add-offer-heading">
        <div class="onboarding-offer-form-head">
            <div>
                <p class="onboarding-offer-kicker"><?= $editingOffering !== null ? 'Update offer' : 'Create offer' ?></p>
                <h2 id="add-offer-heading" class="onboarding-offer-section-title">
                    <?= $editingOffering !== null ? 'Edit offer' : 'Add an offer' ?>
                </h2>
            </div>
        </div>

        <?php require dirname(__DIR__) . '/includes/offering-form.php'; ?>
    </section>
    <?php endif; ?>

    <?php if ($isEditMode): ?>
        <div class="onboarding-actions onboarding-actions--offers-page">
            <a href="<?= e(app_route('dashboard')) ?>" class="onboarding-btn onboarding-btn--ghost">Back to dashboard</a>
        </div>
    <?php else: ?>
        <nav
            class="onboarding-step-nav onboarding-step-nav--offers"
            aria-label="Onboarding navigation"
            data-offer-onboarding-nav
            <?= $showOfferForm ? 'hidden' : '' ?>
        >
            <form class="onboarding-form" method="post" action="<?= e($saveUrl) ?>">
                <input type="hidden" name="action" value="continue">
                <div class="onboarding-actions onboarding-actions--step onboarding-actions--offers-step">
                    <a href="<?= e(onboarding_back_url($onboardingStep)) ?>" class="onboarding-btn onboarding-btn--ghost">Back</a>
                    <button type="submit" class="onboarding-btn onboarding-btn--primary onboarding-btn--continue">Continue</button>
                </div>
            </form>
        </nav>
    <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/includes/onboarding/layout-end.php'; ?>
