<?php

declare(strict_types=1);

require_once __DIR__ . '/VanceVoice.php';
require_once dirname(__DIR__) . '/includes/openai-client.php';

/**
 * AI caption and hashtag generation for social posts.
 */
final class SocialMediaAiService
{
    /**
     * @return array{ok: bool, caption?: string, error?: string}
     */
    public function generateCaption(string $businessId, string $prompt, ?string $tone = null): array
    {
        $business = RepositoryFactory::businesses()->findById($businessId);
        $userId = trim((string) ($business['user_id'] ?? ''));
        $tokenService = new TokenService();
        $cost = TokenCosts::SOCIAL_CAPTION;
        if ($userId === '' || !$tokenService->canSpend($userId, $cost)) {
            return ['ok' => false, 'error' => TokenCosts::insufficientMessage($cost), 'tokens_depleted' => true];
        }

        $settings = RepositoryFactory::businessSettings()->findByBusinessId($businessId);
        $businessName = (string) ($business['business_name'] ?? 'Business');
        $description = trim((string) ($settings['business_description'] ?? ''));
        $tone = trim((string) ($tone ?? ($settings['ai_tone'] ?? '')));

        $system = implode("\n", [
            VanceVoice::socialMediaCaptionRulesBlock(),
            '',
            'Business name: ' . $businessName,
            $description !== '' ? 'About the business: ' . $description : '',
            $tone !== '' ? 'Tone hint (keep wording simple): ' . $tone : '',
            '',
            'Return ONLY the caption. No quotes. No labels.',
        ]);

        $user = 'Write a social media caption for this post idea: ' . $prompt;

        $completion = $this->complete($system, $user, 0.55, $businessId, 'social_caption');
        if (!($completion['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($completion['error'] ?? 'Could not generate caption right now.')];
        }
        $content = (string) ($completion['content'] ?? '');

        $content = VanceVoice::sanitizeSocialCaption($content);
        if ($content === '' || VanceVoice::socialCaptionFailsVoiceCheck($content)) {
            return ['ok' => false, 'error' => 'Could not write a natural caption. Try adding more detail about your offer or price.'];
        }

        $tokenService->spend(
            $userId,
            $cost,
            'social_caption',
            'social_ai',
            Uuid::v4(),
            ['business_id' => $businessId]
        );

        return ['ok' => true, 'caption' => $content];
    }

    /**
     * @return array{ok: bool, hashtags?: string, error?: string}
     */
    public function generateHashtags(string $businessId, string $caption, int $count = 8): array
    {
        $business = RepositoryFactory::businesses()->findById($businessId);
        $userId = trim((string) ($business['user_id'] ?? ''));
        $tokenService = new TokenService();
        $cost = TokenCosts::SOCIAL_HASHTAGS;
        if ($userId === '' || !$tokenService->canSpend($userId, $cost)) {
            return ['ok' => false, 'error' => TokenCosts::insufficientMessage($cost), 'tokens_depleted' => true];
        }

        $businessName = (string) ($business['business_name'] ?? 'Business');

        $system = implode("\n", [
            'You suggest Instagram/Facebook hashtags for small businesses in Nigeria.',
            'Return one line of hashtags separated by spaces. Each starts with #.',
            'Use plain, specific tags. No spammy generic tags like #marketing #business #success.',
            'No markdown. No explanation. Only hashtags.',
            'Business: ' . $businessName,
        ]);

        $user = 'Suggest ' . max(3, min(15, $count)) . ' hashtags for this caption:' . "\n" . $caption;

        $completion = $this->complete($system, $user, 0.5, $businessId, 'social_hashtags');
        if (!($completion['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($completion['error'] ?? 'Could not generate hashtags right now.')];
        }
        $content = (string) ($completion['content'] ?? '');

        $content = trim(preg_replace('/[\r\n]+/', ' ', $content) ?? $content);

        $tokenService->spend(
            $userId,
            $cost,
            'social_hashtags',
            'social_ai',
            Uuid::v4(),
            ['business_id' => $businessId]
        );

        return ['ok' => true, 'hashtags' => $content];
    }

    /**
     * @return array{ok: bool, content?: string, error?: string}
     */
    private function complete(string $system, string $user, float $temperature, string $businessId, string $feature): array
    {
        $apiKey = app_config()['openai']['api_key'] ?? '';
        $model = app_config()['openai']['model'] ?? 'gpt-4o-mini';

        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'AI writing is not configured yet. Add a valid OpenAI API key first.'];
        }

        if ((new OpenAiBudgetService())->isExceeded($businessId)) {
            return [
                'ok' => false,
                'error' => (new OpenAiBudgetService())->blockedUserMessage($businessId),
            ];
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => $temperature,
            'max_tokens' => 220,
        ];

        $api = openai_chat_completion($payload, $businessId, $feature, 0.001);
        if (!($api['ok'] ?? false)) {
            $message = (string) ($api['error'] ?? 'Could not reach the AI service right now.');
            if (!empty($api['budget_exceeded'])) {
                return ['ok' => false, 'error' => $message];
            }

            error_log('[SocialMediaAiService] ' . $message);
            if (($api['status'] ?? 0) === 401) {
                return ['ok' => false, 'error' => 'AI writing is temporarily unavailable. The OpenAI API key needs to be updated.'];
            }

            return ['ok' => false, 'error' => 'Could not reach the AI service right now.'];
        }

        $decoded = $api['body'] ?? [];
        $content = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));

        if ($content === '') {
            return ['ok' => false, 'error' => 'The AI service returned an empty response.'];
        }

        return ['ok' => true, 'content' => $content];
    }
}
