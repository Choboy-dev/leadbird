<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/money.php';

function money_billing_currency(): string
{
    return strtoupper((string) (money_config()['billing_currency'] ?? 'USD'));
}

/**
 * Platform fallback when a business has not set catalog_currency yet.
 */
function money_default_catalog_currency(): string
{
    return money_normalize_currency(
        (string) (money_config()['default_catalog_currency'] ?? 'NGN')
    ) ?? 'NGN';
}

/**
 * @deprecated Prefer money_billing_currency() for Lead Bird prices.
 */
function money_catalog_currency(): string
{
    return money_billing_currency();
}

function money_min_withdraw_usd(): float
{
    return (float) (money_config()['min_withdraw_usd'] ?? 10.0);
}

function money_payment_tolerance(string $currency): float
{
    $currency = strtoupper(trim($currency));
    $tolerance = money_config()['tolerance'] ?? [];

    return (float) ($tolerance[$currency] ?? ($currency === 'USD' ? 0.01 : 1.0));
}

/**
 * Currencies merchants can use for their product catalog.
 *
 * @return array<string, string> code => label
 */
function money_catalog_currency_options(): array
{
    return [
        'NGN' => 'Nigerian Naira (₦)',
        'GHS' => 'Ghanaian Cedi (GH₵)',
        'KES' => 'Kenyan Shilling (KSh)',
        'ZAR' => 'South African Rand (R)',
        'UGX' => 'Ugandan Shilling (USh)',
        'TZS' => 'Tanzanian Shilling (TSh)',
        'EGP' => 'Egyptian Pound (E£)',
        'XOF' => 'West African CFA (CFA)',
        'XAF' => 'Central African CFA (FCFA)',
        'USD' => 'US Dollar ($)',
        'GBP' => 'British Pound (£)',
        'EUR' => 'Euro (€)',
        'CAD' => 'Canadian Dollar (CA$)',
        'AUD' => 'Australian Dollar (A$)',
        'INR' => 'Indian Rupee (₹)',
        'PHP' => 'Philippine Peso (₱)',
    ];
}

function money_normalize_currency(?string $currency): ?string
{
    $currency = strtoupper(trim((string) $currency));
    if ($currency === '') {
        return null;
    }

    $options = money_catalog_currency_options();
    if (isset($options[$currency])) {
        return $currency;
    }

    // Allow other ISO-ish codes that already exist on older rows.
    if (preg_match('/^[A-Z]{3}$/', $currency) === 1) {
        return $currency;
    }

    return null;
}

function money_currency_symbol(string $currency): string
{
    return match (strtoupper($currency)) {
        'USD' => '$',
        'NGN' => '₦',
        'GBP' => '£',
        'EUR' => '€',
        'GHS' => 'GH₵',
        'KES' => 'KSh',
        'ZAR' => 'R',
        'UGX' => 'USh',
        'TZS' => 'TSh',
        'EGP' => 'E£',
        'XOF' => 'CFA',
        'XAF' => 'FCFA',
        'CAD' => 'CA$',
        'AUD' => 'A$',
        'INR' => '₹',
        'PHP' => '₱',
        default => strtoupper($currency) . ' ',
    };
}

function money_currency_decimals(string $currency): int
{
    return match (strtoupper($currency)) {
        'NGN', 'JPY', 'KRW', 'VND', 'XOF', 'XAF', 'UGX', 'TZS', 'KES', 'RWF' => 0,
        default => 2,
    };
}

/**
 * Format a money amount for display.
 * Defaults to Lead Bird billing currency (USD) when $currency is omitted —
 * use an explicit currency for merchant catalog prices.
 */
function money_format(float $amount, ?string $currency = null): string
{
    $currency = money_normalize_currency($currency) ?? money_billing_currency();
    $maxDecimals = money_currency_decimals($currency);
    $decimals = $maxDecimals === 0 || fmod($amount, 1.0) === 0.0 ? 0 : $maxDecimals;
    $symbol = money_currency_symbol($currency);
    $formatted = number_format($amount, $decimals, '.', ',');

    // Codes that read better after the amount.
    if (in_array($currency, ['XOF', 'XAF'], true)) {
        return $formatted . ' ' . trim($symbol);
    }

    return $symbol . $formatted;
}

/**
 * Format a merchant catalog/offer price.
 */
function money_format_catalog(float $amount, ?string $currency = null): string
{
    $currency = money_normalize_currency($currency) ?? money_default_catalog_currency();

    return money_format($amount, $currency);
}

/**
 * Resolve the catalog currency for a business settings row.
 *
 * @param array<string, mixed>|null $settings
 */
function money_business_catalog_currency(?array $settings): string
{
    $fromSettings = money_normalize_currency(
        isset($settings['catalog_currency']) ? (string) $settings['catalog_currency'] : null
    );

    return $fromSettings ?? money_default_catalog_currency();
}

/**
 * Convert a stored amount to USD for dashboard totals.
 */
function money_to_usd(float $amount, string $currency): float
{
    $currency = strtoupper(trim($currency));
    if ($currency === 'USD' || $currency === '') {
        return round($amount, 2);
    }

    if ($currency === 'NGN') {
        require_once dirname(__DIR__) . '/lib/FxRateService.php';
        $rate = (new FxRateService())->ngnPerUsd();

        return $rate > 0 ? round($amount / $rate, 2) : round($amount, 2);
    }

    return round($amount, 2);
}
