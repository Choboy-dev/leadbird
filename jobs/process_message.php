<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap-automation.php';

/**
 * @param array<string, mixed> $context From InboundMessageService::ingest()
 * @return array<string, mixed>
 */
function process_inbound_message(array $context): array
{
    $businessId = (string) ($context['business_id'] ?? '');
    $business = is_array($context['business'] ?? null) ? $context['business'] : null;
    if ($businessId === '' || $business === null) {
        return ['ok' => false, 'error' => 'missing_business'];
    }

    $userId = (string) ($business['user_id'] ?? '');
    $subscriptionService = new SubscriptionService();
    $subscriptionService->syncBusinessSubscription($userId);

    if ($userId === '' || !$subscriptionService->hasActiveSubscription($userId)) {
        return ['ok' => false, 'error' => 'subscription_inactive'];
    }

    $tokenService = new TokenService();
    if (!$tokenService->canSpend($userId, TokenCosts::DM_REPLY)) {
        return ['ok' => false, 'error' => 'tokens_depleted'];
    }

    $activeSubEarly = $subscriptionService->getActiveSubscription($userId);
    $planSlug = (string) ($activeSubEarly['plan_slug'] ?? $business['subscription_plan'] ?? 'starter');

    $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId);
    if ($settings === null) {
        $settings = RepositoryFactory::businessSettings()->create($businessId);
    }

    $offerings = RepositoryFactory::offerings()->listByBusinessId($businessId, true);
    $conversationId = (string) ($context['conversation_id'] ?? '');
    $conversation = $conversationId !== ''
        ? RepositoryFactory::conversations()->findById($conversationId)
        : null;
    if ($conversation !== null && (string) ($conversation['status'] ?? 'open') === 'human') {
        return ['ok' => true, 'skipped' => 'human_mode'];
    }

    if ($conversationId !== '') {
        try {
            RepositoryFactory::conversationFollowups()->resetOnCustomerReply($conversationId);
        } catch (Throwable $e) {
            // Table may not exist until migration 030 is applied.
            error_log('[process_message] followup reset: ' . $e->getMessage());
        }
    }

    $recentMessages = $conversationId !== ''
        ? RepositoryFactory::messages()->listByConversationId($conversationId, 8)
        : [];

    $retrievalQuery = ConversationContextService::buildKnowledgeRetrievalQuery(
        $recentMessages,
        (string) ($context['message_text'] ?? '')
    );
    $referralFormatted = knowledge_format_referral_context($context['referral_context'] ?? null);
    $customerImageUrl = trim((string) ($context['message_media_url'] ?? ''));
    if (($context['message_media_type'] ?? '') !== 'image') {
        $customerImageUrl = '';
    }

    $knowledgeChunks = (new BusinessKnowledgeRetrievalService())->retrieve(
        $businessId,
        $retrievalQuery,
        6,
        $customerImageUrl !== '' ? $customerImageUrl : null,
        $referralFormatted
    );

    $bookingState = $conversationId !== ''
        ? RepositoryFactory::conversationBookingState()->findByConversationId($conversationId)
        : null;
    $bookingContext = ConsultationBookingService::promptContext($bookingState);

    $customerId = (string) ($context['customer_id'] ?? '');

    $ai = openai_generate_sales_reply(
        $business,
        $settings,
        $offerings,
        $recentMessages,
        $context,
        $knowledgeChunks,
        $referralFormatted,
        $bookingContext,
        $planSlug
    );

    $bookingResult = (new ConsultationBookingService())->processAiBooking(
        $businessId,
        $conversationId,
        $customerId !== '' ? $customerId : null,
        $ai,
        $offerings
    );
    $ai = $bookingResult['ai'];

    if (!empty($ai['send_action_url'])) {
        $actionOfferingId = trim((string) ($ai['action_offering_id'] ?? ''));
        foreach ($offerings as $offering) {
            if ((string) ($offering['id'] ?? '') !== $actionOfferingId) {
                continue;
            }
            $link = OfferingTypeRegistry::actionUrl($offering);
            if ($link !== null && !str_contains((string) ($ai['customer_reply'] ?? ''), $link)) {
                $ai['customer_reply'] = trim((string) ($ai['customer_reply'] ?? '')) . "\n\n" . $link;
            }
            break;
        }
    }

    if (!empty($ai['payment_required']) && !empty($ai['payment_amount'])) {
        foreach ($offerings as $offering) {
            if (!empty($offering['is_free'])) {
                $ai['payment_required'] = false;
                $ai['payment_amount'] = null;
                break;
            }
        }
    }

    $platform = (string) ($context['platform'] ?? '');
    $connectedAccount = is_array($context['connected_account'] ?? null) ? $context['connected_account'] : [];
    $senderId = (string) ($context['sender_id'] ?? '');
    $replyText = (string) ($ai['customer_reply'] ?? '');
    $attachmentIds = is_array($ai['knowledge_attachment_ids'] ?? null) ? $ai['knowledge_attachment_ids'] : [];
    if ($attachmentIds !== []) {
        $attachmentAssets = knowledge_attachment_assets_for_ids($businessId, $attachmentIds);
        if ($attachmentAssets !== []) {
            $replyText = knowledge_polish_attachment_reply($replyText, $attachmentAssets);
            $ai['customer_reply'] = $replyText;
        }
    }

    if (!empty($ai['payment_required'])) {
        $paymentInstructions = trim((string) ($settings['payment_instructions'] ?? ''));
        if ($paymentInstructions !== '' && !str_contains($replyText, $paymentInstructions)) {
            $replyText = trim($replyText) . "\n\n" . $paymentInstructions;
        }
    }

    $outbound = RepositoryFactory::messages()->create([
        'business_id' => $businessId,
        'customer_id' => $customerId,
        'conversation_id' => $conversationId,
        'platform' => $platform,
        'sender_type' => 'ai',
        'message_text' => $replyText,
        'ai_intent' => (string) ($ai['intent'] ?? null),
    ]);

    $sent = automation_send_platform_reply($platform, $connectedAccount, $senderId, $replyText);

    if ($sent) {
        $tokenService->spend(
            $userId,
            TokenCosts::DM_REPLY,
            'dm_reply',
            'message',
            (string) ($outbound['id'] ?? ''),
            ['business_id' => $businessId, 'platform' => $platform]
        );

        automation_send_offer_images(
            $platform,
            $connectedAccount,
            $senderId,
            $offerings,
            is_array($ai['offer_image_ids'] ?? null) ? $ai['offer_image_ids'] : []
        );

        automation_send_knowledge_images(
            $platform,
            $connectedAccount,
            $senderId,
            is_array($ai['knowledge_image_urls'] ?? null) ? $ai['knowledge_image_urls'] : []
        );

        automation_send_knowledge_attachments(
            $platform,
            $connectedAccount,
            $senderId,
            $businessId,
            is_array($ai['knowledge_attachment_ids'] ?? null) ? $ai['knowledge_attachment_ids'] : [],
            $knowledgeChunks
        );

        try {
            $receiptId = (new SalesAutomationService())->maybeSendPaymentReceipt(
                $planSlug,
                $business,
                $settings,
                $ai,
                $platform,
                $connectedAccount,
                $senderId,
                $businessId,
                $customerId,
                $conversationId
            );
            if ($receiptId !== null && $conversationId !== '') {
                RepositoryFactory::conversationFollowups()->markStatus($conversationId, 'completed');
            }
        } catch (Throwable $e) {
            error_log('[process_message] receipt: ' . $e->getMessage());
        }
    }

    $activeSub = $subscriptionService->getActiveSubscription($userId);
    $planSlug = (string) ($activeSub['plan_slug'] ?? $business['subscription_plan'] ?? $planSlug);

    $alertSent = false;
    if (!empty($ai['owner_alert_required']) && automation_should_notify_owner($settings, $planSlug, $ai)) {
        $businessName = (string) ($business['business_name'] ?? 'Your business');
        $reason = (string) ($ai['owner_alert_reason'] ?? 'Customer needs your attention');
        $alertBody = $businessName . ' alert: ' . $reason . "\nCustomer: " . $senderId
            . "\nMessage: " . (string) ($context['message_text'] ?? '');

        RepositoryFactory::ownerAlerts()->create($businessId, [
            'customer_id' => $customerId,
            'conversation_id' => $conversationId,
            'platform' => $platform,
            'alert_reason' => $reason,
            'alert_message' => $alertBody,
            'status' => 'unread',
        ]);

        $settingsWithBusiness = $settings;
        $settingsWithBusiness['business_id'] = $businessId;
        $alertSent = whatsapp_send_owner_alert($settingsWithBusiness, $alertBody);
    }

    $isVendorOrAbove = in_array($planSlug, ['vendor', 'shop'], true);
    if ($isVendorOrAbove && !empty($settings['handoff_to_whatsapp']) && ($ai['lead_stage'] ?? '') === 'ready_to_buy') {
        RepositoryFactory::handoffs()->create($businessId, [
            'customer_id' => $customerId,
            'from_platform' => $platform,
            'to_platform' => 'whatsapp',
            'handoff_reason' => 'ready_to_buy',
            'handoff_link' => null,
        ]);
    }

    return [
        'ok' => true,
        'ai' => $ai,
        'outbound_message_id' => (string) ($outbound['id'] ?? ''),
        'reply_sent' => $sent,
        'owner_alert_sent' => $alertSent,
    ];
}

/**
 * @param array<string, mixed> $settings
 * @param array<string, mixed> $ai
 */
function automation_should_notify_owner(array $settings, string $planSlug, array $ai): bool
{
    $leadStage = (string) ($ai['lead_stage'] ?? '');
    $intent = (string) ($ai['intent'] ?? '');

    if ($leadStage === 'complaint' || str_contains($intent, 'complaint')) {
        return (bool) ($settings['notify_owner_on_complaint'] ?? true);
    }

    if (!empty($ai['payment_required']) || $leadStage === 'paid' || str_contains($intent, 'payment')) {
        return (bool) ($settings['notify_owner_on_payment'] ?? true);
    }

    $isVendorOrAbove = in_array($planSlug, ['vendor', 'shop'], true);
    if (!$isVendorOrAbove) {
        return false;
    }

    return (bool) ($settings['notify_owner_on_hot_lead'] ?? true);
}

/**
 * @param array<string, mixed> $connectedAccount
 */
function automation_send_platform_reply(string $platform, array $connectedAccount, string $recipientId, string $text): bool
{
    if ($text === '') {
        return false;
    }

    return match ($platform) {
        'whatsapp' => whatsapp_send_text_message($connectedAccount, $recipientId, $text),
        'instagram', 'facebook' => meta_send_text_message($platform, $connectedAccount, $recipientId, $text),
        default => false,
    };
}

/**
 * @param array<string, mixed> $connectedAccount
 * @param list<string> $attachmentIds
 * @param list<array<string, mixed>> $knowledgeChunks
 */
function automation_send_knowledge_attachments(
    string $platform,
    array $connectedAccount,
    string $recipientId,
    string $businessId,
    array $attachmentIds,
    array $knowledgeChunks
): void {
    if ($attachmentIds === [] || !in_array($platform, ['whatsapp', 'instagram', 'facebook'], true)) {
        return;
    }

    require_once dirname(__DIR__) . '/lib/KnowledgeAssetService.php';

    $catalog = knowledge_attachment_assets_for_ids($businessId, $attachmentIds);
    if ($catalog === []) {
        $catalog = knowledge_collect_attachment_catalog($knowledgeChunks, $businessId);
    }

    $sent = [];

    foreach ($attachmentIds as $chunkId) {
        $chunkId = trim((string) $chunkId);
        if ($chunkId === '' || isset($sent[$chunkId])) {
            continue;
        }

        $asset = $catalog[$chunkId] ?? null;
        if ($asset === null) {
            error_log('[KnowledgeAttachment] Missing asset for chunk ' . $chunkId . ' business=' . $businessId);
            continue;
        }

        $url = trim((string) ($asset['url'] ?? ''));
        if ($url === '') {
            continue;
        }

        $delivery = (string) ($asset['delivery'] ?? 'document');
        $fileName = trim((string) ($asset['file_name'] ?? 'file'));

        $ok = match ($platform) {
            'whatsapp' => match ($delivery) {
                'image' => whatsapp_send_image_message($connectedAccount, $recipientId, $url),
                'video' => whatsapp_send_video_message($connectedAccount, $recipientId, $url),
                default => whatsapp_send_document_message($connectedAccount, $recipientId, $url, $fileName),
            },
            'instagram', 'facebook' => match ($delivery) {
                'image' => meta_send_image_message($platform, $connectedAccount, $recipientId, $url),
                'video' => meta_send_video_message($platform, $connectedAccount, $recipientId, $url),
                default => meta_send_file_message($platform, $connectedAccount, $recipientId, $url)
                    ?: meta_send_text_message($platform, $connectedAccount, $recipientId, 'Here is the file: ' . $url),
            },
            default => false,
        };

        if ($ok) {
            $sent[$chunkId] = true;
        } else {
            error_log('[KnowledgeAttachment] Send failed platform=' . $platform . ' chunk=' . $chunkId . ' url=' . $url);
        }
    }
}

/**
 * @param array<string, mixed> $connectedAccount
 * @param list<string> $imageUrls
 */
function automation_send_knowledge_images(
    string $platform,
    array $connectedAccount,
    string $recipientId,
    array $imageUrls
): void {
    if ($imageUrls === []) {
        return;
    }

    $sentUrls = [];
    foreach ($imageUrls as $imageUrl) {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '' || isset($sentUrls[$imageUrl])) {
            continue;
        }

        $sent = match ($platform) {
            'whatsapp' => whatsapp_send_image_message($connectedAccount, $recipientId, $imageUrl),
            'instagram', 'facebook' => meta_send_image_message($platform, $connectedAccount, $recipientId, $imageUrl),
            default => false,
        };

        if ($sent) {
            $sentUrls[$imageUrl] = true;
        }
    }
}

/**
 * @param array<string, mixed> $connectedAccount
 * @param list<array<string, mixed>> $offerings
 * @param list<string> $offerImageIds
 */
function automation_send_offer_images(
    string $platform,
    array $connectedAccount,
    string $recipientId,
    array $offerings,
    array $offerImageIds
): void {
    if ($offerImageIds === []) {
        return;
    }

    $offeringsById = [];
    foreach ($offerings as $offering) {
        $offeringsById[(string) ($offering['id'] ?? '')] = $offering;
    }

    $sentUrls = [];
    foreach ($offerImageIds as $offeringId) {
        $offering = $offeringsById[$offeringId] ?? null;
        if ($offering === null) {
            continue;
        }

        $imageUrl = offering_public_image_url((string) ($offering['image_url'] ?? ''));
        if ($imageUrl === null || isset($sentUrls[$imageUrl])) {
            continue;
        }

        $sent = match ($platform) {
            'whatsapp' => whatsapp_send_image_message($connectedAccount, $recipientId, $imageUrl),
            'instagram', 'facebook' => meta_send_image_message($platform, $connectedAccount, $recipientId, $imageUrl),
            default => false,
        };

        if ($sent) {
            $sentUrls[$imageUrl] = true;
        }
    }
}

// HTTP entry deliberately removed — queue workers call process_inbound_message() in-process.
