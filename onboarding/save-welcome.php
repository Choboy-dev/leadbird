<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_subscription.php';

$welcomeUrl = app_route('onboarding/welcome');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($welcomeUrl);
}
if (!csrf_verify()) {
    flash_set('error', 'Your session expired. Please try again.');
    redirect($welcomeUrl);
}

$userId = (string) (new SessionAuth())->userId();
$ctx = new BusinessContext();
$business = $ctx->getOnboardingBusiness($userId);
if ($business === null) {
    redirect(app_route('dashboard'));
}

$businessName = trim((string) ($_POST['business_name'] ?? ''));
$industry = (string) ($_POST['industry'] ?? '');
$ownerRole = (string) ($_POST['owner_role'] ?? '');
$teamSize = (string) ($_POST['team_size'] ?? '');
$website = trim((string) ($_POST['website'] ?? ''));

$problem = match (true) {
    $businessName === '' => 'Tell us what your business is called.',
    mb_strlen($businessName) > 120 => 'Your business name is too long.',
    !isset(OnboardingService::INDUSTRIES[$industry]) => 'Choose your industry.',
    !isset(OnboardingService::OWNER_ROLES[$ownerRole]) => 'Choose what best describes you.',
    !isset(OnboardingService::TEAM_SIZES[$teamSize]) => 'Choose how big your team is.',
    default => null,
};

if ($problem === null && $website !== '') {
    if (!preg_match('#^https?://#i', $website)) {
        $website = 'https://' . $website;
    }
    $host = parse_url($website, PHP_URL_HOST);
    if (mb_strlen($website) > 255 || filter_var($website, FILTER_VALIDATE_URL) === false || !is_string($host) || !str_contains($host, '.')) {
        $problem = 'That website link doesn’t look right. Check it or leave it empty.';
    }
}

if ($problem !== null) {
    flash_set('error', $problem);
    redirect($welcomeUrl);
}

$businessId = (string) $business['id'];
RepositoryFactory::businesses()->updateWelcomeProfile($businessId, [
    'business_name' => $businessName,
    'industry' => $industry,
    'website' => $website !== '' ? $website : null,
    'owner_role' => $ownerRole,
    'team_size' => $teamSize,
]);

redirect(app_route('onboarding/your-channels'));
