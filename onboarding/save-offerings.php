<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/onboarding/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_route('onboarding/offerings'));
}

$save = onboarding_resolve_save_request();
$userId = $save['user_id'];
$onboarding = $save['onboarding'];
$appUrl = $save['app_url'];
$dashboard = $save['dashboard'];
$business = $save['business'];

if ($business === null) {
    redirect(app_route('onboarding/profile'));
}

$businessId = (string) $business['id'];
$action = (string) ($_POST['action'] ?? '');
$imageService = new OfferingImageService();

$offeringsListUrl = $dashboard
    ? app_route('offers')
    : app_route('onboarding/offerings');

$offeringsEditUrl = static function (?string $offeringId = null) use ($offeringsListUrl, $dashboard): string {
    if ($offeringId === null || $offeringId === '') {
        return $offeringsListUrl;
    }

    $separator = $dashboard ? '&' : '?';

    return $offeringsListUrl . $separator . 'edit=' . rawurlencode($offeringId);
};

if ($action === 'delete') {
    $offeringId = (string) ($_POST['offering_id'] ?? '');
    $offering = RepositoryFactory::offerings()->findById($offeringId);
    if ($offering !== null && (string) $offering['business_id'] === $businessId) {
        $imageService->deleteStoredPath((string) ($offering['image_url'] ?? ''));
        RepositoryFactory::offerings()->delete($offeringId);
        (new BusinessKnowledgeSyncService())->syncManualSources($businessId);
        flash_set('success', 'Offer removed.');
    }
    redirect($offeringsListUrl);
}

$parseOfferingInput = static function (?string $editingId = null, ?string $existingImageUrl = null) use ($offeringsEditUrl, $businessId, $imageService): array {
    $parsed = OfferingInputParser::parse($editingId);
    if (!$parsed['ok']) {
        flash_set('error', (string) $parsed['error']);
        redirect($offeringsEditUrl($editingId));
    }

    $data = $parsed['data'];

    $imageUrl = $existingImageUrl;
    if (!empty($_POST['remove_image'])) {
        if ($imageUrl !== null && $imageUrl !== '') {
            $imageService->deleteStoredPath($imageUrl);
        }
        $imageUrl = null;
    }

    try {
        $uploadedUrl = $imageService->storeUpload($businessId, $_FILES['image'] ?? null);
    } catch (InvalidArgumentException $exception) {
        flash_set('error', $exception->getMessage());
        redirect($offeringsEditUrl($editingId));
    } catch (Throwable $exception) {
        error_log('[OfferingImage] ' . $exception->getMessage());
        flash_set('error', 'Could not save the image. Please try again.');
        redirect($offeringsEditUrl($editingId));
    }

    if ($uploadedUrl !== null) {
        if ($imageUrl !== null && $imageUrl !== '') {
            $imageService->deleteStoredPath($imageUrl);
        }
        $imageUrl = $uploadedUrl;
    }

    $data['image_url'] = $imageUrl;

    $offerCurrency = money_normalize_currency((string) ($data['currency'] ?? ''));
    if ($offerCurrency !== null) {
        try {
            RepositoryFactory::businessSettings()->update($businessId, [
                'catalog_currency' => $offerCurrency,
            ]);
        } catch (Throwable $e) {
            error_log('[save-offerings] catalog_currency: ' . $e->getMessage());
        }
    }

    return $data;
};

if ($action === 'add') {
    RepositoryFactory::offerings()->create($businessId, $parseOfferingInput());
    (new BusinessKnowledgeSyncService())->syncManualSources($businessId);

    flash_set('success', 'Offer saved.');
    redirect($offeringsListUrl);
}

if ($action === 'update') {
    $offeringId = (string) ($_POST['offering_id'] ?? '');
    $offering = RepositoryFactory::offerings()->findById($offeringId);
    if ($offering === null || (string) $offering['business_id'] !== $businessId) {
        flash_set('error', 'Offer not found.');
        redirect($offeringsListUrl);
    }

    RepositoryFactory::offerings()->update(
        $offeringId,
        $parseOfferingInput($offeringId, (string) ($offering['image_url'] ?? ''))
    );
    (new BusinessKnowledgeSyncService())->syncManualSources($businessId);

    flash_set('success', 'Offer updated.');
    redirect($offeringsListUrl);
}

if ($action === 'continue') {
    if ($onboarding->getOfferingsForUser($userId) === []) {
        flash_set('error', 'Add at least one offer before continuing.');
        redirect(app_route('onboarding/offerings'));
    }

    $onboarding->advanceAfterStep($businessId, OnboardingService::STEP_OFFERINGS);
    redirect(app_route('onboarding/policies'));
}

redirect(app_route('onboarding/offerings'));
