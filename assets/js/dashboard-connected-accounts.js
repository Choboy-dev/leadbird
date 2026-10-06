(function () {
    'use strict';

    var page = document.querySelector('.conn-page');
    if (!page) {
        return;
    }

    var overlay = document.querySelector('[data-conn-add-overlay]');
    var openModalBtn = page.querySelector('[data-conn-open-add-modal]');
    var closeModalBtns = document.querySelectorAll('[data-conn-close-add-modal]');
    var searchInput = page.querySelector('[data-conn-search]');
    var networkFilter = page.querySelector('[data-conn-filter-network]');
    var statusFilter = page.querySelector('[data-conn-filter-status]');
    var rows = page.querySelectorAll('[data-conn-row]');
    var noResultsRow = page.querySelector('[data-conn-no-results]');

    function openAddModal() {
        if (!(overlay instanceof HTMLElement)) {
            return;
        }
        overlay.classList.remove('is-hidden');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeAddModal() {
        if (!(overlay instanceof HTMLElement)) {
            return;
        }
        overlay.classList.add('is-hidden');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (openModalBtn instanceof HTMLButtonElement) {
        openModalBtn.addEventListener('click', openAddModal);
    }

    closeModalBtns.forEach(function (btn) {
        btn.addEventListener('click', closeAddModal);
    });

    if (overlay instanceof HTMLElement) {
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closeAddModal();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && overlay instanceof HTMLElement && !overlay.classList.contains('is-hidden')) {
            closeAddModal();
        }
    });

    function applyFilters() {
        var query = searchInput instanceof HTMLInputElement ? searchInput.value.trim().toLowerCase() : '';
        var network = networkFilter instanceof HTMLSelectElement ? networkFilter.value : '';
        var status = statusFilter instanceof HTMLSelectElement ? statusFilter.value : '';
        var visibleCount = 0;

        rows.forEach(function (row) {
            if (!(row instanceof HTMLElement)) {
                return;
            }

            var haystack = row.getAttribute('data-search') || '';
            var rowPlatformsRaw = row.getAttribute('data-platforms') || row.getAttribute('data-platform') || '';
            var rowPlatforms = rowPlatformsRaw === '' ? [] : rowPlatformsRaw.split(',').filter(Boolean);
            var rowStatus = row.getAttribute('data-status') || '';
            var matchesSearch = query === '' || haystack.indexOf(query) !== -1;
            var matchesNetwork = network === ''
                || (network === 'none' && rowPlatforms.length === 0)
                || rowPlatforms.indexOf(network) !== -1;
            var matchesStatus = status === '' || rowStatus === status;
            var visible = matchesSearch && matchesNetwork && matchesStatus;

            row.hidden = !visible;
            if (visible) {
                visibleCount += 1;
            }
        });

        if (noResultsRow instanceof HTMLElement) {
            var showNoResults = rows.length > 0 && visibleCount === 0;
            noResultsRow.hidden = !showNoResults;
            noResultsRow.classList.toggle('is-hidden', !showNoResults);
        }
    }

    if (searchInput instanceof HTMLInputElement) {
        searchInput.addEventListener('input', applyFilters);
    }

    if (networkFilter instanceof HTMLSelectElement) {
        networkFilter.addEventListener('change', applyFilters);
    }

    if (statusFilter instanceof HTMLSelectElement) {
        statusFilter.addEventListener('change', applyFilters);
    }

    if (window.LeadBirdDropdown && typeof window.LeadBirdDropdown.init === 'function') {
        window.LeadBirdDropdown.init(page);
        if (overlay instanceof HTMLElement) {
            window.LeadBirdDropdown.init(overlay);
        }
    }

    var businessSelect = document.querySelector('[data-conn-business-select]');
    var businessForm = document.querySelector('[data-conn-business-form]');
    if (businessSelect instanceof HTMLSelectElement && businessForm instanceof HTMLFormElement) {
        businessSelect.addEventListener('change', function () {
            businessForm.submit();
        });
    }

    function resetActionsMenu(menu) {
        if (!(menu instanceof HTMLElement)) {
            return;
        }
        menu.hidden = true;
        menu.classList.remove('is-fixed');
        menu.style.top = '';
        menu.style.right = '';
        menu.style.left = '';
        menu.style.visibility = '';
        menu.style.pointerEvents = '';
    }

    function positionActionsMenu(trigger, menu) {
        var gap = 4;
        var viewportPadding = 8;

        menu.classList.add('is-fixed');
        menu.hidden = false;
        menu.style.visibility = 'hidden';
        menu.style.pointerEvents = 'none';

        var triggerRect = trigger.getBoundingClientRect();
        var menuHeight = menu.offsetHeight;
        var menuWidth = menu.offsetWidth;
        var spaceBelow = window.innerHeight - triggerRect.bottom;
        var spaceAbove = triggerRect.top;
        var openUp = spaceBelow < menuHeight + gap && spaceAbove > spaceBelow;

        if (openUp) {
            menu.style.top = Math.max(viewportPadding, triggerRect.top - menuHeight - gap) + 'px';
        } else {
            menu.style.top = Math.min(
                window.innerHeight - menuHeight - viewportPadding,
                triggerRect.bottom + gap
            ) + 'px';
        }

        var right = window.innerWidth - triggerRect.right;
        if (right + menuWidth > window.innerWidth - viewportPadding) {
            menu.style.left = viewportPadding + 'px';
            menu.style.right = 'auto';
        } else {
            menu.style.left = 'auto';
            menu.style.right = Math.max(viewportPadding, right) + 'px';
        }

        menu.style.visibility = '';
        menu.style.pointerEvents = '';
    }

    function closeAllActionMenus() {
        page.querySelectorAll('[data-conn-actions].is-open').forEach(function (actions) {
            actions.classList.remove('is-open');
            var trigger = actions.querySelector('[data-conn-actions-trigger]');
            var menu = actions.querySelector('[data-conn-actions-menu]');
            if (trigger instanceof HTMLButtonElement) {
                trigger.setAttribute('aria-expanded', 'false');
            }
            resetActionsMenu(menu);
        });
    }

    page.querySelectorAll('[data-conn-actions-menu]').forEach(function (menu) {
        menu.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });

    page.querySelectorAll('[data-conn-actions-trigger]').forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.stopPropagation();
            if (!(trigger instanceof HTMLButtonElement)) {
                return;
            }

            var actions = trigger.closest('[data-conn-actions]');
            if (!(actions instanceof HTMLElement)) {
                return;
            }

            var menu = actions.querySelector('[data-conn-actions-menu]');
            var isOpen = actions.classList.contains('is-open');

            closeAllActionMenus();

            if (!isOpen && menu instanceof HTMLElement) {
                actions.classList.add('is-open');
                positionActionsMenu(trigger, menu);
                trigger.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.addEventListener('click', function () {
        closeAllActionMenus();
    });

    window.addEventListener('resize', closeAllActionMenus);
    window.addEventListener('scroll', closeAllActionMenus, true);

    page.querySelectorAll('[data-conn-avatar-img]').forEach(function (img) {
        img.addEventListener('error', function () {
            if (!(img instanceof HTMLImageElement)) {
                return;
            }
            img.classList.add('is-hidden');
            var fallback = img.nextElementSibling;
            if (fallback instanceof HTMLElement) {
                fallback.classList.remove('is-hidden');
            }
        });
    });

    page.querySelectorAll('[data-confirm-disconnect]').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var form = btn.closest('form');

            window.LeadBirdModal.confirm({
                title: 'Disconnect this account?',
                message: 'We will stop replying on this channel.',
                confirmLabel: 'Disconnect',
                cancelLabel: 'Cancel',
                tone: 'danger',
            }).then(function (ok) {
                if (ok && form) {
                    form.submit();
                }
            });
        });
    });

    page.querySelectorAll('[data-confirm-delete-business]').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var form = btn.closest('form');
            var businessName = btn.getAttribute('data-business-name') || 'this business';

            window.LeadBirdModal.confirm({
                title: 'Delete this business?',
                message: 'This permanently removes ' + businessName + ' and all connected channels, messages, customers, and AI knowledge. This cannot be undone.',
                confirmLabel: 'Delete',
                cancelLabel: 'Cancel',
                tone: 'danger',
            }).then(function (ok) {
                if (ok && form) {
                    form.submit();
                }
            });
        });
    });
})();
