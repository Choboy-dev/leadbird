<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_onboarding.php';
require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';

$onboardingStep = OnboardingService::STEP_POLICIES;
$userId = (new SessionAuth())->userId();
$onboarding = new OnboardingService();
$onboarding->assertCanAccessStep((string) $userId, $onboardingStep);

$appUrl = app_config()['app_url'];
$user = (new SessionAuth())->user();
$settings = $onboarding->getSettingsForUser((string) $userId) ?? [];
$policiesContext = $onboarding->getPoliciesContextForUser((string) $userId);
$faqEntries = faq_parse($settings['faq'] ?? null);

$editingFaqIndex = null;
$editParam = trim((string) ($_GET['edit'] ?? ''));
if ($editParam !== '' && ctype_digit($editParam)) {
    $candidateIndex = (int) $editParam;
    if ($candidateIndex >= 0 && $candidateIndex < count($faqEntries)) {
        $editingFaqIndex = $candidateIndex;
    }
}

$editingFaq = $editingFaqIndex !== null ? $faqEntries[$editingFaqIndex] : null;
$faqFormAction = $editingFaq !== null ? 'update_faq' : 'add_faq';
$faqFormQuestion = (string) ($editingFaq['q'] ?? '');
$faqFormAnswer = (string) ($editingFaq['a'] ?? '');

$pageTitle = 'FAQs & Policies';
$stepHeading = $pageTitle;
$stepLead = 'Add the answers and policies your Salesgirl should share when customers need quick clarity.';
$error = flash_get('error');
$success = flash_get('success');

$form = [
    'delivery_policy' => (string) ($settings['delivery_policy'] ?? ''),
    'return_policy' => (string) ($settings['return_policy'] ?? ''),
];

$faqExample = faq_parse($policiesContext['faq_placeholder']);
$faqQuestionPlaceholder = $faqExample[0]['q'] ?? 'e.g. How do I get started?';
$faqAnswerPlaceholder = $faqExample[0]['a'] ?? 'e.g. Send us a DM and we will guide you through the next step.';

require dirname(__DIR__) . '/includes/onboarding/layout-start.php';
?>
<div class="onboarding-offers-page">
    <?php if ($faqEntries !== []): ?>
        <section class="onboarding-offer-catalog onboarding-offer-catalog--mobile" aria-labelledby="your-questions-heading">
            <div class="onboarding-offer-mobile-head">
                <div>
                    <p class="onboarding-offer-kicker">Your FAQs</p>
                    <h2 id="your-questions-heading" class="onboarding-offer-section-title">
                        Your questions <span class="onboarding-offer-count">(<?= count($faqEntries) ?>)</span>
                    </h2>
                </div>
                <span class="onboarding-offer-summary-pill"><?= count($faqEntries) ?> saved</span>
            </div>
            <p class="onboarding-offer-mobile-copy">These are the answers your Salesgirl can reuse when customers need quick clarity.</p>

            <ul class="onboarding-faq-list">
                <?php foreach ($faqEntries as $index => $entry): ?>
                    <?php $isBeingEdited = $editingFaqIndex === $index; ?>
                    <li class="onboarding-faq-item<?= $isBeingEdited ? ' is-editing' : '' ?>">
                        <div>
                            <p class="onboarding-faq-question"><?= e($entry['q']) ?></p>
                            <p class="onboarding-faq-answer"><?= e($entry['a']) ?></p>
                        </div>
                        <div class="onboarding-offering-actions">
                            <?php if ($isBeingEdited): ?>
                                <span class="onboarding-offering-editing-label" aria-current="true">Editing</span>
                            <?php else: ?>
                                <a
                                    href="<?= e(app_route('onboarding/policies', ['edit' => (int) $index])) ?>"
                                    class="onboarding-offering-edit"
                                >Edit</a>
                            <?php endif; ?>
                            <form method="post" action="<?= e(app_route('onboarding/save-policies')) ?>">
                                <input type="hidden" name="action" value="delete_faq">
                                <input type="hidden" name="faq_index" value="<?= (int) $index ?>">
                                <button type="submit" class="onboarding-offering-delete">Remove</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section
        class="onboarding-offer-section<?= $editingFaq !== null ? ' is-editing-offer' : '' ?>"
        aria-labelledby="add-question-heading"
    >
        <div class="onboarding-offer-form-head">
            <div>
                <p class="onboarding-offer-kicker">Frequently asked questions</p>
                <h2 id="add-question-heading" class="onboarding-offer-section-title">
                    <?= $editingFaq !== null ? 'Edit question' : 'Add a question' ?>
                </h2>
            </div>
        </div>
        <p class="onboarding-offer-section-lead">
            <?php if ($editingFaq !== null): ?>
                Update the wording below, then save your changes.
            <?php else: ?>
                Add the customer question your Salesgirl should answer the same way every time.
            <?php endif; ?>
        </p>

        <form
            id="faq-form"
            class="onboarding-form"
            method="post"
            action="<?= e(app_route('onboarding/save-policies')) ?>"
            data-editing="<?= $editingFaq !== null ? 'true' : 'false' ?>"
        >
            <input type="hidden" name="action" value="<?= e($faqFormAction) ?>">
            <?php if ($editingFaqIndex !== null): ?>
                <input type="hidden" name="faq_index" value="<?= (int) $editingFaqIndex ?>">
            <?php endif; ?>

            <div class="onboarding-offer-form-block">
                <div class="onboarding-field">
                    <label for="faq_question">Question</label>
                    <input
                        id="faq_question"
                        name="faq_question"
                        type="text"
                        maxlength="500"
                        placeholder="<?= e($faqQuestionPlaceholder) ?>"
                        value="<?= e($faqFormQuestion) ?>"
                    >
                </div>

                <div class="onboarding-field">
                    <label for="faq_answer">Answer</label>
                    <textarea
                        id="faq_answer"
                        name="faq_answer"
                        rows="3"
                        placeholder="<?= e($faqAnswerPlaceholder) ?>"
                    ><?= e($faqFormAnswer) ?></textarea>
                </div>
            </div>

            <div class="onboarding-form-actions onboarding-form-actions--offer">
                <?php if ($editingFaq !== null): ?>
                    <button type="submit" class="onboarding-btn onboarding-btn--primary">Save changes</button>
                    <a href="<?= e(app_route('onboarding/policies')) ?>" class="onboarding-btn onboarding-btn--ghost">Cancel</a>
                <?php else: ?>
                    <button type="submit" class="onboarding-btn onboarding-btn--primary">Save question</button>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="onboarding-offer-section onboarding-policies-section" aria-labelledby="policies-heading">
        <div class="onboarding-offer-form-head">
            <div>
                <p class="onboarding-offer-kicker">Business policies</p>
                <h2 id="policies-heading" class="onboarding-offer-section-title">Policies</h2>
            </div>
        </div>
        <p class="onboarding-offer-section-lead">Save the delivery, return, or cancellation rules your Salesgirl should quote clearly.</p>

        <form class="onboarding-form" method="post" action="<?= e(app_route('onboarding/save-policies')) ?>">
            <input type="hidden" name="action" value="save_policies_dashboard">

            <div class="onboarding-offer-form-block">
                <?php if ($policiesContext['show_delivery']): ?>
                    <div class="onboarding-field">
                        <label for="delivery_policy">Delivery policy</label>
                        <textarea id="delivery_policy" name="delivery_policy" placeholder="Where you deliver, timelines, and fees."><?= e($form['delivery_policy']) ?></textarea>
                    </div>
                <?php endif; ?>

                <div class="onboarding-field">
                    <label for="return_policy"><?= e($policiesContext['return_label']) ?></label>
                    <textarea id="return_policy" name="return_policy" placeholder="<?= e($policiesContext['return_placeholder']) ?>"><?= e($form['return_policy']) ?></textarea>
                </div>
            </div>

            <div class="onboarding-form-actions onboarding-form-actions--offer">
                <button type="submit" class="onboarding-btn onboarding-btn--primary">Save policies</button>
            </div>
        </form>
    </section>

    <nav class="onboarding-step-nav onboarding-step-nav--offers" aria-label="Onboarding navigation">
        <form class="onboarding-form" method="post" action="<?= e(app_route('onboarding/save-policies')) ?>">
            <input type="hidden" name="action" value="continue">
            <div class="onboarding-actions onboarding-actions--step onboarding-actions--offers-step">
                <a href="<?= e(onboarding_back_url($onboardingStep)) ?>" class="onboarding-btn onboarding-btn--ghost">Back</a>
                <button type="submit" class="onboarding-btn onboarding-btn--ghost onboarding-btn--continue">Continue</button>
            </div>
        </form>
    </nav>
</div>
<?php require dirname(__DIR__) . '/includes/onboarding/layout-end.php'; ?>
