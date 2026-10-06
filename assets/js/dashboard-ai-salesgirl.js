(function () {
    'use strict';

    var wisdom = document.querySelector('[data-ai-wisdom]');
    if (!wisdom) {
        return;
    }

    function initWisdomViews() {
        var navItems = document.querySelectorAll('[data-ai-wisdom-view]');
        var panels = wisdom.querySelectorAll('[data-ai-wisdom-panel]');

        function setActiveView(view) {
            navItems.forEach(function (nav) {
                var navView = nav.getAttribute('data-ai-wisdom-view') || '';
                nav.classList.toggle('is-active', navView === view);
            });

            panels.forEach(function (panel) {
                if (!(panel instanceof HTMLElement)) {
                    return;
                }
                var isMatch = panel.getAttribute('data-ai-wisdom-panel') === view;
                panel.classList.toggle('is-active', isMatch);
                panel.hidden = !isMatch;
            });

            var main = wisdom.querySelector('.ai-wisdom-main');
            if (main instanceof HTMLElement) {
                main.scrollTop = 0;
            }
        }

        navItems.forEach(function (item) {
            item.addEventListener('click', function () {
                if (!(item instanceof HTMLButtonElement)) {
                    return;
                }

                var view = item.getAttribute('data-ai-wisdom-view') || 'chat';
                setActiveView(view);
            });
        });

        var params = new URLSearchParams(window.location.search);
        var initialView = params.get('view');
        if (initialView && ['chat', 'knowledge', 'alerts', 'selling'].indexOf(initialView) !== -1) {
            setActiveView(initialView);
        }
    }

    function initPromptCards() {
        var input = wisdom.querySelector('.ai-wisdom-compose-input');
        if (!(input instanceof HTMLTextAreaElement)) {
            return;
        }

        wisdom.querySelectorAll('[data-ai-prompt]').forEach(function (card) {
            card.addEventListener('click', function () {
                var text = card.getAttribute('data-ai-prompt') || '';
                if (text === '') {
                    return;
                }
                input.value = text;
                input.focus();
            });
        });
    }

    function initPromptCarousel() {
        var track = wisdom.querySelector('[data-ai-prompts-track]');
        var nextBtn = wisdom.querySelector('[data-ai-prompts-next]');
        var prevBtn = wisdom.querySelector('[data-ai-prompts-prev]');
        if (!(track instanceof HTMLElement) || !(nextBtn instanceof HTMLButtonElement) || !(prevBtn instanceof HTMLButtonElement)) {
            return;
        }

        var scrollStep = 280;

        function updateNavState() {
            var maxScroll = track.scrollWidth - track.clientWidth;
            var atStart = track.scrollLeft <= 4;
            var atEnd = track.scrollLeft >= maxScroll - 4;

            prevBtn.hidden = atStart;
            prevBtn.disabled = atStart;
            nextBtn.disabled = atEnd;
            nextBtn.hidden = maxScroll <= 0 || atEnd;
        }

        nextBtn.addEventListener('click', function () {
            track.scrollBy({ left: scrollStep, behavior: 'smooth' });
        });

        prevBtn.addEventListener('click', function () {
            track.scrollBy({ left: -scrollStep, behavior: 'smooth' });
        });

        track.addEventListener('scroll', updateNavState, { passive: true });
        window.addEventListener('resize', updateNavState);
        updateNavState();
    }

    function initTestForm() {
        var form = wisdom.querySelector('[data-ai-test-form]');
        var input = wisdom.querySelector('.ai-wisdom-compose-input');
        var thread = wisdom.querySelector('[data-ai-playground-thread]');
        var errorEl = wisdom.querySelector('[data-ai-playground-error]');
        var config = window.LeadBirdAiPlayground || {};
        var testChatUrl = config.testChatUrl || '';
        var aiName = config.aiName || 'Salesgirl';
        var baseUrl = config.baseUrl || '';

        if (!(form instanceof HTMLFormElement) || !(input instanceof HTMLTextAreaElement) || testChatUrl === '') {
            return;
        }

        var sending = false;
        var playgroundHistory = [];

        function setError(message) {
            if (!(errorEl instanceof HTMLElement)) {
                return;
            }
            if (!message) {
                errorEl.textContent = '';
                errorEl.classList.add('is-hidden');
                return;
            }
            errorEl.textContent = message;
            errorEl.classList.remove('is-hidden');
        }

        function appendMessage(role, label, bodyHtml) {
            if (!(thread instanceof HTMLElement)) {
                return;
            }
            thread.hidden = false;

            var msg = document.createElement('div');
            msg.className = 'ai-wisdom-msg ai-wisdom-msg--' + (role === 'customer' ? 'in' : 'out');

            var labelEl = document.createElement('span');
            labelEl.className = 'ai-wisdom-msg-label';
            if (role === 'ai') {
                labelEl.setAttribute('data-ai-playground-name', '');
            }
            labelEl.textContent = label;

            var body = document.createElement('p');
            if (role === 'ai' && bodyHtml) {
                body.innerHTML = bodyHtml;
            } else {
                body.textContent = bodyHtml || '';
            }

            msg.appendChild(labelEl);
            msg.appendChild(body);
            thread.appendChild(msg);
            msg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function appendTyping() {
            if (!(thread instanceof HTMLElement)) {
                return null;
            }
            thread.hidden = false;

            var msg = document.createElement('div');
            msg.className = 'ai-wisdom-msg ai-wisdom-msg--out ai-wisdom-msg--typing';
            msg.setAttribute('data-ai-typing', '');

            var labelEl = document.createElement('span');
            labelEl.className = 'ai-wisdom-msg-label';
            labelEl.textContent = aiName;

            var body = document.createElement('p');
            body.textContent = '…';

            msg.appendChild(labelEl);
            msg.appendChild(body);
            thread.appendChild(msg);
            msg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return msg;
        }

        function removeTyping(el) {
            if (el instanceof HTMLElement) {
                el.remove();
            }
        }

        function updateUrl(message) {
            if (baseUrl === '') {
                return;
            }
            var url = baseUrl + '?test=' + encodeURIComponent(message);
            window.history.pushState({ test: message }, '', url);
        }

        function sendMessage() {
            var message = input.value.trim();
            if (message === '' || sending) {
                return;
            }

            sending = true;
            setError('');
            form.classList.add('is-sending');

            appendMessage('customer', 'Customer', message);
            var typingEl = appendTyping();
            var historyForRequest = playgroundHistory.slice();

            fetch(testChatUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    history: historyForRequest,
                }),
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    removeTyping(typingEl);

                    if (!result.data || !result.data.ok) {
                        var errMsg = (result.data && (result.data.error || result.data.message))
                            || 'Could not generate a reply. Please try again.';
                        setError(errMsg);
                        return;
                    }

                    input.value = '';

                    var replyHtml = result.data.reply_html || '';
                    if (replyHtml === '' && result.data.reply && window.LeadBirdChatFormat) {
                        replyHtml = window.LeadBirdChatFormat.toHtml(result.data.reply);
                    }

                    var name = result.data.ai_name || aiName;
                    appendMessage('ai', name, replyHtml);

                    playgroundHistory.push({ role: 'customer', content: message });
                    playgroundHistory.push({ role: 'assistant', content: result.data.reply || '' });
                    if (playgroundHistory.length > 24) {
                        playgroundHistory = playgroundHistory.slice(-24);
                    }

                    updateUrl(message);

                    window.dispatchEvent(new CustomEvent('lb-ai-playground-sent', {
                        detail: { message: message },
                    }));
                })
                .catch(function () {
                    removeTyping(typingEl);
                    setError('Network error. Please try again.');
                })
                .finally(function () {
                    sending = false;
                    form.classList.remove('is-sending');
                });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            sendMessage();
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        });
    }

    function scrollToThread() {
        if (!/[?&]test=/.test(window.location.search)) {
            return;
        }
        var thread = wisdom.querySelector('.ai-wisdom-thread');
        if (thread instanceof HTMLElement) {
            thread.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function initProfileModal() {
        var saveUrl = (window.LeadBirdAiProfile && window.LeadBirdAiProfile.saveUrl) || '';
        var overlay = document.querySelector('[data-ai-profile-overlay]');
        var form = document.querySelector('[data-ai-profile-form]');
        if (!overlay || !form || !saveUrl) {
            return;
        }

        var openButtons = document.querySelectorAll('[data-ai-profile-edit]');
        var closeButtons = document.querySelectorAll('[data-ai-profile-close]');
        var submitButton = form.querySelector('[data-ai-profile-submit]');
        var errorBox = form.querySelector('[data-ai-profile-error]');
        var playgroundName = document.querySelector('[data-ai-playground-name]');

        function setError(message) {
            if (!errorBox) {
                return;
            }
            if (!message) {
                errorBox.textContent = '';
                errorBox.classList.add('is-hidden');
                return;
            }
            errorBox.textContent = message;
            errorBox.classList.remove('is-hidden');
        }

        function setOpen(isOpen) {
            overlay.classList.toggle('is-hidden', !isOpen);
            overlay.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            document.body.style.overflow = isOpen ? 'hidden' : '';
            if (!isOpen) {
                setError('');
            }
        }

        function updateDisplay(profile) {
            if (playgroundName) {
                playgroundName.textContent = profile.ai_name || playgroundName.textContent;
            }
            var wrap = wisdom.querySelector('.ai-wisdom-compose-wrap');
            if (wrap instanceof HTMLElement) {
                if (profile.ai_name) {
                    wrap.setAttribute('data-ai-profile-name', profile.ai_name);
                }
                if (profile.business_name) {
                    wrap.setAttribute('data-ai-business-name', profile.business_name);
                }
                if (profile.ai_greeting) {
                    wrap.setAttribute('data-ai-greeting', profile.ai_greeting);
                }
                if (profile.ai_tone) {
                    wrap.setAttribute('data-ai-tone', profile.ai_tone);
                }
            }
        }

        openButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setOpen(true);
            });
        });

        var params = new URLSearchParams(window.location.search);
        if (params.get('profile') === '1') {
            setOpen(true);
        }

        closeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setOpen(false);
            });
        });

        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                setOpen(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (overlay.classList.contains('is-hidden')) {
                return;
            }
            if (event.key === 'Escape') {
                setOpen(false);
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            setError('');

            var payload = new FormData(form);
            form.classList.add('is-loading');
            if (submitButton) {
                submitButton.textContent = 'Saving…';
            }

            fetch(saveUrl, {
                method: 'POST',
                body: payload,
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.data || !result.data.ok) {
                        setError((result.data && result.data.message) || 'Could not save your profile. Please try again.');
                        return;
                    }
                    if (result.data.profile) {
                        updateDisplay(result.data.profile);
                    }
                    setOpen(false);
                })
                .catch(function () {
                    setError('Network error. Please try again.');
                })
                .finally(function () {
                    form.classList.remove('is-loading');
                    if (submitButton) {
                        submitButton.textContent = 'Save';
                    }
                });
        });

        if (window.LeadBirdDropdown && typeof window.LeadBirdDropdown.init === 'function') {
            window.LeadBirdDropdown.init(overlay);
        }
    }

    function initChatHistory() {
        var list = document.querySelector('[data-ai-chat-history]');
        var config = window.LeadBirdAiPlayground || {};
        var businessId = String(config.businessId || 'default');
        var storageKey = 'lb_ai_playground_history_' + businessId;
        var baseUrl = String(config.baseUrl || '');
        var maxItems = 25;

        if (!list || baseUrl === '') {
            return;
        }

        function loadHistory() {
            try {
                var raw = window.localStorage.getItem(storageKey);
                var parsed = raw ? JSON.parse(raw) : [];
                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [];
            }
        }

        function saveHistory(items) {
            window.localStorage.setItem(storageKey, JSON.stringify(items.slice(0, maxItems)));
        }

        function truncateLabel(message) {
            return message.length > 42 ? message.slice(0, 42) + '…' : message;
        }

        function addEntry(message) {
            var trimmed = String(message || '').trim();
            if (trimmed === '') {
                return;
            }

            var items = loadHistory().filter(function (item) {
                return item && item.message !== trimmed;
            });
            items.unshift({ message: trimmed, at: Date.now() });
            saveHistory(items);
        }

        function renderHistory() {
            var items = loadHistory();
            var currentTest = String(config.currentTest || '').trim();

            list.innerHTML = '';

            if (items.length === 0) {
                var empty = document.createElement('li');
                empty.className = 'ai-wisdom-history-empty';
                empty.textContent = 'No chats yet';
                list.appendChild(empty);
                return;
            }

            items.forEach(function (item) {
                if (!item || !item.message) {
                    return;
                }

                var li = document.createElement('li');
                var link = document.createElement('a');
                link.className = 'ai-wisdom-history-item';
                link.href = baseUrl + '?test=' + encodeURIComponent(item.message);
                link.textContent = truncateLabel(item.message);
                link.title = item.message;

                if (item.message === currentTest) {
                    link.classList.add('is-active');
                }

                li.appendChild(link);
                list.appendChild(li);
            });
        }

        if (config.hasResponse && config.currentTest) {
            addEntry(config.currentTest);
        }

        window.addEventListener('lb-ai-playground-sent', function (event) {
            var detail = event.detail || {};
            if (!detail.message) {
                return;
            }
            addEntry(detail.message);
            config.currentTest = detail.message;
            config.hasResponse = true;
            renderHistory();
        });

        renderHistory();
    }

    initWisdomViews();
    initPromptCards();
    initPromptCarousel();
    initTestForm();
    scrollToThread();
    initProfileModal();
    initChatHistory();
    initAlertsForm();
    initSellingForm();
})();

function initSellingForm() {
    var config = window.LeadBirdAiProfile || {};
    var saveUrl = config.saveSellingUrl || '';
    var form = document.querySelector('[data-ai-selling-form]');
    var status = document.querySelector('[data-ai-selling-status]');
    var submitBtn = document.querySelector('[data-ai-selling-submit]');
    if (!(form instanceof HTMLFormElement) || saveUrl === '') {
        return;
    }

    var saving = false;

    function setStatus(message, isError) {
        if (!(status instanceof HTMLElement)) {
            return;
        }
        status.textContent = message;
        status.classList.toggle('is-error', Boolean(isError));
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (saving) {
            return;
        }

        saving = true;
        if (submitBtn instanceof HTMLButtonElement) {
            submitBtn.disabled = true;
        }
        setStatus('Saving…', false);

        fetch(saveUrl, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.data || !result.data.ok) {
                    setStatus((result.data && result.data.message) || 'Could not save selling rules.', true);
                    return;
                }
                setStatus('Saved.', false);
            })
            .catch(function () {
                setStatus('Could not save selling rules.', true);
            })
            .finally(function () {
                saving = false;
                if (submitBtn instanceof HTMLButtonElement) {
                    submitBtn.disabled = false;
                }
            });
    });
}

function initAlertsForm() {
    var config = window.LeadBirdAiProfile || {};
    var saveUrl = config.saveAlertsUrl || '';
    var form = document.querySelector('[data-ai-alerts-form]');
    var status = document.querySelector('[data-ai-alerts-status]');
    if (!(form instanceof HTMLFormElement) || saveUrl === '') {
        return;
    }

    var saveTimer = null;
    var saving = false;

    function setStatus(message, isError) {
        if (!(status instanceof HTMLElement)) {
            return;
        }
        status.textContent = message;
        status.classList.toggle('is-error', Boolean(isError));
    }

    function saveAlerts() {
        if (saving) {
            return;
        }

        saving = true;
        setStatus('Saving…', false);

        fetch(saveUrl, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.data || !result.data.ok) {
                    setStatus((result.data && result.data.message) || 'Could not save alerts.', true);
                    return;
                }

                var angry = form.querySelector('[name="alert_angry_customer"]');
                var refund = form.querySelector('[name="alert_refund_request"]');
                if (
                    angry instanceof HTMLInputElement
                    && refund instanceof HTMLInputElement
                    && result.data.alerts
                ) {
                    var complaintOn = Boolean(result.data.alerts.alert_angry_customer || result.data.alerts.alert_refund_request);
                    if (angry.checked !== complaintOn) {
                        angry.checked = complaintOn;
                    }
                    if (refund.checked !== complaintOn) {
                        refund.checked = complaintOn;
                    }
                }

                setStatus('Saved', false);
                window.setTimeout(function () {
                    if (status && status.textContent === 'Saved') {
                        setStatus('', false);
                    }
                }, 2000);
            })
            .catch(function () {
                setStatus('Network error. Try again.', true);
            })
            .finally(function () {
                saving = false;
            });
    }

    form.addEventListener('change', function () {
        window.clearTimeout(saveTimer);
        saveTimer = window.setTimeout(saveAlerts, 250);
    });
}
