<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/onboarding/helpers.php';
require_once __DIR__ . '/../includes/dashboard/helpers.php';

/**
 * Server-side actions for dashboard Hannah (tools).
 */
final class DashboardAssistantActions
{
    private string $userId;
    private string $businessId;
    private OnboardingService $onboarding;
    private string $appUrl;

    public function __construct(string $userId, string $businessId)
    {
        $this->userId = $userId;
        $this->businessId = $businessId;
        $this->onboarding = new OnboardingService();
        $this->appUrl = app_config()['app_url'];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function execute(string $toolName, array $args): array
    {
        try {
            return match ($toolName) {
                'get_dashboard_summary' => $this->getDashboardSummary(),
                'list_offerings' => $this->listOfferings(),
                'add_offering' => $this->addOffering($args),
                'update_offering' => $this->updateOffering($args),
                'delete_offering' => $this->deleteOffering($args),
                'list_faqs' => $this->listFaqs(),
                'add_faq' => $this->addFaq($args),
                'update_faq' => $this->updateFaq($args),
                'delete_faq' => $this->deleteFaq($args),
                'get_policies' => $this->getPolicies(),
                'save_policies' => $this->savePolicies($args),
                'get_payment_instructions' => $this->getPaymentInstructions(),
                'save_payment_instructions' => $this->savePaymentInstructions($args),
                'get_business_profile' => $this->getBusinessProfile(),
                'update_business_profile' => $this->updateBusinessProfile($args),
                'get_ai_salesgirl_profile' => $this->getAiSalesgirlProfile(),
                'update_ai_salesgirl_profile' => $this->updateAiSalesgirlProfile($args),
                'get_owner_alerts' => $this->getOwnerAlerts(),
                'save_owner_alerts' => $this->saveOwnerAlerts($args),
                'list_connected_accounts' => $this->listConnectedAccounts(),
                'list_businesses' => $this->listBusinesses(),
                'switch_active_business' => $this->switchActiveBusiness($args),
                'sync_knowledge' => $this->syncKnowledge(),
                'get_dashboard_link' => $this->getDashboardLink($args),
                default => ['ok' => false, 'error' => 'Unknown action: ' . $toolName],
            };
        } catch (Throwable $e) {
            error_log('[DashboardAssistantActions] ' . $toolName . ': ' . $e->getMessage());

            return ['ok' => false, 'error' => 'Something went wrong. Please try again.'];
        }
    }

    /** @return array<string, mixed> */
    private function getDashboardSummary(): array
    {
        $business = RepositoryFactory::businesses()->findById($this->businessId);
        if ($business === null) {
            return ['ok' => false, 'error' => 'Business not found.'];
        }

        $subscription = (new SubscriptionService())->getActiveSubscription($this->userId);
        $planSlug = $subscription !== null ? (string) $subscription['plan_slug'] : 'starter';
        $plan = PlanService::find($planSlug);
        $stats = (new DashboardService())->getOverviewStats($this->businessId);
        $tokenUsage = (new TokenService())->getUsageSummary($this->userId);
        $channels = RepositoryFactory::connectedAccounts()->listByBusinessId($this->businessId, true);
        $offerings = RepositoryFactory::offerings()->listByBusinessId($this->businessId);
        $settings = $this->onboarding->ensureSettings($this->businessId);
        $faqs = faq_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);

        return [
            'ok' => true,
            'business_name' => (string) ($business['business_name'] ?? ''),
            'plan' => (string) ($plan['name'] ?? ucfirst($planSlug)),
            'plan_slug' => $planSlug,
            'products_count' => count($offerings),
            'faqs_count' => count($faqs),
            'connected_channels' => array_map(static function (array $row): array {
                return [
                    'platform' => (string) ($row['platform'] ?? ''),
                    'account_id' => (string) ($row['platform_account_id'] ?? ''),
                ];
            }, $channels),
            'stats' => $stats,
            'token_balance' => $tokenUsage['balance'] ?? 0,
            'tokens_unlimited' => (bool) ($tokenUsage['unlimited'] ?? false),
            'has_payment_instructions' => trim((string) ($settings['payment_instructions'] ?? '')) !== '',
            'has_delivery_policy' => trim((string) ($settings['delivery_policy'] ?? '')) !== '',
            'has_return_policy' => trim((string) ($settings['return_policy'] ?? '')) !== '',
        ];
    }

    /** @return array<string, mixed> */
    private function listOfferings(): array
    {
        $rows = RepositoryFactory::offerings()->listByBusinessId($this->businessId);

        return [
            'ok' => true,
            'offerings' => array_map(static function (array $row): array {
                return [
                    'id' => (string) $row['id'],
                    'name' => (string) ($row['name'] ?? ''),
                    'type' => (string) ($row['offering_type'] ?? 'product'),
                    'description' => (string) ($row['description'] ?? ''),
                    'price' => $row['price'] !== null ? (float) $row['price'] : null,
                    'billing_type' => (string) ($row['billing_type'] ?? 'one_time'),
                    'active' => (bool) ($row['active'] ?? true),
                ];
            }, $rows),
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function addOffering(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '') {
            return ['ok' => false, 'error' => 'Product name is required.'];
        }

        $type = (string) ($args['offering_type'] ?? 'product');
        if (!in_array($type, ['product', 'service', 'membership'], true)) {
            $type = 'product';
        }

        $billingType = (string) ($args['billing_type'] ?? 'one_time');
        if (!in_array($billingType, ['one_time', 'monthly', 'per_session'], true)) {
            $billingType = 'one_time';
        }

        $priceRaw = $args['price'] ?? null;
        $price = $priceRaw !== null && $priceRaw !== '' && is_numeric($priceRaw) ? (float) $priceRaw : null;

        $offering = RepositoryFactory::offerings()->create($this->businessId, [
            'offering_type' => $type,
            'name' => $name,
            'description' => trim((string) ($args['description'] ?? '')) ?: null,
            'image_url' => null,
            'price' => $price,
            'billing_type' => $billingType,
        ]);

        (new BusinessKnowledgeSyncService())->syncManualSources($this->businessId);

        return [
            'ok' => true,
            'message' => 'Product added.',
            'offering' => [
                'id' => (string) $offering['id'],
                'name' => (string) $offering['name'],
                'price' => $offering['price'] !== null ? (float) $offering['price'] : null,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function updateOffering(array $args): array
    {
        $offeringId = trim((string) ($args['offering_id'] ?? ''));
        $offering = RepositoryFactory::offerings()->findById($offeringId);
        if ($offering === null || (string) $offering['business_id'] !== $this->businessId) {
            return ['ok' => false, 'error' => 'Product not found.'];
        }

        $name = array_key_exists('name', $args) ? trim((string) $args['name']) : (string) $offering['name'];
        if ($name === '') {
            return ['ok' => false, 'error' => 'Product name cannot be empty.'];
        }

        $type = (string) ($args['offering_type'] ?? $offering['offering_type'] ?? 'product');
        if (!in_array($type, ['product', 'service', 'membership'], true)) {
            $type = (string) ($offering['offering_type'] ?? 'product');
        }

        $billingType = (string) ($args['billing_type'] ?? $offering['billing_type'] ?? 'one_time');
        if (!in_array($billingType, ['one_time', 'monthly', 'per_session'], true)) {
            $billingType = (string) ($offering['billing_type'] ?? 'one_time');
        }

        $price = $offering['price'];
        if (array_key_exists('price', $args)) {
            $priceRaw = $args['price'];
            $price = $priceRaw !== null && $priceRaw !== '' && is_numeric($priceRaw) ? (float) $priceRaw : null;
        }

        $description = array_key_exists('description', $args)
            ? (trim((string) $args['description']) ?: null)
            : ($offering['description'] ?? null);

        RepositoryFactory::offerings()->update($offeringId, [
            'offering_type' => $type,
            'name' => $name,
            'description' => $description,
            'image_url' => $offering['image_url'] ?? null,
            'price' => $price,
            'billing_type' => $billingType,
            'active' => array_key_exists('active', $args) ? (bool) $args['active'] : (bool) ($offering['active'] ?? true),
        ]);

        (new BusinessKnowledgeSyncService())->syncManualSources($this->businessId);

        return ['ok' => true, 'message' => 'Product updated.', 'offering_id' => $offeringId];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function deleteOffering(array $args): array
    {
        $offeringId = trim((string) ($args['offering_id'] ?? ''));
        $offering = RepositoryFactory::offerings()->findById($offeringId);
        if ($offering === null || (string) $offering['business_id'] !== $this->businessId) {
            return ['ok' => false, 'error' => 'Product not found.'];
        }

        $imageService = new OfferingImageService();
        $imageService->deleteStoredPath((string) ($offering['image_url'] ?? ''));
        RepositoryFactory::offerings()->delete($offeringId);
        (new BusinessKnowledgeSyncService())->syncManualSources($this->businessId);

        return ['ok' => true, 'message' => 'Product removed.', 'name' => (string) ($offering['name'] ?? '')];
    }

    /** @return array<string, mixed> */
    private function listFaqs(): array
    {
        $settings = $this->onboarding->ensureSettings($this->businessId);
        $entries = faq_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);

        return [
            'ok' => true,
            'faqs' => array_map(static function (array $entry, int $index): array {
                return [
                    'index' => $index,
                    'question' => $entry['q'],
                    'answer' => $entry['a'],
                ];
            }, $entries, array_keys($entries)),
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function addFaq(array $args): array
    {
        $question = trim((string) ($args['question'] ?? ''));
        $answer = trim((string) ($args['answer'] ?? ''));
        if ($question === '' || $answer === '') {
            return ['ok' => false, 'error' => 'Both question and answer are required.'];
        }

        $settings = $this->onboarding->ensureSettings($this->businessId);
        $entries = faq_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);
        $entries[] = ['q' => $question, 'a' => $answer];

        $this->saveSettingsMerge($settings, ['faq' => faq_format($entries)]);
        (new BusinessKnowledgeSyncService())->syncManualSources($this->businessId);

        return ['ok' => true, 'message' => 'FAQ added.', 'index' => count($entries) - 1];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function updateFaq(array $args): array
    {
        $index = (int) ($args['index'] ?? -1);
        $question = trim((string) ($args['question'] ?? ''));
        $answer = trim((string) ($args['answer'] ?? ''));

        $settings = $this->onboarding->ensureSettings($this->businessId);
        $entries = faq_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);

        if ($index < 0 || $index >= count($entries)) {
            return ['ok' => false, 'error' => 'FAQ not found. Use list_faqs to get the index.'];
        }

        if ($question === '' || $answer === '') {
            return ['ok' => false, 'error' => 'Both question and answer are required.'];
        }

        $entries[$index] = ['q' => $question, 'a' => $answer];
        $this->saveSettingsMerge($settings, ['faq' => faq_format($entries)]);
        (new BusinessKnowledgeSyncService())->syncManualSources($this->businessId);

        return ['ok' => true, 'message' => 'FAQ updated.', 'index' => $index];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function deleteFaq(array $args): array
    {
        $index = (int) ($args['index'] ?? -1);
        $settings = $this->onboarding->ensureSettings($this->businessId);
        $entries = faq_parse(isset($settings['faq']) ? (string) $settings['faq'] : null);

        if ($index < 0 || $index >= count($entries)) {
            return ['ok' => false, 'error' => 'FAQ not found.'];
        }

        $removed = $entries[$index]['q'] ?? '';
        array_splice($entries, $index, 1);
        $this->saveSettingsMerge($settings, ['faq' => faq_format($entries)]);
        (new BusinessKnowledgeSyncService())->syncManualSources($this->businessId);

        return ['ok' => true, 'message' => 'FAQ removed.', 'question' => $removed];
    }

    /** @return array<string, mixed> */
    private function getPolicies(): array
    {
        $settings = $this->onboarding->ensureSettings($this->businessId);

        return [
            'ok' => true,
            'delivery_policy' => (string) ($settings['delivery_policy'] ?? ''),
            'return_policy' => (string) ($settings['return_policy'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function savePolicies(array $args): array
    {
        $settings = $this->onboarding->ensureSettings($this->businessId);
        $policiesContext = $this->onboarding->getPoliciesContextForUser($this->userId);

        $delivery = array_key_exists('delivery_policy', $args)
            ? trim((string) $args['delivery_policy'])
            : (string) ($settings['delivery_policy'] ?? '');
        $returns = array_key_exists('return_policy', $args)
            ? trim((string) $args['return_policy'])
            : (string) ($settings['return_policy'] ?? '');

        $payload = $this->policiesSettingsPayload(
            $settings,
            isset($settings['faq']) ? ((string) $settings['faq'] !== '' ? (string) $settings['faq'] : null) : null,
            $policiesContext,
            $delivery,
            $returns
        );

        RepositoryFactory::businessSettings()->update($this->businessId, $payload);
        (new BusinessKnowledgeSyncService())->syncManualSources($this->businessId);

        return ['ok' => true, 'message' => 'Policies saved.'];
    }

    /** @return array<string, mixed> */
    private function getPaymentInstructions(): array
    {
        $settings = $this->onboarding->ensureSettings($this->businessId);

        return [
            'ok' => true,
            'payment_instructions' => (string) ($settings['payment_instructions'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function savePaymentInstructions(array $args): array
    {
        $instructions = trim((string) ($args['payment_instructions'] ?? ''));
        if ($instructions === '') {
            return ['ok' => false, 'error' => 'Payment instructions cannot be empty.'];
        }

        $settings = $this->onboarding->ensureSettings($this->businessId);
        $this->saveSettingsMerge($settings, ['payment_instructions' => $instructions]);
        (new BusinessKnowledgeSyncService())->syncManualSources($this->businessId);

        return ['ok' => true, 'message' => 'Payment instructions saved.'];
    }

    /** @return array<string, mixed> */
    private function getBusinessProfile(): array
    {
        $business = RepositoryFactory::businesses()->findById($this->businessId);
        $settings = $this->onboarding->ensureSettings($this->businessId);

        if ($business === null) {
            return ['ok' => false, 'error' => 'Business not found.'];
        }

        return [
            'ok' => true,
            'business_name' => (string) ($business['business_name'] ?? ''),
            'owner_name' => (string) ($business['owner_name'] ?? ''),
            'owner_email' => (string) ($business['owner_email'] ?? ''),
            'owner_phone' => (string) ($business['owner_phone'] ?? ''),
            'business_description' => (string) ($settings['business_description'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function updateBusinessProfile(array $args): array
    {
        $business = RepositoryFactory::businesses()->findById($this->businessId);
        if ($business === null) {
            return ['ok' => false, 'error' => 'Business not found.'];
        }

        $businessName = array_key_exists('business_name', $args)
            ? trim((string) $args['business_name'])
            : (string) ($business['business_name'] ?? '');
        if ($businessName === '') {
            return ['ok' => false, 'error' => 'Business name is required.'];
        }

        RepositoryFactory::businesses()->update($this->businessId, [
            'business_name' => $businessName,
            'owner_name' => array_key_exists('owner_name', $args)
                ? (trim((string) $args['owner_name']) ?: null)
                : ($business['owner_name'] ?? null),
            'owner_email' => array_key_exists('owner_email', $args)
                ? (trim((string) $args['owner_email']) ?: null)
                : ($business['owner_email'] ?? null),
            'owner_phone' => array_key_exists('owner_phone', $args)
                ? (trim((string) $args['owner_phone']) ?: null)
                : ($business['owner_phone'] ?? null),
        ]);

        $settings = $this->onboarding->ensureSettings($this->businessId);
        if (array_key_exists('business_description', $args)) {
            $this->saveSettingsMerge($settings, [
                'business_description' => trim((string) $args['business_description']) ?: null,
            ]);
        }

        return ['ok' => true, 'message' => 'Business profile updated.', 'business_name' => $businessName];
    }

    /** @return array<string, mixed> */
    private function getAiSalesgirlProfile(): array
    {
        $business = RepositoryFactory::businesses()->findById($this->businessId);
        $settings = $this->onboarding->ensureSettings($this->businessId);

        return [
            'ok' => true,
            'ai_name' => (string) ($settings['ai_name'] ?? 'Sales Girl'),
            'ai_tone' => (string) ($settings['ai_tone'] ?? ''),
            'ai_greeting' => (string) ($settings['ai_greeting'] ?? ''),
            'business_name' => (string) ($business['business_name'] ?? ''),
            'allowed_tones' => dash_ai_tone_options((string) ($settings['ai_tone'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function updateAiSalesgirlProfile(array $args): array
    {
        $business = RepositoryFactory::businesses()->findById($this->businessId);
        if ($business === null) {
            return ['ok' => false, 'error' => 'Business not found.'];
        }

        $settings = $this->onboarding->ensureSettings($this->businessId);
        $allowedTones = dash_ai_tone_options((string) ($settings['ai_tone'] ?? ''));

        $aiName = array_key_exists('ai_name', $args)
            ? trim((string) $args['ai_name'])
            : (string) ($settings['ai_name'] ?? '');
        $businessName = array_key_exists('business_name', $args)
            ? trim((string) $args['business_name'])
            : (string) ($business['business_name'] ?? '');
        $aiTone = array_key_exists('ai_tone', $args)
            ? trim((string) $args['ai_tone'])
            : (string) ($settings['ai_tone'] ?? '');
        $aiGreeting = array_key_exists('ai_greeting', $args)
            ? trim((string) $args['ai_greeting'])
            : (string) ($settings['ai_greeting'] ?? '');

        if ($aiName === '') {
            return ['ok' => false, 'error' => 'AI name is required.'];
        }
        if ($businessName === '') {
            return ['ok' => false, 'error' => 'Business name is required.'];
        }
        if ($aiTone === '' || !in_array($aiTone, $allowedTones, true)) {
            return ['ok' => false, 'error' => 'Choose a valid tone: ' . implode(', ', $allowedTones)];
        }
        if ($aiGreeting === '') {
            return ['ok' => false, 'error' => 'Greeting message is required.'];
        }

        RepositoryFactory::businesses()->update($this->businessId, [
            'business_name' => $businessName,
            'owner_name' => $business['owner_name'] ?? null,
            'owner_email' => $business['owner_email'] ?? null,
            'owner_phone' => $business['owner_phone'] ?? null,
        ]);

        $this->saveSettingsMerge($settings, [
            'ai_name' => $aiName,
            'ai_tone' => $aiTone,
            'ai_greeting' => $aiGreeting,
        ]);

        return [
            'ok' => true,
            'message' => 'AI Salesgirl profile updated.',
            'ai_name' => $aiName,
            'ai_tone' => $aiTone,
        ];
    }

    /** @return array<string, mixed> */
    private function getOwnerAlerts(): array
    {
        $settings = $this->onboarding->ensureSettings($this->businessId);

        return [
            'ok' => true,
            'escalation_phone' => (string) ($settings['escalation_phone'] ?? ''),
            'escalation_email' => (string) ($settings['escalation_email'] ?? ''),
            'handoff_to_whatsapp' => (bool) ($settings['handoff_to_whatsapp'] ?? true),
            'notify_owner_on_hot_lead' => (bool) ($settings['notify_owner_on_hot_lead'] ?? true),
            'notify_owner_on_payment' => (bool) ($settings['notify_owner_on_payment'] ?? true),
            'notify_owner_on_complaint' => (bool) ($settings['notify_owner_on_complaint'] ?? true),
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function saveOwnerAlerts(array $args): array
    {
        $settings = $this->onboarding->ensureSettings($this->businessId);
        $merge = [];

        if (array_key_exists('escalation_phone', $args)) {
            $merge['escalation_phone'] = trim((string) $args['escalation_phone']) ?: null;
        }
        if (array_key_exists('escalation_email', $args)) {
            $merge['escalation_email'] = trim((string) $args['escalation_email']) ?: null;
        }
        if (array_key_exists('handoff_to_whatsapp', $args)) {
            $merge['handoff_to_whatsapp'] = (bool) $args['handoff_to_whatsapp'];
        }
        if (array_key_exists('notify_owner_on_hot_lead', $args)) {
            $merge['notify_owner_on_hot_lead'] = (bool) $args['notify_owner_on_hot_lead'];
        }
        if (array_key_exists('notify_owner_on_payment', $args)) {
            $merge['notify_owner_on_payment'] = (bool) $args['notify_owner_on_payment'];
        }
        if (array_key_exists('notify_owner_on_complaint', $args)) {
            $merge['notify_owner_on_complaint'] = (bool) $args['notify_owner_on_complaint'];
        }

        if ($merge === []) {
            return ['ok' => false, 'error' => 'No alert settings provided.'];
        }

        $this->saveSettingsMerge($settings, $merge);

        return ['ok' => true, 'message' => 'Alert settings saved.'];
    }

    /** @return array<string, mixed> */
    private function listConnectedAccounts(): array
    {
        $rows = RepositoryFactory::connectedAccounts()->listByBusinessId($this->businessId, true);

        return [
            'ok' => true,
            'accounts' => array_map(static function (array $row): array {
                return [
                    'platform' => (string) ($row['platform'] ?? ''),
                    'account_id' => (string) ($row['platform_account_id'] ?? ''),
                    'connected_at' => (string) ($row['created_at'] ?? ''),
                ];
            }, $rows),
            'connect_url' => app_route('connected-accounts'),
        ];
    }

    /** @return array<string, mixed> */
    private function listBusinesses(): array
    {
        $ctx = new BusinessContext();
        $rows = $ctx->listForUser($this->userId);

        return [
            'ok' => true,
            'active_business_id' => $this->businessId,
            'businesses' => array_map(static function (array $row): array {
                return [
                    'id' => (string) $row['id'],
                    'name' => (string) ($row['business_name'] ?? ''),
                    'onboarding_complete' => !empty($row['onboarding_completed_at']),
                ];
            }, $rows),
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function switchActiveBusiness(array $args): array
    {
        $businessId = trim((string) ($args['business_id'] ?? ''));
        $ctx = new BusinessContext();

        if ($businessId === '' || !$ctx->userOwnsBusiness($this->userId, $businessId)) {
            return ['ok' => false, 'error' => 'Business not found.'];
        }

        $business = RepositoryFactory::businesses()->findById($businessId);
        if ($business === null || !(new BusinessContext())->isBusinessComplete($business)) {
            return ['ok' => false, 'error' => 'That business is not finished with setup yet.'];
        }

        $ctx->setActiveBusinessId($businessId);
        $this->businessId = $businessId;

        return [
            'ok' => true,
            'message' => 'Switched to ' . (string) ($business['business_name'] ?? 'business') . '.',
            'business_id' => $businessId,
            'business_name' => (string) ($business['business_name'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    private function syncKnowledge(): array
    {
        $sync = new BusinessKnowledgeSyncService();
        $result = $sync->syncAll($this->businessId);
        $stats = (new BusinessKnowledgeStatsService())->getDashboardStats($this->businessId);

        return [
            'ok' => true,
            'message' => 'Knowledge synced.',
            'total_chunks' => $stats['total_chunks'],
            'offerings' => $stats['offerings'],
            'faqs' => $stats['faqs'],
            'sync' => $result,
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function getDashboardLink(array $args): array
    {
        $page = trim((string) ($args['page'] ?? ''));
        $links = [
            'overview' => app_route('dashboard'),
            'inbox' => app_route('inbox'),
            'customers' => app_route('customers'),
            'offers' => app_route('offers'),
            'catalog' => app_route('offers'),
            'faqs' => app_route('offers', ['tab' => 'faqs']),
            'policies' => app_route('offers', ['tab' => 'policies']),
            'payment' => app_route('offers', ['tab' => 'payment']),
            'ai_salesgirl' => app_route('ai-salesgirl'),
            'connected_accounts' => app_route('connected-accounts'),
            'billing' => app_route('billing'),
            'settings' => app_route('settings'),
            'businesses' => app_route('businesses'),
            'help' => app_route('help'),
        ];

        if (!isset($links[$page])) {
            return ['ok' => false, 'error' => 'Unknown page. Valid: ' . implode(', ', array_keys($links))];
        }

        return [
            'ok' => true,
            'page' => $page,
            'url' => $links[$page],
        ];
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $changes
     */
    private function saveSettingsMerge(array $settings, array $changes): void
    {
        RepositoryFactory::businessSettings()->update($this->businessId, array_merge([
            'ai_name' => (string) ($settings['ai_name'] ?? 'Sales Girl'),
            'ai_tone' => $settings['ai_tone'] ?? null,
            'ai_greeting' => $settings['ai_greeting'] ?? null,
            'business_description' => $settings['business_description'] ?? null,
            'faq' => $settings['faq'] ?? null,
            'delivery_policy' => $settings['delivery_policy'] ?? null,
            'return_policy' => $settings['return_policy'] ?? null,
            'payment_instructions' => $settings['payment_instructions'] ?? null,
            'escalation_phone' => $settings['escalation_phone'] ?? null,
            'escalation_email' => $settings['escalation_email'] ?? null,
            'handoff_to_whatsapp' => (bool) ($settings['handoff_to_whatsapp'] ?? true),
            'notify_owner_on_hot_lead' => (bool) ($settings['notify_owner_on_hot_lead'] ?? true),
            'notify_owner_on_payment' => (bool) ($settings['notify_owner_on_payment'] ?? true),
            'notify_owner_on_complaint' => (bool) ($settings['notify_owner_on_complaint'] ?? true),
        ], $changes));
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $policiesContext
     * @return array<string, mixed>
     */
    private function policiesSettingsPayload(
        array $settings,
        ?string $faq,
        array $policiesContext,
        string $deliveryPolicy,
        string $returnPolicy
    ): array {
        return [
            'ai_name' => (string) ($settings['ai_name'] ?? 'Sales Girl'),
            'ai_tone' => $settings['ai_tone'] ?? null,
            'ai_greeting' => $settings['ai_greeting'] ?? null,
            'business_description' => $settings['business_description'] ?? null,
            'faq' => $faq,
            'delivery_policy' => ($policiesContext['show_delivery'] ?? true)
                ? ($deliveryPolicy !== '' ? $deliveryPolicy : null)
                : null,
            'return_policy' => $returnPolicy !== '' ? $returnPolicy : null,
            'payment_instructions' => $settings['payment_instructions'] ?? null,
            'escalation_phone' => $settings['escalation_phone'] ?? null,
            'escalation_email' => $settings['escalation_email'] ?? null,
            'handoff_to_whatsapp' => (bool) ($settings['handoff_to_whatsapp'] ?? true),
            'notify_owner_on_hot_lead' => (bool) ($settings['notify_owner_on_hot_lead'] ?? true),
            'notify_owner_on_payment' => (bool) ($settings['notify_owner_on_payment'] ?? true),
            'notify_owner_on_complaint' => (bool) ($settings['notify_owner_on_complaint'] ?? true),
        ];
    }
}
