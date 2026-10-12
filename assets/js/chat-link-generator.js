(function () {
    'use strict';

    var form = document.getElementById('lk-form');
    if (!form) {
        return;
    }

    var country = document.getElementById('lk-country');
    var phone = document.getElementById('lk-phone');
    var email = document.getElementById('lk-email');
    var message = document.getElementById('lk-message');
    var alertBox = document.getElementById('lk-alert');
    var linkInput = document.getElementById('lk-link');
    var canvas = document.getElementById('lk-qr');
    var copied = document.getElementById('lk-copied');
    var submit = form.querySelector('.lk-submit');
    var submitLabel = submit.getAttribute('data-label') || submit.textContent;
    var fields = [phone, email, message].filter(Boolean);

    function showAlert(text) {
        alertBox.textContent = text;
        alertBox.hidden = !text;
    }

    function setError(field, text) {
        var el = document.getElementById(field.id + '-err');
        if (el) {
            el.textContent = text;
        }
        field.setAttribute('aria-invalid', text ? 'true' : 'false');
        if (field === phone) {
            var wrapper = country.closest('.lb-dropdown');
            if (wrapper) {
                wrapper.classList.toggle('is-invalid', !!text);
            }
        }
    }

    // National number -> full international digits, e.g. 2348012345678.
    function fullNumber() {
        var opt = country.selectedOptions[0];
        var dial = opt ? opt.getAttribute('data-dial') || '' : '';
        var digits = phone.value.replace(/\D+/g, '');
        if (!dial) {
            return null;
        }
        if (digits.indexOf(dial) === 0 && digits.length > dial.length + 6) {
            digits = digits.slice(dial.length);
        }
        if (['IT', 'SM', 'VA'].indexOf(country.value) === -1) {
            digits = digits.replace(/^0+/, '');
        }
        if (digits.length < 6 || (dial + digits).length > 15) {
            return null;
        }
        return dial + digits;
    }

    function validate() {
        var errors = [];
        if (!country.value) {
            errors.push([phone, 'Please choose your country code.']);
        } else if (!fullNumber()) {
            errors.push([phone, 'Please enter a valid WhatsApp number.']);
        }
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
            errors.push([email, 'Please enter a valid business email address.']);
        }
        if (message.value.length > 1000) {
            errors.push([message, 'Please keep the welcome message under 1,000 characters.']);
        }
        fields.forEach(function (f) { setError(f, ''); });
        errors.forEach(function (err) { setError(err[0], err[1]); });
        if (errors.length) {
            errors[0][0].focus();
        }
        return !errors.length;
    }

    // Draws the QR onto the canvas and returns the same code as an SVG string.
    function paintQr(target, text, px) {
        var qr = window.qrcode(0, 'M');
        qr.addData(text, 'Byte');
        qr.make();
        var n = qr.getModuleCount();
        var quiet = 4;
        var dim = n + quiet * 2;
        var cell = Math.ceil(px / dim);
        target.width = cell * dim;
        target.height = cell * dim;
        var ctx = target.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, target.width, target.height);
        ctx.fillStyle = '#000';
        var path = '';
        for (var r = 0; r < n; r++) {
            for (var c = 0; c < n; c++) {
                if (!qr.isDark(r, c)) {
                    continue;
                }
                ctx.fillRect((c + quiet) * cell, (r + quiet) * cell, cell, cell);
                path += 'M' + (c + quiet) + ' ' + (r + quiet) + 'h1v1h-1z';
            }
        }
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + dim + ' ' + dim + '" shape-rendering="crispEdges">'
            + '<rect width="' + dim + '" height="' + dim + '" fill="#fff"/><path d="' + path + '" fill="#000"/></svg>';
    }

    function drawQr(link) {
        var svg = paintQr(canvas, link, 1024);
        document.getElementById('lk-png').href = canvas.toDataURL('image/png');
        var svgLink = document.getElementById('lk-svg');
        if (svgLink.dataset.url) {
            URL.revokeObjectURL(svgLink.dataset.url);
        }
        svgLink.dataset.url = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml' }));
        svgLink.href = svgLink.dataset.url;
    }

    function show(link) {
        linkInput.value = link;
        document.getElementById('lk-open').href = link;
        copied.textContent = '';
        var qrBlock = canvas.closest('.lk-out-qr');
        try {
            drawQr(link);
            qrBlock.hidden = false;
        } catch (err) {
            qrBlock.hidden = true;
        }
        document.getElementById('lk-empty').hidden = true;
        document.getElementById('lk-out').hidden = false;
    }

    function revealOnMobile() {
        if (window.matchMedia('(max-width: 860px)').matches) {
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            document.getElementById('lk-result').scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
        }
    }

    function saveLead(number) {
        var endpoint = form.getAttribute('data-lead-endpoint');
        if (!endpoint || !email || !window.fetch) {
            return Promise.resolve();
        }
        return fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({
                _csrf: form.getAttribute('data-csrf') || '',
                website: form.elements.website ? form.elements.website.value : '',
                tool: form.getAttribute('data-tool') || 'chat-link-generator',
                email: email.value.trim(),
                number: '+' + number,
            }),
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (!res.ok || !data.ok) {
                    throw new Error(data.error || 'Could not save your details.');
                }
            });
        });
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        showAlert('');
        if (!validate()) {
            return;
        }
        var number = fullNumber();
        var text = message.value.trim();
        var link = 'https://wa.me/' + number + (text ? '?text=' + encodeURIComponent(text) : '');

        submit.disabled = true;
        submit.textContent = 'Generating…';
        saveLead(number)
            .catch(function (err) {
                showAlert(err.message + ' Your link still works below.');
            })
            .then(function () {
                submit.disabled = false;
                submit.textContent = submitLabel;
                show(link);
                revealOnMobile();
            });
    });

    function fallbackCopy() {
        linkInput.focus();
        linkInput.select();
        try {
            document.execCommand('copy');
            copied.textContent = 'Copied!';
        } catch (err) {
            copied.textContent = 'Press Ctrl+C to copy';
        }
    }

    document.getElementById('lk-copy').addEventListener('click', function () {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(linkInput.value).then(function () {
                copied.textContent = 'Copied!';
            }, fallbackCopy);
        } else {
            fallbackCopy();
        }
    });
    linkInput.addEventListener('focus', function () { linkInput.select(); });

    fields.forEach(function (field) {
        field.addEventListener('input', function () {
            if (field.getAttribute('aria-invalid') === 'true') {
                setError(field, '');
            }
        });
    });

    document.querySelectorAll('.lk canvas[data-qr]').forEach(function (c) {
        try {
            paintQr(c, c.getAttribute('data-qr'), 240);
        } catch (err) {
            c.hidden = true;
        }
    });

    var slider = document.querySelector('.lk [data-slider]');
    if (slider) {
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var track = slider.querySelector('.ts-track');
        var dots = slider.querySelectorAll('.ts-dots button');
        var count = dots.length;
        var current = 0;
        var timer;
        var go = function (index) {
            current = (index + count) % count;
            track.style.transform = 'translateX(' + (-100 * current) + '%)';
            dots.forEach(function (d, i) { d.classList.toggle('on', i === current); });
        };
        var schedule = function () {
            if (reduceMotion || count < 2) {
                return;
            }
            clearInterval(timer);
            timer = setInterval(function () { go(current + 1); }, 5000);
        };
        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () { go(i); schedule(); });
        });
        schedule();
    }
})();
