(function () {
    'use strict';

    var root = document.querySelector('[data-wt-root]');
    if (!root) {
        return;
    }

    var rows = Array.prototype.slice.call(root.querySelectorAll('[data-wt-row]'));
    if (!rows.length) {
        return;
    }

    var endpoint = root.getAttribute('data-endpoint');
    var filters = root.querySelectorAll('[data-wt-filter]');
    var search = root.querySelector('[data-wt-search]');
    var sizeSelect = root.querySelector('[data-wt-size]');
    var count = root.querySelector('[data-wt-count]');
    var none = root.querySelector('[data-wt-none]');
    var pager = root.querySelector('[data-wt-pager]');
    var prev = root.querySelector('[data-wt-prev]');
    var next = root.querySelector('[data-wt-next]');
    var menu = root.querySelector('[data-wt-menu-pop]');
    document.body.appendChild(menu);
    var page = 0;
    var DAY = 86400;

    function matches(row) {
        var now = Date.now() / 1000;
        for (var i = 0; i < filters.length; i++) {
            var key = filters[i].getAttribute('data-wt-filter');
            var value = filters[i].value;
            if (!value) {
                continue;
            }
            if (key === 'updated') {
                if (now - Number(row.getAttribute('data-updated')) > Number(value) * DAY) {
                    return false;
                }
            } else if (row.getAttribute('data-' + key) !== value) {
                return false;
            }
        }
        var term = search.value.trim().toLowerCase();
        return !term || row.getAttribute('data-search').indexOf(term) !== -1;
    }

    function render() {
        var size = Number(sizeSelect.value) || 50;
        var visible = rows.filter(matches);
        var pages = Math.max(1, Math.ceil(visible.length / size));
        page = Math.min(page, pages - 1);
        var start = page * size;
        var end = Math.min(start + size, visible.length);

        rows.forEach(function (row) {
            row.hidden = true;
        });
        visible.slice(start, end).forEach(function (row) {
            row.hidden = false;
        });

        none.hidden = visible.length > 0;
        count.textContent = visible.length
            ? (start + 1) + '–' + end + ' of ' + visible.length + (visible.length === 1 ? ' template' : ' templates')
            : '0 templates';
        pager.hidden = pages < 2;
        prev.disabled = page === 0;
        next.disabled = page >= pages - 1;
    }

    function resetAndRender() {
        page = 0;
        render();
    }

    Array.prototype.forEach.call(filters, function (select) {
        select.addEventListener('change', resetAndRender);
    });
    sizeSelect.addEventListener('change', resetAndRender);
    search.addEventListener('input', resetAndRender);
    prev.addEventListener('click', function () {
        page = Math.max(0, page - 1);
        render();
    });
    next.addEventListener('click', function () {
        page += 1;
        render();
    });
    root.querySelector('[data-wt-clear]').addEventListener('click', function () {
        Array.prototype.forEach.call(filters, function (select) {
            select.value = '';
            var wrapper = select.closest('.lb-dropdown');
            if (wrapper && window.LeadBirdDropdown) {
                window.LeadBirdDropdown.refresh(wrapper, select);
            }
        });
        search.value = '';
        resetAndRender();
    });

    // Approval tooltips and the row menu float above the page so the scrolling table never clips them.
    var tooltip = document.createElement('div');
    tooltip.className = 'wt-tooltip';
    tooltip.setAttribute('aria-hidden', 'true');
    tooltip.hidden = true;
    document.body.appendChild(tooltip);

    function showTip(pill) {
        var tip = pill.parentNode.querySelector('.wt-tip');
        if (!tip) {
            return;
        }
        tooltip.textContent = tip.textContent;
        tooltip.hidden = false;
        var rect = pill.getBoundingClientRect();
        var width = tooltip.offsetWidth;
        var height = tooltip.offsetHeight;
        var center = rect.left + rect.width / 2;
        var left = Math.min(Math.max(8, center - width / 2), window.innerWidth - width - 8);
        var top = rect.top - height - 10;
        var side = 'top';
        if (top < 8) {
            top = rect.bottom + 10;
            side = 'bottom';
        }
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
        tooltip.style.setProperty('--wt-arrow', (center - left) + 'px');
        tooltip.setAttribute('data-side', side);
    }

    function hideTip() {
        tooltip.hidden = true;
    }

    root.addEventListener('mouseover', function (event) {
        var pill = event.target.closest('.wt-pill-wrap .ws-status');
        if (pill) {
            showTip(pill);
        }
    });
    root.addEventListener('mouseout', function (event) {
        if (event.target.closest('.wt-pill-wrap .ws-status')) {
            hideTip();
        }
    });
    root.addEventListener('focusin', function (event) {
        var pill = event.target.closest('.wt-pill-wrap .ws-status');
        if (pill) {
            showTip(pill);
        }
    });
    root.addEventListener('focusout', hideTip);
    window.addEventListener('scroll', hideTip, true);


    var activeRow = null;
    var activeButton = null;

    function closeMenu(returnFocus) {
        if (menu.hidden) {
            return;
        }
        menu.hidden = true;
        if (activeButton) {
            activeButton.setAttribute('aria-expanded', 'false');
            if (returnFocus) {
                activeButton.focus();
            }
        }
        activeRow = null;
        activeButton = null;
    }

    function openMenu(button) {
        activeButton = button;
        activeRow = button.closest('[data-wt-row]');
        button.setAttribute('aria-expanded', 'true');
        var starter = activeRow.getAttribute('data-starter') === '1';
        menu.querySelectorAll('[data-wt-real-only]').forEach(function (item) {
            item.hidden = starter;
        });
        menu.hidden = false;

        var rect = button.getBoundingClientRect();
        var width = menu.offsetWidth;
        var height = menu.offsetHeight;
        var top = rect.bottom + 6;
        if (top + height > window.innerHeight - 8) {
            top = Math.max(8, rect.top - height - 6);
        }
        menu.style.top = top + 'px';
        menu.style.left = Math.max(8, rect.right - width) + 'px';

        var first = menu.querySelector('[role="menuitem"]:not([hidden])');
        if (first) {
            first.focus({ preventScroll: true });
        }
    }

    root.addEventListener('click', function (event) {
        var button = event.target.closest('[data-wt-menu]');
        if (!button) {
            return;
        }
        event.stopPropagation();
        var reopen = activeButton !== button;
        closeMenu(false);
        if (reopen) {
            openMenu(button);
        }
    });

    document.addEventListener('click', function (event) {
        if (!menu.contains(event.target)) {
            closeMenu(false);
        }
    });
    window.addEventListener('resize', function () {
        closeMenu(false);
    });
    window.addEventListener('scroll', function () {
        closeMenu(false);
    }, true);

    menu.addEventListener('keydown', function (event) {
        var items = Array.prototype.filter.call(menu.querySelectorAll('[role="menuitem"]'), function (item) {
            return !item.hidden;
        });
        var index = items.indexOf(document.activeElement);
        if (event.key === 'Escape') {
            event.preventDefault();
            closeMenu(true);
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            items[(index + 1) % items.length].focus();
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            items[(index - 1 + items.length) % items.length].focus();
        } else if (event.key === 'Tab') {
            closeMenu(false);
        }
    });

    function copy(text, item) {
        var label = item.textContent;
        var done = function () {
            item.textContent = 'Copied';
            setTimeout(function () {
                item.textContent = label;
                closeMenu(true);
            }, 900);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, function () {
                closeMenu(true);
            });
        } else {
            closeMenu(true);
        }
    }

    function removeRow(row) {
        rows.splice(rows.indexOf(row), 1);
        row.remove();
        if (!rows.length) {
            window.location.reload();
            return;
        }
        render();
    }

    function deleteTemplate(row) {
        var name = row.getAttribute('data-name');
        window.LeadBirdModal.confirm({
            title: 'Delete this template?',
            message: row.getAttribute('data-approval') === 'draft'
                ? '"' + name + '" hasn’t been sent to WhatsApp, so it is only removed from Lead Bird. This can’t be undone.'
                : 'This deletes "' + name + '" from WhatsApp too. You can’t make a new template with the same name for 30 days.',
            confirmLabel: 'Delete',
            cancelLabel: 'Cancel',
            tone: 'danger',
        }).then(function (ok) {
            if (!ok) {
                return;
            }
            if (root.getAttribute('data-preview') === '1') {
                removeRow(row);
                return;
            }
            fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ action: 'delete_template', id: row.getAttribute('data-id') }),
            })
                .then(function (res) {
                    return res.json().catch(function () {
                        return { ok: false };
                    });
                })
                .then(function (body) {
                    if (body && body.ok) {
                        removeRow(row);
                        return;
                    }
                    window.LeadBirdModal.alert({
                        title: 'Template not deleted',
                        message: (body && body.message) || 'Something went wrong. Please try again.',
                        tone: 'error',
                    });
                })
                .catch(function () {
                    window.LeadBirdModal.alert({
                        title: 'Template not deleted',
                        message: 'Check your internet connection and try again.',
                        tone: 'error',
                    });
                });
        });
    }

    menu.addEventListener('click', function (event) {
        var item = event.target.closest('[data-wt-act]');
        if (!item || !activeRow) {
            if (event.target.closest('a')) {
                closeMenu(false);
            }
            return;
        }
        var row = activeRow;
        var action = item.getAttribute('data-wt-act');
        if (action === 'open') {
            window.location.href = row.getAttribute('data-url');
        } else if (action === 'copy-name') {
            copy(row.getAttribute('data-name'), item);
        } else if (action === 'copy-id') {
            copy(row.getAttribute('data-ref'), item);
        } else if (action === 'delete') {
            closeMenu(false);
            deleteTemplate(row);
        }
    });

    render();
})();
