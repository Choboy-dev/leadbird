<?php

declare(strict_types=1);

/**
 * One-time token top-up packages (Flutterwave checkout).
 * Prices are in USD.
 *
 * @return array<string, array{slug: string, name: string, tokens: int, price: float, badge?: string}>
 */
function token_topup_packages(): array
{
    return [
        'boost_500' => [
            'slug' => 'boost_500',
            'name' => '500 tokens',
            'tokens' => 500,
            'price' => 9.0,
            'badge' => 'Quick boost',
        ],
        'boost_1500' => [
            'slug' => 'boost_1500',
            'name' => '1,500 tokens',
            'tokens' => 1500,
            'price' => 19.0,
            'badge' => 'Best value',
        ],
        'boost_4000' => [
            'slug' => 'boost_4000',
            'name' => '4,000 tokens',
            'tokens' => 4000,
            'price' => 39.0,
        ],
    ];
}

/**
 * @return list<array{slug: string, name: string, tokens: int, price: float, badge?: string}>
 */
function token_topup_packages_list(): array
{
    return array_values(token_topup_packages());
}
