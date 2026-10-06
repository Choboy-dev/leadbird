<?php

declare(strict_types=1);

final class OpenAiUsageRepository extends BaseRepository
{
    public function insert(array $row): void
    {
        $this->execute(
            'INSERT INTO openai_usage_events (
                id, business_id, feature, model, prompt_tokens, completion_tokens, total_tokens, cost_usd, metadata
            ) VALUES (
                :id, :business_id, :feature, :model, :prompt_tokens, :completion_tokens, :total_tokens, :cost_usd, :metadata
            )',
            [
                'id' => $row['id'],
                'business_id' => $row['business_id'],
                'feature' => $row['feature'],
                'model' => $row['model'],
                'prompt_tokens' => (int) ($row['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($row['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($row['total_tokens'] ?? 0),
                'cost_usd' => $row['cost_usd'],
                'metadata' => isset($row['metadata']) ? json_encode($row['metadata'], JSON_THROW_ON_ERROR) : null,
            ]
        );
    }

    public function sumCostUsdSince(string $businessId, string $since): float
    {
        $row = $this->fetchOne(
            'SELECT COALESCE(SUM(cost_usd), 0) AS total
             FROM openai_usage_events
             WHERE business_id = :business_id AND created_at >= :since',
            ['business_id' => $businessId, 'since' => $since]
        );

        return (float) ($row['total'] ?? 0);
    }
}
