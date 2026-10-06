(function () {
    'use strict';

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

    function initBillingCycleToggle() {
        var cycleInput = document.querySelector('[data-billing-cycle-input]');
        var toggle = document.querySelector('[data-billing-cycle-toggle]');
        if (!cycleInput || !toggle) {
            return;
        }

        function currentCycle() {
            return cycleInput.value === 'annual' ? 'annual' : 'monthly';
        }

        function updatePrices() {
            var isAnnual = currentCycle() === 'annual';
            document.querySelectorAll('[data-plan-price]').forEach(function (priceEl) {
                var amount = isAnnual
                    ? priceEl.getAttribute('data-annual-amount')
                    : priceEl.getAttribute('data-monthly-amount');
                priceEl.textContent = formatUsd(amount);
            });
            document.querySelectorAll('[data-plan-period]').forEach(function (periodEl) {
                periodEl.textContent = isAnnual ? 'each year' : 'each month';
            });
            toggle.querySelectorAll('[data-billing-cycle]').forEach(function (button) {
                var active = button.getAttribute('data-billing-cycle') === currentCycle();
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }

        toggle.querySelectorAll('[data-billing-cycle]').forEach(function (button) {
            button.addEventListener('click', function () {
                cycleInput.value = button.getAttribute('data-billing-cycle') === 'annual' ? 'annual' : 'monthly';
                updatePrices();
            });
        });

        updatePrices();
    }

    var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-faq-tab]'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('[data-faq-panel]'));

    initBillingCycleToggle();

    if (tabs.length === 0 || panels.length === 0) {
        return;
    }

    function activate(id) {
        tabs.forEach(function (tab) {
            var isActive = tab.getAttribute('data-faq-tab') === id;
            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach(function (panel) {
            var isActive = panel.getAttribute('data-faq-panel') === id;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = !isActive;
        });
    }

    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () {
            activate(tab.getAttribute('data-faq-tab'));
        });

        tab.addEventListener('keydown', function (event) {
            if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') {
                return;
            }

            event.preventDefault();
            var step = event.key === 'ArrowRight' ? 1 : -1;
            var next = tabs[(index + step + tabs.length) % tabs.length];
            next.focus();
            activate(next.getAttribute('data-faq-tab'));
        });
    });
})();
