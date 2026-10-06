<?php

declare(strict_types=1);

/**
 * Per-business OpenAI spend caps and usage logging.
 */
final class OpenAiBudgetService
{
    public const PLATFORM_BUSINESS_ID = '_platform';

    /** Published OpenAI API rates per 1M tokens (platform.openai.com/docs/pricing). */
    private const MODEL_RATES_PER_MILLION = [
        'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60, 'cached_input' => 0.075],
        'gpt-4o' => ['input' => 2.50, 'output' => 10.00, 'cached_input' => 1.25],
        'text-embedding-3-small' => ['input' => 0.02, 'output' => 0.0],
        'text-embedding-3-large' => ['input' => 0.13, 'output' => 0.0],
    ];

    public function monthlyLimitUsd(string $businessId): float
    {
        if ($businessId === self::PLATFORM_BUSINESS_ID) {
            return $this->platformMonthlyLimitUsd();
        }

        return $this->planBudgetForBusiness($businessId);
    }

    private function planBudgetForBusiness(string $businessId): float
    {
        $business = RepositoryFactory::businesses()->findById($businessId);
        if ($business === null) {
            return max(0.0, (float) (app_config()['openai']['monthly_budget_usd'] ?? 3.0));
        }

        $userId = trim((string) ($business['user_id'] ?? ''));
        $planSlug = $userId !== ''
            ? ((new OnboardingService())->getActivePlanSlug($userId) ?? 'starter')
            : 'starter';
        $tokens = PlanLimits::monthlyTokens($planSlug) ?? 8000;

        // Optimized reply cost (~$0.00043) plus headroom for embeddings and dashboard assistant.
        return max(0.5, round(($tokens * 0.00055) + 0.20, 2));
    }

    public function platformMonthlyLimitUsd(): float
    {
        $configured = app_config()['openai']['platform_monthly_budget_usd'] ?? 10.0;

        return max(0.0, (float) $configured);
    }

    public function monthlySpendUsd(string $businessId): float
    {
        return RepositoryFactory::openAiUsage()->sumCostUsdSince($businessId, $this->monthStartUtc());
    }

    public function remainingUsd(string $businessId): float
    {
        return max(0.0, $this->monthlyLimitUsd($businessId) - $this->monthlySpendUsd($businessId));
    }

    public function canSpend(string $businessId, float $estimatedUsd = 0.001): bool
    {
        $limit = $this->monthlyLimitUsd($businessId);
        if ($limit <= 0.0) {
            return false;
        }

        return ($this->monthlySpendUsd($businessId) + max(0.0, $estimatedUsd)) <= ($limit + 0.000001);
    }

    public function isExceeded(string $businessId): bool
    {
        return !$this->canSpend($businessId, 0.0);
    }

    /**
     * @param array<string, mixed> $responseBody
     * @param array<string, mixed> $metadata
     */
    public function recordFromResponse(
        string $businessId,
        string $feature,
        string $model,
        array $responseBody,
        array $metadata = []
    ): void {
        $usage = is_array($responseBody['usage'] ?? null) ? $responseBody['usage'] : [];
        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);
        $totalTokens = (int) ($usage['total_tokens'] ?? ($promptTokens + $completionTokens));
        $promptDetails = is_array($usage['prompt_tokens_details'] ?? null) ? $usage['prompt_tokens_details'] : [];
        $cachedPromptTokens = (int) ($promptDetails['cached_tokens'] ?? 0);
        $costUsd = self::estimateCostUsd($model, $promptTokens, $completionTokens, $cachedPromptTokens);

        RepositoryFactory::openAiUsage()->insert([
            'id' => Uuid::v4(),
            'business_id' => $businessId,
            'feature' => $feature,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'cost_usd' => number_format($costUsd, 6, '.', ''),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    public static function estimateCostUsd(
        string $model,
        int $promptTokens,
        int $completionTokens,
        int $cachedPromptTokens = 0
    ): float {
        $rates = self::MODEL_RATES_PER_MILLION[$model] ?? self::MODEL_RATES_PER_MILLION['gpt-4o-mini'];
        $cachedInput = (float) ($rates['cached_input'] ?? ($rates['input'] * 0.5));
        $cachedPromptTokens = max(0, min($promptTokens, $cachedPromptTokens));
        $billablePromptTokens = max(0, $promptTokens - $cachedPromptTokens);

        return (($billablePromptTokens / 1_000_000) * $rates['input'])
            + (($cachedPromptTokens / 1_000_000) * $cachedInput)
            + (($completionTokens / 1_000_000) * $rates['output']);
    }

    public function blockedUserMessage(string $businessId): string
    {
        $limit = number_format($this->monthlyLimitUsd($businessId), 2);
        $spent = number_format($this->monthlySpendUsd($businessId), 2);

        return 'This business reached its AI budget for this month ($' . $spent . ' of $' . $limit . '). '
            . 'AI replies and writing are paused until next month or you raise the limit.';
    }

    private function monthStartUtc(): string
    {
        return gmdate('Y-m-01 00:00:00');
    }
}
