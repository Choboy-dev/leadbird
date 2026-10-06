<?php

declare(strict_types=1);

/** @var string $appUrl */
/** @var array<string, mixed>|null $settings */
/** @var array<string, mixed>|null $business */
/** @var array<string, mixed>|null $user */
/** @var array<string, mixed> $alertsContext */
/** @var array{business_name: string, ai_name: string, business_description: string, catalog_currency?: string} $businessProfileForm */
/** @var array{owner_name: string, owner_email: string, owner_phone: string} $ownerProfileForm */
/** @var array<string, mixed> $alertsForm */
/** @var array{account_number: string, account_name: string, bank_name: string} $bankDetails */
/** @var string $selectedBankCode */
/** @var list<array<string, mixed>> $banks */
/** @var string $userSignInEmail */

$saveBusinessProfileUrl = e(app_route('settings/save-business-profile'));
$saveOwnerProfileUrl = e(app_route('settings/save-owner-profile'));
$saveAlertsUrl = e(app_route('settings/save-alerts'));
$savePaymentUrl = e(app_route('settings/save-payment'));
?>
<div class="settings-modal-overlay is-hidden" data-settings-overlay aria-hidden="true">
    <div
        class="settings-modal billing-card-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="settings-modal-business-profile-title"
        aria-hidden="true"
        data-settings-modal="business-profile"
    >
        <button type="button" class="billing-card-modal-close" data-settings-close aria-label="Close">×</button>
        <h2 id="settings-modal-business-profile-title" class="billing-card-modal-title font-heading">Business profile</h2>
        <p class="billing-card-modal-copy">Name, description, and the persona your Salesgirl uses in chats.</p>
        <form method="post" action="<?= $saveBusinessProfileUrl ?>" class="settings-modal-form">
            <?= csrf_field() ?>
            <div class="settings-modal-fields">
                <label class="billing-card-field billing-card-field--full">
                    <span class="billing-card-label">Business name</span>
                    <input class="billing-card-input" type="text" name="business_name" required maxlength="255" value="<?= e($businessProfileForm['business_name']) ?>" placeholder="e.g. The Bloom Tribe">
                </label>
                <label class="billing-card-field billing-card-field--full">
                    <span class="billing-card-label">Salesgirl name</span>
                    <input class="billing-card-input" type="text" name="ai_name" maxlength="100" value="<?= e($businessProfileForm['ai_name']) ?>" placeholder="Sales Girl">
                    <span class="settings-modal-hint">How your AI introduces itself in DMs.</span>
                </label>
                <label class="billing-card-field billing-card-field--full">
                    <span class="billing-card-label">What does your business do?</span>
                    <textarea class="billing-card-input settings-modal-textarea" name="business_description" rows="4" placeholder="Briefly describe what you sell and who you serve."><?= e($businessProfileForm['business_description']) ?></textarea>
                </label>
                <label class="billing-card-field billing-card-field--full" id="settings-catalog-currency-label">
                    <span class="billing-card-label">Catalog currency</span>
                    <select
                        id="settings-catalog-currency"
                        class="billing-card-input"
                        name="catalog_currency"
                        aria-labelledby="settings-catalog-currency-label"
                    >
                        <?php
                        $selectedCatalogCurrency = money_normalize_currency((string) ($businessProfileForm['catalog_currency'] ?? ''))
                            ?? money_default_catalog_currency();
                        foreach (money_catalog_currency_options() as $code => $label):
                        ?>
                            <option value="<?= e($code) ?>"<?= $selectedCatalogCurrency === $code ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="settings-modal-hint">Currency for your products and prices. Lead Bird plans stay in USD.</span>
                </label>
            </div>
            <div class="billing-card-actions">
                <button type="submit" class="bill-btn bill-btn--primary bill-btn--block">Save business profile</button>
                <button type="button" class="bill-btn bill-btn--outline bill-btn--block" data-settings-close>Cancel</button>
            </div>
        </form>
    </div>

    <div
        class="settings-modal billing-card-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="settings-modal-owner-profile-title"
        aria-hidden="true"
        data-settings-modal="owner-profile"
    >
        <button type="button" class="billing-card-modal-close" data-settings-close aria-label="Close">×</button>
        <h2 id="settings-modal-owner-profile-title" class="billing-card-modal-title font-heading">Owner profile</h2>
        <p class="billing-card-modal-copy">Your contact details for alerts and customer handoffs.</p>
        <form method="post" action="<?= $saveOwnerProfileUrl ?>" class="settings-modal-form">
            <?= csrf_field() ?>
            <div class="settings-modal-fields">
                <label class="billing-card-field billing-card-field--full">
                    <span class="billing-card-label">Your name</span>
                    <input class="billing-card-input" type="text" name="owner_name" maxlength="255" value="<?= e($ownerProfileForm['owner_name']) ?>" placeholder="Owner / contact name">
                </label>
                <label class="billing-card-field billing-card-field--full">
                    <span class="billing-card-label">Business email</span>
                    <input class="billing-card-input" type="email" name="owner_email" maxlength="255" value="<?= e($ownerProfileForm['owner_email']) ?>" placeholder="hello@yourbusiness.com">
                </label>
                <label class="billing-card-field billing-card-field--full">
                    <span class="billing-card-label">Phone number</span>
                    <input class="billing-card-input" type="tel" name="owner_phone" maxlength="50" value="<?= e($ownerProfileForm['owner_phone']) ?>" placeholder="+234 800 000 0000">
                </label>
            </div>
            <div class="billing-card-actions">
                <button type="submit" class="bill-btn bill-btn--primary bill-btn--block">Save owner details</button>
                <button type="button" class="bill-btn bill-btn--outline bill-btn--block" data-settings-close>Cancel</button>
            </div>
        </form>
    </div>

    <div
        class="settings-modal billing-card-modal settings-modal--wide"
        role="dialog"
        aria-modal="true"
        aria-labelledby="settings-modal-notifications-title"
        aria-hidden="true"
        data-settings-modal="notifications"
    >
        <button type="button" class="billing-card-modal-close" data-settings-close aria-label="Close">×</button>
        <h2 id="settings-modal-notifications-title" class="billing-card-modal-title font-heading">Notifications</h2>
        <p class="billing-card-modal-copy">Choose where Lead Bird reaches you on your <?= e((string) ($alertsContext['plan_name'] ?? 'plan')) ?> plan.</p>
        <form method="post" action="<?= $saveAlertsUrl ?>" class="settings-modal-form">
            <?= csrf_field() ?>
            <div class="settings-modal-fields">
                <h3 class="settings-modal-section-title font-heading">Contact</h3>
                <div class="settings-modal-grid<?= $alertsContext['show_whatsapp_number'] ? '' : ' settings-modal-grid--single' ?>">
                    <?php if ($alertsContext['show_whatsapp_number']): ?>
                        <label class="billing-card-field">
                            <span class="billing-card-label">WhatsApp number</span>
                            <input class="billing-card-input" type="tel" name="escalation_phone" maxlength="50" value="<?= e((string) $alertsForm['escalation_phone']) ?>" placeholder="+234 800 000 0000">
                        </label>
                    <?php endif; ?>
                    <label class="billing-card-field<?= $alertsContext['show_whatsapp_number'] ? '' : ' billing-card-field--full' ?>">
                        <span class="billing-card-label">Preferred email</span>
                        <input class="billing-card-input" type="email" name="escalation_email" maxlength="255" value="<?= e((string) $alertsForm['escalation_email']) ?>" placeholder="you@company.com">
                    </label>
                </div>

                <h3 class="settings-modal-section-title font-heading">Alert me when</h3>
                <div class="settings-modal-checks">
                    <?php if ($alertsContext['show_handoff_to_whatsapp']): ?>
                        <label class="settings-check">
                            <input type="checkbox" name="handoff_to_whatsapp" value="1"<?= $alertsForm['handoff_to_whatsapp'] ? ' checked' : '' ?>>
                            <span>Move interested customers to WhatsApp when they are ready to buy</span>
                        </label>
                    <?php endif; ?>

                    <?php if ($alertsContext['show_hot_lead_alerts']): ?>
                        <label class="settings-check">
                            <input type="checkbox" name="notify_owner_on_hot_lead" value="1"<?= $alertsForm['notify_owner_on_hot_lead'] ? ' checked' : '' ?>>
                            <span><?= e((string) $alertsContext['hot_lead_label']) ?></span>
                        </label>
                    <?php endif; ?>

                    <label class="settings-check">
                        <input type="checkbox" name="notify_owner_on_payment" value="1"<?= $alertsForm['notify_owner_on_payment'] ? ' checked' : '' ?>>
                        <span>A customer says they have paid</span>
                    </label>

                    <label class="settings-check">
                        <input type="checkbox" name="notify_owner_on_complaint" value="1"<?= $alertsForm['notify_owner_on_complaint'] ? ' checked' : '' ?>>
                        <span>There is a complaint or issue I should handle personally</span>
                    </label>
                </div>
            </div>
            <div class="billing-card-actions">
                <button type="submit" class="bill-btn bill-btn--primary bill-btn--block">Save notification settings</button>
                <button type="button" class="bill-btn bill-btn--outline bill-btn--block" data-settings-close>Cancel</button>
            </div>
        </form>
    </div>

    <div
        class="settings-modal billing-card-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="settings-modal-security-title"
        aria-hidden="true"
        data-settings-modal="security"
    >
        <button type="button" class="billing-card-modal-close" data-settings-close aria-label="Close">×</button>
        <h2 id="settings-modal-security-title" class="billing-card-modal-title font-heading">Sign-in &amp; security</h2>
        <p class="billing-card-modal-copy">Lead Bird uses secure email codes — there is no password to manage.</p>
        <div class="settings-security-panel">
            <div class="settings-security-row">
                <span class="billing-card-label">Sign-in email</span>
                <strong class="settings-security-value"><?= e($userSignInEmail) ?></strong>
            </div>
            <p class="settings-modal-hint">We send a one-time code to this address whenever you sign in. To change contact details used in alerts, update your owner profile.</p>
        </div>
        <div class="billing-card-actions">
            <button type="button" class="bill-btn bill-btn--primary bill-btn--block" data-settings-open="owner-profile">Edit owner profile</button>
            <button type="button" class="bill-btn bill-btn--outline bill-btn--block" data-settings-close>Close</button>
        </div>
    </div>

    <div
        class="settings-modal billing-card-modal settings-modal--wide"
        role="dialog"
        aria-modal="true"
        aria-labelledby="settings-modal-payment-title"
        aria-hidden="true"
        data-settings-modal="payment"
    >
        <button type="button" class="billing-card-modal-close" data-settings-close aria-label="Close">×</button>
        <h2 id="settings-modal-payment-title" class="billing-card-modal-title font-heading">Payment instructions</h2>
        <p class="billing-card-modal-copy">Bank details your Salesgirl shares when customers are ready to pay.</p>
        <form method="post" action="<?= $savePaymentUrl ?>" class="settings-modal-form" data-payment-form>
            <?= csrf_field() ?>
            <input type="hidden" name="payment_method" value="bank_transfer">
            <div class="settings-modal-fields">
                <label class="billing-card-field billing-card-field--full">
                    <span class="billing-card-label">Bank</span>
                    <select id="settings_bank_code" name="bank_code" class="billing-card-select" data-payment-bank-select required>
                        <option value="">Select bank</option>
                        <?php foreach ($banks as $bank): ?>
                            <?php
                            $code = (string) ($bank['code'] ?? '');
                            $name = (string) ($bank['name'] ?? $code);
                            ?>
                            <option value="<?= e($code) ?>" data-bank-name="<?= e($name) ?>" <?= $selectedBankCode === $code ? 'selected' : '' ?>><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="settings-modal-grid">
                    <label class="billing-card-field">
                        <span class="billing-card-label">Account number</span>
                        <input class="billing-card-input" type="text" name="account_number" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" value="<?= e($bankDetails['account_number']) ?>" data-payment-account-number required>
                    </label>
                    <label class="billing-card-field">
                        <span class="billing-card-label">Account name</span>
                        <input class="billing-card-input" type="text" name="account_name" maxlength="255" value="<?= e($bankDetails['account_name']) ?>" data-payment-account-name required>
                    </label>
                </div>
            </div>
            <div class="billing-card-actions">
                <button type="submit" class="bill-btn bill-btn--primary bill-btn--block">Save payment details</button>
                <button type="button" class="bill-btn bill-btn--outline bill-btn--block" data-settings-close>Cancel</button>
            </div>
        </form>
    </div>
</div>
