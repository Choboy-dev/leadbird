(function () {
    'use strict';

    var DEFAULT_TITLE = 'Let’s get you the answers you need.';
    var brandTitle = document.title.replace(/^.*\|\s*/, '');

    var $ = function (id) { return document.getElementById(id); };
    var grid = $('hcGrid');
    var form = $('hcSearch');
    var input = $('search-input');

    /* Search (topic index only) */
    function render(query) {
        var q = query.trim();
        var needle = q.toLowerCase();
        var cards = grid.querySelectorAll('.hc-card');
        var count = 0;

        cards.forEach(function (card) {
            var match = needle === '' || (card.getAttribute('data-hc-search') || '').indexOf(needle) !== -1;
            card.hidden = !match;
            if (match) count += 1;
        });

        document.title = (q ? 'Search results for "' + q + '" | ' : '') + brandTitle;
        $('hcTitle').textContent = q ? 'Search results' : DEFAULT_TITLE;
        input.value = q;

        $('hcBanner').hidden = q !== '';
        $('hcEmpty').hidden = !(q && count === 0);
        $('hcCount').hidden = !(q && count > 0);
        document.querySelectorAll('[data-hc-q]').forEach(function (el) { el.textContent = q; });
        document.querySelector('[data-hc-n]').textContent = String(count);
        document.querySelector('[data-hc-unit]').textContent = count === 1 ? 'topic' : 'topics';
        grid.hidden = count === 0;
    }

    function currentQuery() {
        return new URLSearchParams(location.search).get('q') || '';
    }

    if (grid && form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var q = input.value.trim();
            var url = new URL(location.href);
            if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
            history.pushState(null, '', url);
            render(q);
        });

        window.addEventListener('popstate', function () { render(currentQuery()); });

        document.querySelectorAll('[data-hc-home]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                history.pushState(null, '', location.pathname);
                render('');
                window.scrollTo(0, 0);
            });
        });
    }

    /* Ask Hannah: open the site assistant, fall back to the contact link */
    document.querySelectorAll('[data-hc-ask-hannah]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            var launcher = document.querySelector('[data-lb-assistant-launcher]');
            if (!launcher) return;
            e.preventDefault();
            var panel = document.querySelector('[data-lb-assistant-panel]');
            if (!panel || panel.classList.contains('is-hidden')) launcher.click();
        });
    });

    /* Mobile menu */
    var menu = $('hcMenu');
    var openBtn = document.querySelector('.hc-burger');
    var closeBtn = menu ? menu.querySelector('.hc-menu-close') : null;

    function setOpen(open) {
        menu.hidden = !open;
        openBtn.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';
        (open ? closeBtn : openBtn).focus();
    }

    if (menu && openBtn && closeBtn) {
        openBtn.addEventListener('click', function () { setOpen(true); });
        closeBtn.addEventListener('click', function () { setOpen(false); });
        menu.addEventListener('click', function (e) { if (e.target === menu) setOpen(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !menu.hidden) setOpen(false);
        });
    }
})();
