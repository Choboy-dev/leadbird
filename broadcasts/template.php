<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/bootstrap.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

$pageTitle = 'WhatsApp template — ' . $brandName;
$dashboardNav = 'wa_templates';
$pageHeading = 'Templates';
$extraStylesheets = ['dashboard-wa-senders.css?v=37'];
$extraScripts = ['dashboard-wa-template.js?v=33'];

if (!dashboard_require_feature('broadcasts') || $businessId === null) {
    $extraScripts = [];
    require dirname(__DIR__) . '/includes/dashboard/layout-start.php';
    require dirname(__DIR__) . '/includes/dashboard/feature-locked.php';
    require dirname(__DIR__) . '/includes/dashboard/layout-end.php';
    return;
}

$preview = ($_GET['preview'] ?? '') === '1';
$previewQuery = $preview ? ['preview' => '1'] : [];
$templatesUrl = app_route('broadcasts/templates', $previewQuery);
$repo = RepositoryFactory::broadcasts();
$business = RepositoryFactory::businesses()->findById($businessId);
$findTemplate = static fn(string $templateId): ?array => $preview
    ? null
    : $repo->findTemplate($templateId, $businessId);

$blank = [
    'name' => '',
    'language' => 'en',
    'category' => 'MARKETING',
    'content_type' => 'text',
    'header' => '',
    'header_format' => '',
    'media_url' => '',
    'body' => '',
    'footer' => '',
    'samples' => [],
    'buttons' => [],
    'editable' => true,
];

$template = null;
$spec = $blank;
$source = 'new';
$id = trim((string) ($_GET['id'] ?? ''));
$copyId = trim((string) ($_GET['copy'] ?? ''));

if ($id !== '') {
    $template = $findTemplate($id);
    if ($template === null || $template['deleted_at'] !== null) {
        flash_set('error', 'That template no longer exists.');
        redirect($templatesUrl);
    }
    $spec = WhatsAppBroadcastService::editorSpec($template);
    $source = 'existing';
} elseif ($copyId !== '') {
    $original = $findTemplate($copyId);
    if ($original !== null) {
        $spec = WhatsAppBroadcastService::editorSpec($original);
        $spec['name'] = mb_substr($spec['name'], 0, 500) . '_copy';
        $source = 'copy';
    }
} else {
    $newName = trim((string) ($_GET['name'] ?? ''));
    $newLanguage = trim((string) ($_GET['language'] ?? ''));
    $newType = trim((string) ($_GET['type'] ?? ''));
    if (
        ($_GET['edit'] ?? '') === '1'
        || preg_match('/^[a-z0-9_]{1,512}$/', $newName) !== 1
        || !isset(WhatsAppBroadcastService::TEMPLATE_LANGUAGES[$newLanguage])
        || !in_array($newType, WhatsAppBroadcastService::EDITABLE_TYPES, true)
    ) {
        $extraScripts = ['dashboard-wa-template-create.js?v=5'];
        $pageBackUrl = $templatesUrl;
        $prefill = ['name' => $newName, 'language' => $newLanguage, 'type' => $newType];
        require dirname(__DIR__) . '/includes/dashboard/layout-start.php';
        require dirname(__DIR__) . '/includes/dashboard/wa-template-create-content.php';
        require dirname(__DIR__) . '/includes/dashboard/layout-end.php';
        return;
    }
    $spec['name'] = $newName;
    $spec['language'] = $newLanguage;
    $spec['content_type'] = $newType;
}

if ($source !== 'existing') {
    preg_match_all('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', $spec['header'] . ' ' . $spec['body'], $vars);
    foreach (array_unique($vars[1]) as $var) {
        if (trim((string) ($spec['samples'][$var] ?? '')) === '') {
            $spec['samples'][$var] = WhatsAppStarterTemplates::sampleFor($var);
        }
    }
}

$status = $template !== null ? strtoupper((string) $template['status']) : null;
$lockedReason = null;
if ($template !== null && !$spec['editable']) {
    $lockedReason = 'This template uses parts you can only change in WhatsApp Manager, like a carousel, or a photo together with buttons.';
} elseif ($status === 'APPROVED') {
    $lockedReason = 'WhatsApp has approved this template, so it can’t be edited. To make changes, duplicate it and submit the copy.';
} elseif ($status !== null && !in_array($status, ['DRAFT', 'REJECTED', 'PAUSED'], true)) {
    $lockedReason = $status === 'PENDING' || $status === 'IN_APPEAL'
        ? 'WhatsApp is reviewing this template. You can edit it once they have decided.'
        : 'You can’t edit this template right now.';
}

$editUrl = $source === 'new'
    ? app_route('broadcasts/template', ['edit' => '1', 'name' => $spec['name'], 'language' => $spec['language'], 'type' => $spec['content_type']] + $previewQuery)
    : null;
$pageBackUrl = $templatesUrl;
$templateRef = $source === 'existing'
    ? WhatsAppBroadcastService::templateRef($businessId, (string) $spec['name'], (string) $spec['language'])
    : null;
$wabaId = $template !== null && !$preview ? (string) $template['waba_id'] : null;
$hannahLibrary = WhatsAppTemplateLibrary::all((string) ($business['business_name'] ?? ''));
$flashSuccess = flash_get('success');

require dirname(__DIR__) . '/includes/dashboard/layout-start.php';
require dirname(__DIR__) . '/includes/dashboard/wa-template-content.php';
require dirname(__DIR__) . '/includes/dashboard/layout-end.php';
