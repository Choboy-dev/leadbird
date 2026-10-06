<?php

declare(strict_types=1);

final class PlanService
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $catalog = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function catalog(): array
    {
        if (self::$catalog === null) {
            require_once dirname(__DIR__) . '/config/plans.php';
            self::$catalog = plans_catalog();
        }

        return self::$catalog;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function list(): array
    {
        return array_values(self::catalog());
    }

    public static function isValidSlug(string $slug): bool
    {
        return isset(self::catalog()[$slug]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $slug): ?array
    {
        return self::catalog()[$slug] ?? null;
    }

    public static function flutterwavePlanId(
        string $slug,
        string $currency = 'USD',
        string $billingCycle = CheckoutPricing::CYCLE_MONTHLY
    ): ?string {
        $plan = self::find($slug);
        if ($plan === null) {
            return null;
        }

        $currency = strtoupper(trim($currency));
        if ($currency === 'NGN') {
            // NGN checkout uses a live FX quote at payment time — not fixed Flutterwave plans.
            return null;
        }

        $isAnnual = $billingCycle === CheckoutPricing::CYCLE_ANNUAL;

        $envKey = match (true) {
            $currency === 'NGN' && $isAnnual => (string) ($plan['flutterwave_annual_plan_env_ngn'] ?? ''),
            $currency === 'NGN' => (string) ($plan['flutterwave_plan_env_ngn'] ?? ''),
            $isAnnual => (string) ($plan['flutterwave_annual_plan_env'] ?? ''),
            default => (string) ($plan['flutterwave_plan_env'] ?? ''),
        };

        if ($envKey === '') {
            return null;
        }

        $value = env($envKey);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @deprecated Use flutterwavePlanId($slug, $currency) */
    public static function legacyFlutterwavePlanId(string $slug): ?string
    {
        return self::flutterwavePlanId($slug, 'USD');
    }
}
