<?php

declare(strict_types=1);

function onboarding_has_back_button(int $currentStep): bool
{
    return $currentStep > OnboardingService::STEP_CHANNELS;
}

function onboarding_back_url(int $currentStep): string
{
    $prev = max(OnboardingService::STEP_CHANNELS, $currentStep - 1);
    $meta = OnboardingService::STEPS[$prev] ?? OnboardingService::STEPS[OnboardingService::STEP_CHANNELS];

    return app_route(ltrim($meta['path'], '/'));
}

function onboarding_bool_from_post(string $key, bool $default = false): bool
{
    if (!isset($_POST[$key])) {
        return $default;
    }

    $value = $_POST[$key];

    return in_array($value, ['1', 'on', 'true', 'yes'], true);
}

function onboarding_is_edit_mode(?string $userId): bool
{
    if ($userId === null || $userId === '') {
        return false;
    }

    $ctx = new BusinessContext();
    if ($ctx->getOnboardingBusiness($userId) !== null) {
        return false;
    }

    return (new OnboardingService())->isComplete($userId);
}

/**
 * @return array{
 *     user_id: string,
 *     onboarding: OnboardingService,
 *     business: array<string, mixed>|null,
 *     dashboard: bool,
 *     app_url: string
 * }
 */
function onboarding_resolve_save_request(): array
{
    $userId = (string) (new SessionAuth())->userId();
    $onboarding = new OnboardingService();
    $appUrl = app_config()['app_url'];
    $root = dirname(__DIR__, 2);
    $ctx = new BusinessContext();

    if ((string) ($_POST['context'] ?? '') === 'dashboard') {
        require_once $root . '/includes/require_subscription.php';
        $business = $ctx->resolveActiveBusiness($userId);

        return [
            'user_id' => $userId,
            'onboarding' => $onboarding,
            'business' => $business,
            'dashboard' => true,
            'app_url' => $appUrl,
        ];
    }

    $inProgress = $ctx->getOnboardingBusiness($userId);
    if ($inProgress !== null) {
        require_once $root . '/includes/require_onboarding.php';
        $business = $onboarding->getBusinessForUser($userId);

        return [
            'user_id' => $userId,
            'onboarding' => $onboarding,
            'business' => $business,
            'dashboard' => false,
            'app_url' => $appUrl,
        ];
    }

    if ($onboarding->isComplete($userId)) {
        require_once $root . '/includes/require_subscription.php';
        $business = $onboarding->getBusinessForUser($userId);

        return [
            'user_id' => $userId,
            'onboarding' => $onboarding,
            'business' => $business,
            'dashboard' => true,
            'app_url' => $appUrl,
        ];
    }

    require_once $root . '/includes/require_onboarding.php';
    $business = $onboarding->getBusinessForUser($userId);

    return [
        'user_id' => $userId,
        'onboarding' => $onboarding,
        'business' => $business,
        'dashboard' => false,
        'app_url' => $appUrl,
    ];
}

/**
 * @return list<array{q: string, a: string}>
 */
function faq_parse(?string $text): array
{
    return array_map(
        static fn (array $entry): array => [
            'q' => (string) ($entry['q'] ?? ''),
            'a' => faq_entry_answer_text($entry),
        ],
        faq_entries_parse($text)
    );
}

/**
 * @return list<array<string, mixed>>
 */
function faq_entries_parse(?string $text): array
{
    if ($text === null || trim($text) === '') {
        return [];
    }

    $trim = trim($text);
    if (str_starts_with($trim, '[')) {
        $decoded = json_decode($trim, true);
        if (is_array($decoded)) {
            $entries = [];
            foreach ($decoded as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $normalized = faq_normalize_entry($entry);
                if ($normalized['q'] !== '') {
                    $entries[] = $normalized;
                }
            }

            return $entries;
        }
    }

    $entries = [];
    $blocks = preg_split('/\n(?=Q:\s)/', $trim) ?: [];
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '' || preg_match('/^Q:\s*(.+?)\nA:\s*(.+)$/s', $block, $matches) !== 1) {
            continue;
        }

        $entries[] = faq_normalize_entry([
            'q' => trim($matches[1]),
            'answer_type' => 'text',
            'payload' => ['text' => trim($matches[2])],
            'a' => trim($matches[2]),
        ]);
    }

    return $entries;
}

/**
 * @param list<array<string, mixed>> $entries
 */
function faq_entries_format(array $entries): ?string
{
    if ($entries === []) {
        return null;
    }

    $normalized = [];
    foreach ($entries as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $normalized[] = faq_normalize_entry($entry);
    }

    if ($normalized === []) {
        return null;
    }

    return json_encode($normalized, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
}

/**
 * @param array<string, mixed> $entry
 * @return array<string, mixed>
 */
function faq_normalize_entry(array $entry): array
{
    $question = trim((string) ($entry['q'] ?? ''));
    $answerType = trim((string) ($entry['answer_type'] ?? 'text'));
    $allowed = ['text', 'list', 'video', 'images', 'checklist', 'pdf', 'location'];
    if (!in_array($answerType, $allowed, true)) {
        $answerType = 'text';
    }

    $payload = is_array($entry['payload'] ?? null) ? $entry['payload'] : [];
    $id = trim((string) ($entry['id'] ?? ''));
    if ($id === '') {
        $id = 'faq_' . substr(str_replace('-', '', Uuid::v4()), 0, 12);
    }

    $normalized = [
        'id' => $id,
        'q' => $question,
        'answer_type' => $answerType,
        'payload' => $payload,
        'learned_at' => trim((string) ($entry['learned_at'] ?? '')),
    ];

    $answerText = trim((string) ($entry['a'] ?? ''));
    if ($answerText === '') {
        $answerText = faq_entry_answer_text($normalized);
    }
    $normalized['a'] = $answerText;

    return $normalized;
}

/**
 * @param array<string, mixed> $entry
 */
function faq_entry_answer_text(array $entry): string
{
    $answerType = (string) ($entry['answer_type'] ?? 'text');
    $payload = is_array($entry['payload'] ?? null) ? $entry['payload'] : [];
    $fallback = trim((string) ($entry['a'] ?? ''));

    return match ($answerType) {
        'list' => faq_format_list_answer($payload),
        'video' => faq_format_video_answer($payload),
        'images' => faq_format_images_answer($payload),
        'checklist' => faq_format_checklist_answer($payload),
        'pdf' => faq_format_pdf_answer($payload),
        'location' => faq_format_location_answer($payload),
        default => faq_format_text_answer($payload, $fallback),
    };
}

/**
 * @param array<string, mixed> $payload
 */
function faq_format_text_answer(array $payload, string $fallback = ''): string
{
    $html = trim((string) ($payload['html'] ?? ''));
    if ($html !== '') {
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    $text = trim((string) ($payload['text'] ?? ''));
    return $text !== '' ? $text : $fallback;
}

/**
 * @param array<string, mixed> $payload
 */
function faq_format_list_answer(array $payload): string
{
    $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
    $parts = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $title = trim((string) ($item['title'] ?? ''));
        $description = trim((string) ($item['description'] ?? ''));
        if ($title === '' && $description !== '') {
            $title = $description;
        }
        if ($title === '') {
            continue;
        }
        $parts[] = $title;
    }

    return implode('; ', $parts);
}

/**
 * @param array<string, mixed> $payload
 */
function faq_format_video_answer(array $payload): string
{
    $platform = trim((string) ($payload['platform'] ?? ''));
    $url = trim((string) ($payload['url'] ?? ''));

    if ($url === '') {
        return '';
    }

    return ($platform !== '' ? ucfirst($platform) . ' video: ' : 'Video: ') . $url;
}

/**
 * @param array<string, mixed> $payload
 */
function faq_format_images_answer(array $payload): string
{
    $images = is_array($payload['images'] ?? null) ? $payload['images'] : [];
    $names = [];
    foreach ($images as $image) {
        if (!is_array($image)) {
            continue;
        }
        $name = trim((string) ($image['name'] ?? $image['url'] ?? ''));
        if ($name !== '') {
            $names[] = $name;
        }
    }

    return $names === [] ? 'Image answer' : 'Images: ' . implode(', ', $names);
}

/**
 * @param array<string, mixed> $payload
 */
function faq_format_checklist_answer(array $payload): string
{
    $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
    $options = [];
    foreach ($items as $item) {
        $option = trim(is_string($item) ? $item : (string) ($item['label'] ?? ''));
        if ($option !== '') {
            $options[] = $option;
        }
    }

    return $options === [] ? '' : implode(', ', $options);
}

/**
 * @param array<string, mixed> $payload
 */
function faq_format_pdf_answer(array $payload): string
{
    $name = trim((string) ($payload['file_name'] ?? ''));
    $url = trim((string) ($payload['url'] ?? ''));

    if ($name !== '') {
        return 'PDF: ' . $name;
    }

    return $url !== '' ? 'PDF: ' . $url : 'PDF answer';
}

/**
 * @param array<string, mixed> $payload
 */
function faq_format_location_answer(array $payload): string
{
    $address = trim((string) ($payload['address'] ?? ''));
    $mapType = trim((string) ($payload['map_type'] ?? ''));

    if ($address === '') {
        return '';
    }

    return $mapType !== '' ? $address . ' (' . $mapType . ' map)' : $address;
}

/**
 * @param list<array{q: string, a: string}> $entries
 */
function faq_format(array $entries): ?string
{
    if ($entries === []) {
        return null;
    }

    $rich = [];
    foreach ($entries as $entry) {
        $rich[] = faq_normalize_entry([
            'q' => (string) ($entry['q'] ?? ''),
            'answer_type' => 'text',
            'payload' => ['text' => (string) ($entry['a'] ?? '')],
            'a' => (string) ($entry['a'] ?? ''),
        ]);
    }

    return faq_entries_format($rich);
}
