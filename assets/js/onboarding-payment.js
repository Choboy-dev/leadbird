(function () {
    'use strict';

    function initForm(form) {
        form.addEventListener('submit', function (event) {
            var bankSelect = form.querySelector('[data-payment-bank-select]');
            var accountNumber = form.querySelector('[data-payment-account-number]');
            var accountName = form.querySelector('[data-payment-account-name]');

            if (!bankSelect || bankSelect.value === '') {
                event.preventDefault();
                bankSelect.focus();
                return;
            }

            if (!accountNumber || accountNumber.value.trim().length !== 10) {
                event.preventDefault();
                accountNumber.focus();
                return;
            }

            if (!accountName || accountName.value.trim() === '') {
                event.preventDefault();
                accountName.focus();
            }
        });
    }

    document.querySelectorAll('[data-payment-form]').forEach(initForm);

    if (window.LeadBirdDropdown && typeof window.LeadBirdDropdown.init === 'function') {
        window.LeadBirdDropdown.init(document);
    }
})();
