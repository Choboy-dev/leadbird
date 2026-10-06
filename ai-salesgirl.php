<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/helpers.php';
require_once __DIR__ . '/includes/dashboard/nav.php';

$dashboardNav = 'ai_salesgirl';
$dashboardHideTopbar = true;
$aiSalesgirlView = (string) ($_GET['view'] ?? 'chat');
if (!in_array($aiSalesgirlView, ['chat', 'knowledge', 'alerts', 'selling'], true)) {
    $aiSalesgirlView = 'chat';
}
$dashboardNavContext = ['ai_salesgirl_view' => $aiSalesgirlView];
$extraStylesheets = ['dashboard-ai-salesgirl.css?v=66'];

$knowledgeTraining = $businessId !== null
    ? (new KnowledgeTrainingService())->getTrainingState($businessId)
    : null;

$hasFullSettings = PlanAccess::allows($planSlug, 'ai_salesgirl_full');
$canNegotiate = PlanAccess::allows($planSlug, 'ai_negotiation');
$canFollowup = PlanAccess::allows($planSlug, 'proactive_followups');
$canReceipt = PlanAccess::allows($planSlug, 'payment_receipts');
$sellingSettings = [
    'negotiation_enabled' => (bool) ($settings['negotiation_enabled'] ?? true),
    'max_discount_percent' => (float) ($settings['max_discount_percent'] ?? 10),
    'negotiation_rules' => (string) ($settings['negotiation_rules'] ?? ''),
    'upsell_enabled' => (bool) ($settings['upsell_enabled'] ?? true),
    'upsell_notes' => (string) ($settings['upsell_notes'] ?? ''),
    'followup_enabled' => (bool) ($settings['followup_enabled'] ?? true),
    'followup_delay_hours' => (int) ($settings['followup_delay_hours'] ?? 6),
    'followup_max_attempts' => (int) ($settings['followup_max_attempts'] ?? 2),
    'followup_interval_hours' => (int) ($settings['followup_interval_hours'] ?? 12),
    'followup_notes' => (string) ($settings['followup_notes'] ?? ''),
    'receipt_enabled' => (bool) ($settings['receipt_enabled'] ?? true),
    'receipt_message' => (string) ($settings['receipt_message'] ?? ''),
];
$testMessage = trim((string) ($_GET['test'] ?? ''));
$testResponse = '';
$testError = '';

if ($testMessage !== '' && $businessId !== null && is_array($business)) {
    require_once __DIR__ . '/includes/openai.php';

    $testSettings = is_array($settings)
        ? $settings
        : (new OnboardingService())->ensureSettings($businessId);
    $offerings = RepositoryFactory::offerings()->listByBusinessId($businessId, true);

    try {
        $knowledgeChunks = (new BusinessKnowledgeRetrievalService())->retrieve($businessId, $testMessage);
        $ai = openai_generate_sales_reply(
            $business,
            $testSettings,
            $offerings,
            [],
            ['message_text' => $testMessage],
            $knowledgeChunks,
            null,
            null,
            $planSlug
        );
        $testResponse = trim((string) ($ai['customer_reply'] ?? ''));
        if ($testResponse === '') {
            $testError = 'Could not generate a reply. Check that your OpenAI API key is configured.';
        }
    } catch (Throwable $e) {
        error_log('[ai-salesgirl test] ' . $e->getMessage());
        $testError = 'Something went wrong generating a test reply. Please try again.';
    }
}

$aiName = trim((string) ($settings['ai_name'] ?? ''));
if ($aiName === '') {
    $aiName = 'Salesgirl';
}
$aiNavLabel = dashboard_ai_assistant_label(is_array($settings) ? $settings : null);
$pageTitle = $aiNavLabel . ' — ' . $brandName;
$pageHeading = $aiNavLabel;
$businessName = (string) ($business['business_name'] ?? '—');
$aiTone = (string) ($settings['ai_tone'] ?? 'Friendly and helpful');
$aiGreeting = (string) ($settings['ai_greeting'] ?? 'Hi. Thanks for reaching out. How can I help you today?');
$aiToneOptions = dash_ai_tone_options($aiTone);
$businessUrl = e(app_route('offers'));

$userFirstName = dash_first_name(trim((string) ($user['name'] ?? '')));
if ($userFirstName === '') {
    $userFirstName = 'there';
}

$hour = (int) date('G');
$timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$connectedChannels = [];
if ($businessId !== null) {
    foreach (RepositoryFactory::connectedAccounts()->listByBusinessId($businessId, true) as $account) {
        $platform = (string) ($account['platform'] ?? '');
        if ($platform === '') {
            continue;
        }
        $connectedChannels[] = [
            'id' => (string) ($account['id'] ?? ''),
            'name' => dash_connected_account_name($account),
            'platform' => $platform,
            'platform_label' => dash_platform_label($platform),
            'avatar_src' => dash_connected_account_avatar_src($account),
        ];
    }
}

$defaultChannel = $connectedChannels[0] ?? null;

$promptSuggestions = [
    [
        'tag' => 'DM Replies',
        'text' => 'How would you reply if a customer asks about delivery times?',
    ],
    [
        'tag' => 'DM Replies',
        'text' => 'Draft a friendly greeting for a new lead in my DMs.',
    ],
    [
        'tag' => 'DM Replies',
        'text' => 'A customer wants a refund — what should I say?',
    ],
    [
        'tag' => 'DM Replies',
        'text' => 'Suggest 3 upsell messages for my best-selling product.',
    ],
];

$alertsContext = $userId !== null ? (new OnboardingService())->getAlertsContextForUser((string) $userId) : [];
$complaintEnabled = (bool) ($settings['notify_owner_on_complaint'] ?? true);

$ownerAlertRules = [
    [
        'id' => 'alert_angry_customer',
        'label' => 'Angry customer',
        'checked' => $complaintEnabled,
        'enabled' => true,
    ],
    [
        'id' => 'alert_refund_request',
        'label' => 'Refund request',
        'checked' => $complaintEnabled,
        'enabled' => true,
    ],
    [
        'id' => 'alert_payment_issue',
        'label' => 'Payment issue',
        'checked' => (bool) ($settings['notify_owner_on_payment'] ?? true),
        'enabled' => true,
    ],
    [
        'id' => 'alert_large_order',
        'label' => 'Large order',
        'checked' => (bool) ($settings['notify_owner_on_hot_lead'] ?? true),
        'enabled' => !empty($alertsContext['show_hot_lead_alerts']),
    ],
    [
        'id' => 'alert_human_handoff',
        'label' => 'Customer asks for human',
        'checked' => (bool) ($settings['handoff_to_whatsapp'] ?? true),
        'enabled' => !empty($alertsContext['show_handoff_to_whatsapp']),
    ],
];

require __DIR__ . '/includes/dashboard/layout-start.php';
require __DIR__ . '/includes/dashboard/ai-salesgirl-content.php';
require __DIR__ . '/includes/dashboard/layout-end.php';
