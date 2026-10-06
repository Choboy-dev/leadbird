<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_onboarding.php';
require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';

$onboardingStep = OnboardingService::STEP_PAYMENT;
$userId = (new SessionAuth())->userId();
$onboarding = new OnboardingService();
$onboarding->assertCanAccessStep((string) $userId, $onboardingStep);

$appUrl = app_config()['app_url'];
$business = $onboarding->getBusinessForUser((string) $userId);
$businessId = $business !== null ? (string) $business['id'] : '';
$settings = $onboarding->getSettingsForUser((string) $userId) ?? [];
$bankDetails = PaymentSetupService::parseBankInstructions((string) ($settings['payment_instructions'] ?? ''));

$banks = [];
try {
    $banks = (new FlutterwaveService())->listBanks('NG');
} catch (Throwable $e) {
    $banks = [];
}

$selectedBankCode = '';
$selectedBankName = trim($bankDetails['bank_name']);
foreach ($banks as $bank) {
    $name = (string) ($bank['name'] ?? '');
    if ($name !== '' && strcasecmp($name, $selectedBankName) === 0) {
        $selectedBankCode = (string) ($bank['code'] ?? '');
        break;
    }
}

$pageTitle = 'Payment';
$stepHeading = $pageTitle;
$stepLead = '';
$error = flash_get('error');
$success = flash_get('success');
$extraStylesheets = ['onboarding-payment.css?v=3'];
$extraScripts = ['onboarding-payment.js?v=2'];

require dirname(__DIR__) . '/includes/onboarding/layout-start.php';
?>
<div class="onboarding-offers-page">
    <form class="onboarding-form lb-payment-form" method="post" action="<?= e(app_route('onboarding/save-payment')) ?>" data-payment-form>
        <section class="onboarding-offer-section lb-payment-section" aria-labelledby="payment-method-heading">
            <h2 id="payment-method-heading" class="onboarding-offer-section-title lb-payment-section-title font-heading">How do customers pay?</h2>
            <p class="onboarding-offer-section-lead">Share the bank account your Salesgirl should send to customers in chat.</p>

            <input type="hidden" name="payment_method" value="bank_transfer">

            <div class="lb-payment-panel lb-payment-panel--bank" data-payment-panel="bank_transfer">
                <div class="lb-payment-bank-fields">
                    <div class="onboarding-field">
                        <label for="bank_code">Bank</label>
                        <select id="bank_code" name="bank_code" data-payment-bank-select required>
                            <option value="">Select bank</option>
                            <?php foreach ($banks as $bank): ?>
                                <?php
                                $code = (string) ($bank['code'] ?? '');
                                $name = (string) ($bank['name'] ?? $code);
                                ?>
                                <option value="<?= e($code) ?>" data-bank-name="<?= e($name) ?>" <?= $selectedBankCode === $code ? 'selected' : '' ?>><?= e($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="onboarding-grid-2">
                        <div class="onboarding-field">
                            <label for="account_number">Account number</label>
                            <input id="account_number" name="account_number" type="text" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" value="<?= e($bankDetails['account_number']) ?>" data-payment-account-number required>
                        </div>
                        <div class="onboarding-field">
                            <label for="account_name">Account name</label>
                            <input id="account_name" name="account_name" type="text" maxlength="255" value="<?= e($bankDetails['account_name']) ?>" data-payment-account-name required>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="onboarding-step-nav onboarding-step-nav--offers">
            <div class="onboarding-actions onboarding-actions--step onboarding-actions--offers-step">
                <a href="<?= e(onboarding_back_url($onboardingStep)) ?>" class="onboarding-btn onboarding-btn--ghost">Back</a>
                <button type="submit" class="onboarding-btn onboarding-btn--primary onboarding-btn--continue">Save &amp; continue</button>
            </div>
        </div>
    </form>
</div>
<?php require dirname(__DIR__) . '/includes/onboarding/layout-end.php'; ?>
