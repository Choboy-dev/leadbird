(function () {
    'use strict';

    function initDropdowns(root) {
        if (window.LeadBirdDropdown && typeof window.LeadBirdDropdown.init === 'function') {
            window.LeadBirdDropdown.init(root || document);
        }
    }

    function initSettingsModals() {
        var overlay = document.querySelector('[data-settings-overlay]');
        if (!overlay) {
            return;
        }

        var modals = overlay.querySelectorAll('[data-settings-modal]');
        var activeModal = null;
        var lastFocused = null;
        var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function getModal(name) {
            if (!name) {
                return null;
            }

            return overlay.querySelector('[data-settings-modal="' + name + '"]');
        }

        function showModal(name) {
            var modal = getModal(name);
            if (!modal) {
                return;
            }

            if (!activeModal) {
                lastFocused = document.activeElement;
            }

            modals.forEach(function (item) {
                item.classList.toggle('is-active', item === modal);
                item.setAttribute('aria-hidden', item === modal ? 'false' : 'true');
            });

            activeModal = modal;
            overlay.classList.remove('is-hidden');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('settings-modal-open');

            if (name === 'payment') {
                initDropdowns(modal);
            }

            window.requestAnimationFrame(function () {
                var focusTarget = modal.querySelector('input, select, textarea, button:not([data-settings-close])');
                if (focusTarget && typeof focusTarget.focus === 'function') {
                    focusTarget.focus();
                }
            });
        }

        function closeModal() {
            overlay.classList.add('is-hidden');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('settings-modal-open');

            modals.forEach(function (item) {
                item.classList.remove('is-active');
                item.setAttribute('aria-hidden', 'true');
            });

            activeModal = null;

            if (lastFocused && typeof lastFocused.focus === 'function') {
                lastFocused.focus();
            }
        }

        document.addEventListener('click', function (event) {
            var openButton = event.target.closest('[data-settings-open]');
            if (openButton) {
                event.preventDefault();
                showModal(openButton.getAttribute('data-settings-open'));
                return;
            }

            var closeButton = event.target.closest('[data-settings-close]');
            if (closeButton && overlay.contains(closeButton)) {
                event.preventDefault();
                closeModal();
            }
        });

        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && activeModal) {
                event.preventDefault();
                closeModal();
                return;
            }

            if (event.key !== 'Tab' || !activeModal) {
                return;
            }

            var focusable = activeModal.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            if (!focusable.length) {
                return;
            }

            var first = focusable[0];
            var last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });

        if (!prefersReducedMotion) {
            modals.forEach(function (modal) {
                modal.classList.add('settings-modal--animated');
            });
        }
    }

    function initDeleteForm() {
        var CONFIRM_WORD = 'Delete';
        var input = document.querySelector('[data-delete-confirm-input]');
        var submit = document.querySelector('[data-delete-confirm-submit]');
        var form = document.querySelector('[data-delete-form]');

        if (!input || !submit || !form) {
            return;
        }

        function syncSubmitState() {
            submit.disabled = input.value.trim() !== CONFIRM_WORD;
        }

        input.addEventListener('input', syncSubmitState);

        form.addEventListener('submit', function (event) {
            if (input.value.trim() !== CONFIRM_WORD) {
                event.preventDefault();
                syncSubmitState();
            }
        });
    }

    function init() {
        initSettingsModals();
        initDeleteForm();
        initDropdowns(document);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
