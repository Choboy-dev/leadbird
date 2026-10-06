<?php

declare(strict_types=1);

/**
 * Plan limits: businesses and social channels per business.
 */
final class PlanLimits
{
    public static function forPlanSlug(?string $planSlug): array
    {
        $plan = $planSlug !== null ? PlanService::find($planSlug) : null;

        return is_array($plan) ? $plan : [];
    }

    public static function businessLimit(?string $planSlug): ?int
    {
        $plan = self::forPlanSlug($planSlug);
        if (!array_key_exists('business_limit', $plan)) {
            return 1;
        }

        $limit = $plan['business_limit'];

        return $limit === null ? null : (int) $limit;
    }

    /**
     * Max active connections per platform (Facebook, Instagram, WhatsApp) per business.
     * null = no cap (Shop plan).
     */
    public static function channelsPerPlatform(?string $planSlug): ?int
    {
        $plan = self::forPlanSlug($planSlug);
        if (!array_key_exists('channels_per_platform', $plan)) {
            return 1;
        }

        $limit = $plan['channels_per_platform'];

        return $limit === null ? null : (int) $limit;
    }

    /** Monthly tokens included with the plan (all plans are metered; Shop = 8,000). */
    public static function monthlyTokens(?string $planSlug): ?int
    {
        $plan = self::forPlanSlug($planSlug);
        if (array_key_exists('monthly_tokens', $plan)) {
            $limit = $plan['monthly_tokens'];

            return $limit === null ? null : (int) $limit;
        }

        if (array_key_exists('dm_reply_limit', $plan)) {
            $limit = $plan['dm_reply_limit'];

            return $limit === null ? null : (int) $limit;
        }

        return 50;
    }

    /** @deprecated Use monthlyTokens() */
    public static function dmReplyLimit(?string $planSlug): ?int
    {
        return self::monthlyTokens($planSlug);
    }

    public static function canConnectPlatform(string $userId, string $businessId, string $platform): bool
    {
        $slug = (new OnboardingService())->getActivePlanSlug($userId) ?? 'starter';
        $perPlatform = self::channelsPerPlatform($slug);

        if ($perPlatform === null) {
            return true;
        }

        $activeOnPlatform = RepositoryFactory::connectedAccounts()->countActiveByBusinessAndPlatform($businessId, $platform);

        return $activeOnPlatform < $perPlatform;
    }

    public static function canAddBusiness(string $userId): bool
    {
        return (new BusinessContext())->canAddBusiness($userId);
    }

    /**
     * Short label for billing / connected accounts headers.
     */
    public static function planAllowanceLabel(?string $planSlug): string
    {
        $businessLimit = self::businessLimit($planSlug);
        $perPlatform = self::channelsPerPlatform($planSlug);

        if ($businessLimit === null && $perPlatform === null) {
            return 'Unlimited businesses and social accounts';
        }

        if ($businessLimit === 1 && $perPlatform === 1) {
            return '1 business with Facebook, Instagram & WhatsApp';
        }

        if ($businessLimit !== null && $perPlatform === 1) {
            return $businessLimit . ' businesses — each gets Facebook, Instagram & WhatsApp';
        }

        return 'Multiple businesses and social accounts';
    }

    /**
     * @return array{businesses_used: int, business_limit: int|null, business_label: string, channels_label: string}
     */
    public static function usageSummaryForUser(string $userId, ?string $businessId): array
    {
        $slug = (new OnboardingService())->getActivePlanSlug($userId) ?? 'starter';
        $businessLimit = self::businessLimit($slug);
        $businessesUsed = count((new BusinessContext())->listForUser($userId));

        $channelsOnBusiness = $businessId !== null
            ? RepositoryFactory::connectedAccounts()->countActiveByBusinessId($businessId)
            : 0;

        $perPlatform = self::channelsPerPlatform($slug);
        if ($perPlatform === null) {
            $channelsLabel = $channelsOnBusiness . ' connected on this business (no limit)';
        } elseif ($perPlatform === 1) {
            $channelsLabel = $channelsOnBusiness . ' connected on this business (1 account per network)';
        } else {
            $channelsLabel = $channelsOnBusiness . ' connected on this business';
        }

        if ($businessLimit === null) {
            $businessLabel = $businessesUsed . ' businesses (no limit)';
        } else {
            $businessLabel = $businessesUsed . ' of ' . $businessLimit . ' business' . ($businessLimit === 1 ? '' : 'es');
        }

        return [
            'businesses_used' => $businessesUsed,
            'business_limit' => $businessLimit,
            'business_label' => $businessLabel,
            'channels_label' => $channelsLabel,
            'channels_on_business' => $channelsOnBusiness,
            'channels_per_platform' => $perPlatform,
        ];
    }
}
