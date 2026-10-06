(function () {
    'use strict';

    var page = document.querySelector('[data-catalog-page]');
    if (!page) {
        return;
    }

    var modal = document.querySelector('[data-offer-modal]');
    var searchInput = page.querySelector('[data-catalog-search]');
    var cards = page.querySelectorAll('[data-catalog-card]');
    var noResults = page.querySelector('[data-catalog-no-results]');
    var openTriggers = document.querySelectorAll('button[data-offer-modal-open]');
    var closeTriggers = document.querySelectorAll('[data-offer-modal-close]');
    var offerForm = document.getElementById('offer-modal-form');
    var editForm = document.getElementById('offers-edit-form');
    var isEditPage = page.hasAttribute('data-offer-edit-page');
    var lastFocusedElement = null;

    function openModal() {
        if (!(modal instanceof HTMLElement)) {
            return;
        }

        lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        var closeBtn = modal.querySelector('.lb-offer-modal-close');
        if (closeBtn instanceof HTMLElement) {
            closeBtn.focus();
        }

        if (window.LeadBirdDropdown && typeof window.LeadBirdDropdown.init === 'function') {
            window.LeadBirdDropdown.init(modal);
        }

        setupOfferingImagePreview(modal || document);
    }

    function closeModal() {
        if (!(modal instanceof HTMLElement)) {
            return;
        }

        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';

        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }

        if (window.location.search !== '') {
            var params = new URLSearchParams(window.location.search);
            if (params.get('new') === '1') {
                window.history.replaceState({}, '', window.location.pathname);
            }
        }
    }

    openTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            var params = new URLSearchParams(window.location.search);
            if (params.has('edit')) {
                window.location.href = window.location.pathname + '?new=1';
                return;
            }
            openModal();
        });
    });

    closeTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal instanceof HTMLElement && !modal.hidden) {
            closeModal();
        }
    });

    if (!isEditPage && page.getAttribute('data-offer-modal-autoload') === 'true') {
        openModal();
    }

    if (offerForm) {
        offerForm.addEventListener('lb-offer-modal-close', closeModal);
    }

    function applySearch() {
        var query = searchInput instanceof HTMLInputElement ? searchInput.value.trim().toLowerCase() : '';
        var visibleCount = 0;

        cards.forEach(function (card) {
            if (!(card instanceof HTMLElement)) {
                return;
            }

            var haystack = card.getAttribute('data-search') || '';
            var visible = query === '' || haystack.indexOf(query) !== -1;
            card.hidden = !visible;
            if (visible) {
                visibleCount += 1;
            }
        });

        if (noResults instanceof HTMLElement) {
            var showEmpty = cards.length > 0 && visibleCount === 0;
            noResults.hidden = !showEmpty;
            noResults.classList.toggle('is-hidden', !showEmpty);
        }
    }

    if (searchInput instanceof HTMLInputElement) {
        searchInput.addEventListener('input', applySearch);
    }

    document.querySelectorAll('[data-confirm-delete]').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            var form = btn.closest('form');

            window.LeadBirdModal.confirm({
                title: 'Remove this offer?',
                message: 'This cannot be undone.',
                confirmLabel: 'Remove',
                cancelLabel: 'Cancel',
                tone: 'danger',
            }).then(function (ok) {
                if (ok && form) {
                    form.submit();
                }
            });
        });
    });

    document.querySelectorAll('[data-catalog-menu]').forEach(function (menuRoot) {
        var trigger = menuRoot.querySelector('[data-catalog-menu-trigger]');
        var panel = menuRoot.querySelector('[data-catalog-menu-panel]');
        if (!(trigger instanceof HTMLButtonElement) || !(panel instanceof HTMLElement)) {
            return;
        }

        trigger.addEventListener('click', function (event) {
            event.stopPropagation();
            var isOpen = trigger.getAttribute('aria-expanded') === 'true';

            document.querySelectorAll('[data-catalog-menu-trigger][aria-expanded="true"]').forEach(function (other) {
                if (other !== trigger) {
                    other.setAttribute('aria-expanded', 'false');
                    var otherPanel = other.closest('[data-catalog-menu]');
                    var otherMenu = otherPanel ? otherPanel.querySelector('[data-catalog-menu-panel]') : null;
                    if (otherMenu instanceof HTMLElement) {
                        otherMenu.hidden = true;
                    }
                }
            });

            trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            panel.hidden = isOpen;
        });
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('[data-catalog-menu-trigger][aria-expanded="true"]').forEach(function (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
            var menuRoot = trigger.closest('[data-catalog-menu]');
            var panel = menuRoot ? menuRoot.querySelector('[data-catalog-menu-panel]') : null;
            if (panel instanceof HTMLElement) {
                panel.hidden = true;
            }
        });
    });

    function setupOfferingImagePreview(root) {
        var scope = root || document;
        var field = scope.querySelector('[data-offering-image-field]');
        if (!field || field.getAttribute('data-offer-image-init') === 'true') {
            return;
        }

        field.setAttribute('data-offer-image-init', 'true');

        var input = field.querySelector('[data-offering-image-input]');
        var preview = field.querySelector('[data-offering-image-preview]');
        var image = field.querySelector('[data-offering-image-preview-img]');
        var empty = field.querySelector('[data-offering-image-empty]');
        var status = field.querySelector('[data-offering-image-status]');
        var remove = field.querySelector('[data-offering-image-remove]');
        var originalSrc = image && image.getAttribute('src') ? image.getAttribute('src') : '';
        var objectUrl = '';

        if (!input || !preview || !image || !empty || !status) {
            return;
        }

        function revokeObjectUrl() {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = '';
            }
        }

        function showEmpty(message) {
            preview.classList.remove('has-image');
            image.classList.add('is-hidden');
            empty.classList.remove('is-hidden');
            if (message) {
                status.textContent = message;
            }
        }

        function showImage(src, message) {
            preview.classList.add('has-image');
            image.src = src;
            image.classList.remove('is-hidden');
            empty.classList.add('is-hidden');
            status.textContent = message;
        }

        function syncPreview() {
            var file = input.files && input.files[0] ? input.files[0] : null;

            if (file) {
                revokeObjectUrl();
                objectUrl = URL.createObjectURL(file);
                showImage(objectUrl, 'Selected photo: ' + file.name);
                return;
            }

            revokeObjectUrl();

            if (remove && remove.checked && originalSrc) {
                showEmpty('Current photo will be removed when you save.');
                return;
            }

            if (originalSrc) {
                showImage(originalSrc, 'Current photo is ready. Upload a new image to replace it.');
                return;
            }

            showEmpty('No photo selected yet.');
        }

        input.addEventListener('change', syncPreview);
        if (remove) {
            remove.addEventListener('change', syncPreview);
        }

        syncPreview();
    }

    setupOfferingImagePreview(isEditPage && editForm ? editForm : (modal || document));
})();
