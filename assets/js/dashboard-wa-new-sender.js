(function () {
    'use strict';

    var root = document.querySelector('[data-ws-es]');
    if (!root) {
        return;
    }

    var accHeads = Array.prototype.slice.call(root.querySelectorAll('[data-ws-acc]'));

    function openStep(panelId) {
        accHeads.forEach(function (head) {
            var id = head.getAttribute('aria-controls');
            var panel = document.getElementById(id);
            var open = id === panelId;
            head.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (panel) {
                panel.hidden = !open;
            }
        });
    }

    accHeads.forEach(function (head) {
        head.addEventListener('click', function () {
            var id = head.getAttribute('aria-controls');
            if (head.getAttribute('aria-expanded') === 'true') {
                head.setAttribute('aria-expanded', 'false');
                document.getElementById(id).hidden = true;
                return;
            }
            openStep(id);
        });
    });

    root.querySelectorAll('[data-ws-acc-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-ws-acc-open');
            openStep(id);
            var head = root.querySelector('[aria-controls="' + id + '"]');
            if (head) {
                head.focus();
            }
        });
    });

    var appId = root.getAttribute('data-app-id') || '';
    var configId = root.getAttribute('data-config-id') || '';
    var graphVersion = root.getAttribute('data-graph-version') || 'v21.0';
    var endpoint = root.getAttribute('data-endpoint') || '';
    var startBtn = root.querySelector('[data-ws-es-start]');
    var statusEl = root.querySelector('[data-ws-es-status]');

    if (!startBtn || !appId || !configId) {
        return;
    }

    var FINISH_EVENTS = ['FINISH'];
    var state = null;

    function setStatus(text) {
        if (statusEl) {
            statusEl.textContent = text || '';
        }
    }

    function showError(message) {
        var text = message || 'We could not connect your number. Please try again.';
        if (window.LeadBirdModal) {
            window.LeadBirdModal.alert({ title: 'Your number is not connected yet', message: text, tone: 'error' });
        } else {
            setStatus(text);
        }
    }

    function reset() {
        state = null;
        startBtn.disabled = false;
        setStatus('');
    }

    function fail(message) {
        reset();
        showError(message);
    }

    function submit() {
        if (!state || state.submitted || !state.code || !state.phoneNumberId || !state.wabaId) {
            return;
        }
        state.submitted = true;
        setStatus('Connecting your number…');

        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({
                code: state.code,
                waba_id: state.wabaId,
                phone_number_id: state.phoneNumberId,
            }),
        })
            .then(function (res) {
                return res.json().catch(function () {
                    return { ok: false };
                });
            })
            .then(function (body) {
                if (body && body.ok && body.redirect) {
                    window.location.href = body.redirect;
                    return;
                }
                fail(body && (body.message || body.error));
            })
            .catch(function () {
                fail('We lost the connection. Check your internet and try again.');
            });
    }

    function onLoginDone(response) {
        if (!state) {
            return;
        }
        var code = response && response.authResponse && response.authResponse.code;
        if (!code) {
            // The popup's own messages can arrive just after the login callback.
            window.setTimeout(function () {
                if (state && !state.code) {
                    fail(state.error || 'The Meta window closed before setup finished. Click “Continue with Facebook” to try again.');
                }
            }, 800);
            return;
        }
        state.code = code;
        if (state.phoneNumberId) {
            submit();
            return;
        }
        window.setTimeout(function () {
            if (state && !state.submitted) {
                fail(state.wabaId
                    ? 'You finished without adding a phone number. Click “Continue with Facebook” and add your number in the Meta window.'
                    : 'Meta did not send back your WhatsApp details. Please try again.');
            }
        }, 4000);
    }

    window.addEventListener('message', function (event) {
        var host = '';
        try {
            host = new URL(event.origin).hostname;
        } catch (e) {
            return;
        }
        if (host !== 'facebook.com' && !/\.facebook\.com$/.test(host)) {
            return;
        }

        var data = null;
        try {
            data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
        } catch (e) {
            return;
        }
        if (!data || data.type !== 'WA_EMBEDDED_SIGNUP' || !state) {
            return;
        }

        var payload = data.data || {};
        if (FINISH_EVENTS.indexOf(data.event) !== -1) {
            state.phoneNumberId = String(payload.phone_number_id || '');
            state.wabaId = String(payload.waba_id || '');
            submit();
        } else if (data.event === 'FINISH_ONLY_WABA') {
            state.wabaId = String(payload.waba_id || '');
        } else if (data.event === 'ERROR') {
            state.error = payload.error_message || '';
        }
    });

    window.fbAsyncInit = function () {
        window.FB.init({ appId: appId, autoLogAppEvents: true, xfbml: false, version: graphVersion });
        startBtn.disabled = false;
    };

    var sdk = document.createElement('script');
    sdk.src = 'https://connect.facebook.net/en_US/sdk.js';
    sdk.async = true;
    sdk.defer = true;
    sdk.crossOrigin = 'anonymous';
    sdk.onerror = function () {
        setStatus('Could not load Facebook. Turn off any ad blocker for this page, then refresh.');
    };
    document.body.appendChild(sdk);

    startBtn.addEventListener('click', function () {
        if (state || !window.FB) {
            return;
        }
        state = { submitted: false };
        startBtn.disabled = true;
        setStatus('Finish the steps in the Meta window…');

        window.FB.login(onLoginDone, {
            config_id: configId,
            response_type: 'code',
            override_default_response_type: true,
            extras: { setup: {}, sessionInfoVersion: '3' },
        });
    });
})();
