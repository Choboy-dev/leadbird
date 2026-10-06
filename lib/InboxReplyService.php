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
