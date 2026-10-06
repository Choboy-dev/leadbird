<?php

declare(strict_types=1);

/**
 * Negotiation, cold-lead follow-ups, and payment receipts.
 */
final class SalesAutomationService
{
    public const DEFAULT_RECEIPT = "Thanks for your payment. Here is your receipt from {business_name}.\n\nDate: {date}\n{amount_line}We have recorded your payment. We will confirm and process your order shortly.";

    /**
     * @param array<string, mixed> $settings
     */
    public static function negotiationPromptBlock(array $settings, string $planSlug): ?string
    {
        if (!PlanAccess::allows($planSlug, 'ai_negotiation')) {
            return implode("\n", [
                'Price rules (Starter):',
                '- Quote listed catalog prices only.',
                '- Do not offer discounts, haggling, or special deals unless a knowledge chunk or FAQ already states that promo.',
                '- If the customer pushes for a lower price, politely hold the listed price or set owner_alert_required true.',
            ]);
        }

        if (!(bool) ($settings['negotiation_enabled'] ?? true)) {
            return implode("\n", [
                'Price rules:',
                '- Negotiation is turned off for this business.',
                '- Quote listed catalog prices only. Do not offer discounts.',
            ]);
        }

        $maxDiscount = max(0.0, min(90.0, (float) ($settings['max_discount_percent'] ?? 10)));
        $rules = trim((string) ($settings['negotiation_rules'] ?? ''));
        $upsellOn = (bool) ($settings['upsell_enabled'] ?? true);
        $upsellNotes = trim((string) ($settings['upsell_notes'] ?? ''));

        $lines = [
            'Price negotiation & closing (Vendor+):',
            '- You may negotiate when the customer objects to price.',
            '- Never discount more than ' . rtrim(rtrim(number_format($maxDiscount, 2, '.', ''), '0'), '.') . '% off the listed catalog price.',
            '- If they ask for more than that, politely hold the floor and set owner_alert_required true.',
            '- Handle objections with product value, delivery, policies, and FAQs. Stay warm and clear.',
            '- When both sides agree on a price, move to close: set payment_required true and payment_amount to the agreed catalog-currency total.',
        ];

        if ($rules !== '') {
            $lines[] = '- Extra negotiation rules from the owner: ' . knowledge_excerpt($rules, 600);
        }

        if ($upsellOn) {
            $lines[] = '- Upsell when it fits: suggest a matching add-on or upgrade from the catalog after the main item is clear.';
            if ($upsellNotes !== '') {
                $lines[] = '- Upsell notes: ' . knowledge_excerpt($upsellNotes, 400);
            }
        } else {
            $lines[] = '- Do not upsell unless the customer asks for related items.';
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $business
     * @param array<string, mixed> $ai
     */
    public function maybeSendPaymentReceipt(
        string $planSlug,
        array $business,
        array $settings,
        array $ai,
        string $platform,
        array $connectedAccount,
        string $senderId,
        string $businessId,
        string $customerId,
        string $conversationId
    ): ?string {
        if (!PlanAccess::allows($planSlug, 'payment_receipts')) {
            return null;
        }
        if (!(bool) ($settings['receipt_enabled'] ?? true)) {
            return null;
        }

        $leadStage = (string) ($ai['lead_stage'] ?? '');
        $intent = strtolower((string) ($ai['intent'] ?? ''));
        $isPaid = $leadStage === 'paid' || str_contains($intent, 'payment_confirmation') || $intent === 'paid';
        if (!$isPaid) {
            return null;
        }

        if ($this->recentReceiptExists($conversationId)) {
            return null;
        }

        $amount = $ai['payment_amount'] ?? null;
        $amountLine = '';
        if ($amount !== null && is_numeric($amount) && (float) $amount > 0) {
            $amountLine = 'Amount: ' . money_format_catalog(
                (float) $amount,
                money_business_catalog_currency($settings)
            ) . "\n";
        }

        $template = trim((string) ($settings['receipt_message'] ?? ''));
        if ($template === '') {
            $template = self::DEFAULT_RECEIPT;
        }

        $businessName = trim((string) ($business['business_name'] ?? 'our store')) ?: 'our store';
        $receipt = str_replace(
            ['{business_name}', '{date}', '{amount_line}', '{amount}'],
            [
                $businessName,
                date('j M Y, g:i A'),
                $amountLine,
                $amount !== null && is_numeric($amount)
                    ? money_format_catalog((float) $amount, money_business_catalog_currency($settings))
                    : '',
            ],
            $template
        );
        $receipt = trim($receipt);
        if ($receipt === '') {
            return null;
        }

        $outbound = RepositoryFactory::messages()->create([
            'business_id' => $businessId,
            'customer_id' => $customerId !== '' ? $customerId : null,
            'conversation_id' => $conversationId !== '' ? $conversationId : null,
            'platform' => $platform,
            'sender_type' => 'ai',
            'message_text' => $receipt,
            'ai_intent' => 'payment_receipt',
        ]);

        $sent = automation_send_platform_reply($platform, $connectedAccount, $senderId, $receipt);
        if (!$sent) {
            return null;
        }

        if ($customerId !== '' && $amount !== null && is_numeric($amount) && (float) $amount > 0) {
            try {
                RepositoryFactory::orders()->create($businessId, [
                    'customer_id' => $customerId,
                    'conversation_id' => $conversationId !== '' ? $conversationId : null,
                    'status' => 'paid',
                    'total_amount' => (string) round((float) $amount, 2),
                    'currency' => money_business_catalog_currency($settings),
                ]);
            } catch (Throwable $e) {
                error_log('[SalesAutomation] order create failed: ' . $e->getMessage());
            }
        }

        return (string) ($outbound['id'] ?? '');
    }

    public function recentReceiptExists(string $conversationId): bool
    {
        if ($conversationId === '') {
            return false;
        }

        $messages = RepositoryFactory::messages()->listByConversationId($conversationId, 40);
        foreach (array_reverse($messages) as $message) {
            if ((string) ($message['ai_intent'] ?? '') === 'payment_receipt') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{sent: int, skipped: int, errors: int}
     */
    public function processDueFollowups(int $limit = 25): array
    {
        $sent = 0;
        $skipped = 0;
        $errors = 0;
        $remaining = max(1, min(100, $limit));

        $businesses = $this->listFollowupEligibleBusinesses();
        foreach ($businesses as $row) {
            if ($remaining <= 0) {
                break;
            }

            $businessId = (string) ($row['business_id'] ?? '');
            $userId = (string) ($row['user_id'] ?? '');
            $planSlug = (string) ($row['plan_slug'] ?? 'starter');
            if ($businessId === '' || $userId === '' || !PlanAccess::allows($planSlug, 'proactive_followups')) {
                continue;
            }

            $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId);
            if ($settings === null || !(bool) ($settings['followup_enabled'] ?? true)) {
                continue;
            }

            $tokenService = new TokenService();
            if (!$tokenService->canSpend($userId, TokenCosts::DM_REPLY)) {
                $skipped++;
                continue;
            }

            $delayHours = max(1, min(72, (int) ($settings['followup_delay_hours'] ?? 6)));
            $maxAttempts = max(1, min(5, (int) ($settings['followup_max_attempts'] ?? 2)));
            $intervalHours = max(1, min(72, (int) ($settings['followup_interval_hours'] ?? 12)));

            $candidates = RepositoryFactory::conversations()->listQuietForFollowup($businessId, $delayHours, min(20, $remaining));
            $business = RepositoryFactory::businesses()->findById($businessId);
            if ($business === null) {
                continue;
            }

            foreach ($candidates as $conversation) {
                if ($remaining <= 0) {
                    break;
                }

                $result = $this->sendFollowupForConversation(
                    $business,
                    $settings,
                    $conversation,
                    $userId,
                    $planSlug,
                    $maxAttempts,
                    $intervalHours
                );

                if ($result === 'sent') {
                    $sent++;
                    $remaining--;
                } elseif ($result === 'error') {
                    $errors++;
                } else {
                    $skipped++;
                }
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * @return list<array{business_id: string, user_id: string, plan_slug: string}>
     */
    private function listFollowupEligibleBusinesses(): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query(
            "SELECT b.id AS business_id, b.user_id,
                    COALESCE(us.plan_slug, b.subscription_plan, 'starter') AS plan_slug
             FROM businesses b
             INNER JOIN business_settings bs ON bs.business_id = b.id AND bs.followup_enabled = 1
             LEFT JOIN user_subscriptions us ON us.user_id = b.user_id AND us.status = 'active'
             WHERE b.onboarding_completed_at IS NOT NULL"
        );
        if ($stmt === false) {
            return [];
        }

        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!is_array($row)) {
                continue;
            }
            $plan = (string) ($row['plan_slug'] ?? 'starter');
            if (!PlanAccess::allows($plan, 'proactive_followups')) {
                continue;
            }
            $rows[] = [
                'business_id' => (string) ($row['business_id'] ?? ''),
                'user_id' => (string) ($row['user_id'] ?? ''),
                'plan_slug' => $plan,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $business
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $conversation
     */
    private function sendFollowupForConversation(
        array $business,
        array $settings,
        array $conversation,
        string $userId,
        string $planSlug,
        int $maxAttempts,
        int $intervalHours
    ): string {
        $conversationId = (string) ($conversation['id'] ?? '');
        $businessId = (string) ($business['id'] ?? '');
        $customerId = (string) ($conversation['customer_id'] ?? '');
        $platform = (string) ($conversation['platform'] ?? '');
        if ($conversationId === '' || $customerId === '' || $platform === '') {
            return 'skip';
        }

        if ((string) ($conversation['status'] ?? '') === 'human') {
            return 'skip';
        }

        $customer = RepositoryFactory::customers()->findById($customerId);
        $recipientId = trim((string) ($customer['platform_user_id'] ?? ''));
        if ($recipientId === '') {
            return 'skip';
        }

        $lastCustomerAt = strtotime((string) ($conversation['last_customer_at'] ?? $conversation['last_message'] ?? '')) ?: 0;
        if (in_array($platform, ['instagram', 'facebook', 'whatsapp'], true) && $lastCustomerAt > 0) {
            // Meta / WhatsApp customer-care windows are ~24h from last customer message.
            if ((time() - $lastCustomerAt) > (23 * 3600)) {
                return 'skip';
            }
        }

        $followupRepo = RepositoryFactory::conversationFollowups();
        $state = $followupRepo->findByConversationId($conversationId);
        $attemptCount = (int) ($state['attempt_count'] ?? 0);
        if ($state !== null && (string) ($state['status'] ?? '') === 'completed') {
            return 'skip';
        }
        if ($attemptCount >= $maxAttempts) {
            if ($state !== null) {
                $followupRepo->markStatus($conversationId, 'completed');
            }
            return 'skip';
        }

        if ($attemptCount > 0) {
            $lastAt = strtotime((string) ($state['last_followup_at'] ?? '')) ?: 0;
            if ($lastAt > 0 && (time() - $lastAt) < ($intervalHours * 3600)) {
                return 'skip';
            }
        }

        $accounts = RepositoryFactory::connectedAccounts()->listActiveByBusinessAndPlatform($businessId, $platform);
        $connectedAccount = $accounts[0] ?? null;
        if ($connectedAccount === null) {
            return 'skip';
        }

        $tokenService = new TokenService();
        if (!$tokenService->canSpend($userId, TokenCosts::DM_REPLY)) {
            return 'skip';
        }

        $recent = RepositoryFactory::messages()->listByConversationId($conversationId, 10);
        foreach (array_reverse($recent) as $message) {
            $intent = (string) ($message['ai_intent'] ?? '');
            if (in_array($intent, ['payment_receipt', 'payment_confirmation'], true)) {
                $followupRepo->markStatus($conversationId, 'completed');
                return 'skip';
            }
            if ((string) ($message['sender_type'] ?? '') === 'customer') {
                break;
            }
        }

        $reply = $this->generateFollowupReply($business, $settings, $recent, $attemptCount + 1);
        if ($reply === '') {
            return 'error';
        }

        $outbound = RepositoryFactory::messages()->create([
            'business_id' => $businessId,
            'customer_id' => $customerId,
            'conversation_id' => $conversationId,
            'platform' => $platform,
            'sender_type' => 'ai',
            'message_text' => $reply,
            'ai_intent' => 'cold_followup',
        ]);

        $ok = automation_send_platform_reply($platform, $connectedAccount, $recipientId, $reply);
        if (!$ok) {
            return 'error';
        }

        $tokenService->spend(
            $userId,
            TokenCosts::DM_REPLY,
            'dm_followup',
            'message',
            (string) ($outbound['id'] ?? ''),
            ['business_id' => $businessId, 'platform' => $platform, 'plan' => $planSlug]
        );

        $state = $followupRepo->upsertActive($businessId, $conversationId, $customerId, $platform);
        $newCount = $attemptCount + 1;
        $followupRepo->recordAttempt((string) $state['id'], $newCount);
        if ($newCount >= $maxAttempts) {
            $followupRepo->markStatus($conversationId, 'completed');
        }

        return 'sent';
    }

    /**
     * @param array<string, mixed> $business
     * @param array<string, mixed> $settings
     * @param list<array<string, mixed>> $recent
     */
    private function generateFollowupReply(array $business, array $settings, array $recent, int $attemptNumber): string
    {
        $aiName = trim((string) ($settings['ai_name'] ?? 'Sales Girl')) ?: 'Sales Girl';
        $businessName = trim((string) ($business['business_name'] ?? 'our store')) ?: 'our store';
        $notes = trim((string) ($settings['followup_notes'] ?? ''));

        $historyLines = [];
        foreach (array_slice($recent, -6) as $message) {
            $role = ((string) ($message['sender_type'] ?? '')) === 'customer' ? 'Customer' : $aiName;
            $text = trim((string) ($message['message_text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $historyLines[] = $role . ': ' . knowledge_excerpt($text, 220);
        }

        $system = implode("\n", array_filter([
            'You are ' . $aiName . ' for ' . $businessName . '.',
            'Write ONE short re-engagement DM because the customer went quiet.',
            'This is follow-up attempt #' . $attemptNumber . '.',
            'Rules:',
            '- 1 to 3 short sentences. Simple words.',
            '- No exclamation marks. No em dashes.',
            '- Do not invent prices or products.',
            '- Do not introduce yourself again.',
            '- Ask one soft question or offer help to continue.',
            $notes !== '' ? 'Owner guidance: ' . knowledge_excerpt($notes, 400) : null,
            'Return ONLY the message text. No JSON. No quotes around the whole reply.',
        ]));

        $user = "Conversation so far:\n" . ($historyLines !== [] ? implode("\n", $historyLines) : '(no recent text)')
            . "\n\nWrite the follow-up message now.";

        try {
            require_once dirname(__DIR__) . '/includes/openai-client.php';
            $api = openai_chat_completion([
                'model' => app_config()['openai']['model'] ?? 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'temperature' => 0.5,
                'max_tokens' => 180,
            ], (string) ($business['id'] ?? ''), 'dm_followup', 0.002);

            if (!empty($api['ok'])) {
                $content = $api['body']['choices'][0]['message']['content'] ?? null;
                if (is_string($content)) {
                    $text = trim($content);
                    $text = trim($text, " \t\n\r\0\x0B\"'");
                    if ($text !== '') {
                        return $text;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('[SalesAutomation] followup generate failed: ' . $e->getMessage());
        }

        if ($attemptNumber <= 1) {
            return 'Just checking in. Are you still interested, or do you want me to help with anything else?';
        }

        return 'I am still here if you want to continue. Tell me what you need and I will help.';
    }
}
