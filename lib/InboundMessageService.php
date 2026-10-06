<?php

declare(strict_types=1);

/**
 * Persist inbound DM events and prepare context for AI processing.
 */
final class InboundMessageService
{
    /**
     * @return array<string, mixed>|null Context for process_inbound_message()
     */
    public function ingest(
        string $platform,
        string $platformAccountId,
        string $senderId,
        string $messageText,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?string $messageMediaUrl = null,
        ?string $messageMediaType = null,
        ?string $referralContext = null,
        ?string $profilePictureUrl = null
    ): ?array {
        $messageText = trim($messageText);
        $messageMediaUrl = trim((string) $messageMediaUrl);
        $messageMediaType = trim((string) $messageMediaType);
        $referralContext = trim((string) $referralContext);

        if ($messageText === '' && $messageMediaUrl === '') {
            return null;
        }

        if ($messageText === '' && $messageMediaType === 'image') {
            $messageText = '[Customer sent a photo]';
        }

        $account = RepositoryFactory::connectedAccounts()->findActiveByPlatformAccountId($platform, $platformAccountId);
        if ($account === null) {
            error_log('[InboundMessage] No connected account for ' . $platform . ':' . $platformAccountId);
            return null;
        }

        $businessId = (string) $account['business_id'];
        $business = RepositoryFactory::businesses()->findById($businessId);
        if ($business === null) {
            return null;
        }

        $customer = RepositoryFactory::customers()->findByPlatformUser($businessId, $platform, $senderId);
        $nextPicture = trim((string) $profilePictureUrl);
        if ($customer === null) {
            $customer = RepositoryFactory::customers()->create($businessId, [
                'customer_name' => $customerName,
                'phone' => $customerPhone ?? ($platform === 'whatsapp' ? $senderId : null),
                'platform' => $platform,
                'platform_user_id' => $senderId,
                'profile_picture_url' => $nextPicture !== '' ? $nextPicture : null,
            ]);
        } else {
            $existingName = trim((string) ($customer['customer_name'] ?? ''));
            $existingPhone = trim((string) ($customer['phone'] ?? ''));
            $existingPicture = trim((string) ($customer['profile_picture_url'] ?? ''));

            $nextName = $existingName !== '' ? $existingName : trim((string) $customerName);
            $nextPhone = $existingPhone !== '' ? $existingPhone : trim((string) ($customerPhone ?? ($platform === 'whatsapp' ? $senderId : '')));
            $storedPicture = $existingPicture !== '' ? $existingPicture : ($nextPicture !== '' ? $nextPicture : null);

            if (
                $nextName !== $existingName
                || $nextPhone !== $existingPhone
                || ($existingPicture === '' && $nextPicture !== '')
            ) {
                RepositoryFactory::customers()->update((string) $customer['id'], [
                    'customer_name' => $nextName !== '' ? $nextName : null,
                    'phone' => $nextPhone !== '' ? $nextPhone : null,
                    'email' => $customer['email'] ?? null,
                    'platform' => $customer['platform'] ?? $platform,
                    'platform_user_id' => $customer['platform_user_id'] ?? $senderId,
                    'profile_picture_url' => $storedPicture,
                ]);
                $customer = RepositoryFactory::customers()->findById((string) $customer['id']) ?? $customer;
            }
        }

        $customerId = (string) $customer['id'];
        $conversation = RepositoryFactory::conversations()->findOpenForCustomer($businessId, $customerId, $platform);
        if ($conversation === null) {
            $conversation = RepositoryFactory::conversations()->create($businessId, [
                'customer_id' => $customerId,
                'platform' => $platform,
                'status' => 'open',
            ]);
        }

        $conversationId = (string) $conversation['id'];
        $inboundMessage = RepositoryFactory::messages()->create([
            'business_id' => $businessId,
            'customer_id' => $customerId,
            'conversation_id' => $conversationId,
            'platform' => $platform,
            'sender_type' => 'customer',
            'message_text' => $messageText,
            'message_media_url' => $messageMediaUrl !== '' ? $messageMediaUrl : null,
            'message_media_type' => $messageMediaType !== '' ? $messageMediaType : null,
            'referral_context' => $referralContext !== '' ? $referralContext : null,
        ]);

        return [
            'business_id' => $businessId,
            'business' => $business,
            'connected_account' => $account,
            'customer_id' => $customerId,
            'customer' => $customer,
            'conversation_id' => $conversationId,
            'inbound_message_id' => (string) $inboundMessage['id'],
            'platform' => $platform,
            'platform_account_id' => $platformAccountId,
            'sender_id' => $senderId,
            'message_text' => $messageText,
            'message_media_url' => $messageMediaUrl !== '' ? $messageMediaUrl : null,
            'message_media_type' => $messageMediaType !== '' ? $messageMediaType : null,
            'referral_context' => $referralContext !== '' ? $referralContext : null,
        ];
    }
}
