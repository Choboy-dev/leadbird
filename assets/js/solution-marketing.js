(function () {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var root = document.querySelector('.smk');
    if (!root) {
        return;
    }

    paintQrCodes();
    initRotatingIndustry();
    initAccordions();
    initUseCaseTabs();
    initTestimonials();
    initPricingToggle();

    function paintQrCodes() {
        if (typeof window.qrcode !== 'function') {
            return;
        }
        var styles = getComputedStyle(document.documentElement);
        var dark = styles.getPropertyValue('--brand-black').trim() || 'black';
        var light = styles.getPropertyValue('--brand-white').trim() || 'white';
        root.querySelectorAll('canvas[data-qr]').forEach(function (canvas) {
            // High error correction so the logo in the middle doesn't break the scan.
            var qr = window.qrcode(0, 'H');
            qr.addData(canvas.getAttribute('data-qr'), 'Byte');
            qr.make();
            var n = qr.getModuleCount();
            var cell = Math.max(2, Math.floor(canvas.width / n));
            var size = cell * n;
            canvas.width = size;
            canvas.height = size;
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = light;
            ctx.fillRect(0, 0, size, size);
            ctx.fillStyle = dark;
            for (var r = 0; r < n; r++) {
                for (var c = 0; c < n; c++) {
                    if (qr.isDark(r, c)) {
                        ctx.fillRect(c * cell, r * cell, cell, cell);
                    }
                }
            }
        });
    }

    function initRotatingIndustry() {
        var el = document.getElementById('industryText');
        if (!el || reduceMotion) {
            return;
        }
        var words = JSON.parse(el.getAttribute('data-words') || '[]');
        if (words.length < 2) {
            return;
        }
        var index = 0;
        setInterval(function () {
            el.style.opacity = '0';
            setTimeout(function () {
                index = (index + 1) % words.length;
                el.textContent = words[index];
                el.style.opacity = '1';
            }, 500);
        }, 3000);
    }

    function initAccordions() {
        root.querySelectorAll('[data-acc]').forEach(function (section) {
            var items = section.querySelectorAll('.acc-item');
            var visuals = section.querySelectorAll('.acc-visual');
            var texts = section.querySelectorAll('.acc-text');
            var current = 0;
            var timer;

            function show(index) {
                current = index;
                items.forEach(function (item, i) {
                    item.classList.toggle('open', i === index);
                    item.setAttribute('aria-selected', String(i === index));
                });
                visuals.forEach(function (v, i) { v.classList.toggle('on', i === index); });
                texts.forEach(function (t, i) { t.classList.toggle('on', i === index); });
            }

            function schedule() {
                clearInterval(timer);
                if (reduceMotion || items.length < 2) {
                    return;
                }
                timer = setInterval(function () { show((current + 1) % items.length); }, 3000);
            }

            items.forEach(function (item, i) {
                item.addEventListener('click', function () { show(i); schedule(); });
            });
            section.addEventListener('mouseenter', function () { clearInterval(timer); });
            section.addEventListener('mouseleave', schedule);
            schedule();
        });
    }

    function initUseCaseTabs() {
        var tabs = root.querySelectorAll('.uc-tab');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) {
                    var on = t === tab;
                    t.classList.toggle('active', on);
                    t.setAttribute('aria-selected', String(on));
                    var panel = document.getElementById('uc-' + t.getAttribute('data-tab'));
                    if (panel) {
                        panel.classList.toggle('active', on);
                    }
                });
            });
        });
    }

    function initTestimonials() {
        var slider = root.querySelector('[data-slider]');
        if (!slider) {
            return;
        }
        var track = slider.querySelector('.ts-track');
        var dots = slider.querySelectorAll('.ts-dots button');
        var count = dots.length;
        var current = 0;
        var timer;

        function go(index) {
            current = (index + count) % count;
            track.style.transform = 'translateX(' + (-100 * current) + '%)';
            dots.forEach(function (d, i) { d.classList.toggle('on', i === current); });
        }

        function schedule() {
            if (reduceMotion || count < 2) {
                return;
            }
            clearInterval(timer);
            timer = setInterval(function () { go(current + 1); }, 5000);
        }

        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () { go(i); schedule(); });
        });
        schedule();
    }

    function initPricingToggle() {
        var buttons = root.querySelectorAll('.pr-toggle button');
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var period = button.getAttribute('data-period');
                buttons.forEach(function (b) {
                    b.classList.toggle('on', b === button);
                    b.setAttribute('aria-checked', String(b === button));
                });
                root.querySelectorAll('.pr [data-' + period + ']').forEach(function (el) {
                    el.textContent = el.getAttribute('data-' + period);
                });
            });
        });
    }
})();
