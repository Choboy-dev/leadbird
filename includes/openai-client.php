<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/repositories/OpenAiUsageRepository.php';
require_once dirname(__DIR__) . '/lib/OpenAiBudgetService.php';

/**
 * @return array{ok: bool, status: int, body?: array<string, mixed>, error?: string, budget_exceeded?: bool}
 */
function openai_chat_completion(array $payload, string $businessId, string $feature, float $reserveUsd = 0.002): array
{
    return openai_api_request('https://api.openai.com/v1/chat/completions', $payload, $businessId, $feature, $reserveUsd);
}

/**
 * @return array{ok: bool, status: int, body?: array<string, mixed>, error?: string, budget_exceeded?: bool}
 */
function openai_embedding_request(array $payload, string $businessId, string $feature, float $reserveUsd = 0.00005): array
{
    return openai_api_request('https://api.openai.com/v1/embeddings', $payload, $businessId, $feature, $reserveUsd);
}

/**
 * @return array{ok: bool, status: int, body?: array<string, mixed>, error?: string, budget_exceeded?: bool}
 */
function openai_api_request(
    string $endpoint,
    array $payload,
    string $businessId,
    string $feature,
    float $reserveUsd = 0.001
): array {
    $apiKey = app_config()['openai']['api_key'] ?? '';
    if ($apiKey === '') {
        return ['ok' => false, 'status' => 0, 'error' => 'OpenAI API key is not configured.'];
    }

    if ($businessId === '') {
        $businessId = OpenAiBudgetService::PLATFORM_BUSINESS_ID;
    }

    $budget = new OpenAiBudgetService();
    if (!$budget->canSpend($businessId, $reserveUsd)) {
        error_log('[OpenAI] Budget exceeded for business ' . $businessId . ' feature ' . $feature);
        return [
            'ok' => false,
            'status' => 429,
            'error' => $budget->blockedUserMessage($businessId),
            'budget_exceeded' => true,
        ];
    }

    $model = (string) ($payload['model'] ?? 'gpt-4o-mini');

    $ch = curl_init($endpoint);
    if ($ch === false) {
        return ['ok' => false, 'status' => 0, 'error' => 'Could not start OpenAI request.'];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if (!is_string($response)) {
        $message = $curlError !== '' ? $curlError : 'OpenAI request failed.';
        error_log('[OpenAI] ' . $feature . ': ' . $message);
        return ['ok' => false, 'status' => $status, 'error' => $message];
    }

    /** @var array<string, mixed>|null $decoded */
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'status' => $status, 'error' => 'Invalid OpenAI response.'];
    }

    if ($status < 200 || $status >= 300) {
        $apiMessage = trim((string) ($decoded['error']['message'] ?? ''));
        error_log('[OpenAI] ' . $feature . ' HTTP ' . $status . ': ' . ($apiMessage !== '' ? $apiMessage : substr($response, 0, 200)));
        return ['ok' => false, 'status' => $status, 'error' => $apiMessage !== '' ? $apiMessage : 'OpenAI API error.'];
    }

    $budget->recordFromResponse($businessId, $feature, $model, $decoded, ['endpoint' => $endpoint]);

    return ['ok' => true, 'status' => $status, 'body' => $decoded];
}
