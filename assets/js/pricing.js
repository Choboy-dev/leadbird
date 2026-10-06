(function () {
    'use strict';

    var config = window.LeadBirdPricing || {};
    var overlay = document.querySelector('[data-checkout-overlay]');
    var messageEl = document.querySelector('[data-checkout-message]');
    var cycleInput = document.querySelector('[data-billing-cycle-input]');
    var isSubmitting = false;

    function formatUsd(amount) {
        var value = Number(amount);
        if (!Number.isFinite(value)) {
            return '$0';
        }
        var decimals = value % 1 === 0 ? 0 : 2;
        return '$' + value.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    }

    function currentBillingCycle() {
        if (!cycleInput) {
            return 'monthly';
        }
        return cycleInput.value === 'annual' ? 'annual' : 'monthly';
    }

    function updateDisplayedPrices() {
        var cycle = currentBillingCycle();
        var isAnnual = cycle === 'annual';

        document.querySelectorAll('[data-plan-price]').forEach(function (priceEl) {
            var amount = isAnnual
                ? priceEl.getAttribute('data-annual-amount')
                : priceEl.getAttribute('data-monthly-amount');
            priceEl.textContent = formatUsd(amount);
        });

        document.querySelectorAll('[data-plan-period]').forEach(function (periodEl) {
            periodEl.textContent = isAnnual ? '/year' : '/month';
        });

        document.querySelectorAll('[data-billing-cycle]').forEach(function (button) {
            var active = button.getAttribute('data-billing-cycle') === cycle;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function initBillingCycleToggle() {
        var toggle = document.querySelector('[data-billing-cycle-toggle]');
        if (!toggle || !cycleInput) {
            return;
        }

        toggle.querySelectorAll('[data-billing-cycle]').forEach(function (button) {
            button.addEventListener('click', function () {
                cycleInput.value = button.getAttribute('data-billing-cycle') === 'annual' ? 'annual' : 'monthly';
                updateDisplayedPrices();
            });
        });

        updateDisplayedPrices();
    }

    function showOverlay(message) {
        if (!overlay) {
            return;
        }
        if (messageEl && message) {
            messageEl.textContent = message;
        }
        overlay.classList.remove('hidden');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function hideOverlay() {
        if (!overlay) {
            return;
        }
        overlay.classList.add('hidden');
        overlay.setAttribute('aria-hidden', 'true');
    }

    function setButtonLoading(button, loading) {
        if (!button) {
            return;
        }
        button.disabled = loading;
        button.classList.toggle('is-loading', loading);
    }

    function startCheckout(planSlug, triggerButton) {
        if (isSubmitting || !config.initiateUrl || !planSlug) {
            return;
        }

        isSubmitting = true;
        setButtonLoading(triggerButton, true);
        showOverlay('Redirecting you to Flutterwave…');

        var csrf = config.csrfToken || window.LeadBirdCsrf || '';
        var body = 'plan=' + encodeURIComponent(planSlug)
            + '&billing_cycle=' + encodeURIComponent(currentBillingCycle())
            + '&_csrf=' + encodeURIComponent(csrf);

        fetch(config.initiateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                Accept: 'application/json',
                'X-CSRF-Token': csrf,
            },
            body: body,
            credentials: 'same-origin',
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                var data = result.data || {};
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                throw new Error(data.error || 'Could not start checkout.');
            })
            .catch(function (error) {
                hideOverlay();
                isSubmitting = false;
                setButtonLoading(triggerButton, false);
                window.LeadBirdModal.alert({
                    title: 'Checkout failed',
                    message: error.message || 'Something went wrong. Please try again.',
                    tone: 'error',
                });
            });
    }

    document.querySelectorAll('[data-plan-select]').forEach(function (button) {
        button.addEventListener('click', function () {
            startCheckout(button.getAttribute('data-plan-select'), button);
        });
    });

    if (config.autoPlan) {
        var autoButton = document.querySelector('[data-auto-checkout="true"]');
        if (autoButton) {
            window.setTimeout(function () {
                startCheckout(config.autoPlan, autoButton);
            }, 600);
        }
    }

    initBillingCycleToggle();
})();
