<?php

declare(strict_types=1);

/**
 * Lead Bird money settings.
 * - billing_currency: Lead Bird subscription / top-ups (always USD).
 * - default_catalog_currency: fallback for merchant offer prices when a business
 *   has not chosen a catalog currency yet.
 *
 * @return array<string, mixed>
 */
function money_config(): array
{
    return [
        'billing_currency' => 'USD',
        'default_catalog_currency' => 'NGN',
        /** @deprecated use billing_currency — kept so older callers keep working */
        'catalog_currency' => 'USD',
        'display_currency' => 'USD',
        /** Emergency fallback only when Flutterwave rates API and cache are both unavailable. */
        'fx_ngn_per_usd' => env_float('FX_NGN_PER_USD', 1364.0),
        /** Minimum affiliate withdrawal in USD. */
        'min_withdraw_usd' => 10.0,
        /** Payment amount tolerance by currency. */
        'tolerance' => [
            'USD' => 0.01,
            'NGN' => 100.0,
        ],
    ];
}
