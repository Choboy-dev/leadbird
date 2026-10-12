<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/require_dashboard.php';
require_once dirname(__DIR__) . '/includes/dashboard/helpers.php';

header('Content-Type: application/json; charset=utf-8');

function broadcast_json(array $body, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($body, JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    broadcast_json(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

$userId = (new SessionAuth())->userId();
if ($userId === null) {
    broadcast_json(['ok' => false, 'message' => 'Please sign in again.'], 401);
}

require_csrf();

$subscription = (new SubscriptionService())->getActiveSubscription((string) $userId);
$planSlug = $subscription !== null ? (string) $subscription['plan_slug'] : 'starter';
if (!PlanAccess::allows($planSlug, 'broadcasts')) {
    broadcast_json(['ok' => false, 'message' => 'Bulk WhatsApp Messaging is available on the Vendor plan and above.'], 403);
}

$business = (new BusinessContext())->resolveActiveBusiness((string) $userId);
if ($business === null) {
    broadcast_json(['ok' => false, 'message' => 'No active business found.'], 422);
}
$businessId = (string) $business['id'];

if (!rate_limit_allow('broadcast_action:' . $userId, 120, 3600)) {
    broadcast_json(['ok' => false, 'message' => 'Too many requests. Please wait a few minutes.'], 429);
}

$raw = file_get_contents('php://input');
$input = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($input)) {
    broadcast_json(['ok' => false, 'message' => 'Invalid request.'], 400);
}

$service = new WhatsAppBroadcastService();
$repo = RepositoryFactory::broadcasts();
$action = (string) ($input['action'] ?? '');
$str = static fn(string $key): string => trim((string) ($input[$key] ?? ''));

try {
    switch ($action) {
        case 'refresh_status':
            $service->accountStatus($businessId, true);
            $service->syncTemplates($businessId);
            broadcast_json(['ok' => true]);

        case 'draft_template':
            if (!rate_limit_allow('broadcast_draft:' . $userId, 30, 3600)) {
                broadcast_json(['ok' => false, 'message' => 'Too many drafts. Please wait a little.'], 429);
            }
            $result = $service->draftTemplate($businessId, $str('brief'), $str('category'));
            broadcast_json(
                $result['ok'] ? ['ok' => true, 'body' => $result['body']] : ['ok' => false, 'message' => $result['error']],
                $result['ok'] ? 200 : 422
            );

        case 'write_template_content':
            if (!rate_limit_allow('broadcast_draft:' . $userId, 30, 3600)) {
                broadcast_json(['ok' => false, 'message' => 'You asked Hannah to write a lot in a short time. Please wait a little and try again.'], 429);
            }
            $result = $service->writeTemplateContent(
                $businessId,
                $str('field'),
                [
                    'name' => $str('name'),
                    'body' => (string) ($input['body'] ?? ''),
                    'buttons' => is_array($input['buttons'] ?? null) ? array_values($input['buttons']) : [],
                    'category' => $str('category'),
                    'content_type' => $str('content_type'),
                    'variable' => $str('variable'),
                    'button_type' => $str('button_type'),
                    'current' => $str('current'),
                ]
            );
            broadcast_json(
                $result['ok']
                    ? ['ok' => true, 'text' => $result['text'], 'samples' => $result['samples']]
                    : ['ok' => false, 'message' => $result['error']],
                $result['ok'] ? 200 : 422
            );

        case 'create_template':
            if (!rate_limit_allow('broadcast_template:' . $userId, 20, 3600)) {
                broadcast_json(['ok' => false, 'message' => 'Too many templates in a short time. Please wait a little.'], 429);
            }
            $result = $service->createTemplate($businessId, $str('title'), $str('category'), (string) ($input['body'] ?? ''));
            broadcast_json(
                $result['ok'] ? ['ok' => true] : ['ok' => false, 'message' => $result['error']],
                $result['ok'] ? 200 : 422
            );

        case 'submit_template':
            if (!empty($input['preview'])) {
                $result = $service->submitTemplate($businessId, $input, null, true);
                broadcast_json($result['ok'] ? ['ok' => true, 'payload' => $result['payload']] : ['ok' => false, 'message' => $result['error']], $result['ok'] ? 200 : 422);
            }
            if (!rate_limit_allow('broadcast_template:' . $userId, 20, 3600)) {
                broadcast_json(['ok' => false, 'message' => 'Too many templates in a short time. Please wait a little.'], 429);
            }
            $templateId = $str('id');
            $result = $service->submitTemplate($businessId, $input, $templateId !== '' ? $templateId : null);
            if (!$result['ok']) {
                broadcast_json(['ok' => false, 'message' => $result['error']], 422);
            }
            unset($_SESSION['lb_wa_templates_synced_' . $businessId]);
            flash_set('success', $templateId !== ''
                ? 'Your changes were sent to WhatsApp. They usually review them within a few minutes.'
                : 'Your template was sent to WhatsApp. They usually review it within a few minutes.');
            broadcast_json(['ok' => true, 'redirect' => app_route('broadcasts/templates')]);

        case 'save_template_draft':
            if (!empty($input['preview'])) {
                broadcast_json(['ok' => true, 'preview' => true]);
            }
            if (!rate_limit_allow('broadcast_template_draft:' . $userId, 120, 3600)) {
                broadcast_json(['ok' => false, 'message' => 'Too many saves in a short time. Please wait a little.'], 429);
            }
            $templateId = $str('id');
            $result = $service->saveTemplateDraft($businessId, $input, $templateId !== '' ? $templateId : null);
            if (!$result['ok']) {
                broadcast_json(['ok' => false, 'message' => $result['error']], 422);
            }
            flash_set('success', 'Saved. Submit it for WhatsApp approval when you’re ready.');
            broadcast_json(['ok' => true, 'redirect' => app_route('broadcasts/template', ['id' => $result['id']])]);

        case 'delete_template':
            $result = $service->deleteTemplate($businessId, $str('id'));
            broadcast_json($result['ok'] ? ['ok' => true] : ['ok' => false, 'message' => $result['error']], $result['ok'] ? 200 : 422);

        case 'add_contacts':
            if (empty($input['consent'])) {
                broadcast_json(['ok' => false, 'message' => 'Confirm these people agreed to receive WhatsApp messages from you.'], 422);
            }
            $parsed = WhatsAppBroadcastService::parseContactLines((string) ($input['lines'] ?? ''));
            $result = $service->addContacts($businessId, $planSlug, $parsed['rows'], 'manual');
            if (!$result['ok']) {
                broadcast_json(['ok' => false, 'message' => $result['error']], 422);
            }
            broadcast_json(['ok' => true, 'added' => $result['added'], 'skipped' => $result['skipped'], 'invalid' => $parsed['invalid']]);

        case 'import_inbox':
            if (empty($input['consent'])) {
                broadcast_json(['ok' => false, 'message' => 'Confirm these customers agreed to receive WhatsApp messages from you.'], 422);
            }
            $result = $service->importInboxContacts($businessId, $planSlug);
            if (!$result['ok']) {
                broadcast_json(['ok' => false, 'message' => $result['error']], 422);
            }
            broadcast_json(['ok' => true, 'added' => $result['added'], 'skipped' => $result['skipped'], 'invalid' => 0]);

        case 'delete_contact':
            $repo->deleteContact($str('id'), $businessId);
            broadcast_json(['ok' => true]);

        case 'estimate_broadcast':
            $result = $service->estimateBroadcast($businessId, (string) $userId, $str('template_id'));
            broadcast_json(
                $result['ok'] ? ['ok' => true, 'summary' => $result['summary']] : ['ok' => false, 'message' => $result['error']],
                $result['ok'] ? 200 : 422
            );

        case 'create_broadcast':
            $result = $service->createBroadcast(
                $businessId,
                (string) $userId,
                $str('name'),
                $str('template_id'),
                $str('name_fallback'),
                !empty($input['confirm_low_tokens'])
            );
            if (!$result['ok']) {
                broadcast_json(array_filter([
                    'ok' => false,
                    'message' => $result['error'],
                    'needs_confirm' => $result['needs_confirm'] ?? null,
                    'summary' => $result['summary'] ?? null,
                ], static fn ($value) => $value !== null), 422);
            }
            $service->processBroadcast((string) $result['broadcast_id']);
            broadcast_json(['ok' => true]);

        case 'resume_broadcast':
            $result = $service->resume($str('id'), $businessId);
            if ($result['ok']) {
                $service->processBroadcast($str('id'));
            }
            broadcast_json($result['ok'] ? ['ok' => true] : ['ok' => false, 'message' => $result['error']], $result['ok'] ? 200 : 422);

        case 'cancel_broadcast':
            $result = $service->cancel($str('id'), $businessId);
            broadcast_json($result['ok'] ? ['ok' => true] : ['ok' => false, 'message' => $result['error']], $result['ok'] ? 200 : 422);

        default:
            broadcast_json(['ok' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (Throwable $e) {
    error_log('[Broadcast] action ' . $action . ': ' . $e->getMessage());
    broadcast_json(['ok' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}
