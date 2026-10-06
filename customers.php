<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/helpers.php';

$pageTitle = 'Customers — ' . $brandName;
$dashboardNav = 'customers';
$pageHeading = 'Customers';
$extraStylesheets = ['dashboard-connected-accounts.css?v=26'];

if (!dashboard_require_feature('customers')) {
    require __DIR__ . '/includes/dashboard/layout-start.php';
    require __DIR__ . '/includes/dashboard/feature-locked.php';
    require __DIR__ . '/includes/dashboard/layout-end.php';
    return;
}

$customers = $businessId !== null
    ? $dashboardService->getCustomersWithSummary($businessId)
    : [];

$conversationByCustomer = [];
if ($businessId !== null) {
    foreach (RepositoryFactory::conversations()->listByBusinessId($businessId) as $conversation) {
        $customerId = (string) ($conversation['customer_id'] ?? '');
        if ($customerId === '' || isset($conversationByCustomer[$customerId])) {
            continue;
        }
        $conversationByCustomer[$customerId] = (string) ($conversation['id'] ?? '');
    }
}

$allCustomersList = [];

foreach ($customers as $customer) {
    $customerId = (string) ($customer['id'] ?? '');
    $displayName = trim((string) ($customer['customer_name'] ?? ''));
    if ($displayName === '') {
        $displayName = 'Unknown contact';
    }

    $platform = trim((string) ($customer['platform'] ?? ''));

    $lastMessage = trim((string) ($customer['last_message'] ?? ''));
    if ($lastMessage === '') {
        $lastMessage = 'No messages yet';
    }

    $conversationId = $conversationByCustomer[$customerId] ?? '';
    $inboxUrl = $conversationId !== ''
        ? app_route('inbox', ['conversation' => $conversationId])
        : app_route('inbox');

    $initial = dash_initials_for_name($displayName);
    $avatarSrc = dash_customer_avatar_src($customer);
    $searchText = strtolower(trim(
        $displayName . ' ' . dash_platform_label($platform) . ' ' . $platform . ' ' . $lastMessage
    ));

    $allCustomersList[] = [
        'id' => $customerId,
        'customer' => $customer,
        'name' => $displayName,
        'initial' => $initial,
        'avatar_src' => $avatarSrc,
        'platform' => $platform,
        'platform_label' => dash_platform_label($platform),
        'last_message' => knowledge_excerpt($lastMessage, 100),
        'inbox_url' => $inboxUrl,
        'search_text' => $searchText,
    ];
}

usort($allCustomersList, static function (array $a, array $b): int {
    return strcmp((string) ($b['customer']['created_at'] ?? ''), (string) ($a['customer']['created_at'] ?? ''));
});

require __DIR__ . '/includes/dashboard/layout-start.php';
require __DIR__ . '/includes/dashboard/customers-content.php';
require __DIR__ . '/includes/dashboard/layout-end.php';
