<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/meta.php';
require_once dirname(__DIR__) . '/includes/whatsapp.php';

/**
 * Send manual replies from the Lead Bird inbox to connected channels.
 */
final class InboxReplyService
{
    /**
     * @return array{ok: bool, message?: string, data?: array<string, mixed>}
     */
    public function sendReply(string $businessId, string $conversationId, string $text, bool $pauseAi = true): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['ok' => false, 'message' => 'Write a message before sending.'];
        }

        if (strlen($text) > 4000) {
            return ['ok' => false, 'message' => 'Message is too long. Keep it under 4,000 characters.'];
        }

        $conversation = RepositoryFactory::conversations()->findById($conversationId);
        if ($conversation === null || (string) ($conversation['business_id'] ?? '') !== $businessId) {
            return ['ok' => false, 'message' => 'Conversation not found.'];
        }

        $platform = trim((string) ($conversation['platform'] ?? ''));
        if (!in_array($platform, ['facebook', 'instagram', 'whatsapp'], true)) {
            return ['ok' => false, 'message' => 'This channel does not support replies yet.'];
        }

        $customerId = trim((string) ($conversation['customer_id'] ?? ''));
        $customer = $customerId !== '' ? RepositoryFactory::customers()->findById($customerId) : null;
        if ($customer === null) {
            return ['ok' => false, 'message' => 'Customer record not found.'];
        }

        $recipientId = trim((string) ($customer['platform_user_id'] ?? ''));
        if ($recipientId === '' && $platform === 'whatsapp') {
            $recipientId = trim((string) ($customer['phone'] ?? ''));
        }
        if ($recipientId === '') {
            return ['ok' => false, 'message' => 'We cannot reach this customer on ' . dash_platform_label($platform) . '.'];
        }

        $account = RepositoryFactory::connectedAccounts()->findActiveByPlatform($businessId, $platform);
        if ($account === null) {
            return [
                'ok' => false,
                'message' => 'Connect ' . dash_platform_label($platform) . ' in Connected Accounts first.',
            ];
        }

        $sent = match ($platform) {
            'whatsapp' => whatsapp_send_text_message($account, $recipientId, $text),
            'instagram', 'facebook' => meta_send_text_message($platform, $account, $recipientId, $text),
            default => false,
        };

        if (!$sent) {
            return [
                'ok' => false,
                'message' => 'Could not send your message. Reconnect the channel in Connected Accounts and try again.',
            ];
        }

        $stored = RepositoryFactory::messages()->create([
            'business_id' => $businessId,
            'customer_id' => $customerId,
            'conversation_id' => $conversationId,
            'platform' => $platform,
            'sender_type' => 'owner',
            'message_text' => $text,
        ]);

        if ($pauseAi) {
            RepositoryFactory::conversations()->updateStatus($conversationId, 'human');
        }

        return [
            'ok' => true,
            'data' => [
                'message' => $stored,
                'ai_auto_reply' => !$pauseAi,
                'conversation_status' => $pauseAi ? 'human' : (string) ($conversation['status'] ?? 'open'),
            ],
        ];
    }

    /**
     * @return list<array{id: string, name: string, body: string, button: string, items: list<array{id: string, name: string, description: string}>}>
     */
    public function listPickers(string $businessId): array
    {
        $pickers = [];
        foreach (RepositoryFactory::broadcasts()->listListPickers($businessId) as $row) {
            $spec = json_decode((string) ($row['draft_spec'] ?? ''), true);
            $items = [];
            foreach (is_array($spec['list_items'] ?? null) ? $spec['list_items'] : [] as $item) {
                if (is_array($item) && trim((string) ($item['name'] ?? '')) !== '' && trim((string) ($item['id'] ?? '')) !== '') {
                    $items[] = [
                        'id' => (string) $item['id'],
                        'name' => (string) $item['name'],
                        'description' => (string) ($item['description'] ?? ''),
                    ];
                }
            }
            $button = trim((string) ($spec['list_button'] ?? ''));
            $body = trim((string) ($row['body'] ?? ''));
            if ($items === [] || $button === '' || $body === '') {
                continue;
            }
            $pickers[] = [
                'id' => (string) $row['id'],
                'name' => (string) $row['name'],
                'body' => $body,
                'button' => $button,
                'items' => $items,
            ];
        }

        return $pickers;
    }

    /**
     * @return array{ok: bool, message?: string, data?: array<string, mixed>}
     */
    public function sendListPicker(string $businessId, string $conversationId, string $templateId): array
    {
        $conversation = RepositoryFactory::conversations()->findById($conversationId);
        if ($conversation === null || (string) ($conversation['business_id'] ?? '') !== $businessId) {
            return ['ok' => false, 'message' => 'Conversation not found.'];
        }
        if ((string) ($conversation['platform'] ?? '') !== 'whatsapp') {
            return ['ok' => false, 'message' => 'Lists can only be sent on WhatsApp.'];
        }
        if (!RepositoryFactory::messages()->customerWroteWithin24Hours($conversationId)) {
            return ['ok' => false, 'message' => 'This customer last wrote more than 24 hours ago. WhatsApp only allows lists within 24 hours of their last message.'];
        }

        $picker = null;
        foreach ($this->listPickers($businessId) as $row) {
            if ($row['id'] === $templateId) {
                $picker = $row;
                break;
            }
        }
        if ($picker === null) {
            return ['ok' => false, 'message' => 'That list was not found. It may have been deleted.'];
        }

        $customerId = trim((string) ($conversation['customer_id'] ?? ''));
        $customer = $customerId !== '' ? RepositoryFactory::customers()->findById($customerId) : null;
        $recipientId = trim((string) ($customer['platform_user_id'] ?? ''));
        if ($recipientId === '') {
            $recipientId = trim((string) ($customer['phone'] ?? ''));
        }
        if ($recipientId === '') {
            return ['ok' => false, 'message' => 'We cannot reach this customer on WhatsApp.'];
        }

        $account = RepositoryFactory::connectedAccounts()->findActiveByPlatform($businessId, 'whatsapp');
        if ($account === null) {
            return ['ok' => false, 'message' => 'Connect WhatsApp in Connected Accounts first.'];
        }

        if (!whatsapp_send_list_message($account, $recipientId, $picker['body'], $picker['button'], $picker['items'])) {
            return ['ok' => false, 'message' => 'Could not send the list. Reconnect WhatsApp in Connected Accounts and try again.'];
        }

        $text = $picker['body'] . "\n\n" . $picker['button'] . ':';
        foreach ($picker['items'] as $item) {
            $text .= "\n- " . $item['name'];
        }

        $stored = RepositoryFactory::messages()->create([
            'business_id' => $businessId,
            'customer_id' => $customerId,
            'conversation_id' => $conversationId,
            'platform' => 'whatsapp',
            'sender_type' => 'owner',
            'message_text' => $text,
        ]);

        return ['ok' => true, 'data' => ['message' => $stored]];
    }

    /**
     * @return array{ok: bool, message?: string, ai_auto_reply?: bool}
     */
    public function setAiAutoReply(string $businessId, string $conversationId, bool $enabled): array
    {
        $conversation = RepositoryFactory::conversations()->findById($conversationId);
        if ($conversation === null || (string) ($conversation['business_id'] ?? '') !== $businessId) {
            return ['ok' => false, 'message' => 'Conversation not found.'];
        }

        RepositoryFactory::conversations()->updateStatus($conversationId, $enabled ? 'open' : 'human');

        return ['ok' => true, 'ai_auto_reply' => $enabled];
    }
}
