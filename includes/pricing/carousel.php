<?php

declare(strict_types=1);

function pricing_carousel_open(string $trackClass = ''): void
{
    ?>
    <div class="pricing-carousel" data-pricing-carousel>
        <div class="pricing-carousel-controls md:hidden" aria-hidden="false">
            <button
                type="button"
                class="pricing-carousel-arrow pricing-carousel-arrow--prev"
                data-pricing-carousel-prev
                aria-label="Previous plan"
            >
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </button>
            <div class="pricing-carousel-dots" data-pricing-carousel-dots role="tablist" aria-label="Pricing plans"></div>
            <button
                type="button"
                class="pricing-carousel-arrow pricing-carousel-arrow--next"
                data-pricing-carousel-next
                aria-label="Next plan"
            >
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </button>
        </div>
        <div class="pricing-carousel-viewport" data-pricing-carousel-viewport>
            <div
                class="pricing-carousel-track"
                data-pricing-carousel-track
            >
    <?php
}

function pricing_carousel_close(): void
{
    ?>
            </div>
        </div>
    </div>
    <?php
}
