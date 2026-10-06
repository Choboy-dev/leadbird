<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/require_dashboard.php';
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/helpers.php';
require_once __DIR__ . '/includes/onboarding/helpers.php';

$pageTitle = 'Offers — ' . $brandName;
$dashboardNav = 'my_business';
$pageHeading = 'Offers & Catalog';
$extraStylesheets = ['onboarding.css?v=21', 'dashboard-offers.css?v=16', 'onboarding-offers.css?v=14'];
$extraScripts = ['onboarding-offers.js?v=7', 'dashboard-offers.js?v=8'];

$onboarding = new OnboardingService();
$offerings = $userId !== null ? $onboarding->getOfferingsForUser($userId) : [];

$editingOffering = null;
$editingId = trim((string) ($_GET['edit'] ?? ''));
if ($editingId !== '' && $businessId !== null) {
    $candidate = RepositoryFactory::offerings()->findById($editingId);
    if ($candidate !== null && (string) ($candidate['business_id'] ?? '') === $businessId) {
        $editingOffering = $candidate;
    }
}

$isEditing = $editingOffering !== null;
$formContext = $isEditing ? 'dashboard' : 'modal';
$saveUrl = app_route('onboarding/save-offerings');
$cancelUrl = app_route('offers');
$formAction = $isEditing ? 'update' : 'add';
$formState = OfferingInputParser::formState($editingOffering);
if ($editingOffering === null && is_array($settings ?? null)) {
    $formState['currency'] = money_business_catalog_currency($settings);
}
$googleCalendarConnected = $businessId !== null
    && RepositoryFactory::businessGoogleCalendar()->findByBusinessId($businessId) !== null;

$error = flash_get('error');
$success = flash_get('success');
$openOfferModal = !$isEditing && (($_GET['new'] ?? '') === '1');

if ($isEditing) {
    $offerName = trim((string) ($editingOffering['name'] ?? ''));
    $pageHeading = $offerName !== '' ? $offerName : 'Edit offer';
}

require __DIR__ . '/includes/dashboard/layout-start.php';
require __DIR__ . '/includes/dashboard/offers-catalog-content.php';
require __DIR__ . '/includes/dashboard/layout-end.php';
