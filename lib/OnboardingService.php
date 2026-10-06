<?php

declare(strict_types=1);

/**
 * Onboarding wizard progress and step routing (connect-first, per business).
 */
final class OnboardingService
{
    public const STEP_CHANNELS = 1;
    public const STEP_PROFILE = 2;
    public const STEP_OFFERINGS = 3;
    public const STEP_POLICIES = 4;
    public const STEP_PAYMENT = 5;
    public const STEP_ALERTS = 6;
    public const STEP_COMPLETE = 7;

    /** @var array<int, array{slug: string, label: string, path: string}> */
    public const STEPS = [
        self::STEP_CHANNELS => ['slug' => 'channels', 'label' => 'Connect Channels', 'path' => '/onboarding/channels'],
        self::STEP_PROFILE => ['slug' => 'profile', 'label' => 'Business Profile', 'path' => '/onboarding/profile'],
        self::STEP_OFFERINGS => ['slug' => 'offerings', 'label' => 'Offers & Services', 'path' => '/onboarding/offerings'],
        self::STEP_POLICIES => ['slug' => 'policies', 'label' => 'FAQs & Policies', 'path' => '/onboarding/policies'],
        self::STEP_PAYMENT => ['slug' => 'payment', 'label' => 'Payment Instructions', 'path' => '/onboarding/payment'],
        self::STEP_ALERTS => ['slug' => 'alerts', 'label' => 'Owner Alerts', 'path' => '/onboarding/alerts'],
    ];

    public function getBusinessForUser(string $userId): ?array
    {
        $ctx = new BusinessContext();
        $onboarding = $ctx->getOnboardingBusiness($userId);
        if ($onboarding !== null) {
            return $onboarding;
        }

        return $ctx->getActiveBusiness($userId) ?? RepositoryFactory::businesses()->findByUserId($userId);
    }

    public function isBusinessComplete(string $businessId): bool
    {
        $business = RepositoryFactory::businesses()->findById($businessId);
        if ($business === null) {
            return false;
        }

        return (new BusinessContext())->isBusinessComplete($business);
    }

    public function isComplete(string $userId): bool
    {
        return (new BusinessContext())->hasAnyCompleteBusiness($userId);
    }

    public function userCanAccessDashboard(string $userId): bool
    {
        return $this->isComplete($userId);
    }

    public function getCurrentStepForBusiness(string $businessId): int
    {
        if ($this->isBusinessComplete($businessId)) {
            return self::STEP_COMPLETE;
        }

        $business = RepositoryFactory::businesses()->findById($businessId);
        if ($business === null) {
            return self::STEP_CHANNELS;
        }

        $step = (int) ($business['onboarding_step'] ?? self::STEP_CHANNELS);

        return max(self::STEP_CHANNELS, min(self::STEP_ALERTS, $step));
    }

    public function getCurrentStep(string $userId): int
    {
        $ctx = new BusinessContext();
        $business = $ctx->getOnboardingBusiness($userId);
        if ($business === null) {
            return self::STEP_CHANNELS;
        }

        return $this->getCurrentStepForBusiness((string) $business['id']);
    }

    public function stepUrl(int $step): string
    {
        if ($step >= self::STEP_COMPLETE) {
            return app_route('dashboard');
        }

        $meta = self::STEPS[$step] ?? self::STEPS[self::STEP_CHANNELS];

        return app_route(ltrim($meta['path'], '/'));
    }

    public function redirectUrlForUser(string $userId): string
    {
        $ctx = new BusinessContext();
        $inProgress = $ctx->getOnboardingBusiness($userId);
        if ($inProgress !== null) {
            return $this->stepUrl($this->getCurrentStepForBusiness((string) $inProgress['id']));
        }

        if ($ctx->hasAnyCompleteBusiness($userId)) {
            return app_route('dashboard');
        }

        $business = $ctx->ensureOnboardingBusiness($userId);

        return $this->stepUrl($this->getCurrentStepForBusiness((string) $business['id']));
    }

    public function assertCanAccessStep(string $userId, int $requestedStep): void
    {
        $ctx = new BusinessContext();
        $business = $ctx->ensureOnboardingBusiness($userId);
        $businessId = (string) $business['id'];

        if ($this->isBusinessComplete($businessId)) {
            redirect(app_route('dashboard'));
        }

        $current = $this->getCurrentStepForBusiness($businessId);
        if ($requestedStep > $current) {
            redirect($this->stepUrl($current));
        }
    }

    public function advanceAfterStep(string $businessId, int $completedStep): void
    {
        if ($completedStep >= self::STEP_ALERTS) {
            return;
        }

        RepositoryFactory::businesses()->updateOnboardingStep($businessId, $completedStep + 1);
    }

    public function completeOnboarding(string $businessId): void
    {
        RepositoryFactory::businesses()->markOnboardingComplete($businessId);
        (new BusinessContext())->setActiveBusinessId($businessId);
        $business = RepositoryFactory::businesses()->findById($businessId);
        if ($business !== null) {
            (new SubscriptionService())->syncBusinessSubscription((string) $business['user_id']);
        }
    }

    public function getSettingsForBusiness(string $businessId): ?array
    {
        return RepositoryFactory::businessSettings()->findByBusinessId($businessId);
    }

    public function getSettingsForUser(string $userId): ?array
    {
        $business = $this->getBusinessForUser($userId);

        return $business !== null ? $this->getSettingsForBusiness((string) $business['id']) : null;
    }

    public function ensureSettings(string $businessId, array $data = []): array
    {
        $existing = RepositoryFactory::businessSettings()->findByBusinessId($businessId);
        if ($existing !== null) {
            return $existing;
        }

        return RepositoryFactory::businessSettings()->create($businessId, $data);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getOfferingsForBusiness(string $businessId): array
    {
        return RepositoryFactory::offerings()->listByBusinessId($businessId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getOfferingsForUser(string $userId): array
    {
        $business = $this->getBusinessForUser($userId);

        return $business !== null ? $this->getOfferingsForBusiness((string) $business['id']) : [];
    }

    public function getActivePlanSlug(string $userId): ?string
    {
        $subscription = (new SubscriptionService())->getActiveSubscription($userId);

        return $subscription !== null ? (string) $subscription['plan_slug'] : null;
    }

    public function getChannelLimitForUser(string $userId): ?int
    {
        $slug = $this->getActivePlanSlug($userId);
        $perPlatform = PlanLimits::channelsPerPlatform($slug);
        $businessLimit = PlanLimits::businessLimit($slug);

        if ($perPlatform === null || $businessLimit === null) {
            return null;
        }

        return $businessLimit * $perPlatform * 3;
    }

    public function getPlanAllowanceLabel(string $userId): string
    {
        return PlanLimits::planAllowanceLabel($this->getActivePlanSlug($userId));
    }

    public function getPlanNameForUser(string $userId): string
    {
        $slug = $this->getActivePlanSlug($userId);
        if ($slug === null) {
            return 'Starter';
        }

        $plan = PlanService::find($slug);

        return is_array($plan) ? (string) ($plan['name'] ?? ucfirst($slug)) : ucfirst($slug);
    }

    public function hasProductOfferings(string $userId): bool
    {
        foreach ($this->getOfferingsForUser($userId) as $offering) {
            if ((string) ($offering['offering_type'] ?? '') === 'product') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{
     *     show_delivery: bool,
     *     step_lead: string,
     *     faq_placeholder: string,
     *     return_label: string,
     *     return_placeholder: string
     * }
     */
    public function getPoliciesContextForBusiness(string $businessId): array
    {
        $types = [];
        if ($businessId !== '__none__') {
            foreach ($this->getOfferingsForBusiness($businessId) as $offering) {
                $type = (string) ($offering['offering_type'] ?? '');
                if ($type !== '') {
                    $types[$type] = true;
                }
            }
        }

        $hasProducts = isset($types['product']);
        $hasServices = isset($types['service']);
        $hasMemberships = isset($types['membership']);
        $showDelivery = $hasProducts;

        if ($hasProducts) {
            $faqPlaceholder = "Q: How long does delivery take?\nA: 2–3 days in Lagos, 5–7 days nationwide.";
        } elseif ($hasMemberships && !$hasServices) {
            $faqPlaceholder = "Q: What is included in the membership?\nA: List perks, access, and how members get started.";
        } elseif ($hasServices && !$hasMemberships) {
            $faqPlaceholder = "Q: How do I book?\nA: Share your preferred date and we'll confirm availability within 24 hours.";
        } else {
            $faqPlaceholder = "Q: How do I get started?\nA: Tell us what you need and we'll guide you through the next step.";
        }

        if ($hasProducts && ($hasServices || $hasMemberships)) {
            $returnLabel = 'Returns, cancellations & refunds';
            $returnPlaceholder = 'When customers can return products, cancel services, or get a refund.';
        } elseif ($hasProducts) {
            $returnLabel = 'Return & refund policy';
            $returnPlaceholder = 'When returns are accepted and how refunds work.';
        } else {
            $returnLabel = 'Cancellation & refund policy';
            $returnPlaceholder = 'How customers can cancel and when refunds apply.';
        }

        $stepLead = $showDelivery
            ? 'Help your salesgirl answer common questions about delivery, returns, and your business.'
            : 'Help your salesgirl answer common questions about how you work, cancellations, and your business.';

        return [
            'show_delivery' => $showDelivery,
            'step_lead' => $stepLead,
            'faq_placeholder' => $faqPlaceholder,
            'return_label' => $returnLabel,
            'return_placeholder' => $returnPlaceholder,
        ];
    }

    public function getPoliciesContextForUser(string $userId): array
    {
        $business = $this->getBusinessForUser($userId);
        if ($business === null) {
            return $this->getPoliciesContextForBusiness('__none__');
        }

        return $this->getPoliciesContextForBusiness((string) $business['id']);
    }

    /**
     * @return array{
     *     plan_slug: string,
     *     plan_name: string,
     *     show_whatsapp_number: bool,
     *     show_handoff_to_whatsapp: bool,
     *     show_hot_lead_alerts: bool,
     *     hot_lead_label: string,
     *     step_lead: string
     * }
     */
    public function getAlertsContextForUser(string $userId): array
    {
        $slug = $this->getActivePlanSlug($userId) ?? 'starter';
        $plan = PlanService::find($slug);
        $planName = is_array($plan) ? (string) ($plan['name'] ?? ucfirst($slug)) : ucfirst($slug);

        $isVendorOrAbove = in_array($slug, ['vendor', 'shop'], true);
        $isShop = $slug === 'shop';

        $stepLead = match ($slug) {
            'vendor' => 'Your Vendor plan can move buyers to WhatsApp and alert you when someone is ready to purchase.',
            'shop' => 'Your Shop plan includes WhatsApp handoffs and owner alerts for hot leads that need you.',
            default => 'Your Starter plan keeps customers in the DMs. Tell us how to reach you for payments and issues that need you personally.',
        };

        return [
            'plan_slug' => $slug,
            'plan_name' => $planName,
            'show_whatsapp_number' => $isVendorOrAbove,
            'show_handoff_to_whatsapp' => $isVendorOrAbove,
            'show_hot_lead_alerts' => $isVendorOrAbove,
            'hot_lead_label' => $isShop
                ? 'Send WhatsApp owner alerts when a hot lead needs my attention'
                : 'Notify me when a customer is ready to buy',
            'step_lead' => $stepLead,
        ];
    }
}
