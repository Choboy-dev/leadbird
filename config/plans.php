<?php

declare(strict_types=1);

/**
 * Lead Bird subscription plans — single source of truth for pricing UI and billing.
 *
 * Prices are in USD. Nigerian customers are charged the naira equivalent at checkout.
 *
 * Limits are per business:
 * - Starter: 1 business, each with 1 Facebook + 1 Instagram + 1 WhatsApp
 * - Vendor: 3 businesses, each with 1 of each channel
 * - Shop: unlimited businesses, unlimited channels per business
 *
 * @return array<string, array<string, mixed>>
 */
function plans_catalog(): array
{
    return [
        'starter' => [
            'slug' => 'starter',
            'name' => 'Starter',
            'icon' => 'pencil',
            'price' => 29,
            'annual_price' => 290,
            'description' => 'For one shop that is just starting out.',
            'features' => [
                '1 business',
                '1 Facebook Page, 1 Instagram account and 1 WhatsApp number',
                '500 tokens each month',
                'Buy more tokens whenever you need them',
                'Answers questions about your products and prices',
                'Sends your payment details to buyers',
                'Works day and night, even while you sleep',
            ],
            'color' => 'blue',
            'business_limit' => 1,
            'channels_per_platform' => 1,
            'monthly_tokens' => 500,
            'flutterwave_plan_env' => 'FLUTTERWAVE_PLAN_STARTER_USD',
            'flutterwave_plan_env_ngn' => 'FLUTTERWAVE_PLAN_STARTER_NGN',
            'flutterwave_annual_plan_env' => 'FLUTTERWAVE_PLAN_STARTER_ANNUAL_USD',
            'flutterwave_annual_plan_env_ngn' => 'FLUTTERWAVE_PLAN_STARTER_ANNUAL_NGN',
        ],
        'vendor' => [
            'slug' => 'vendor',
            'name' => 'Vendor',
            'icon' => 'star',
            'price' => 59,
            'annual_price' => 590,
            'description' => 'For sellers running more than one shop.',
            'features' => [
                '3 businesses',
                'Each business gets its own Facebook, Instagram and WhatsApp',
                '2,000 tokens each month',
                'Buy more tokens whenever you need them',
                ['label' => 'Everything in Starter, plus:', 'header' => true],
                'Talks with customers until you both agree on a price',
                'Moves ready buyers over to WhatsApp',
                'Writes your social media posts for you',
                'Schedules posts and publishes them with one click',
            ],
            'popular' => true,
            'color' => 'amber',
            'business_limit' => 3,
            'channels_per_platform' => 1,
            'monthly_tokens' => 2000,
            'flutterwave_plan_env' => 'FLUTTERWAVE_PLAN_VENDOR_USD',
            'flutterwave_plan_env_ngn' => 'FLUTTERWAVE_PLAN_VENDOR_NGN',
            'flutterwave_annual_plan_env' => 'FLUTTERWAVE_PLAN_VENDOR_ANNUAL_USD',
            'flutterwave_annual_plan_env_ngn' => 'FLUTTERWAVE_PLAN_VENDOR_ANNUAL_NGN',
        ],
        'shop' => [
            'slug' => 'shop',
            'name' => 'Shop',
            'icon' => 'ai',
            'price' => 99,
            'annual_price' => 990,
            'description' => 'For busy stores with plenty of accounts.',
            'features' => [
                'As many businesses as you need',
                'Connect as many Facebook, Instagram and WhatsApp accounts as you want',
                '8,000 tokens each month',
                'Buy more tokens whenever you need them',
                ['label' => 'Everything in Vendor, plus:', 'header' => true],
                'Follows up when a customer stops replying',
                'Sends a receipt after someone pays',
            ],
            'color' => 'purple',
            'business_limit' => null,
            'channels_per_platform' => null,
            'monthly_tokens' => 8000,
            'flutterwave_plan_env' => 'FLUTTERWAVE_PLAN_SHOP_USD',
            'flutterwave_plan_env_ngn' => 'FLUTTERWAVE_PLAN_SHOP_NGN',
            'flutterwave_annual_plan_env' => 'FLUTTERWAVE_PLAN_SHOP_ANNUAL_USD',
            'flutterwave_annual_plan_env_ngn' => 'FLUTTERWAVE_PLAN_SHOP_ANNUAL_NGN',
        ],
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function plans_list(): array
{
    return array_values(plans_catalog());
}
