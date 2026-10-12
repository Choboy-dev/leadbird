(function () {
    'use strict';

    var W = window.LeadBirdChatWidget;
    var form = document.getElementById('cb-form');
    if (!W || !form) {
        return;
    }

    document.querySelectorAll('.cb [data-widget]').forEach(function (node) {
        try {
            W.mount(JSON.parse(node.getAttribute('data-widget')), node);
        } catch (err) {
            // Decorative only.
        }
    });

    var $ = function (id) { return document.getElementById(id); };
    var country = $('lk-country');
    var phone = $('lk-phone');
    var email = $('lk-email');
    var message = $('lk-message');
    var color = $('cb-color');
    var alertBox = $('lk-alert');
    var stage = $('cb-stage');
    var codeBox = $('cb-code');
    var submit = form.querySelector('.lk-submit');
    var widgetSrc = (form.getAttribute('data-widget-src') || '').replace(/\?.*$/, '');
    var preview = null;
    var timer = 0;

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

    function config() {
        var fd = new FormData(form);
        return {
            phone: fullNumber() || '',
            brandName: String(fd.get('brand_name') || '').trim(),
            brandImage: String(fd.get('brand_image') || '').trim(),
            welcome: String(fd.get('welcome') || '').trim(),
            message: String(fd.get('message') || '').trim(),
            buttonStyle: fd.get('button_style'),
            buttonText: String(fd.get('button_text') || '').trim(),
            color: String(fd.get('color') || '').toUpperCase(),
            position: fd.get('position'),
            openByDefault: fd.get('open_default') === '1',
        };
    }

    function renderPreview() {
        var wasOpen = preview ? preview.isOpen() : true;
        if (preview) {
            preview.destroy();
        }
        preview = W.mount(config(), stage);
        if (wasOpen) {
            preview.open();
        } else {
            preview.close();
        }
        codeBox.hidden = true;
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(renderPreview, 120);
    }

    function syncSwatches() {
        form.querySelectorAll('.cb-swatch').forEach(function (b) {
            var on = b.getAttribute('data-color').toLowerCase() === color.value.toLowerCase();
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    form.querySelectorAll('.cb-swatch').forEach(function (b) {
        b.addEventListener('click', function () {
            color.value = b.getAttribute('data-color');
            syncSwatches();
            schedule();
        });
    });

    // "+ Page link" / "+ Page title" insert a placeholder at the cursor.
    form.querySelectorAll('[data-token]').forEach(function (b) {
        b.addEventListener('click', function () {
            var token = b.getAttribute('data-token');
            var start = message.selectionStart != null ? message.selectionStart : message.value.length;
            var end = message.selectionEnd != null ? message.selectionEnd : message.value.length;
            var before = message.value.slice(0, start);
            var pad = before && !/\s$/.test(before) ? ' ' : '';
            message.value = before + pad + token + message.value.slice(end);
            message.focus();
            message.selectionStart = message.selectionEnd = start + pad.length + token.length;
            schedule();
        });
    });

    function onEdit(ev) {
        if (ev.target === color) {
            syncSwatches();
        }
        if (ev.target.getAttribute && ev.target.getAttribute('aria-invalid') === 'true') {
            setError(ev.target.id, '');
        }
        if (ev.target !== email) {
            schedule();
        }
    }
    form.addEventListener('input', onEdit);
    form.addEventListener('change', onEdit);

    function setError(id, text) {
        var field = $(id);
        var err = $(id + '-err');
        if (err) {
            err.textContent = text || '';
        }
        if (field) {
            field.setAttribute('aria-invalid', text ? 'true' : 'false');
        }
        if (id === 'lk-phone') {
            var wrapper = country.closest('.lb-dropdown');
            if (wrapper) {
                wrapper.classList.toggle('is-invalid', !!text);
            }
        }
    }

    var FIELD = {
        brand_name: 'cb-brand',
        button_text: 'cb-button-text',
        phone: 'lk-phone',
        welcome: 'cb-welcome',
        message: 'lk-message',
        brand_image: 'cb-image',
        email: 'lk-email',
    };

    function validate() {
        var errs = {};
        if (!country.value) {
            errs.phone = 'Please choose your country code.';
        } else if (!fullNumber()) {
            errs.phone = 'Please enter a valid WhatsApp number.';
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
            errs.email = 'Please enter a valid business email address.';
        }
        var img = $('cb-image').value.trim();
        if (img && !/^https:\/\/\S+$/i.test(img)) {
            errs.brand_image = 'Please enter a full image address starting with https://';
        }
        return errs;
    }

    function showErrors(errs) {
        Object.keys(FIELD).forEach(function (k) { setError(FIELD[k], ''); });
        Object.keys(errs).forEach(function (k) {
            if (FIELD[k]) {
                setError(FIELD[k], errs[k]);
            }
        });
        var first = Object.keys(FIELD).filter(function (k) { return errs[k]; })[0];
        if (first) {
            $(FIELD[first]).focus();
        }
    }

    function snippet(cfg) {
        var json = JSON.stringify(cfg, null, 2).replace(/</g, '\\u003c').replace(/\n/g, '\n  ');
        return '<!-- Lead Bird WhatsApp chat widget -->\n'
            + '<script>\n  window.leadBirdChatWidget = ' + json + ';\n</' + 'script>\n'
            + '<script src="' + widgetSrc + '" async></' + 'script>';
    }

    function saveLead(cfg) {
        var endpoint = form.getAttribute('data-lead-endpoint');
        if (!endpoint || !window.fetch) {
            return Promise.resolve();
        }
        return fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({
                _csrf: form.getAttribute('data-csrf') || '',
                website: form.elements.website ? form.elements.website.value : '',
                tool: 'chat-button-generator',
                email: email.value.trim(),
                number: '+' + cfg.phone,
                brand: cfg.brandName,
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
        alertBox.hidden = true;
        var errs = validate();
        showErrors(errs);
        if (Object.keys(errs).length) {
            return;
        }

        var cfg = config();
        submit.disabled = true;
        submit.textContent = 'Generating…';
        saveLead(cfg)
            .catch(function (err) {
                alertBox.textContent = err.message + ' Your code still works below.';
                alertBox.hidden = false;
            })
            .then(function () {
                submit.disabled = false;
                submit.textContent = submit.getAttribute('data-label') || 'Get the code';
                Object.keys(cfg).forEach(function (k) {
                    if (cfg[k] === '') {
                        delete cfg[k];
                    }
                });
                $('cb-snippet').textContent = snippet(cfg);
                $('cb-copied').textContent = '';
                codeBox.hidden = false;
                var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                codeBox.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'nearest' });
            });
    });

    $('cb-copy').addEventListener('click', function () {
        var text = $('cb-snippet').textContent;
        function done() {
            $('cb-copied').textContent = 'Copied!';
        }
        function fallback() {
            var range = document.createRange();
            range.selectNodeContents($('cb-snippet'));
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
            try {
                document.execCommand('copy');
                done();
            } catch (err) {
                $('cb-copied').textContent = 'Press Ctrl+C to copy';
            }
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done, fallback);
        } else {
            fallback();
        }
    });

    syncSwatches();
    renderPreview();
})();
