<?php

declare(strict_types=1);

/**
 * Monthly / annual billing toggle for pricing and checkout.
 */
function pricing_billing_cycle_toggle(string $inputName = 'billing_cycle'): void
{
    ?>
    <div class="pricing-cycle-toggle" data-billing-cycle-toggle role="group" aria-label="Billing period">
        <button
            type="button"
            class="pricing-cycle-btn is-active"
            data-billing-cycle="monthly"
            aria-pressed="true"
        >Monthly</button>
        <button
            type="button"
            class="pricing-cycle-btn"
            data-billing-cycle="annual"
            aria-pressed="false"
        >Yearly <span class="pricing-cycle-save">2 months free</span></button>
        <input type="hidden" name="<?= e($inputName) ?>" value="monthly" data-billing-cycle-input>
    </div>
    <?php
}
