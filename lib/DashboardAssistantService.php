<?php

declare(strict_types=1);

require_once __DIR__ . '/VanceVoice.php';
require_once __DIR__ . '/DashboardAssistantActions.php';
require_once __DIR__ . '/../includes/onboarding/helpers.php';
require_once dirname(__DIR__) . '/includes/openai-client.php';

/**
 * Dashboard Hannah — helps logged-in users manage their business via tool actions.
 */
final class DashboardAssistantService
{
    private const MAX_USER_MESSAGE = 800;
    private const MAX_HISTORY_TURNS = 12;
    private const MAX_TOOL_ROUNDS = 5;
    private const RATE_LIMIT_PER_HOUR = 60;

    private string $userId;
    private string $businessId;
    private array $business;
    private DashboardAssistantActions $actions;

    /**
     * @param array<string, mixed> $business
     */
    public function __construct(string $userId, array $business)
    {
        $this->userId = $userId;
        $this->businessId = (string) $business['id'];
        $this->business = $business;
        $this->actions = new DashboardAssistantActions($userId, $this->businessId);
    }

    /**
     * @param list<array{role: string, content: string}> $history
     * @return array{ok: bool, reply?: string, error?: string, actions?: list<string>}
     */
    public function chat(array $history, string $userMessage): array
    {
        $userMessage = trim($userMessage);
        if ($userMessage === '') {
            return ['ok' => false, 'error' => 'Please type a message first.'];
        }

        if (mb_strlen($userMessage) > self::MAX_USER_MESSAGE) {
            return ['ok' => false, 'error' => 'That message is too long. Please shorten it and try again.'];
        }

        if (!$this->checkRateLimit()) {
            return ['ok' => false, 'error' => 'You sent a lot of messages. Please wait a little and try again.'];
        }

        $tokenService = new TokenService();
        $assistantCost = TokenCosts::DASHBOARD_ASSISTANT;
        if (!$tokenService->canSpend($this->userId, $assistantCost)) {
            return ['ok' => false, 'error' => TokenCosts::insufficientMessage($assistantCost)];
        }

        if ((new OpenAiBudgetService())->isExceeded($this->businessId)) {
            return ['ok' => false, 'error' => (new OpenAiBudgetService())->blockedUserMessage($this->businessId)];
        }

        $messages = $this->buildMessages($history, $userMessage);
        $actionsTaken = [];

        for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
            $response = $this->callOpenAi($messages);
            if ($response === null) {
                return ['ok' => false, 'error' => 'Sorry, I could not answer right now. Please try again in a moment.'];
            }

            $choice = $response['choices'][0]['message'] ?? null;
            if (!is_array($choice)) {
                return ['ok' => false, 'error' => 'Sorry, I could not answer right now. Please try again in a moment.'];
            }

            $messages[] = $choice;
            $toolCalls = $choice['tool_calls'] ?? null;

            if (!is_array($toolCalls) || $toolCalls === []) {
                $reply = trim((string) ($choice['content'] ?? ''));
                if ($reply === '') {
                    return ['ok' => false, 'error' => 'Sorry, I could not answer right now. Please try again in a moment.'];
                }

                $this->recordRequest();
                $tokenService->spend(
                    $this->userId,
                    $assistantCost,
                    'dashboard_assistant',
                    'assistant_chat',
                    Uuid::v4(),
                    ['business_id' => $this->businessId]
                );

                return [
                    'ok' => true,
                    'reply' => chat_normalize_ai_reply($reply),
                    'actions' => $actionsTaken,
                ];
            }

            foreach ($toolCalls as $toolCall) {
                if (!is_array($toolCall)) {
                    continue;
                }

                $toolId = (string) ($toolCall['id'] ?? '');
                $function = is_array($toolCall['function'] ?? null) ? $toolCall['function'] : [];
                $name = (string) ($function['name'] ?? '');
                $argsRaw = (string) ($function['arguments'] ?? '{}');
                $args = json_decode($argsRaw, true);
                if (!is_array($args)) {
                    $args = [];
                }

                $result = $this->actions->execute($name, $args);
                if (($result['ok'] ?? false) === true) {
                    $actionsTaken[] = $name;
                }

                if ($name === 'switch_active_business' && ($result['ok'] ?? false) === true) {
                    $this->businessId = (string) ($result['business_id'] ?? $this->businessId);
                    $business = RepositoryFactory::businesses()->findById($this->businessId);
                    if ($business !== null) {
                        $this->business = $business;
                    }
                }

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolId,
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ];
            }
        }

        return ['ok' => false, 'error' => 'That task took too many steps. Please try a smaller request.'];
    }

    public function greeting(): string
    {
        $name = trim((string) ($this->business['business_name'] ?? 'your business'));

        return 'I\'m Hannah. I can update products, FAQs, policies, payment details, and other settings for '
            . $name . '. Tell me what you want done.';
    }

    /**
     * @return list<string>
     */
    public function suggestedQuestions(): array
    {
        return [
            'Add a product to my catalog',
            'Show my current products',
            'Update my delivery policy',
            'Add an FAQ about shipping',
            'Sync my AI knowledge',
        ];
    }

    /**
     * @param list<array{role: string, content: string}> $history
     * @return list<array<string, mixed>>
     */
    private function buildMessages(array $history, string $userMessage): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt()],
        ];

        $sanitizedHistory = [];
        foreach ($history as $turn) {
            $role = (string) ($turn['role'] ?? '');
            $content = trim((string) ($turn['content'] ?? ''));
            if ($content === '' || !in_array($role, ['user', 'assistant'], true)) {
                continue;
            }
            $sanitizedHistory[] = [
                'role' => $role,
                'content' => mb_substr($content, 0, self::MAX_USER_MESSAGE),
            ];
        }

        $sanitizedHistory = array_slice($sanitizedHistory, -self::MAX_HISTORY_TURNS * 2);
        foreach ($sanitizedHistory as $turn) {
            $messages[] = $turn;
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $messages;
    }

    private function buildSystemPrompt(): string
    {
        $appUrl = app_config()['app_url'];
        $businessName = (string) ($this->business['business_name'] ?? 'Business');
        $subscription = (new SubscriptionService())->getActiveSubscription($this->userId);
        $planSlug = $subscription !== null ? (string) $subscription['plan_slug'] : 'starter';
        $plan = PlanService::find($planSlug);
        $settings = (new OnboardingService())->ensureSettings($this->businessId);
        $offerings = RepositoryFactory::offerings()->listByBusinessId($this->businessId);
        $faqs = faq_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);

        return implode("\n", [
            'You are Hannah. You help this Lead Bird customer manage their business inside the dashboard.',
            'Your name is always Hannah. You speak with the same tone as the public-site Hannah.',
            'The active business is "' . $businessName . '" (id: ' . $this->businessId . ').',
            'Plan: ' . (string) ($plan['name'] ?? ucfirst($planSlug)) . '.',
            'Current snapshot: ' . count($offerings) . ' products in catalog, ' . count($faqs) . ' FAQs.',
            'Payment instructions saved: ' . (trim((string) ($settings['payment_instructions'] ?? '')) !== '' ? 'yes' : 'no') . '.',
            '',
            'YOUR JOB:',
            '- Do dashboard tasks for the user using your tools — add/update/delete products, FAQs, policies, payment info, business profile, AI Salesgirl settings, owner alerts, sync knowledge, switch business.',
            '- When they ask you to do something, use the right tool. Do not tell them to go do it manually unless the action is not supported.',
            '- If you need a detail (product name, price, FAQ text), ask in one short sentence, then act when they reply.',
            '- After tools run, confirm what changed in plain English.',
            '',
            VanceVoice::rulesBlock(),
            '',
            ...VanceVoice::dashboardResponseRules(),
            '',
            'TOOLS YOU HAVE:',
            '- get_dashboard_summary — overview of this business',
            '- list_offerings / add_offering / update_offering / delete_offering — product catalog',
            '- list_faqs / add_faq / update_faq / delete_faq — customer FAQs',
            '- get_policies / save_policies — delivery and return policies',
            '- get_payment_instructions / save_payment_instructions',
            '- get_business_profile / update_business_profile',
            '- get_ai_salesgirl_profile / update_ai_salesgirl_profile — AI name, tone, greeting',
            '- get_owner_alerts / save_owner_alerts',
            '- list_connected_accounts — read only; connect/disconnect on Connected Accounts page',
            '- list_businesses / switch_active_business — when user has multiple brands',
            '- sync_knowledge — refresh AI knowledge from catalog, FAQs, policies',
            '- get_dashboard_link — get URL for a dashboard page (inbox, billing, connected_accounts, etc.)',
            '',
            'NOT SUPPORTED IN CHAT (give page link with get_dashboard_link):',
            '- Product image uploads, connecting/disconnecting social accounts, billing card changes, deleting business or account, replying to customer inbox messages.',
            '',
            'Dashboard base URL: ' . $appUrl,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function toolDefinitions(): array
    {
        return [
            self::tool('get_dashboard_summary', 'Get overview of the active business — products, FAQs, channels, stats.', []),
            self::tool('list_offerings', 'List all products and services in the catalog.', []),
            self::tool('add_offering', 'Add a product or service to the catalog.', [
                'name' => ['type' => 'string', 'description' => 'Product or service name'],
                'price' => ['type' => 'number', 'description' => 'Price in Naira (optional)'],
                'description' => ['type' => 'string', 'description' => 'Short description (optional)'],
                'offering_type' => ['type' => 'string', 'enum' => ['product', 'service', 'membership']],
                'billing_type' => ['type' => 'string', 'enum' => ['one_time', 'monthly', 'per_session']],
            ], ['name']),
            self::tool('update_offering', 'Update an existing catalog item. Use list_offerings first for the id.', [
                'offering_id' => ['type' => 'string', 'description' => 'Product id from list_offerings'],
                'name' => ['type' => 'string'],
                'price' => ['type' => 'number'],
                'description' => ['type' => 'string'],
                'offering_type' => ['type' => 'string', 'enum' => ['product', 'service', 'membership']],
                'billing_type' => ['type' => 'string', 'enum' => ['one_time', 'monthly', 'per_session']],
                'active' => ['type' => 'boolean'],
            ], ['offering_id']),
            self::tool('delete_offering', 'Remove a product from the catalog.', [
                'offering_id' => ['type' => 'string'],
            ], ['offering_id']),
            self::tool('list_faqs', 'List all FAQs with index numbers for update/delete.', []),
            self::tool('add_faq', 'Add a new FAQ.', [
                'question' => ['type' => 'string'],
                'answer' => ['type' => 'string'],
            ], ['question', 'answer']),
            self::tool('update_faq', 'Update an FAQ by index from list_faqs.', [
                'index' => ['type' => 'integer'],
                'question' => ['type' => 'string'],
                'answer' => ['type' => 'string'],
            ], ['index', 'question', 'answer']),
            self::tool('delete_faq', 'Delete an FAQ by index.', [
                'index' => ['type' => 'integer'],
            ], ['index']),
            self::tool('get_policies', 'Get delivery and return policies.', []),
            self::tool('save_policies', 'Save delivery and/or return policy text.', [
                'delivery_policy' => ['type' => 'string'],
                'return_policy' => ['type' => 'string'],
            ]),
            self::tool('get_payment_instructions', 'Get how customers should pay.', []),
            self::tool('save_payment_instructions', 'Save payment instructions for customers.', [
                'payment_instructions' => ['type' => 'string'],
            ], ['payment_instructions']),
            self::tool('get_business_profile', 'Get business name, owner details, description.', []),
            self::tool('update_business_profile', 'Update business profile fields.', [
                'business_name' => ['type' => 'string'],
                'owner_name' => ['type' => 'string'],
                'owner_email' => ['type' => 'string'],
                'owner_phone' => ['type' => 'string'],
                'business_description' => ['type' => 'string'],
            ]),
            self::tool('get_ai_salesgirl_profile', 'Get AI Salesgirl name, tone, greeting.', []),
            self::tool('update_ai_salesgirl_profile', 'Update AI Salesgirl persona.', [
                'ai_name' => ['type' => 'string'],
                'business_name' => ['type' => 'string'],
                'ai_tone' => ['type' => 'string'],
                'ai_greeting' => ['type' => 'string'],
            ]),
            self::tool('get_owner_alerts', 'Get owner notification settings.', []),
            self::tool('save_owner_alerts', 'Update owner alert settings.', [
                'escalation_phone' => ['type' => 'string'],
                'escalation_email' => ['type' => 'string'],
                'handoff_to_whatsapp' => ['type' => 'boolean'],
                'notify_owner_on_hot_lead' => ['type' => 'boolean'],
                'notify_owner_on_payment' => ['type' => 'boolean'],
                'notify_owner_on_complaint' => ['type' => 'boolean'],
            ]),
            self::tool('list_connected_accounts', 'List connected WhatsApp, Instagram, Messenger accounts.', []),
            self::tool('list_businesses', 'List all businesses on this account.', []),
            self::tool('switch_active_business', 'Switch dashboard to another business the user owns.', [
                'business_id' => ['type' => 'string'],
            ], ['business_id']),
            self::tool('sync_knowledge', 'Refresh AI knowledge from catalog, FAQs, and policies.', []),
            self::tool('get_dashboard_link', 'Get URL for a dashboard page.', [
                'page' => [
                    'type' => 'string',
                    'enum' => [
                        'overview', 'inbox', 'customers', 'offers', 'catalog', 'faqs',
                        'policies', 'payment', 'ai_salesgirl', 'connected_accounts',
                        'billing', 'settings', 'businesses', 'help',
                    ],
                ],
            ], ['page']),
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $properties
     * @param list<string> $required
     * @return array<string, mixed>
     */
    private static function tool(string $name, string $description, array $properties, array $required = []): array
    {
        $schema = [
            'type' => 'object',
            'properties' => $properties === [] ? (object) [] : $properties,
        ];
        if ($required !== []) {
            $schema['required'] = $required;
        }

        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $description,
                'parameters' => $schema,
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $messages
     * @return array<string, mixed>|null
     */
    private function callOpenAi(array $messages): ?array
    {
        $apiKey = app_config()['openai']['api_key'] ?? '';
        $model = app_config()['openai']['model'] ?? 'gpt-4o-mini';

        if ($apiKey === '') {
            error_log('[DashboardAssistant] OPENAI_API_KEY is not configured.');
            return null;
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'tools' => self::toolDefinitions(),
            'tool_choice' => 'auto',
            'temperature' => 0.62,
            'max_tokens' => 900,
        ];

        $api = openai_chat_completion($payload, $this->businessId, 'dashboard_assistant', 0.004);
        if (!($api['ok'] ?? false)) {
            error_log('[DashboardAssistant] OpenAI error: ' . (string) ($api['error'] ?? 'unknown'));
            return null;
        }

        $decoded = $api['body'] ?? null;

        return is_array($decoded) ? $decoded : null;
    }

    private function checkRateLimit(): bool
    {
        $bucket = $_SESSION['dashboard_assistant_rate'] ?? ['window' => time(), 'count' => 0];
        if (!is_array($bucket)) {
            $bucket = ['window' => time(), 'count' => 0];
        }

        $windowStart = (int) ($bucket['window'] ?? time());
        $count = (int) ($bucket['count'] ?? 0);

        if (time() - $windowStart >= 3600) {
            $windowStart = time();
            $count = 0;
        }

        return $count < self::RATE_LIMIT_PER_HOUR;
    }

    private function recordRequest(): void
    {
        $bucket = $_SESSION['dashboard_assistant_rate'] ?? ['window' => time(), 'count' => 0];
        if (!is_array($bucket)) {
            $bucket = ['window' => time(), 'count' => 0];
        }

        $windowStart = (int) ($bucket['window'] ?? time());
        $count = (int) ($bucket['count'] ?? 0);

        if (time() - $windowStart >= 3600) {
            $windowStart = time();
            $count = 0;
        }

        $_SESSION['dashboard_assistant_rate'] = [
            'window' => $windowStart,
            'count' => $count + 1,
        ];
    }
}
