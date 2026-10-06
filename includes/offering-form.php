<?php

declare(strict_types=1);

/** @var string $formContext onboarding|dashboard */
/** @var string $formAction */
/** @var array<string, mixed>|null $editingOffering */
/** @var array<string, mixed> $formState */
/** @var string $saveUrl */
/** @var string|null $cancelUrl */
/** @var string $businessId */
/** @var bool $googleCalendarConnected */

$isOnboarding = $formContext === 'onboarding';
$isModal = $formContext === 'modal';
$isDashboardEdit = $formContext === 'dashboard';
$useWizard = $isOnboarding || $isModal;
$useOfferUi = $useWizard || $isDashboardEdit;
$formId = $isOnboarding ? 'offering-form' : ($isModal ? 'offer-modal-form' : 'offers-edit-form');
$formClass = $useWizard
    ? 'onboarding-form onboarding-form--offers lb-offer-form lb-offer-form--wizard'
    : ($isDashboardEdit ? 'lb-offer-form lb-offer-form--page' : 'offers-form lb-offer-form');
$fieldClass = $useOfferUi ? 'onboarding-field' : 'offers-field';
$inputClass = $useOfferUi ? 'lb-offer-input' : 'offers-input';
$textareaClass = $useOfferUi ? 'lb-offer-textarea' : 'offers-textarea';
$labelClass = $useOfferUi ? 'lb-offer-label' : 'offers-label';
$blockClass = $useOfferUi ? 'onboarding-offer-form-block' : 'lb-offer-block';
$blockTitleClass = $useOfferUi ? 'onboarding-offer-form-block-title font-heading' : 'lb-offer-block-title font-heading';
$gridClass = $useOfferUi ? 'onboarding-grid-2' : 'offers-field-row';
$type = (string) ($formState['offering_type'] ?? 'product');
$config = is_array($formState['type_config'] ?? null) ? $formState['type_config'] : [];
$isFree = !empty($formState['is_free']);
$calendarConnectUrl = app_route('integrations/google-calendar', [
    'return' => $isOnboarding ? app_route('onboarding/offerings') : app_route('offers'),
]);
$wizardSteps = [1 => 'Type', 2 => 'Basics', 3 => 'Pricing', 4 => 'Details', 5 => 'Photo'];
$submitLabel = $editingOffering !== null ? 'Save changes' : ($isModal ? 'Publish offer' : 'Save offer');
$panelsPath = __DIR__ . '/offering-form-type-panels.php';

$renderTypePicker = static function () use ($blockClass, $blockTitleClass, $type): void {
    ?>
    <div class="<?= e($blockClass) ?> lb-offer-block--types">
        <div class="lb-offer-block-head">
            <h3 class="<?= e($blockTitleClass) ?>">What are you selling?</h3>
        </div>
        <fieldset class="lb-offer-type-grid" aria-label="Offer type">
            <legend class="sr-only">Offer type</legend>
            <?php foreach (OfferingTypeRegistry::allTypes() as $typeValue): ?>
                <?php $meta = OfferingTypeRegistry::meta($typeValue); ?>
                <label class="lb-offer-type-card<?= $type === $typeValue ? ' is-selected' : '' ?>" data-offer-type-card="<?= e($typeValue) ?>">
                    <input type="radio" name="offering_type" value="<?= e($typeValue) ?>" <?= $type === $typeValue ? 'checked' : '' ?> required data-offer-type-input>
                    <span class="lb-offer-type-icon" aria-hidden="true">
                        <img class="lb-offer-type-icon-img" src="<?= e($meta['icon']) ?>" alt="" width="32" height="32" loading="lazy" decoding="async">
                    </span>
                    <span class="lb-offer-type-label font-heading"><?= e($meta['label']) ?></span>
                    <span class="lb-offer-type-tagline"><?= e($meta['tagline']) ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>
    </div>
    <?php
};

$renderBasics = static function () use ($blockClass, $blockTitleClass, $fieldClass, $labelClass, $inputClass, $textareaClass, $formState): void {
    ?>
    <div class="<?= e($blockClass) ?> lb-offer-step lb-offer-step--basics">
        <div class="lb-offer-block-head">
            <h3 class="<?= e($blockTitleClass) ?>">Basics</h3>
            <p class="lb-offer-block-lead">Give your offer a clear name and short description. Your Salesgirl uses these in customer DMs.</p>
        </div>
        <div class="lb-offer-fields">
            <div class="<?= e($fieldClass) ?> lb-offer-field">
                <label class="<?= e($labelClass) ?>" for="offer-name">
                    Name <span class="lb-offer-required" aria-hidden="true">*</span>
                </label>
                <input
                    id="offer-name"
                    class="<?= e($inputClass) ?>"
                    name="name"
                    type="text"
                    required
                    maxlength="255"
                    value="<?= e((string) ($formState['name'] ?? '')) ?>"
                    placeholder="e.g. The Bloom Tribe Membership"
                    data-offer-name-placeholder
                >
            </div>
            <div class="<?= e($fieldClass) ?> lb-offer-field">
                <label class="<?= e($labelClass) ?>" for="offer-description">Short description</label>
                <textarea
                    id="offer-description"
                    class="<?= e($textareaClass) ?>"
                    name="description"
                    rows="4"
                    placeholder="What customers get, who it is for, and anything they should know before buying."
                    data-offer-description-placeholder
                ><?= e((string) ($formState['description'] ?? '')) ?></textarea>
                <p class="lb-offer-field-hint" data-offer-description-hint hidden></p>
            </div>
        </div>
    </div>
    <?php
};

$renderPricing = static function () use ($blockClass, $blockTitleClass, $fieldClass, $labelClass, $inputClass, $gridClass, $useOfferUi, $isFree, $formState): void {
    $billingType = (string) ($formState['billing_type'] ?? 'one_time');
    $currency = money_normalize_currency((string) ($formState['currency'] ?? ''))
        ?? money_default_catalog_currency();
    $currencyOptions = money_catalog_currency_options();
    ?>
    <div class="<?= e($blockClass) ?>">
        <div class="lb-offer-block-head">
            <h3 class="<?= e($blockTitleClass) ?>">Pricing</h3>
            <p class="lb-offer-block-lead">Use the currency your customers pay you in. Lead Bird subscriptions stay in USD.</p>
        </div>
        <div class="lb-offer-pricing-row">
            <label class="lb-offer-free-toggle">
                <input type="checkbox" name="is_free" value="1" <?= $isFree ? 'checked' : '' ?> data-offer-free-toggle>
                <span class="lb-offer-free-toggle-ui">
                    <span class="lb-offer-free-toggle-label font-heading">Free</span>
                </span>
            </label>
            <div class="<?= e($gridClass) ?> lb-offer-price-fields<?= $isFree ? ' is-hidden' : '' ?>" data-offer-price-fields>
                <div class="<?= e($fieldClass) ?>">
                    <label class="<?= e($labelClass) ?>" for="offer-currency">Currency</label>
                    <select id="offer-currency" class="<?= $useOfferUi ? 'lb-offer-billing-select' : 'offers-select' ?>" name="currency" data-offer-currency-select aria-label="Catalog currency">
                        <?php foreach ($currencyOptions as $code => $label): ?>
                            <option value="<?= e($code) ?>" <?= $currency === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="<?= e($fieldClass) ?>">
                    <label class="<?= e($labelClass) ?>" for="offer-price">Price</label>
                    <input id="offer-price" class="<?= e($inputClass) ?>" name="price" type="number" min="0" step="any" value="<?= e((string) ($formState['price'] ?? '')) ?>" data-offer-price-input inputmode="decimal">
                </div>
                <div class="<?= e($fieldClass) ?>">
                    <label class="<?= e($labelClass) ?>" for="offer-billing">Billing</label>
                    <select id="offer-billing" class="<?= $useOfferUi ? 'lb-offer-billing-select' : 'offers-select' ?>" name="billing_type" data-offer-billing-select>
                        <?php foreach (['one_time' => 'One-time', 'monthly' => 'Monthly', 'per_session' => 'Per session'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $billingType === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <?php
};

$renderPhoto = static function () use ($blockClass, $blockTitleClass, $fieldClass, $formState, $useOfferUi): void {
    $formImageUrl = $formState['image_url'] ?? null;
    if ($useOfferUi): ?>
        <div class="<?= e($blockClass) ?> onboarding-offer-form-block--media">
            <div class="lb-offer-block-head">
                <h3 class="<?= e($blockTitleClass) ?>">Photo</h3>
                <p class="lb-offer-block-lead">Optional — your Salesgirl can show this in DMs.</p>
            </div>
            <div class="<?= e($fieldClass) ?>">
                <div class="onboarding-offer-media" data-offering-image-field>
                    <div class="onboarding-offer-media-preview<?= $formImageUrl !== null ? ' has-image' : '' ?>" data-offering-image-preview>
                        <img src="<?= e($formImageUrl ?? '') ?>" alt="Offer photo preview" class="onboarding-offer-media-image<?= $formImageUrl === null ? ' is-hidden' : '' ?>" data-offering-image-preview-img>
                        <div class="onboarding-offer-media-empty<?= $formImageUrl !== null ? ' is-hidden' : '' ?>" data-offering-image-empty>
                            <span class="onboarding-offer-media-icon" aria-hidden="true">
                                <?= brand_upload_icon_html('', 28) ?>
                            </span>
                            <strong class="onboarding-offer-media-title font-heading">Upload image</strong>
                        </div>
                    </div>
                    <div class="onboarding-offer-media-controls">
                        <input id="offering_image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="onboarding-offer-file-input" data-offering-image-input>
                        <div class="onboarding-offer-media-actions">
                            <label for="offering_image" class="onboarding-offer-upload-btn">Choose photo</label>
                        </div>
                        <p class="onboarding-offer-media-status" data-offering-image-status><?= $formImageUrl !== null ? 'Current photo ready.' : 'No photo selected yet.' ?></p>
                        <?php if ($formImageUrl !== null): ?>
                            <label class="onboarding-checkbox onboarding-checkbox--media">
                                <input type="checkbox" name="remove_image" value="1" data-offering-image-remove>
                                Remove current photo
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="<?= e($fieldClass) ?>">
            <label class="offers-label" for="offers-image">Photo</label>
            <?php if ($formImageUrl !== null): ?>
                <div class="offers-image-preview">
                    <img src="<?= e($formImageUrl) ?>" alt="Current offer photo" class="offers-image-preview-img">
                </div>
                <label class="offers-image-remove">
                    <input type="checkbox" name="remove_image" value="1">
                    Remove photo
                </label>
            <?php endif; ?>
            <input id="offers-image" class="offers-file-input" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" data-offer-image-input>
            <p class="offers-field-hint">JPG, PNG, or WebP · 5 MB max</p>
        </div>
    <?php endif;
};
?>
<form
    id="<?= e($formId) ?>"
    class="<?= e($formClass) ?>"
    method="post"
    action="<?= e($saveUrl) ?>"
    enctype="multipart/form-data"
    data-editing="<?= $editingOffering !== null ? 'true' : 'false' ?>"
    data-offer-form
    <?= $useWizard ? 'data-offer-wizard="true"' : '' ?>
    <?= $isModal ? 'data-offer-wizard-modal="true"' : '' ?>
    <?php if ($useWizard && $cancelUrl !== null && ($editingOffering !== null || !empty($showOfferFormCancel))): ?>
    data-offer-exit-url="<?= e($cancelUrl) ?>"
    <?php endif; ?>
>
    <?php if (!$isOnboarding): ?>
        <input type="hidden" name="context" value="dashboard">
    <?php endif; ?>
    <input type="hidden" name="action" value="<?= e($formAction) ?>">
    <?php if ($editingOffering !== null): ?>
        <input type="hidden" name="offering_id" value="<?= e((string) $editingOffering['id']) ?>">
    <?php endif; ?>

    <?php if ($useWizard): ?>
        <div class="lb-offer-wizard" data-offer-wizard-root>
            <div class="lb-offer-wizard-progress" aria-live="polite">
                <div class="lb-offer-wizard-progress-track" aria-hidden="true">
                    <span class="lb-offer-wizard-progress-fill" data-offer-wizard-progress-fill style="width: 20%"></span>
                </div>
                <ol class="lb-offer-wizard-dots" aria-label="Offer form steps">
                    <?php foreach ($wizardSteps as $num => $label): ?>
                        <li class="lb-offer-wizard-dot<?= $num === 1 ? ' is-current' : '' ?>" data-offer-wizard-dot="<?= (int) $num ?>">
                            <span class="lb-offer-wizard-dot-mark"><?= (string) $num ?></span>
                            <span class="lb-offer-wizard-dot-label"><?= e($label) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <div class="lb-offer-wizard-body">
                <div class="lb-offer-wizard-step is-active" data-offer-wizard-step="1"><?php $renderTypePicker(); ?></div>
                <div class="lb-offer-wizard-step" data-offer-wizard-step="2"><?php $renderBasics(); ?></div>
                <div class="lb-offer-wizard-step" data-offer-wizard-step="3"><?php $renderPricing(); ?></div>
                <div class="lb-offer-wizard-step" data-offer-wizard-step="4"><?php require $panelsPath; ?></div>
                <div class="lb-offer-wizard-step" data-offer-wizard-step="5"><?php $renderPhoto(); ?></div>
            </div>
            <div class="lb-offer-wizard-error" data-offer-wizard-error role="alert" hidden></div>
            <div class="lb-offer-wizard-nav onboarding-actions onboarding-form-actions--offer">
                <div class="lb-offer-wizard-nav-start">
                    <button type="button" class="onboarding-btn onboarding-btn--ghost" data-offer-wizard-back hidden>Back</button>
                </div>
                <div class="lb-offer-wizard-nav-end">
                    <button type="button" class="onboarding-btn onboarding-btn--primary" data-offer-wizard-next>Continue</button>
                    <button type="submit" class="onboarding-btn onboarding-btn--primary" data-offer-wizard-submit hidden><?= e($submitLabel) ?></button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php $renderTypePicker(); ?>
        <?php $renderBasics(); ?>
        <?php $renderPricing(); ?>
        <?php require $panelsPath; ?>
        <?php $renderPhoto(); ?>
        <?php if ($isDashboardEdit): ?>
            <div class="catalog-edit-actions">
                <a href="<?= e((string) $cancelUrl) ?>" class="onboarding-btn onboarding-btn--ghost">Cancel</a>
                <button type="submit" class="onboarding-btn onboarding-btn--primary"><?= e($submitLabel) ?></button>
            </div>
        <?php else: ?>
        <div class="offers-form-actions">
            <?php if ($editingOffering !== null): ?>
                <button type="submit" class="offers-btn offers-btn--primary">Save changes</button>
                <?php if ($cancelUrl !== null): ?>
                    <a href="<?= e($cancelUrl) ?>" class="offers-btn offers-btn--ghost">Cancel</a>
                <?php endif; ?>
            <?php else: ?>
                <button type="submit" class="offers-btn offers-btn--primary">Add offer</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</form>
