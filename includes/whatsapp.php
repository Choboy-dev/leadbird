<?php

declare(strict_types=1);

function whatsapp_webhook_verify_challenge(): bool
{
    return meta_webhook_verify_challenge();
}

function whatsapp_verify_signature(string $rawBody): bool
{
    return meta_verify_signature($rawBody);
}

/**
 * @param array<string, mixed> $connectedAccount
 */
function whatsapp_send_text_message(array $connectedAccount, string $recipientPhone, string $text): bool
{
    $token = (string) ($connectedAccount['access_token'] ?? app_config()['whatsapp']['system_user_token'] ?? '');
    $phoneNumberId = (string) ($connectedAccount['platform_account_id'] ?? '');

    if ($token === '' || $phoneNumberId === '' || $text === '') {
        return false;
    }

    $version = (string) (app_config()['meta']['graph_version'] ?? 'v21.0');
    $url = 'https://graph.facebook.com/' . $version . '/' . rawurlencode($phoneNumberId) . '/messages';

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => preg_replace('/\D+/', '', $recipientPhone),
        'type' => 'text',
        'text' => ['body' => $text],
    ];

    return meta_graph_post($url, $token, $payload);
}

/**
 * @param array<string, mixed> $connectedAccount
 */
function whatsapp_send_image_message(array $connectedAccount, string $recipientPhone, string $imageUrl): bool
{
    $token = (string) ($connectedAccount['access_token'] ?? app_config()['whatsapp']['system_user_token'] ?? '');
    $phoneNumberId = (string) ($connectedAccount['platform_account_id'] ?? '');

    if ($token === '' || $phoneNumberId === '' || $imageUrl === '') {
        return false;
    }

    $version = (string) (app_config()['meta']['graph_version'] ?? 'v21.0');
    $url = 'https://graph.facebook.com/' . $version . '/' . rawurlencode($phoneNumberId) . '/messages';

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => preg_replace('/\D+/', '', $recipientPhone),
        'type' => 'image',
        'image' => ['link' => $imageUrl],
    ];

    return meta_graph_post($url, $token, $payload);
}

/**
 * @param array<string, mixed> $connectedAccount
 */
function whatsapp_send_document_message(array $connectedAccount, string $recipientPhone, string $fileUrl, string $filename): bool
{
    $token = (string) ($connectedAccount['access_token'] ?? app_config()['whatsapp']['system_user_token'] ?? '');
    $phoneNumberId = (string) ($connectedAccount['platform_account_id'] ?? '');

    if ($token === '' || $phoneNumberId === '' || $fileUrl === '') {
        return false;
    }

    $version = (string) (app_config()['meta']['graph_version'] ?? 'v21.0');
    $url = 'https://graph.facebook.com/' . $version . '/' . rawurlencode($phoneNumberId) . '/messages';

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => preg_replace('/\D+/', '', $recipientPhone),
        'type' => 'document',
        'document' => [
            'link' => $fileUrl,
            'filename' => $filename !== '' ? $filename : 'file',
        ],
    ];

    return meta_graph_post($url, $token, $payload);
}

/**
 * @param array<string, mixed> $connectedAccount
 */
function whatsapp_send_video_message(array $connectedAccount, string $recipientPhone, string $videoUrl): bool
{
    $token = (string) ($connectedAccount['access_token'] ?? app_config()['whatsapp']['system_user_token'] ?? '');
    $phoneNumberId = (string) ($connectedAccount['platform_account_id'] ?? '');

    if ($token === '' || $phoneNumberId === '' || $videoUrl === '') {
        return false;
    }

    $version = (string) (app_config()['meta']['graph_version'] ?? 'v21.0');
    $url = 'https://graph.facebook.com/' . $version . '/' . rawurlencode($phoneNumberId) . '/messages';

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => preg_replace('/\D+/', '', $recipientPhone),
        'type' => 'video',
        'video' => ['link' => $videoUrl],
    ];

    return meta_graph_post($url, $token, $payload);
}

/**
 * @param array<string, mixed> $settings
 */
function whatsapp_send_owner_alert(array $settings, string $alertMessage): bool
{
    $ownerPhone = (string) ($settings['escalation_phone'] ?? '');
    if ($ownerPhone === '') {
        return false;
    }

    $businessId = (string) ($settings['business_id'] ?? '');
    $account = null;
    if ($businessId !== '') {
        $accounts = RepositoryFactory::connectedAccounts()->listByBusinessId($businessId, true);
        foreach ($accounts as $row) {
            if (($row['platform'] ?? '') === 'whatsapp') {
                $account = $row;
                break;
            }
        }
    }

    if ($account === null) {
        error_log('[WhatsApp] No WhatsApp connected account for owner alert.');
        return false;
    }

    return whatsapp_send_text_message($account, $ownerPhone, $alertMessage);
}

/**
 * @return list<array{
 *   phone_number_id: string,
 *   sender_phone: string,
 *   message_text: string,
 *   customer_name: ?string,
 *   media_id: ?string,
 *   media_type: ?string
 * }>
 */
function whatsapp_parse_inbound_events(array $payload): array
{
    $events = [];

    foreach ($payload['entry'] ?? [] as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        foreach ($entry['changes'] ?? [] as $change) {
            if (!is_array($change)) {
                continue;
            }

            $value = $change['value'] ?? null;
            if (!is_array($value)) {
                continue;
            }

            $phoneNumberId = (string) ($value['metadata']['phone_number_id'] ?? '');
            $contactNames = [];
            foreach ($value['contacts'] ?? [] as $contact) {
                if (!is_array($contact)) {
                    continue;
                }

                $waId = (string) ($contact['wa_id'] ?? '');
                if ($waId === '') {
                    continue;
                }

                $profile = $contact['profile'] ?? null;
                $name = is_array($profile) ? trim((string) ($profile['name'] ?? '')) : '';
                if ($name !== '') {
                    $contactNames[$waId] = $name;
                }
            }

            foreach ($value['messages'] ?? [] as $message) {
                if (!is_array($message)) {
                    continue;
                }

                $text = trim((string) ($message['text']['body'] ?? ''));
                $from = (string) ($message['from'] ?? '');
                $type = (string) ($message['type'] ?? 'text');
                $mediaId = null;
                $mediaType = null;

                if ($type === 'image') {
                    $mediaId = trim((string) ($message['image']['id'] ?? ''));
                    $mediaType = 'image';
                }

                if ($from === '' || $phoneNumberId === '') {
                    continue;
                }

                if ($text === '' && $mediaId === null) {
                    continue;
                }

                $events[] = [
                    'phone_number_id' => $phoneNumberId,
                    'sender_phone' => $from,
                    'message_text' => $text,
                    'customer_name' => $contactNames[$from] ?? null,
                    'media_id' => $mediaId,
                    'media_type' => $mediaType,
                ];
            }
        }
    }

    return $events;
}

function whatsapp_fetch_media_url(string $mediaId, string $accessToken): ?string
{
    if ($mediaId === '' || $accessToken === '') {
        return null;
    }

    $meta = meta_graph_get(rawurlencode($mediaId), $accessToken, ['fields' => 'url,mime_type']);
    $url = trim((string) ($meta['url'] ?? ''));

    return $url !== '' ? $url : null;
}

/**
 * Ingest WhatsApp webhook payload (entry/changes format).
 * Used by webhooks/meta.php (app callback) and webhooks/whatsapp.php.
 */
function whatsapp_process_inbound_payload(array $payload): int
{
    $service = new InboundMessageService();
    $enqueued = 0;

    foreach (whatsapp_parse_inbound_events($payload) as $event) {
        $connectedAccount = RepositoryFactory::connectedAccounts()->findActiveByPlatformAccountId(
            'whatsapp',
            $event['phone_number_id']
        );

        if ($connectedAccount === null) {
            meta_webhook_log(
                'whatsapp ingest skipped phone_number_id=' . $event['phone_number_id']
                . ' sender=' . $event['sender_phone']
                . ' reason=no_active_account'
            );
            continue;
        }

        $mediaUrl = null;
        if (!empty($event['media_id'])) {
            $mediaUrl = whatsapp_fetch_media_url(
                (string) $event['media_id'],
                (string) ($connectedAccount['access_token'] ?? '')
            );
        }

        $context = $service->ingest(
            'whatsapp',
            $event['phone_number_id'],
            $event['sender_phone'],
            $event['message_text'],
            $event['customer_name'] ?? null,
            $event['sender_phone'],
            $mediaUrl,
            $event['media_type'] ?? null,
            null
        );

        if ($context !== null) {
            meta_webhook_log(
                'whatsapp ingested phone_number_id=' . $event['phone_number_id']
                . ' sender=' . $event['sender_phone']
                . ' business=' . (string) ($context['business_id'] ?? '')
            );
            (new AutomationQueue())->enqueueProcessMessage($context);
            $enqueued++;
        } else {
            meta_webhook_log(
                'whatsapp ingest skipped phone_number_id=' . $event['phone_number_id']
                . ' sender=' . $event['sender_phone']
                . ' has_text=' . ($event['message_text'] !== '' ? '1' : '0')
            );
        }
    }

    return $enqueued;
}
