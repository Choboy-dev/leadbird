(function () {
    var SEARCH_THRESHOLD = 8;
    var GAP = 16;
    var MIN_PANEL_HEIGHT = 140;

    var CHEVRON_SVG =
        '<svg class="lb-dropdown-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="m6 9 6 6 6-6"></path></svg>';

    var CHECK_SVG =
        '<span class="lb-dropdown-check" aria-hidden="true">' +
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">' +
        '<path d="M20 6 9 17l-5-5"></path></svg></span>';

    function getSelectedOption(select) {
        return select.options[select.selectedIndex] || null;
    }

    function isSearchable(select) {
        if (select.hasAttribute('data-no-search')) {
            return false;
        }
        if (select.hasAttribute('data-searchable')) {
            return true;
        }
        return select.options.length >= SEARCH_THRESHOLD;
    }

    function visibleOptions(menu) {
        return Array.prototype.filter.call(
            menu.querySelectorAll('.lb-dropdown-option'),
            function (btn) {
                return !btn.classList.contains('is-filtered-out');
            }
        );
    }

    function clearSearch(wrapper) {
        var search = wrapper.querySelector('.lb-dropdown-search');
        if (!search) {
            return;
        }
        search.value = '';
        filterOptions(wrapper, '');
    }

    function filterOptions(wrapper, query) {
        var menu = wrapper.querySelector('.lb-dropdown-menu');
        var empty = wrapper.querySelector('.lb-dropdown-empty');
        if (!menu) {
            return;
        }

        var q = String(query || '').trim().toLowerCase();
        var visibleCount = 0;

        menu.querySelectorAll('.lb-dropdown-option').forEach(function (optionBtn) {
            var label = (optionBtn.getAttribute('data-label') || optionBtn.textContent || '').toLowerCase();
            var match = q === '' || label.indexOf(q) !== -1;
            optionBtn.classList.toggle('is-filtered-out', !match);
            if (match) {
                visibleCount += 1;
            }
        });

        if (empty) {
            empty.classList.toggle('is-visible', visibleCount === 0);
            empty.hidden = visibleCount !== 0;
        }
    }

    function closeDropdown(wrapper) {
        wrapper.classList.remove('is-open');
        wrapper.classList.remove('is-drop-up');
        var trigger = wrapper.querySelector('.lb-dropdown-trigger');
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        }
        var panel = wrapper.querySelector('.lb-dropdown-panel');
        if (panel) {
            panel.style.maxHeight = '';
        }
        clearSearch(wrapper);
    }

    /**
     * The visible band for the panel. The viewport is not enough: a scrolling
     * column or a modal body clips the panel long before the window edge, so
     * intersect every ancestor that clips its overflow.
     */
    function visibleBounds(element) {
        var top = 0;
        var bottom = window.innerHeight;
        var node = element.parentElement;

        while (node && node !== document.documentElement) {
            var overflowY = window.getComputedStyle(node).overflowY;
            if (overflowY !== 'visible') {
                var rect = node.getBoundingClientRect();
                if (rect.top > top) {
                    top = rect.top;
                }
                if (rect.bottom < bottom) {
                    bottom = rect.bottom;
                }
            }
            node = node.parentElement;
        }

        return { top: top, bottom: bottom };
    }

    /**
     * The panel is absolutely positioned, so a trigger near the bottom of its
     * clipping container would push the panel out of sight. Flip it above the
     * trigger when there isn't room below, and cap its height to what is left.
     */
    function positionPanel(wrapper) {
        var trigger = wrapper.querySelector('.lb-dropdown-trigger');
        var panel = wrapper.querySelector('.lb-dropdown-panel');
        wrapper.classList.remove('is-drop-up');
        if (!trigger || !panel) {
            return;
        }

        panel.style.maxHeight = '';

        var rect = trigger.getBoundingClientRect();
        var bounds = visibleBounds(trigger);
        var spaceBelow = bounds.bottom - rect.bottom;
        var spaceAbove = rect.top - bounds.top;
        var panelHeight = panel.offsetHeight || 0;
        var dropUp = spaceBelow < panelHeight + GAP && spaceAbove > spaceBelow;

        if (dropUp) {
            wrapper.classList.add('is-drop-up');
        }

        var available = (dropUp ? spaceAbove : spaceBelow) - GAP;
        if (available < panelHeight && available > 0) {
            panel.style.maxHeight = Math.max(available, MIN_PANEL_HEIGHT) + 'px';
        }
    }

    function openDropdown(wrapper) {
        document.querySelectorAll('.lb-dropdown.is-open').forEach(function (other) {
            if (other !== wrapper) {
                closeDropdown(other);
            }
        });
        wrapper.classList.add('is-open');
        var trigger = wrapper.querySelector('.lb-dropdown-trigger');
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'true');
        }

        positionPanel(wrapper);

        var search = wrapper.querySelector('.lb-dropdown-search');
        var menu = wrapper.querySelector('.lb-dropdown-menu');
        window.requestAnimationFrame(function () {
            if (search) {
                search.focus();
                return;
            }
            if (!menu) {
                return;
            }
            var current =
                menu.querySelector('.lb-dropdown-option.is-selected:not(.is-filtered-out)') ||
                visibleOptions(menu)[0];
            if (current) {
                current.focus();
            }
        });
    }

    function syncDisabledState(wrapper, select) {
        var trigger = wrapper.querySelector('.lb-dropdown-trigger');
        if (!trigger) {
            return;
        }

        trigger.disabled = select.disabled;
        wrapper.classList.toggle('is-disabled', select.disabled);
    }

    function linkLabel(select) {
        var selectId = select.id || '';
        if (selectId === '') {
            return;
        }

        var linkedLabel = document.querySelector('label[for="' + CSS.escape(selectId) + '"]');
        if (linkedLabel && !linkedLabel.id) {
            linkedLabel.id = selectId + '-label';
        }
    }

    function setValue(wrapper, select, value) {
        select.value = value;
        var label = wrapper.querySelector('.lb-dropdown-value');
        var selectedOption = getSelectedOption(select);

        if (label && selectedOption) {
            label.textContent = selectedOption.textContent;
        }

        wrapper.querySelectorAll('.lb-dropdown-option').forEach(function (optionBtn) {
            var isSelected = optionBtn.getAttribute('data-value') === value;
            optionBtn.classList.toggle('is-selected', isSelected);
            optionBtn.setAttribute('aria-selected', isSelected ? 'true' : 'false');
        });

        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function rebuildOptions(wrapper, select) {
        var menu = wrapper.querySelector('.lb-dropdown-menu');
        if (!menu) {
            return;
        }

        menu.innerHTML = '';
        Array.prototype.forEach.call(select.options, function (option) {
            // A hidden option (e.g. a "pick one" placeholder) labels the trigger
            // but must not appear as a selectable row with a checkmark.
            if (option.hidden) {
                return;
            }

            var item = document.createElement('li');
            item.setAttribute('role', 'none');

            var optionBtn = document.createElement('button');
            optionBtn.type = 'button';
            optionBtn.className = 'lb-dropdown-option';
            optionBtn.setAttribute('role', 'option');
            optionBtn.setAttribute('data-value', option.value);
            optionBtn.setAttribute('data-label', option.textContent);
            optionBtn.setAttribute('aria-selected', option.selected ? 'true' : 'false');
            if (option.selected) {
                optionBtn.classList.add('is-selected');
            }
            if (option.disabled) {
                optionBtn.classList.add('is-option-disabled');
                optionBtn.setAttribute('aria-disabled', 'true');
                if (option.title) {
                    optionBtn.title = option.title;
                }
            }
            optionBtn.innerHTML = CHECK_SVG + option.textContent;

            optionBtn.addEventListener('click', function () {
                if (option.disabled) {
                    return;
                }
                setValue(wrapper, select, option.value);
                closeDropdown(wrapper);
                var trigger = wrapper.querySelector('.lb-dropdown-trigger');
                if (trigger) {
                    trigger.focus();
                }
            });

            item.appendChild(optionBtn);
            menu.appendChild(item);
        });

        var selectedOption = getSelectedOption(select);
        var valueLabel = wrapper.querySelector('.lb-dropdown-value');
        if (valueLabel && selectedOption) {
            valueLabel.textContent = selectedOption.textContent;
        }

        var search = wrapper.querySelector('.lb-dropdown-search');
        filterOptions(wrapper, search ? search.value : '');
    }

    function buildDropdown(select) {
        if (select.dataset.lbDropdownInit === 'true') {
            return select.closest('.lb-dropdown');
        }

        select.dataset.lbDropdownInit = 'true';
        linkLabel(select);

        var wrapper = document.createElement('div');
        var selectClasses = select.className.trim();
        wrapper.className = 'lb-dropdown' + (selectClasses ? ' ' + selectClasses : '');
        if (isSearchable(select)) {
            wrapper.classList.add('lb-dropdown--searchable');
        }

        var selectId = select.id || '';
        var listboxId = selectId !== '' ? selectId + '-listbox' : 'lb-dropdown-' + Math.random().toString(36).slice(2, 9);
        var searchId = selectId !== '' ? selectId + '-search' : listboxId + '-search';

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        select.className = 'lb-dropdown-native';
        select.tabIndex = -1;

        var selectedOption = getSelectedOption(select);

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'lb-dropdown-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        if (selectId !== '') {
            trigger.id = selectId + '-trigger';
            trigger.setAttribute('aria-labelledby', selectId + '-label ' + trigger.id);
        }
        trigger.setAttribute('aria-controls', listboxId);
        trigger.innerHTML =
            '<span class="lb-dropdown-value">' +
            (selectedOption ? selectedOption.textContent : '') +
            '</span>' +
            CHEVRON_SVG;

        var panel = document.createElement('div');
        panel.className = 'lb-dropdown-panel';

        if (isSearchable(select)) {
            var searchWrap = document.createElement('div');
            searchWrap.className = 'lb-dropdown-search-wrap';

            var search = document.createElement('input');
            search.type = 'search';
            search.className = 'lb-dropdown-search';
            search.id = searchId;
            search.setAttribute('autocomplete', 'off');
            search.setAttribute('spellcheck', 'false');
            search.setAttribute('placeholder', 'Search…');
            search.setAttribute('aria-label', 'Search options');
            search.setAttribute('aria-controls', listboxId);

            search.addEventListener('input', function () {
                filterOptions(wrapper, search.value);
            });

            search.addEventListener('keydown', function (event) {
                var options = visibleOptions(wrapper.querySelector('.lb-dropdown-menu'));
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeDropdown(wrapper);
                    trigger.focus();
                    return;
                }
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    if (options[0]) {
                        options[0].focus();
                    }
                    return;
                }
                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (options.length === 1) {
                        options[0].click();
                    }
                }
            });

            // Keep panel open when interacting with search
            search.addEventListener('click', function (event) {
                event.stopPropagation();
            });

            searchWrap.appendChild(search);
            panel.appendChild(searchWrap);
        }

        var menu = document.createElement('ul');
        menu.className = 'lb-dropdown-menu';
        menu.id = listboxId;
        menu.setAttribute('role', 'listbox');
        if (select.required) {
            menu.setAttribute('aria-required', 'true');
        }

        var empty = document.createElement('p');
        empty.className = 'lb-dropdown-empty';
        empty.setAttribute('role', 'status');
        empty.hidden = true;
        empty.textContent = 'No matches';

        panel.appendChild(menu);
        panel.appendChild(empty);
        wrapper.appendChild(trigger);
        wrapper.appendChild(panel);
        rebuildOptions(wrapper, select);

        trigger.addEventListener('click', function () {
            if (trigger.disabled) {
                return;
            }
            if (wrapper.classList.contains('is-open')) {
                closeDropdown(wrapper);
            } else {
                openDropdown(wrapper);
            }
        });

        trigger.addEventListener('keydown', function (event) {
            if (trigger.disabled) {
                return;
            }
            if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openDropdown(wrapper);
            }
        });

        menu.addEventListener('keydown', function (event) {
            var options = visibleOptions(menu);
            var index = options.indexOf(document.activeElement);

            if (event.key === 'Escape') {
                event.preventDefault();
                closeDropdown(wrapper);
                trigger.focus();
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                var next = options[Math.min(index + 1, options.length - 1)];
                if (next) {
                    next.focus();
                }
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (index <= 0) {
                    var searchInput = wrapper.querySelector('.lb-dropdown-search');
                    if (searchInput) {
                        searchInput.focus();
                    }
                    return;
                }
                var prev = options[Math.max(index - 1, 0)];
                if (prev) {
                    prev.focus();
                }
                return;
            }

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                if (document.activeElement && document.activeElement.classList.contains('lb-dropdown-option')) {
                    document.activeElement.click();
                }
            }
        });

        var observer = new MutationObserver(function () {
            syncDisabledState(wrapper, select);
        });
        observer.observe(select, { attributes: true, attributeFilter: ['disabled'] });
        syncDisabledState(wrapper, select);

        return wrapper;
    }

    function initDropdowns(root) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('select:not(.lb-dropdown-native):not([data-native-select])').forEach(buildDropdown);
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.lb-dropdown')) {
            document.querySelectorAll('.lb-dropdown.is-open').forEach(closeDropdown);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.lb-dropdown.is-open').forEach(closeDropdown);
        }
    });

    window.LeadBirdDropdown = {
        init: initDropdowns,
        build: buildDropdown,
        refresh: rebuildOptions,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initDropdowns(document);
        });
    } else {
        initDropdowns(document);
    }
})();
