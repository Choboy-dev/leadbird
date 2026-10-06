<?php

declare(strict_types=1);

require_once __DIR__ . '/openai-client.php';
require_once dirname(__DIR__) . '/lib/VanceVoice.php';
require_once dirname(__DIR__) . '/lib/ConversationContextService.php';
require_once dirname(__DIR__) . '/lib/SalesAutomationService.php';
require_once dirname(__DIR__) . '/lib/PlanAccess.php';

/**
 * @param array<string, mixed> $business
 * @param array<string, mixed> $settings
 * @param list<array<string, mixed>> $offerings
 * @param list<array<string, mixed>> $recentMessages
 * @param array<string, mixed> $inbound
 * @param list<array<string, mixed>> $knowledgeChunks
 * @return array<string, mixed>
 */
function openai_generate_sales_reply(
    array $business,
    array $settings,
    array $offerings,
    array $recentMessages,
    array $inbound,
    array $knowledgeChunks = [],
    ?string $referralContext = null,
    ?string $bookingContext = null,
    string $planSlug = 'starter'
): array {
    $apiKey = app_config()['openai']['api_key'] ?? '';
    $model = app_config()['openai']['model'] ?? 'gpt-4o-mini';
    $inboundText = trim((string) ($inbound['message_text'] ?? ''));
    $businessId = (string) ($business['id'] ?? '');
    $faqMatch = $businessId !== '' && $inboundText !== ''
        ? knowledge_find_best_faq_match($businessId, $inboundText)
        : null;
    if ($faqMatch !== null) {
        $faqChunk = $faqMatch['chunk'] ?? null;
        if (is_array($faqChunk)) {
            $faqChunkId = (string) ($faqChunk['id'] ?? '');
            $hasFaqChunk = false;
            foreach ($knowledgeChunks as $chunk) {
                if ((string) ($chunk['id'] ?? '') === $faqChunkId) {
                    $hasFaqChunk = true;
                    break;
                }
            }
            if (!$hasFaqChunk) {
                array_unshift($knowledgeChunks, $faqChunk);
            }
        }
    }

    $fallback = openai_build_sales_reply_fallback(
        $business,
        $settings,
        $offerings,
        $recentMessages,
        $inbound,
        $knowledgeChunks,
        $referralContext
    );

    if ($apiKey === '') {
        error_log('[OpenAI] OPENAI_API_KEY is not configured.');
        return $fallback;
    }

    $offerLines = [];
    $inboundText = trim((string) ($inbound['message_text'] ?? ''));
    $relevantOfferings = openai_relevant_offerings_for_prompt($offerings, $knowledgeChunks, $inboundText);
    foreach ($relevantOfferings as $offering) {
        $offeringId = (string) ($offering['id'] ?? '');
        $type = (string) ($offering['offering_type'] ?? 'offer');
        $price = OfferingTypeRegistry::formatPrice($offering);
        $hasImage = offering_public_image_url((string) ($offering['image_url'] ?? '')) !== null ? 'yes' : 'no';
        $actionUrl = OfferingTypeRegistry::actionUrl($offering);
        $typeMeta = OfferingTypeRegistry::meta($type);
        $line = '- id:' . $offeringId
            . ' | ' . ($offering['name'] ?? 'Offer')
            . ' | type:' . $type
            . ' | ' . $price
            . ' | photo:' . $hasImage;
        if ($actionUrl !== null) {
            $line .= ' | link:' . $actionUrl;
        }
        $line .= ' | collect:' . implode(',', OfferingTypeRegistry::aiCollectFields($type));
        $line .= ' | provide:' . implode(',', OfferingTypeRegistry::aiProvideFields($type));
        $line .= ' | role:' . knowledge_excerpt($typeMeta['ai_role'], 120);
        $description = trim((string) ($offering['description'] ?? ''));
        if ($description !== '') {
            $line .= ' | ' . knowledge_excerpt($description, 100);
        }
        $offerLines[] = $line;
    }

    $historyLines = [];
    foreach (array_slice($recentMessages, -6) as $msg) {
        $role = ($msg['sender_type'] ?? '') === 'customer' ? 'Customer' : 'Salesgirl';
        $text = trim((string) ($msg['message_text'] ?? ''));
        if ($text === '') {
            continue;
        }
        $historyLines[] = $role . ': ' . knowledge_excerpt($text, 280);
    }

    $knowledgeChunks = ConversationContextService::filterKnowledgeChunks($knowledgeChunks, $recentMessages);
    $knowledgeBlock = knowledge_format_for_prompt($knowledgeChunks);
    $conversationMemory = ConversationContextService::memoryBlockForPrompt($recentMessages);
    $referralBlock = $referralContext !== null && trim($referralContext) !== ''
        ? 'Customer context: ' . trim($referralContext)
        : null;

    $aiName = trim((string) ($settings['ai_name'] ?? 'Sales Girl')) ?: 'Sales Girl';
    $businessName = trim((string) ($business['business_name'] ?? 'this business')) ?: 'this business';
    $aiGreeting = trim((string) ($settings['ai_greeting'] ?? ''));
    $isFirstReply = openai_is_first_salesgirl_reply($recentMessages);

    $systemPrompt = implode("\n", array_filter([
        'You are ' . $aiName . ', the sales assistant for ' . $businessName . '.',
        'You answer from the business knowledge below. Do not invent prices, products, policies, or post details.',
        $conversationMemory,
        $referralBlock,
        $bookingContext,
        '',
        VanceVoice::salesgirlDmVoiceBlock($aiName, $businessName, $isFirstReply, $aiGreeting),
        '',
        'Catalog (always use these ids and prices when quoting offers):',
        $offerLines !== [] ? implode("\n", $offerLines) : '- No offers listed yet. Ask the customer which product or service they mean.',
        '',
        'Relevant business knowledge for this message:',
        $knowledgeBlock,
        '',
        'Tone: ' . ($settings['ai_tone'] ?? 'friendly, concise') . '.',
        '',
        SalesAutomationService::negotiationPromptBlock($settings, $planSlug),
        PlanAccess::allows($planSlug, 'payment_receipts') && !empty($settings['receipt_enabled'])
            ? 'When the customer says they already paid, set lead_stage to paid, thank them briefly, and say their receipt is coming. Do not ask for payment proof again unless something looks wrong.'
            : null,
        '',
        'Type-specific behavior:',
        '- product: quote variants/delivery; collect size, address, phone for orders.',
        '- service: explain scope and turnaround; collect requirements and phone.',
        '- membership: explain benefits; only when they are eligible and want to join, set send_action_url true with action_offering_id and include the registration link in customer_reply.',
        '- consultation: explain duration/format; collect name, phone, preferred date/time; when ready to book set booking_required true with booking_offering_id, booking_customer_name, booking_customer_phone, booking_preferred_datetime.',
        '- course: explain modules/access; when ready to enroll set send_action_url true with the enrollment link.',
        '- event: share date/location; when ready to register set send_action_url true with the ticket link.',
        '- If price shows Free, say it is free — never ask them to pay.',
        '',
        'Rules:',
        '- Read the full conversation before replying. Never contradict what you already told this customer in this thread.',
        '- If knowledge chunks conflict, follow eligibility and restriction rules (who can or cannot join) over signup or registration instructions.',
        '- If the customer is not eligible (wrong gender, age, location, membership rules, etc.), politely decline. Do not send registration links, signup links, or payment steps.',
        '- customer_reply is YOUR message TO the customer (you are the sales assistant). Never copy, quote, or repeat the customer\'s message.',
        '- Prefer knowledge chunks over guessing. Social posts may describe promos, services, or products.',
        '- web_link chunks are scraped website pages (with page URLs listed). Answer using that page text — do not invent details that are not in the chunk.',
        '- When a web_link chunk answers the customer\'s question, include the most relevant page URL as a plain tap-friendly link in customer_reply (e.g. "Details here: https://example.com/page").',
        '- If a customer asks about something from a post or comment, answer using the matching social_post knowledge.',
        '- learned_memory chunks come from past successful chats — treat them as trusted tips about how this business sells.',
        '- Social posts linked to catalog items mention related products in keywords — use those offerings when customers reference the post.',
        '- If the customer sent a photo, use the matched catalog or social knowledge to identify the item.',
        '- When you recommend a specific offer with photo:yes, put its id in offer_image_ids.',
        '- When a social_post or offering knowledge chunk has [has photo] and the customer wants to see it, include that image URL in knowledge_image_urls.',
        '- When a document chunk has [has file] and the customer clearly asks for that kind of file (resume, CV, brochure, catalog, menu, template, PDF sample, photo, or video), put its matching id in knowledge_attachment_ids and say you are sending it now.',
        '- Never attach a document that does not match the customer\'s request topic (example: do not send a CV when they asked for a registration link, price, or membership info).',
        '- If you are declining the customer (not eligible, cannot join, wrong audience), leave knowledge_attachment_ids empty and do not send files or registration links.',
        '- Asking for a link/URL is not a request for a PDF/file unless they also ask for a document, sample, brochure, or similar.',
        '- When a [faq] chunk question matches the customer message, answer using the trained A: text. Do not guess or say you are unsure.',
        '- Never tell the customer you cannot accept files when they are asking you to send them a sample. They want you to share a file, not upload one to you.',
        '- Only include image URLs and attachment ids that appear in the knowledge or catalog above.',
        '- Do not say you do not have a file if a matching trained document chunk exists for this request.',
        '- When sending a registration, enrollment, ticket, or booking link, set send_action_url true and action_offering_id to the offer id.',
        '- Links: never use markdown like [text](url). Write a plain URL customers can tap, e.g. https://example.com/register or "Join here: https://example.com/register".',
        '',
        'Return ONLY valid JSON with these keys:',
        'customer_reply (string — your helpful reply TO the customer, written as the sales assistant), intent (string), lead_stage (string: new|interested|ready_to_buy|paid|complaint),',
        'owner_alert_required (boolean), owner_alert_reason (string|null), payment_required (boolean),',
        'payment_amount (number|null — amount in the catalog currency when asking the customer to pay now; use catalog prices; null if free),',
        'offer_image_ids (array of offering id strings), knowledge_image_urls (array of image URL strings),',
        'knowledge_attachment_ids (array of trained document chunk id strings from knowledge with [has file]),',
        'send_action_url (boolean), action_offering_id (string|null),',
        'booking_required (boolean), booking_offering_id (string|null), booking_customer_name (string|null), booking_customer_phone (string|null), booking_preferred_datetime (string|null), booking_customer_email (string|null).',
        'Set owner_alert_required true for hot leads, payment claims, or complaints that need the owner.',
        'When the customer is ready to pay, set payment_required true and payment_amount to the agreed catalog-currency total.',
        'The system shares the business bank payment instructions in chat when payment is required.',
    ], static fn(?string $line): bool => $line !== null));

    $userPrompt = "Conversation so far:\n" . implode("\n", $historyLines)
        . "\n\nLatest customer message:\n" . (string) ($inbound['message_text'] ?? '');

    $payload = [
        'model' => $model,
        'response_format' => ['type' => 'json_object'],
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ],
        'temperature' => 0.35,
        'max_tokens' => 450,
    ];

    $businessId = (string) ($business['id'] ?? '');
    $api = openai_chat_completion($payload, $businessId, 'dm_reply', 0.003);
    if (!($api['ok'] ?? false)) {
        if (!empty($api['budget_exceeded'])) {
            error_log('[OpenAI] DM reply budget exceeded for business ' . $businessId);
        }
        return $fallback;
    }

    /** @var array<string, mixed> $decoded */
    $decoded = $api['body'] ?? [];
    $content = $decoded['choices'][0]['message']['content'] ?? null;
    if (!is_string($content) || $content === '') {
        return $fallback;
    }

    /** @var array<string, mixed>|null $result */
    $result = json_decode($content, true);
    if (!is_array($result)) {
        return $fallback;
    }

    $offerImageIds = [];
    foreach ($result['offer_image_ids'] ?? [] as $id) {
        $id = trim((string) $id);
        if ($id !== '') {
            $offerImageIds[] = $id;
        }
    }

    $businessId = (string) ($business['id'] ?? '');
    $attachmentCatalog = knowledge_collect_attachment_catalog($knowledgeChunks, $businessId);
    $allowedKnowledgeImages = knowledge_collect_image_urls($knowledgeChunks);
    foreach ($attachmentCatalog as $asset) {
        if (($asset['delivery'] ?? '') === 'image') {
            $allowedKnowledgeImages[] = $asset['url'];
        }
    }
    $allowedKnowledgeLookup = array_fill_keys(array_values(array_unique($allowedKnowledgeImages)), true);
    foreach ($offerings as $offering) {
        $imageUrl = offering_public_image_url((string) ($offering['image_url'] ?? ''));
        if ($imageUrl !== null) {
            $allowedKnowledgeLookup[$imageUrl] = true;
        }
    }

    $knowledgeImageUrls = [];
    foreach ($result['knowledge_image_urls'] ?? [] as $url) {
        $url = trim((string) $url);
        if ($url !== '' && isset($allowedKnowledgeLookup[$url])) {
            $knowledgeImageUrls[] = $url;
        }
    }

    $inboundText = trim((string) ($inbound['message_text'] ?? ''));
    $replyDraft = trim((string) ($result['customer_reply'] ?? $fallback['customer_reply']));

    $knowledgeAttachmentIds = [];
    foreach ($result['knowledge_attachment_ids'] ?? [] as $chunkId) {
        $chunkId = trim((string) $chunkId);
        if ($chunkId !== '' && isset($attachmentCatalog[$chunkId])) {
            $knowledgeAttachmentIds[] = $chunkId;
        }
    }

    // Only auto-infer attachments for clear file requests, never after a refusal,
    // and only keep documents that topically match the customer message.
    $shouldAttach = knowledge_message_requests_file($inboundText)
        && !knowledge_reply_is_refusal($replyDraft)
        && !ConversationContextService::joinDeclined($recentMessages)
        && !ConversationContextService::customerMarkedIneligible($recentMessages);

    if ($shouldAttach) {
        if ($knowledgeAttachmentIds === []) {
            $knowledgeAttachmentIds = knowledge_infer_attachment_ids($inboundText, $knowledgeChunks, $businessId);
        } else {
            $knowledgeAttachmentIds = knowledge_filter_relevant_attachment_ids(
                $inboundText,
                $knowledgeAttachmentIds,
                $knowledgeChunks,
                $businessId
            );
        }
    } else {
        $knowledgeAttachmentIds = [];
    }

    $attachmentAssets = knowledge_attachment_assets_for_ids($businessId, $knowledgeAttachmentIds);

    $reply = $replyDraft;
    if ($attachmentAssets !== []) {
        $reply = knowledge_polish_attachment_reply($reply, $attachmentAssets);
    }
    $reply = knowledge_polish_faq_reply($reply, $faqMatch);
    $reply = openai_finalize_customer_reply($reply, $settings, $recentMessages, $inboundText);
    if ($inboundText !== '' && strcasecmp($reply, $inboundText) === 0) {
        error_log('[OpenAI] Echo reply blocked for inbound: ' . mb_substr($inboundText, 0, 120));
        $fallback['customer_reply'] = openai_finalize_customer_reply(
            (string) ($fallback['customer_reply'] ?? ''),
            $settings,
            $recentMessages,
            $inboundText
        );

        return $fallback;
    }

    $final = [
        'customer_reply' => $reply,
        'intent' => (string) ($result['intent'] ?? $fallback['intent']),
        'lead_stage' => (string) ($result['lead_stage'] ?? $fallback['lead_stage']),
        'owner_alert_required' => (bool) ($result['owner_alert_required'] ?? false),
        'owner_alert_reason' => isset($result['owner_alert_reason']) ? (string) $result['owner_alert_reason'] : null,
        'payment_required' => (bool) ($result['payment_required'] ?? false),
        'payment_amount' => isset($result['payment_amount']) ? (float) $result['payment_amount'] : null,
        'offer_image_ids' => $offerImageIds,
        'knowledge_image_urls' => $knowledgeImageUrls,
        'knowledge_attachment_ids' => $knowledgeAttachmentIds,
        'send_action_url' => (bool) ($result['send_action_url'] ?? false),
        'action_offering_id' => isset($result['action_offering_id']) ? trim((string) $result['action_offering_id']) : null,
        'booking_required' => (bool) ($result['booking_required'] ?? false),
        'booking_offering_id' => isset($result['booking_offering_id']) ? trim((string) $result['booking_offering_id']) : null,
        'booking_customer_name' => isset($result['booking_customer_name']) ? trim((string) $result['booking_customer_name']) : null,
        'booking_customer_phone' => isset($result['booking_customer_phone']) ? trim((string) $result['booking_customer_phone']) : null,
        'booking_preferred_datetime' => isset($result['booking_preferred_datetime']) ? trim((string) $result['booking_preferred_datetime']) : null,
        'booking_customer_email' => isset($result['booking_customer_email']) ? trim((string) $result['booking_customer_email']) : null,
    ];

    $final = ConversationContextService::enforceOnReply($final, $recentMessages);
    $final['customer_reply'] = openai_finalize_customer_reply(
        (string) ($final['customer_reply'] ?? ''),
        $settings,
        $recentMessages,
        $inboundText
    );

    return $final;
}

/**
 * Deterministic fallback when the model is unavailable.
 *
 * @param array<string, mixed> $business
 * @param array<string, mixed> $settings
 * @param list<array<string, mixed>> $offerings
 * @param list<array<string, mixed>> $recentMessages
 * @param array<string, mixed> $inbound
 * @param list<array<string, mixed>> $knowledgeChunks
 * @return array<string, mixed>
 */
function openai_build_sales_reply_fallback(
    array $business,
    array $settings,
    array $offerings,
    array $recentMessages,
    array $inbound,
    array $knowledgeChunks,
    ?string $referralContext = null
): array {
    $message = trim((string) ($inbound['message_text'] ?? ''));
    $normalized = mb_strtolower($message);
    $paymentInstructions = trim((string) ($settings['payment_instructions'] ?? ''));
    $deliveryPolicy = trim((string) ($settings['delivery_policy'] ?? ''));
    $returnPolicy = trim((string) ($settings['return_policy'] ?? ''));
    $businessName = trim((string) ($business['business_name'] ?? 'our business'));
    $matchedOfferings = openai_match_fallback_offerings($message, $offerings, $knowledgeChunks);
    $faqAnswer = openai_extract_best_faq_answer($knowledgeChunks, $message, (string) ($business['id'] ?? ''));
    $knowledgeSummary = openai_build_knowledge_summary($knowledgeChunks);
    $visualIntent = knowledge_has_visual_intent($message);
    $recentCustomerIntent = openai_recent_customer_context($recentMessages);

    $intent = 'general_inquiry';
    $leadStage = 'new';
    $ownerAlertRequired = false;
    $ownerAlertReason = null;
    $paymentRequired = false;

    if (preg_match('/\b(problem|issue|complaint|angry|bad|upset|frustrat|refund me|not happy)\b/i', $normalized) === 1) {
        $intent = 'complaint';
        $leadStage = 'complaint';
        $ownerAlertRequired = true;
        $ownerAlertReason = 'Customer has a complaint that needs attention';
    } elseif (preg_match('/\b(i paid|payment made|payment sent|sent payment|transferred|have paid|paid already|payment proof|receipt)\b/i', $normalized) === 1) {
        $intent = 'payment_confirmation';
        $leadStage = 'paid';
        $ownerAlertRequired = true;
        $ownerAlertReason = 'Customer says payment has been made';
    } elseif (preg_match('/\b(buy|order|book|take it|i want it|i need it|get this|get it)\b/i', $normalized) === 1) {
        $intent = 'order_request';
        $leadStage = 'ready_to_buy';
    } elseif (preg_match('/\b(price|prices|cost|costs|how much|rate|rates|fee|fees|charge|charges|budget)\b/i', $normalized) === 1) {
        $intent = 'pricing';
        $leadStage = 'interested';
    } elseif (preg_match('/\b(pay|payment|bank|transfer|account number|account name|paystack|wallet|how do i pay)\b/i', $normalized) === 1) {
        $intent = 'payment_instructions';
        $leadStage = 'ready_to_buy';
        $paymentRequired = true;
    } elseif (preg_match('/\b(deliver|delivery|ship|shipping|dispatch|location|pickup)\b/i', $normalized) === 1) {
        $intent = 'delivery_info';
        $leadStage = 'interested';
    } elseif (preg_match('/\b(return|refund|exchange|cancel)\b/i', $normalized) === 1) {
        $intent = 'return_policy';
        $leadStage = 'interested';
    } elseif ($recentCustomerIntent !== '') {
        $intent = $recentCustomerIntent;
    }

    $reply = openai_fallback_reply_for_context(
        $businessName,
        $message,
        $normalized,
        $paymentInstructions,
        $deliveryPolicy,
        $returnPolicy,
        $matchedOfferings,
        $knowledgeChunks,
        $faqAnswer,
        $knowledgeSummary,
        $referralContext
    );

    $offerImageIds = [];
    if ($visualIntent) {
        foreach (array_slice($matchedOfferings, 0, 2) as $offering) {
            $offeringId = trim((string) ($offering['id'] ?? ''));
            $imageUrl = offering_public_image_url((string) ($offering['image_url'] ?? ''));
            if ($offeringId !== '' && $imageUrl !== null) {
                $offerImageIds[] = $offeringId;
            }
        }
    }

    $knowledgeImageUrls = [];
    if ($visualIntent) {
        foreach (array_slice($knowledgeChunks, 0, 3) as $chunk) {
            $imageUrl = trim((string) ($chunk['image_url'] ?? ''));
            if ($imageUrl !== '') {
                $knowledgeImageUrls[$imageUrl] = true;
            }
        }
    }

    if ($reply === '') {
        $reply = 'Hi. Tell me the exact product or service you want and I will help with price, delivery, payment, or availability.';
    }

    $attachmentIds = [];
    if (knowledge_message_requests_file($message)
        && !knowledge_reply_is_refusal($reply)
        && !ConversationContextService::joinDeclined($recentMessages)
        && !ConversationContextService::customerMarkedIneligible($recentMessages)
    ) {
        $attachmentIds = knowledge_infer_attachment_ids($message, $knowledgeChunks, (string) ($business['id'] ?? ''));
    }
    $attachmentAssets = knowledge_attachment_assets_for_ids((string) ($business['id'] ?? ''), $attachmentIds);
    if ($attachmentAssets !== []) {
        $reply = knowledge_polish_attachment_reply($reply, $attachmentAssets);
    } elseif ($attachmentIds !== [] && !preg_match('/\b(do not|cannot|can\'t|sorry)\b/i', $reply)) {
        $reply = 'Sure. I am sending the file now.';
    }

    $result = [
        'customer_reply' => openai_finalize_customer_reply($reply, $settings, $recentMessages, $message),
        'intent' => $intent,
        'lead_stage' => $leadStage,
        'owner_alert_required' => $ownerAlertRequired,
        'owner_alert_reason' => $ownerAlertReason,
        'payment_required' => $paymentRequired,
        'payment_amount' => null,
        'offer_image_ids' => array_values(array_unique($offerImageIds)),
        'knowledge_image_urls' => array_keys($knowledgeImageUrls),
        'knowledge_attachment_ids' => $attachmentIds,
    ];

    return ConversationContextService::enforceOnReply($result, $recentMessages);
}

/**
 * @param list<array<string, mixed>> $offerings
 * @param list<array<string, mixed>> $knowledgeChunks
 * @return list<array<string, mixed>>
 */
function openai_match_fallback_offerings(string $message, array $offerings, array $knowledgeChunks): array
{
    $normalized = mb_strtolower($message);
    $tokens = knowledge_tokenize($message);
    $scored = [];

    foreach ($offerings as $offering) {
        $name = trim((string) ($offering['name'] ?? ''));
        if ($name === '') {
            continue;
        }

        $haystack = mb_strtolower($name . ' ' . trim((string) ($offering['description'] ?? '')));
        $score = 0.0;

        if (str_contains($normalized, mb_strtolower($name))) {
            $score += 8.0;
        }

        foreach ($tokens as $token) {
            if (str_contains($haystack, $token)) {
                $score += strlen($token) >= 5 ? 2.0 : 1.0;
            }
        }

        foreach ($knowledgeChunks as $chunk) {
            if ((string) ($chunk['source_type'] ?? '') !== 'offering') {
                continue;
            }

            $metadata = json_decode((string) ($chunk['metadata'] ?? ''), true);
            $chunkOfferingId = is_array($metadata) ? (string) ($metadata['offering_id'] ?? '') : '';
            if ($chunkOfferingId !== '' && $chunkOfferingId === (string) ($offering['id'] ?? '')) {
                $score += 4.0;
            }
        }

        if ($score > 0) {
            $offering['_fallback_score'] = $score;
            $scored[] = $offering;
        }
    }

    usort($scored, static function (array $a, array $b): int {
        return (($b['_fallback_score'] ?? 0) <=> ($a['_fallback_score'] ?? 0));
    });

    return array_slice($scored, 0, 3);
}

/**
 * @param list<array<string, mixed>> $knowledgeChunks
 */
function openai_extract_best_faq_answer(array $knowledgeChunks, string $message = '', string $businessId = ''): ?string
{
    if ($businessId !== '' && trim($message) !== '') {
        $match = knowledge_find_best_faq_match($businessId, $message);
        if ($match !== null) {
            $answer = trim((string) ($match['answer'] ?? ''));
            if ($answer !== '') {
                return $answer;
            }
        }
    }

    foreach ($knowledgeChunks as $chunk) {
        if ((string) ($chunk['source_type'] ?? '') !== 'faq') {
            continue;
        }

        $question = trim((string) ($chunk['title'] ?? ''));
        if ($message !== '' && $question !== '' && knowledge_score_faq_question_match($message, $question) < 55.0) {
            continue;
        }

        $answer = knowledge_extract_faq_answer($chunk);
        if ($answer !== '') {
            return $answer;
        }
    }

    return null;
}

/**
 * @param list<array<string, mixed>> $knowledgeChunks
 */
function openai_build_knowledge_summary(array $knowledgeChunks): ?string
{
    if ($knowledgeChunks === []) {
        return null;
    }

    $lines = [];
    foreach (array_slice($knowledgeChunks, 0, 2) as $chunk) {
        $text = openai_humanize_chunk_content($chunk);
        if ($text === '') {
            continue;
        }

        $lines[] = '- ' . $text;
    }

    if ($lines === []) {
        return null;
    }

    return count($lines) === 1
        ? substr($lines[0], 2)
        : "Here is what I found:\n" . implode("\n", $lines);
}

/**
 * @param array<string, mixed> $chunk
 */
function openai_humanize_chunk_content(array $chunk): string
{
    $sourceType = (string) ($chunk['source_type'] ?? '');
    $content = trim((string) ($chunk['content'] ?? ''));
    if ($content === '') {
        return '';
    }

    if ($sourceType === 'faq' && preg_match('/\bA:\s*(.+)$/s', $content, $matches) === 1) {
        return trim($matches[1]);
    }

    return knowledge_excerpt($content, 220);
}

/**
 * @param list<array<string, mixed>> $matchedOfferings
 * @param list<array<string, mixed>> $knowledgeChunks
 */
function openai_fallback_reply_for_context(
    string $businessName,
    string $message,
    string $normalized,
    string $paymentInstructions,
    string $deliveryPolicy,
    string $returnPolicy,
    array $matchedOfferings,
    array $knowledgeChunks,
    ?string $faqAnswer,
    ?string $knowledgeSummary,
    ?string $referralContext
): string {
    if (preg_match('/\b(problem|issue|complaint|angry|bad|upset|frustrat|refund me|not happy)\b/i', $normalized) === 1) {
        return 'I am sorry about that. Please tell me exactly what went wrong and our team will sort it out as quickly as possible.';
    }

    if (preg_match('/\b(i paid|payment made|payment sent|sent payment|transferred|have paid|paid already|payment proof|receipt)\b/i', $normalized) === 1) {
        return 'Thanks for the update. Please send your payment proof here and we will confirm it shortly.';
    }

    if (preg_match('/\b(pay|payment|bank|transfer|account number|account name|paystack|wallet|how do i pay)\b/i', $normalized) === 1 && $paymentInstructions !== '') {
        return "You can pay with these details:\n" . $paymentInstructions;
    }

    if (preg_match('/\b(deliver|delivery|ship|shipping|dispatch|location|pickup)\b/i', $normalized) === 1 && $deliveryPolicy !== '') {
        return $deliveryPolicy;
    }

    if (preg_match('/\b(return|refund|exchange|cancel)\b/i', $normalized) === 1 && $returnPolicy !== '') {
        return $returnPolicy;
    }

    if ($faqAnswer !== null && $faqAnswer !== '') {
        return $faqAnswer;
    }

    if ($matchedOfferings !== []) {
        $lines = [];
        foreach (array_slice($matchedOfferings, 0, 2) as $offering) {
            $name = trim((string) ($offering['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $price = $offering['price'] !== null
                ? money_format_catalog(
                    (float) $offering['price'],
                    (string) ($offering['currency'] ?? money_default_catalog_currency())
                )
                : 'price on request';
            $description = trim((string) ($offering['description'] ?? ''));

            $line = $name . ' is ' . $price . '.';
            if ($description !== '') {
                $line .= ' ' . knowledge_excerpt($description, 140);
            }

            $lines[] = $line;
        }

        if ($lines !== []) {
            return implode("\n", $lines);
        }
    }

    if ($knowledgeSummary !== null && $knowledgeSummary !== '') {
        return $knowledgeSummary;
    }

    if ($referralContext !== null && trim($referralContext) !== '') {
        return 'I can help with questions about ' . $businessName . '. Tell me the exact product, service, or post you mean and I will answer from the business information I have.';
    }

    if (preg_match('/\b(hello|hi|hey|good morning|good afternoon|good evening)\b/i', $normalized) === 1) {
        return 'Hi! How can I help you today? You can ask about price, delivery, payment, or any product or service.';
    }

    return 'I can help with questions about ' . $businessName . '. Tell me the exact product, service, price, delivery, or payment question and I will answer from the business information I have.';
}

/**
 * @param list<array<string, mixed>> $recentMessages
 */
function openai_recent_customer_context(array $recentMessages): string
{
    $recentCustomerText = [];
    foreach (array_slice($recentMessages, -4) as $message) {
        if (($message['sender_type'] ?? '') !== 'customer') {
            continue;
        }

        $text = trim((string) ($message['message_text'] ?? ''));
        if ($text !== '') {
            $recentCustomerText[] = mb_strtolower($text);
        }
    }

    $joined = implode(' ', $recentCustomerText);
    if ($joined === '') {
        return '';
    }

    if (preg_match('/\b(pay|payment|transfer|account)\b/i', $joined) === 1) {
        return 'payment_followup';
    }

    if (preg_match('/\b(price|cost|how much)\b/i', $joined) === 1) {
        return 'pricing_followup';
    }

    return '';
}

/**
 * Keep DM prompts small: only catalog rows likely relevant to this message.
 *
 * @param list<array<string, mixed>> $offerings
 * @param list<array<string, mixed>> $knowledgeChunks
 * @return list<array<string, mixed>>
 */
function openai_relevant_offerings_for_prompt(array $offerings, array $knowledgeChunks, string $inboundText): array
{
    if ($offerings === []) {
        return [];
    }

    $selectedIds = [];

    foreach ($knowledgeChunks as $chunk) {
        if ((string) ($chunk['source_type'] ?? '') !== 'offering') {
            continue;
        }

        $metadata = json_decode((string) ($chunk['metadata'] ?? ''), true);
        $offeringId = is_array($metadata) ? trim((string) ($metadata['offering_id'] ?? '')) : '';
        if ($offeringId === '') {
            $offeringId = trim((string) ($chunk['source_id'] ?? ''));
        }
        if ($offeringId !== '') {
            $selectedIds[$offeringId] = true;
        }
    }

    foreach (openai_match_fallback_offerings($inboundText, $offerings, $knowledgeChunks) as $offering) {
        $offeringId = trim((string) ($offering['id'] ?? ''));
        if ($offeringId !== '') {
            $selectedIds[$offeringId] = true;
        }
    }

    if ($selectedIds !== []) {
        return array_values(array_filter(
            $offerings,
            static fn(array $offering): bool => isset($selectedIds[(string) ($offering['id'] ?? '')])
        ));
    }

    if (preg_match('/\b(hello|hi|hey|good morning|good afternoon|good evening)\b/i', $inboundText) === 1) {
        return array_slice($offerings, 0, 5);
    }

    return array_slice($offerings, 0, 8);
}

/**
 * @param list<array<string, mixed>> $recentMessages
 */
function openai_is_first_salesgirl_reply(array $recentMessages): bool
{
    foreach ($recentMessages as $message) {
        $senderType = (string) ($message['sender_type'] ?? '');
        if ($senderType === 'ai' || $senderType === 'owner') {
            return false;
        }
    }

    return true;
}

function openai_reply_has_welcome(string $reply, string $aiName): bool
{
    if (preg_match('/\bmy name is\b/i', $reply)) {
        return true;
    }

    if ($aiName === '') {
        return false;
    }

    $intro = mb_substr(trim($reply), 0, 140);
    if (!preg_match('/\b(hi|hello|hey|good morning|good afternoon|good evening)\b/i', $intro)) {
        return false;
    }

    $namePattern = '/\b' . preg_quote($aiName, '/') . '\b/i';

    return preg_match($namePattern, $intro) === 1;
}

function openai_strip_leading_greeting(string $reply): string
{
    $reply = trim($reply);
    if ($reply === '') {
        return '';
    }

    $patterns = [
        '/^(hi|hello|hey|good morning|good afternoon|good evening)[.!,?\s]+/iu',
        '/^(thanks for reaching out|thank you for reaching out)[.!,?\s]*/iu',
    ];

    foreach ($patterns as $pattern) {
        $next = preg_replace($pattern, '', $reply);
        if (is_string($next) && $next !== $reply) {
            $reply = ltrim($next);
        }
    }

    return trim($reply);
}

function openai_inbound_is_greeting(string $text): bool
{
    $text = trim($text);
    if ($text === '') {
        return false;
    }

    if (preg_match('/^(hi|hello|hey|good morning|good afternoon|good evening)\b/i', $text) === 1) {
        return true;
    }

    return mb_strlen($text) <= 80
        && preg_match('/\b(hi|hello|hey)\b/i', $text) === 1;
}

/**
 * @param array<string, mixed> $settings
 */
function openai_build_welcome_intro(array $settings): string
{
    $aiName = trim((string) ($settings['ai_name'] ?? 'Sales Girl')) ?: 'Sales Girl';
    $greeting = trim((string) ($settings['ai_greeting'] ?? ''));

    if ($greeting !== '' && (stripos($greeting, $aiName) !== false || preg_match('/\bmy name is\b/i', $greeting))) {
        return chat_polish_salesgirl_reply($greeting);
    }

    if ($greeting !== '') {
        return chat_polish_salesgirl_reply('Hi. My name is ' . $aiName . '. ' . $greeting);
    }

    return 'Hi. My name is ' . $aiName . '. It is nice to meet you.';
}

/**
 * @param array<string, mixed> $settings
 * @param list<array<string, mixed>> $recentMessages
 */
function openai_finalize_customer_reply(string $reply, array $settings, array $recentMessages, string $inboundText = ''): string
{
    $reply = chat_polish_salesgirl_reply(chat_normalize_ai_reply($reply));
    $inboundText = trim($inboundText);

    if (openai_is_first_salesgirl_reply($recentMessages)) {
        if (!preg_match('/\bmy name is\b/i', $reply)) {
            $body = openai_strip_leading_greeting($reply);
            $reply = trim(openai_build_welcome_intro($settings) . ($body !== '' ? ' ' . $body : ''));
            $reply = chat_polish_salesgirl_reply($reply);
        }
    } elseif ($inboundText !== '' && openai_inbound_is_greeting($inboundText) && !preg_match('/\b(hi|hello|hey)\b/i', $reply)) {
        $reply = chat_polish_salesgirl_reply('Hi. ' . ltrim($reply));
    }

    return trim($reply);
}
