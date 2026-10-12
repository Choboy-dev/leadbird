(function () {
    'use strict';

    var page = document.querySelector('.pp');
    if (!page) {
        return;
    }

    var toggles = page.querySelectorAll('.pp-toggle button');
    toggles.forEach(function (button) {
        button.addEventListener('click', function () {
            var period = button.getAttribute('data-period');
            toggles.forEach(function (b) {
                var on = b === button;
                b.classList.toggle('on', on);
                b.setAttribute('aria-checked', String(on));
            });
            page.querySelectorAll('.pp-card [data-' + period + ']').forEach(function (el) {
                el.textContent = el.getAttribute('data-' + period);
            });
        });
    });

    function disclose(button) {
        var panel = document.getElementById(button.getAttribute('aria-controls'));
        if (!panel) {
            return;
        }
        button.addEventListener('click', function () {
            var open = button.getAttribute('aria-expanded') !== 'true';
            button.setAttribute('aria-expanded', String(open));
            panel.hidden = !open;
        });
    }
    page.querySelectorAll('.pp-compare-btn, .pp-msg-btn').forEach(disclose);

    page.querySelectorAll('a[href="#token-pricing"]').forEach(function (link) {
        link.addEventListener('click', function () {
            var btn = page.querySelector('.pp-msg-btn');
            if (btn && btn.getAttribute('aria-expanded') !== 'true') {
                btn.click();
            }
        });
    });

    var tips = page.querySelectorAll('.pp-info');
    function closeTips(except) {
        tips.forEach(function (b) {
            if (b === except) {
                return;
            }
            b.setAttribute('aria-expanded', 'false');
            b.nextElementSibling.hidden = true;
        });
    }
    tips.forEach(function (b) {
        b.addEventListener('click', function (event) {
            event.stopPropagation();
            var open = b.getAttribute('aria-expanded') !== 'true';
            closeTips(b);
            b.setAttribute('aria-expanded', String(open));
            b.nextElementSibling.hidden = !open;
        });
    });
    document.addEventListener('click', function (event) {
        if (!event.target.closest('.pp-tip')) {
            closeTips(null);
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeTips(null);
        }
    });
})();
