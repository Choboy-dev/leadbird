<?php

declare(strict_types=1);

function pricing_render_icon(string $icon, string $colorClass): void
{
    if ($icon === 'ai' || $icon === 'sparkles') {
        echo brand_ai_icon_html('brand-ai-icon--pricing', 24);

        return;
    }

    $svgOpen = '<svg viewBox="0 0 24 24" class="w-6 h-6 ' . $colorClass . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';

    match ($icon) {
        'pencil' => print $svgOpen . '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg>',
        'star' => print $svgOpen . '<path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path></svg>',
        default => null,
    };
}

function pricing_tier_color_class(string $color): string
{
    return match ($color) {
        'blue' => 'text-blue-500',
        'lime' => 'text-brand-lime',
        'amber' => 'text-brand-amber-dark',
        'purple' => 'text-purple-500',
        default => 'text-zinc-900',
    };
}

function pricing_tier_rotation_class(int $index): string
{
    return match ($index) {
        0 => '-rotate-1',
        1 => 'rotate-1',
        2 => '-rotate-2',
        default => '',
    };
}

/**
 * @param array<string, mixed> $tier
 */
function pricing_render_tier(
    array $tier,
    int $index,
    string $ctaMode,
    ?string $selectedSlug = null,
    bool $isAuthenticated = false
): void {
    $colorClass = pricing_tier_color_class((string) $tier['color']);
    $rotationClass = pricing_tier_rotation_class($index);
    $isPopular = !empty($tier['popular']);
    $slug = (string) $tier['slug'];
    $isSelected = $selectedSlug === $slug;
    $ctaLabel = $isAuthenticated
        ? ($isSelected ? 'Continue to payment' : 'Choose plan')
        : 'Get Started';
    ?>
    <div
        class="pricing-tier relative group h-full transition-all duration-300 <?= e($rotationClass) ?><?= $isSelected ? ' is-selected' : '' ?>"
        data-plan-card
        data-plan-slug="<?= e($slug) ?>"
    >
        <div class="pricing-tier-shadow absolute inset-0 bg-white border-2 border-zinc-900 rounded-lg shadow-[4px_4px_0px_0px] shadow-zinc-900 transition-all duration-300 group-hover:shadow-[8px_8px_0px_0px] group-hover:-translate-x-1 group-hover:-translate-y-1"></div>

        <?php if ($isSelected): ?>
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 z-10 bg-brand-blue text-white font-heading px-4 py-1 rounded-full text-xs border-2 border-zinc-900 whitespace-nowrap">
                Your selected plan
            </div>
        <?php endif; ?>

        <div class="relative flex h-full flex-col p-5">
            <?php if ($isPopular): ?>
                <div class="absolute -top-2 -right-2 bg-brand-lime text-black font-heading px-3 py-1 rounded-full rotate-12 text-xs border-2 border-zinc-900">
                    Popular!
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <div class="w-10 h-10 rounded-full mb-3 flex items-center justify-center border-2 border-zinc-900 <?= e($colorClass) ?>">
                    <?php pricing_render_icon((string) $tier['icon'], $colorClass); ?>
                </div>
                <h3 class="text-2xl leading-tight text-zinc-900 font-heading"><?= e((string) $tier['name']) ?></h3>
                <?php if (($tier['description'] ?? '') !== ''): ?>
                    <p class="mt-1 text-[0.9375rem] leading-snug text-zinc-600"><?= e((string) $tier['description']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-5 flex items-baseline gap-1.5" data-plan-price-wrap>
                <span
                    class="text-[2.25rem] leading-none font-bold text-zinc-900"
                    data-plan-price
                    data-monthly-amount="<?= e((string) ($tier['price'] ?? 0)) ?>"
                    data-annual-amount="<?= e((string) ($tier['annual_price'] ?? 0)) ?>"
                ><?= e(money_format((float) $tier['price'])) ?></span>
                <span class="text-[0.9375rem] text-zinc-600" data-plan-period>/month</span>
            </div>

            <div class="space-y-2.5 mb-5 flex-1">
                <?php foreach ($tier['features'] as $feature): ?>
                    <?php
                    $isHeader = is_array($feature) && !empty($feature['header']);
                    $label = is_array($feature) ? $feature['label'] : $feature;
                    ?>
                    <?php if ($isHeader): ?>
                        <p class="pt-1 text-[0.8125rem] font-semibold text-zinc-600"><?= e((string) $label) ?></p>
                    <?php else: ?>
                        <div class="flex items-start gap-2.5">
                            <div class="w-4 h-4 mt-0.5 rounded-full border-2 border-zinc-900 flex items-center justify-center flex-shrink-0">
                                <svg viewBox="0 0 24 24" class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 6 9 17l-5-5"></path>
                                </svg>
                            </div>
                            <span class="text-[0.9375rem] leading-snug text-zinc-900"><?= e((string) $label) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($ctaMode === 'checkout'): ?>
                <button
                    type="button"
                    class="<?= $isPopular
                        ? 'bg-brand-blue text-white hover:bg-brand-blue-dark active:bg-brand-blue'
                        : 'bg-zinc-50 text-zinc-900 hover:bg-white active:bg-zinc-50' ?> pricing-cta mt-auto w-full h-11 rounded-full inline-flex items-center justify-center font-heading text-base relative border-2 border-zinc-900 transition-all duration-300 shadow-[4px_4px_0px_0px] shadow-zinc-900 hover:shadow-[6px_6px_0px_0px] hover:-translate-x-0.5 hover:-translate-y-0.5 disabled:opacity-60 disabled:pointer-events-none"
                    data-plan-select="<?= e($slug) ?>"
                    <?= $isSelected ? 'data-auto-checkout="true"' : '' ?>
                >
                    <?= e($ctaLabel) ?>
                </button>
            <?php else: ?>
                <a
                    href="<?= e(app_route('signup', ['plan' => $slug])) ?>"
                    class="<?= $isPopular
                        ? 'bg-brand-blue text-white hover:bg-brand-blue-dark active:bg-brand-blue'
                        : 'bg-zinc-50 text-zinc-900 hover:bg-white active:bg-zinc-50' ?> pricing-cta mt-auto w-full h-11 rounded-full inline-flex items-center justify-center font-heading text-base relative border-2 border-zinc-900 transition-all duration-300 shadow-[4px_4px_0px_0px] shadow-zinc-900 hover:shadow-[6px_6px_0px_0px] hover:-translate-x-0.5 hover:-translate-y-0.5"
                >
                    Get Started
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
