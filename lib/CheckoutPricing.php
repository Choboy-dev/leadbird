<?php

declare(strict_types=1);

/**
 * Resolves checkout currency and charge amounts (dual USD catalog / NGN for Nigeria).
 */
final class CheckoutPricing
{
    public const CYCLE_MONTHLY = 'monthly';
    public const CYCLE_ANNUAL = 'annual';

    /**
     * @return array{
     *     currency: string,
     *     amount: float,
     *     amount_usd: float,
     *     payment_plan_id: string|null,
     *     billing_cycle: string
     * }
     */
    public function forPlan(string $planSlug, string $userId, string $billingCycle = self::CYCLE_MONTHLY): array
    {
        $plan = PlanService::find($planSlug);
        if ($plan === null) {
            throw new InvalidArgumentException('Unknown plan.');
        }

        $currency = $this->checkoutCurrencyForUser($userId);
        $amountUsd = $this->catalogAmountUsd($plan, $billingCycle);
        $amount = $currency === 'NGN'
            ? (float) (new FxRateService())->usdToNgn($amountUsd)
            : $amountUsd;

        return [
            'currency' => $currency,
            'amount' => $amount,
            'amount_usd' => $amountUsd,
            'payment_plan_id' => PlanService::flutterwavePlanId($planSlug, $currency, $billingCycle),
            'billing_cycle' => $billingCycle,
        ];
    }

    /**
     * @param array<string, mixed> $package
     * @return array{currency: string, amount: float, amount_usd: float}
     */
    public function forTopup(array $package, string $userId): array
    {
        $currency = $this->checkoutCurrencyForUser($userId);
        $amountUsd = (float) ($package['price'] ?? 0);
        $amount = $currency === 'NGN'
            ? (float) (new FxRateService())->usdToNgn($amountUsd)
            : $amountUsd;

        return [
            'currency' => $currency,
            'amount' => $amount,
            'amount_usd' => $amountUsd,
        ];
    }

    public function checkoutCurrencyForUser(string $userId): string
    {
        $profile = (new BillingProfileService())->findForUser($userId);
        $country = strtoupper(trim((string) ($profile['country'] ?? 'NG')));

        return $country === 'NG' ? 'NGN' : 'USD';
    }

    /**
     * @param array<string, mixed> $plan
     */
    private function catalogAmountUsd(array $plan, string $billingCycle): float
    {
        if ($billingCycle === self::CYCLE_ANNUAL) {
            return (float) ($plan['annual_price'] ?? ((float) ($plan['price'] ?? 0) * 10));
        }

        return (float) ($plan['price'] ?? 0);
    }
}
