<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/helpers.php';

$pageTitle = 'Settings — ' . $brandName;
$dashboardNav = 'settings';
$pageHeading = 'Settings';
$extraStylesheets = ['dashboard-billing.css?v=21', 'dashboard-settings.css?v=11'];
$extraScripts = [
    'dashboard-billing.js?v=12',
    'onboarding-payment.js?v=2',
    'dashboard-settings.js?v=8',
];
$dashboardPortalPartial = __DIR__ . '/includes/dashboard/settings-modals.php';

$success = flash_get('success');
$error = flash_get('error');

$userEmail = trim((string) ($user['email'] ?? ''));
$userDisplayName = trim((string) ($user['name'] ?? ''));
if ($userDisplayName === '') {
    $userDisplayName = trim((string) ($business['owner_name'] ?? ''));
}
if ($userDisplayName === '') {
    $userDisplayName = $userEmail !== '' ? (strstr($userEmail, '@', true) ?: $userEmail) : 'Account';
}

$businessName = trim((string) ($business['business_name'] ?? ''));
$profileTitle = $businessName !== '' ? $businessName : $userDisplayName;
$profileSubtitle = $businessName !== '' ? $userDisplayName : $userEmail;
$profileInitials = dash_initials_for_name($profileTitle);

$planLabel = is_array($plan) ? (string) ($plan['name'] ?? 'Your plan') : 'Your plan';
$channelsConnected = (int) ($stats['connected_accounts'] ?? 0);

$settingsRow = is_array($settings) ? $settings : [];
if ($businessId !== null && $settingsRow === []) {
    $settingsRow = $onboarding->ensureSettings($businessId);
}

$userIdStr = (string) $userId;
$alertsContext = $onboarding->getAlertsContextForUser($userIdStr);
$userSignInEmail = $userEmail !== '' ? $userEmail : trim((string) ($business['owner_email'] ?? ''));

$businessProfileForm = [
    'business_name' => $businessName !== '' && $businessName !== 'New business' ? $businessName : '',
    'ai_name' => trim((string) ($settingsRow['ai_name'] ?? 'Sales Girl')) ?: 'Sales Girl',
    'business_description' => trim((string) ($settingsRow['business_description'] ?? '')),
    'catalog_currency' => money_business_catalog_currency($settingsRow),
];

$ownerProfileForm = [
    'owner_name' => trim((string) ($business['owner_name'] ?? $user['name'] ?? '')),
    'owner_email' => trim((string) ($business['owner_email'] ?? $user['email'] ?? '')),
    'owner_phone' => trim((string) ($business['owner_phone'] ?? '')),
];

$alertsForm = [
    'escalation_phone' => trim((string) ($settingsRow['escalation_phone'] ?? $business['owner_phone'] ?? '')),
    'escalation_email' => trim((string) ($settingsRow['escalation_email'] ?? $business['owner_email'] ?? $user['email'] ?? '')),
    'handoff_to_whatsapp' => $alertsContext['show_handoff_to_whatsapp']
        && (bool) ($settingsRow['handoff_to_whatsapp'] ?? true),
    'notify_owner_on_hot_lead' => $alertsContext['show_hot_lead_alerts']
        && (bool) ($settingsRow['notify_owner_on_hot_lead'] ?? true),
    'notify_owner_on_payment' => (bool) ($settingsRow['notify_owner_on_payment'] ?? true),
    'notify_owner_on_complaint' => (bool) ($settingsRow['notify_owner_on_complaint'] ?? true),
];

$bankDetails = PaymentSetupService::parseBankInstructions((string) ($settingsRow['payment_instructions'] ?? ''));
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

$settingsSections = [
    [
        'id' => 'business',
        'title' => 'Business',
        'open' => true,
        'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'items' => [
            [
                'title' => 'My businesses',
                'desc' => 'Switch, add, or delete a business.',
                'action' => 'link',
                'href' => app_route('businesses'),
                'cta' => 'Manage businesses',
            ],
            [
                'title' => 'Business profile',
                'desc' => 'Name, description, and AI persona.',
                'action' => 'modal',
                'modal' => 'business-profile',
                'cta' => 'Edit profile',
            ],
        ],
    ],
    [
        'id' => 'account',
        'title' => 'Account',
        'open' => false,
        'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.25" stroke="currentColor" stroke-width="1.75"/><path d="M5 20v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>',
        'items' => [
            [
                'title' => 'Owner profile',
                'desc' => 'Your name, email, and phone.',
                'action' => 'modal',
                'modal' => 'owner-profile',
                'cta' => 'Edit details',
            ],
            [
                'title' => 'Notifications',
                'desc' => 'When and how you get alerted.',
                'action' => 'modal',
                'modal' => 'notifications',
                'cta' => 'Manage alerts',
            ],
            [
                'title' => 'Security',
                'desc' => 'Email code sign-in — no password.',
                'action' => 'modal',
                'modal' => 'security',
                'cta' => 'View sign-in',
            ],
        ],
    ],
    [
        'id' => 'ai',
        'title' => 'AI Salesgirl',
        'open' => false,
        'icon' => brand_ai_icon_html('settings-acc-ai-icon', 20),
        'items' => [
            [
                'title' => 'AI preferences',
                'desc' => 'Tone, FAQs, and knowledge.',
                'action' => 'link',
                'href' => app_route('ai-salesgirl'),
                'cta' => 'Configure AI',
            ],
        ],
    ],
    [
        'id' => 'payments',
        'title' => 'Customer payments',
        'open' => false,
        'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M2 10h20" stroke="currentColor" stroke-width="1.75"/></svg>',
        'items' => [
            [
                'title' => 'Payment instructions',
                'desc' => 'How customers pay you in DMs.',
                'action' => 'modal',
                'modal' => 'payment',
                'cta' => 'Edit payment details',
            ],
        ],
    ],
];

require __DIR__ . '/includes/dashboard/layout-start.php';
require __DIR__ . '/includes/dashboard/settings-content.php';
require __DIR__ . '/includes/dashboard/layout-end.php';
