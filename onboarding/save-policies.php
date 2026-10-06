<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('onboarding/policies'));
}

$userId = (string) (new SessionAuth())->userId();
$save = onboarding_resolve_save_request();
$onboarding = $save['onboarding'];
$appUrl = $save['app_url'];
$dashboard = $save['dashboard'];
$business = $save['business'];

if ($business === null) {
    redirect(app_route('onboarding/profile'));
}

$businessId = (string) $business['id'];
$settings = $onboarding->ensureSettings($businessId);
$policiesContext = $onboarding->getPoliciesContextForUser($userId);
$action = (string) ($_POST['action'] ?? 'continue');

$policiesPageUrl = $dashboard
    ? app_route('offers', ['tab' => 'policies'])
    : app_route('onboarding/policies');

$faqsListUrl = $dashboard
    ? app_route('offers', ['tab' => 'faqs'])
    : app_route('onboarding/policies');

$faqsEditUrl = static function (?int $index = null) use ($faqsListUrl, $dashboard): string {
    if ($index === null || $index < 0) {
        return $faqsListUrl;
    }

    if ($dashboard) {
        return $faqsListUrl . '&faq_edit=' . $index;
    }

    return $faqsListUrl . '?edit=' . $index;
};

/**
 * @param array<string, mixed> $settings
 * @return array<string, mixed>
 */
function policies_settings_payload(
    array $settings,
    ?string $faq,
    array $policiesContext,
    string $deliveryPolicy,
    string $returnPolicy
): array {
    return [
        'ai_name' => (string) ($settings['ai_name'] ?? 'Sales Girl'),
        'business_description' => $settings['business_description'] ?? null,
        'faq' => $faq,
        'delivery_policy' => $policiesContext['show_delivery']
            ? ($deliveryPolicy !== '' ? $deliveryPolicy : null)
            : null,
        'return_policy' => $returnPolicy !== '' ? $returnPolicy : null,
        'payment_instructions' => $settings['payment_instructions'] ?? null,
        'payment_method' => $settings['payment_method'] ?? null,
        'escalation_phone' => $settings['escalation_phone'] ?? null,
        'escalation_email' => $settings['escalation_email'] ?? null,
        'handoff_to_whatsapp' => (bool) ($settings['handoff_to_whatsapp'] ?? true),
        'notify_owner_on_hot_lead' => (bool) ($settings['notify_owner_on_hot_lead'] ?? true),
        'notify_owner_on_payment' => (bool) ($settings['notify_owner_on_payment'] ?? true),
        'notify_owner_on_complaint' => (bool) ($settings['notify_owner_on_complaint'] ?? true),
    ];
}

if ($action === 'add_faq') {
    $question = trim((string) ($_POST['faq_question'] ?? ''));
    $answer = trim((string) ($_POST['faq_answer'] ?? ''));

    if ($question === '' || $answer === '') {
        flash_set('error', 'Please enter both a question and an answer.');
        redirect($faqsListUrl);
    }

    $entries = faq_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);
    $entries[] = ['q' => $question, 'a' => $answer];

    RepositoryFactory::businessSettings()->update(
        $businessId,
        policies_settings_payload(
            $settings,
            faq_format($entries),
            $policiesContext,
            (string) ($settings['delivery_policy'] ?? ''),
            (string) ($settings['return_policy'] ?? '')
        )
    );
    (new BusinessKnowledgeSyncService())->syncManualSources($businessId);

    flash_set('success', 'Question saved. Add another below, or continue when you\'re ready.');
    redirect($faqsListUrl);
}

if ($action === 'update_faq') {
    $index = (int) ($_POST['faq_index'] ?? -1);
    $question = trim((string) ($_POST['faq_question'] ?? ''));
    $answer = trim((string) ($_POST['faq_answer'] ?? ''));

    if ($question === '' || $answer === '') {
        flash_set('error', 'Please enter both a question and an answer.');
        redirect($faqsEditUrl($index >= 0 ? $index : null));
    }

    $entries = faq_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);
    if ($index < 0 || $index >= count($entries)) {
        flash_set('error', 'Question not found.');
        redirect($faqsListUrl);
    }

    $entries[$index] = ['q' => $question, 'a' => $answer];

    RepositoryFactory::businessSettings()->update(
        $businessId,
        policies_settings_payload(
            $settings,
            faq_format($entries),
            $policiesContext,
            (string) ($settings['delivery_policy'] ?? ''),
            (string) ($settings['return_policy'] ?? '')
        )
    );
    (new BusinessKnowledgeSyncService())->syncManualSources($businessId);

    flash_set('success', 'FAQ updated.');
    redirect($faqsListUrl);
}

if ($action === 'delete_faq') {
    $index = (int) ($_POST['faq_index'] ?? -1);
    $entries = faq_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);

    if ($index >= 0 && $index < count($entries)) {
        array_splice($entries, $index, 1);
        RepositoryFactory::businessSettings()->update(
            $businessId,
            policies_settings_payload(
                $settings,
                faq_format($entries),
                $policiesContext,
                (string) ($settings['delivery_policy'] ?? ''),
                (string) ($settings['return_policy'] ?? '')
            )
        );
        (new BusinessKnowledgeSyncService())->syncManualSources($businessId);
        flash_set('success', 'FAQ removed.');
    }

    redirect($faqsListUrl);
}

if ($action === 'save_policies_dashboard') {
    $deliveryPolicy = trim((string) ($_POST['delivery_policy'] ?? ''));
    $returnPolicy = trim((string) ($_POST['return_policy'] ?? ''));

    RepositoryFactory::businessSettings()->update(
        $businessId,
        policies_settings_payload(
            $settings,
            isset($settings['faq']) ? ((string) $settings['faq'] !== '' ? (string) $settings['faq'] : null) : null,
            $policiesContext,
            $deliveryPolicy,
            $returnPolicy
        )
    );
    (new BusinessKnowledgeSyncService())->syncManualSources($businessId);

    flash_set('success', 'Policies saved. Continue when you\'re ready.');
    redirect($policiesPageUrl);
}

if ($action === 'continue') {
    $onboarding->advanceAfterStep($businessId, OnboardingService::STEP_POLICIES);
    redirect(app_route('onboarding/payment'));
}

redirect($policiesPageUrl);
