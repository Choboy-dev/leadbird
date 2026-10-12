<?php

declare(strict_types=1);

require_once __DIR__ . '/VanceVoice.php';
require_once dirname(__DIR__) . '/includes/openai-client.php';

/**
 * Public-site AI guide — answers questions about Lead Bird using product knowledge.
 */
final class SiteAssistantService
{
    private const MAX_USER_MESSAGE = 800;
    private const MAX_HISTORY_TURNS = 10;
    private const RATE_LIMIT_PER_HOUR = 45;

    /**
     * @param list<array{role: string, content: string}> $history
     * @return array{ok: bool, reply?: string, error?: string}
     */
    public function chat(array $history, string $userMessage): array
    {
        $userMessage = trim($userMessage);
        if ($userMessage === '') {
            return ['ok' => false, 'error' => 'Please type a question first.'];
        }

        if (mb_strlen($userMessage) > self::MAX_USER_MESSAGE) {
            return ['ok' => false, 'error' => 'That message is too long. Please shorten it and try again.'];
        }

        if (!$this->checkRateLimit()) {
            return ['ok' => false, 'error' => 'You asked a lot of questions! Please wait a little and try again.'];
        }

        if ((new OpenAiBudgetService())->isExceeded(OpenAiBudgetService::PLATFORM_BUSINESS_ID)) {
            $reply = $this->fallbackReply($userMessage);
            if ($reply !== '') {
                $this->recordRequest();
                return ['ok' => true, 'reply' => $reply];
            }

            return ['ok' => false, 'error' => 'Sorry, I could not answer right now. Please try again in a moment.'];
        }

        $messages = $this->buildMessages($history, $userMessage);
        $reply = $this->callOpenAi($messages);

        if ($reply === null) {
            $reply = $this->fallbackReply($userMessage);
        }

        if ($reply === '') {
            return ['ok' => false, 'error' => 'Sorry, I could not answer right now. Please try again in a moment.'];
        }

        $this->recordRequest();

        return ['ok' => true, 'reply' => $reply];
    }

    public function greeting(): string
    {
        return 'Hey, I\'m Hannah. Got a question about Lead Bird? Just ask. Plans, setup, WhatsApp, Instagram, whatever you need.';
    }

    /**
     * @return list<string>
     */
    public function suggestedQuestions(): array
    {
        return [
            'What is Lead Bird?',
            'How does the AI Salesgirl work?',
            'What is on the Starter plan?',
            'How do I connect WhatsApp or Instagram?',
        ];
    }

    /**
     * @param list<array{role: string, content: string}> $history
     * @return list<array{role: string, content: string}>
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
        $brandName = app_config()['brand_name'] ?? 'Lead Bird';
        $contact = app_config()['contact'] ?? [];

        $planLines = [];
        foreach (plans_catalog() as $plan) {
            $name = (string) ($plan['name'] ?? 'Plan');
            $price = number_format((int) ($plan['price'] ?? 0));
            $features = [];
            foreach ($plan['features'] ?? [] as $feature) {
                if (is_array($feature)) {
                    $label = trim((string) ($feature['label'] ?? ''));
                    if ($label !== '') {
                        $features[] = $label;
                    }
                    continue;
                }
                $features[] = (string) $feature;
            }

            $businessLimit = $plan['business_limit'] ?? null;
            $channelsPerPlatform = $plan['channels_per_platform'] ?? null;
            $monthlyTokens = $plan['monthly_tokens'] ?? null;

            $limits = [];
            if ($businessLimit === null) {
                $limits[] = 'many businesses allowed';
            } else {
                $limits[] = (int) $businessLimit . ' business' . ((int) $businessLimit === 1 ? '' : 'es');
            }
            if ($channelsPerPlatform === null) {
                $limits[] = 'unlimited Facebook, Instagram, and WhatsApp per business';
            } else {
                $limits[] = 'each business gets ' . (int) $channelsPerPlatform . ' Facebook, ' . (int) $channelsPerPlatform . ' Instagram, and ' . (int) $channelsPerPlatform . ' WhatsApp';
            }
            if ($monthlyTokens === null) {
                $limits[] = '8,000 tokens each month';
            } else {
                $limits[] = (int) $monthlyTokens . ' tokens each month (AI replies, social AI, and publishing)';
            }

            $planLines[] = '- ' . $name . ' plan — ' . money_format((float) $price) . '/month'
                . '. ' . implode('. ', $limits) . '.'
                . ' Includes: ' . implode('; ', $features);
        }

        $topupLines = [];
        foreach (token_topup_packages() as $pack) {
            $topupLines[] = '- ' . ($pack['name'] ?? 'Top up')
                . ': ' . money_format((float) ($pack['price'] ?? 0))
                . ' for ' . (int) ($pack['tokens'] ?? 0) . ' extra AI replies';
        }

        $supportEmail = trim((string) ($contact['email'] ?? ''));
        $supportPhone = trim((string) ($contact['phone'] ?? ''));

        return implode("\n", [
            'You are Hannah. You work for Lead Bird and you talk to people on the website.',
            'Your job is to explain Lead Bird clearly, like a professional salesman who speaks good English.',
            'Your name is always Hannah. Say "I\'m Hannah" sometimes, not "I am an AI assistant" or "I am a helper bot".',
            'You ONLY answer questions about Lead Bird: the product, plans, setup, channels, tokens, billing, and how businesses use it.',
            'If someone asks about unrelated topics, say nicely that you only know Lead Bird and ask what they want to know about it.',
            'If you do not know something, say so. Tell them to sign up, check pricing, or contact support. Do not make things up.',
            '',
            VanceVoice::rulesBlock(),
            '',
            'WHAT LEAD BIRD IS (use these facts, but say them in your own simple words):',
            '- Lead Bird gives every business an AI Salesgirl that replies in DMs on WhatsApp, Instagram, and Facebook Messenger.',
            '- She answers product and price questions, shares payment details, helps close sales, and works 24/7 even when the owner is busy or asleep.',
            '- Tagline: "Never miss another DM."',
            '- She learns from your catalog, FAQs, policies, and past chats so answers match your business.',
            '- She replies in DMs only — she does not post on your feed or stories.',
            '',
            'KEY FEATURES:',
            '- Answers questions instantly (size, price, delivery, location, etc.).',
            '- Sends your payment account number, wallet, or link automatically.',
            '- Can help collect payment proof and share your payment instructions.',
            '- Works day and night on connected channels.',
            '- Bulk WhatsApp Messaging (Vendor and Shop plans): send a Meta-approved message to every customer who opted in, from your own WhatsApp Business number. Vendor allows up to 5,000 contacts, Shop up to 50,000. Hannah answers the replies. Anyone who replies STOP is removed. More at ' . app_route('features/bulk-whatsapp'),
            '- You choose her name, tone, and greeting on the AI Salesgirl page after signup.',
            '- Dashboard includes: Inbox (read all chats), Customers, Offers & Catalog, AI Salesgirl settings, Connected Accounts, Billing, Settings, Help, and My Businesses (switch or add brands).',
            '',
            'HOW TO GET STARTED:',
            '1. Go to ' . app_route('signup') . ' and create a free account (email code or Google).',
            '2. Pick a plan at ' . app_route('pricing') . ' (Starter, Vendor, or Shop).',
            '3. Complete setup steps: Connect channels → Business profile → Offers & services → FAQs & policies → Payment instructions → Owner alerts.',
            '4. Train/sync your AI on the AI Salesgirl page so she knows your products.',
            '5. Connect Meta channels (Instagram, Facebook Page/Messenger, WhatsApp) — guide at ' . app_route('guides/connect-meta-channels'),
            '',
            'ONBOARDING STEPS (in order):',
            '- Connect Channels — link WhatsApp, Instagram, and Messenger.',
            '- Business Profile — your business name and owner details.',
            '- Offers & Services — products and services you sell.',
            '- FAQs & Policies — common questions and rules.',
            '- Payment Instructions — how customers should pay you.',
            '- Owner Alerts — when Lead Bird should notify you.',
            '',
            'PLANS (prices in US dollars per month; yearly saves two months):',
            implode("\n", $planLines),
            '',
            'TOKEN TOP-UPS (extra AI replies anytime on limited plans):',
            implode("\n", $topupLines),
            '',
            'TOKENS EXPLAINED:',
            '- On Starter and Vendor, each AI reply to a customer uses 1 token.',
            '- Tokens refill every month with your plan.',
            '- Shop plan has unlimited tokens.',
            '- If tokens run out, AI pauses but messages are still saved — you can reply manually or top up on Billing.',
            '',
            'HOW TO RECOMMEND PLANS (BE SMART — VERY IMPORTANT):',
            'When someone asks which plan fits them, what they get, or "benefits of each plan":',
            '- DO NOT repeat all three plans with feature bullets. That feels robotic.',
            '- DO NOT give a generic "it depends" answer without picking a plan and saying why.',
            '- Talk about what they GET TO ENJOY — outcomes, not a spec sheet.',
            '  Good: "On Starter, your AI answers price and payment messages on Instagram while you are away."',
            '  Bad: "Starter: 1 business, 50 tokens, 1 Instagram."',
            '',
            'STEP 1 — USE WHAT THEY TOLD YOU:',
            '- If they say what they sell and where (e.g. handbags on Instagram), think about that business first.',
            '- One brand, one Instagram shop, moderate DMs → usually Starter ($29). They get AI replies on Instagram, payment details sent to customers, 500 replies per month.',
            '- Growing seller, wants haggling, social posts, or moving hot buyers to WhatsApp → Vendor ($59). They enjoy: price talks, WhatsApp handoff, social media publishing, 3 brands if they run multiple shops, 2,000 replies/month.',
            '- High volume, many accounts/pages, or needs follow-ups when buyers go quiet → Shop ($99). They enjoy: 8,000 replies, many channels connected, follow-ups, receipts.',
            '',
            'STEP 2 — ASK IF YOU NEED MORE INFO (1 or 2 short questions max):',
            'Ask before guessing when it changes the answer. Examples:',
            '- "How many social media accounts does your business use at the moment?"',
            '- "About how many customer messages do you receive in a week?"',
            '- "Do you sell under one brand, or do you have separate shops?"',
            '- "Do your customers often ask to negotiate the price, or is your price fixed?"',
            'If you ask a question, still give your best recommendation first: "I would recommend Starter for you, but tell me..."',
            '',
            'STEP 3 — GIVE A CLEAR PICK WITH REASONS:',
            '- Name ONE plan as your recommendation when you can: "For your handbag shop on Instagram, I\'d go with Starter."',
            '- Say WHY in simple benefit language tied to THEIR business — not generic plan text.',
            '- Mention price once when recommending.',
            '- If they need more than 1 IG/WhatsApp/FB per business, or more than 1–3 businesses, say which plan unlocks that.',
            '- Use earlier messages in the chat. If they already said "I have 4 accounts", recommend Shop or explain the limit on Starter/Vendor.',
            '',
            'WHEN TO LIST ALL PLANS:',
            '- Only if they literally ask to compare all plans, list every plan, or see everything side by side.',
            '- Even then, keep it short and benefit-focused — not a copy-paste of the plan table.',
            '',
            'EXAMPLE — User: "I sell women handbags and shoes on Instagram, which plan is best?"',
            'Good Hannah reply:',
            '"For a handbag and shoe shop on Instagram, I would recommend Starter at $29. You connect your Instagram account, add your products and prices, and your AI Salesgirl replies when customers ask about size, colour, or payment — even when you are not online. Five hundred replies a month is enough for most small Instagram shops. How many social media accounts does your business use at the moment — just that one Instagram page, or do you also sell on WhatsApp?"',
            '',
            'CHANNELS:',
            '- Supported live channels: WhatsApp, Instagram DMs, Facebook Messenger.',
            '- You connect them through Meta (Facebook) in Connected Accounts or during onboarding.',
            '- Instagram must be linked to a Facebook Page first.',
            '',
            'IMPORTANT LINKS:',
            '- Home: ' . $appUrl . '/',
            '- Sign up: ' . app_route('signup'),
            '- Log in: ' . app_route('login'),
            '- Pricing: ' . app_route('pricing'),
            '- Contact: ' . app_route('contact'),
            '- Privacy: ' . app_route('privacy'),
            '- Terms: ' . app_route('terms'),
            '- Meta connect guide: ' . app_route('guides/connect-meta-channels'),
            ($supportEmail !== '' ? '- Support email: ' . $supportEmail : ''),
            ($supportPhone !== '' ? '- Support phone: ' . $supportPhone : ''),
            '',
            ...VanceVoice::publicResponseRules(),
        ]);
    }

    /**
     * @param list<array{role: string, content: string}> $messages
     */
    private function callOpenAi(array $messages): ?string
    {
        $apiKey = app_config()['openai']['api_key'] ?? '';
        $model = app_config()['openai']['model'] ?? 'gpt-4o-mini';

        if ($apiKey === '') {
            error_log('[SiteAssistant] OPENAI_API_KEY is not configured.');
            return null;
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.62,
            'max_tokens' => 500,
        ];

        $api = openai_chat_completion(
            $payload,
            OpenAiBudgetService::PLATFORM_BUSINESS_ID,
            'site_assistant',
            0.002
        );
        if (!($api['ok'] ?? false)) {
            error_log('[SiteAssistant] OpenAI error: ' . (string) ($api['error'] ?? 'unknown'));
            return null;
        }

        $decoded = $api['body'] ?? [];
        $content = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));

        return $content !== '' ? $content : null;
    }

    private function fallbackReply(string $userMessage): string
    {
        $question = mb_strtolower(trim($userMessage));

        if ($question === '') {
            return '';
        }

        if ($this->containsAny($question, ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening'])) {
            return 'Hello. I\'m Hannah. I can help with Lead Bird plans, setup, WhatsApp, Instagram, Facebook Messenger, tokens, and billing. What would you like to know?';
        }

        if ($this->containsAny($question, ['what is lead bird', 'what is ai salesgirl', 'how does lead bird work', 'how does the ai salesgirl work', 'what do you do'])) {
            return 'Lead Bird gives businesses an AI Salesgirl that replies to customer DMs on WhatsApp, Instagram, and Facebook Messenger. She answers product and price questions, shares payment details, helps close sales, and works all day and night so the business does not miss messages.';
        }

        if ($this->containsAny($question, ['price', 'pricing', 'cost', 'plan', 'plans', 'package', 'packages', 'which one is best', 'which plan'])) {
            return $this->fallbackPlanReply($question);
        }

        if ($this->containsAny($question, ['token', 'tokens', 'top up', 'topup', 'reply limit', 'ai replies'])) {
            return $this->fallbackTokenReply();
        }

        if ($this->containsAny($question, ['whatsapp', 'instagram', 'facebook', 'messenger', 'connect channel', 'connect my channel', 'connect account'])) {
            return 'Lead Bird works on WhatsApp, Instagram DMs, and Facebook Messenger. To get started, sign up, choose a plan, then connect your channels in onboarding or Connected Accounts. Instagram should be linked to a Facebook Page first. The guide is here: ' . app_route('guides/connect-meta-channels');
        }

        if ($this->containsAny($question, ['sign up', 'signup', 'register', 'create account', 'get started', 'start'])) {
            return 'To get started, create your account at ' . app_route('signup') . ', choose a plan at ' . app_route('pricing') . ', then complete setup in this order: Connect Channels, Business Profile, Offers and Services, FAQs and Policies, Payment Instructions, and Owner Alerts. After that, sync your AI knowledge so Hannah can answer correctly.';
        }

        if ($this->containsAny($question, ['support', 'contact', 'email', 'phone', 'help'])) {
            return $this->fallbackSupportReply();
        }

        if ($this->containsAny($question, ['article', 'blog', 'news', 'football', 'post on your site', 'guest post'])) {
            return 'I only answer questions about Lead Bird, like plans, setup, WhatsApp, Instagram, Messenger, tokens, and billing. If you want to reach the team directly, use ' . app_route('contact') . '.';
        }

        return 'I only answer questions about Lead Bird. You can ask me about pricing, setup, WhatsApp, Instagram, Facebook Messenger, tokens, billing, or how the AI Salesgirl works.';
    }

    private function fallbackPlanReply(string $question): string
    {
        $plans = plans_catalog();

        if ($this->containsAny($question, ['compare', 'all plans', 'list plans', 'benefits of each', 'difference between'])) {
            $lines = [];
            foreach (['starter', 'vendor', 'shop'] as $slug) {
                $plan = $plans[$slug] ?? null;
                if (!is_array($plan)) {
                    continue;
                }

                $lines[] = $this->formatPlanSummary($plan);
            }

            return 'Here is the quick plan breakdown:' . "\n" . implode("\n", $lines) . "\nYou can see full pricing here: " . app_route('pricing');
        }

        if ($this->containsAny($question, ['many accounts', 'multiple accounts', 'many businesses', 'multiple businesses', 'high volume', 'unlimited', 'follow up', 'follow-up'])) {
            $plan = $plans['shop'] ?? null;
            if (is_array($plan)) {
                return 'I would recommend ' . $this->formatPlanRecommendation($plan, 'if you run many brands or accounts, or you want unlimited AI replies and follow-ups when buyers go quiet');
            }
        }

        if ($this->containsAny($question, ['negotiate', 'haggling', 'haggle', 'social media management', 'publish', 'scheduling', 'schedule posts', 'whatsapp handoff'])) {
            $plan = $plans['vendor'] ?? null;
            if (is_array($plan)) {
                return 'I would recommend ' . $this->formatPlanRecommendation($plan, 'if you want price negotiation, WhatsApp handoff for hot buyers, and social media publishing tools');
            }
        }

        $plan = $plans['starter'] ?? null;
        if (!is_array($plan)) {
            return 'You can see our pricing here: ' . app_route('pricing');
        }

        return 'For most single-business sellers, I would recommend ' . $this->formatPlanRecommendation($plan, 'if you mainly want Lead Bird to answer product, price, and payment questions on one business') . ' If you want, tell me how many social accounts or businesses you run and I will point you to the best fit.';
    }

    private function fallbackTokenReply(): string
    {
        $packages = token_topup_packages();
        $lines = [];

        foreach ($packages as $package) {
            $lines[] = '- ' . ($package['name'] ?? 'Top up')
                . ': ' . (int) ($package['tokens'] ?? 0)
                . ' extra replies for ' . money_format((float) ($package['price'] ?? 0));
        }

        return 'On Starter, Vendor, and Shop, 1 AI reply to a customer uses 1 token. Tokens refill every month with your plan, and if you run low you can top up anytime.' . "\n" . implode("\n", $lines);
    }

    private function fallbackSupportReply(): string
    {
        $contact = app_config()['contact'] ?? [];
        $supportEmail = trim((string) ($contact['email'] ?? ''));
        $supportPhone = trim((string) ($contact['phone'] ?? ''));
        $parts = ['You can contact the Lead Bird team here: ' . app_route('contact') . '.'];

        if ($supportEmail !== '') {
            $parts[] = 'Email: ' . $supportEmail . '.';
        }

        if ($supportPhone !== '') {
            $parts[] = 'Phone: ' . $supportPhone . '.';
        }

        return implode(' ', $parts);
    }

    /**
     * @param array<string, mixed> $plan
     */
    private function formatPlanSummary(array $plan): string
    {
        $name = (string) ($plan['name'] ?? 'Plan');
        $price = money_format((float) ($plan['price'] ?? 0)) . '/month';
        $businessLimit = $plan['business_limit'] ?? null;
        $monthlyTokens = $plan['monthly_tokens'] ?? null;
        $channelsPerPlatform = $plan['channels_per_platform'] ?? null;

        $businessText = $businessLimit === null
            ? 'many businesses'
            : (int) $businessLimit . ' business' . ((int) $businessLimit === 1 ? '' : 'es');
        $channelText = $channelsPerPlatform === null
            ? 'many Facebook, Instagram, and WhatsApp accounts'
            : (int) $channelsPerPlatform . ' Facebook, ' . (int) $channelsPerPlatform . ' Instagram, and ' . (int) $channelsPerPlatform . ' WhatsApp per business';
        $tokenText = $monthlyTokens === null
            ? '8,000 tokens each month'
            : (int) $monthlyTokens . ' tokens each month';

        return '- ' . $name . ': ' . $price . '. Best for ' . $businessText . ', with ' . $channelText . ' and ' . $tokenText . '.';
    }

    /**
     * @param array<string, mixed> $plan
     */
    private function formatPlanRecommendation(array $plan, string $reason): string
    {
        $name = (string) ($plan['name'] ?? 'this plan');
        $price = money_format((float) ($plan['price'] ?? 0)) . '/month';

        return $name . ' at ' . $price . ', because it is the best fit ' . $reason . '. See pricing here: ' . app_route('pricing');
    }

    /**
     * @param list<string> $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            $needle = mb_strtolower(trim($needle));
            if ($needle === '') {
                continue;
            }

            if (mb_strlen($needle) <= 3 && !str_contains($needle, ' ')) {
                if (preg_match('/\b' . preg_quote($needle, '/') . '\b/u', $haystack) === 1) {
                    return true;
                }
                continue;
            }

            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function checkRateLimit(): bool
    {
        $bucket = $_SESSION['site_assistant_rate'] ?? ['window' => time(), 'count' => 0];
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
        $bucket = $_SESSION['site_assistant_rate'] ?? ['window' => time(), 'count' => 0];
        if (!is_array($bucket)) {
            $bucket = ['window' => time(), 'count' => 0];
        }

        $windowStart = (int) ($bucket['window'] ?? time());
        $count = (int) ($bucket['count'] ?? 0);

        if (time() - $windowStart >= 3600) {
            $windowStart = time();
            $count = 0;
        }

        $_SESSION['site_assistant_rate'] = [
            'window' => $windowStart,
            'count' => $count + 1,
        ];
    }
}
