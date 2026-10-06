(function () {
    'use strict';

    document.querySelectorAll('[data-admin-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            var form = btn.closest('form');
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            var title = btn.getAttribute('data-admin-confirm-title') || 'Are you sure?';
            var message = btn.getAttribute('data-admin-confirm') || '';
            var confirmLabel = btn.getAttribute('data-admin-confirm-label') || 'Confirm';
            var tone = btn.getAttribute('data-admin-confirm-tone') || 'danger';

            window.LeadBirdModal.confirm({
                title: title,
                message: message,
                confirmLabel: confirmLabel,
                cancelLabel: 'Cancel',
                tone: tone,
            }).then(function (ok) {
                if (ok) {
                    form.submit();
                }
            });
        });
    });
})();
