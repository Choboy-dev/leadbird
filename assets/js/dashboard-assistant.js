(function () {
    var config = window.LeadBirdDashboardAssistant || {};
    var chatUrl = config.chatUrl || '';
    var faviconUrl = config.faviconUrl || '';

    var root = document.querySelector('.lb-assistant--dashboard[data-lb-assistant]');
    if (!root || chatUrl === '') return;

    var launcher = root.querySelector('[data-lb-assistant-launcher]');
    var panel = root.querySelector('[data-lb-assistant-panel]');
    var closeBtn = root.querySelector('[data-lb-assistant-close]');
    var messagesEl = root.querySelector('[data-lb-assistant-messages]');
    var form = root.querySelector('[data-lb-assistant-form]');
    var input = root.querySelector('[data-lb-assistant-input]');
    var sendBtn = root.querySelector('[data-lb-assistant-send]');
    var suggestions = root.querySelector('[data-lb-assistant-suggestions]');
    var history = [];
    var isSending = false;

    function setOpen(isOpen) {
        if (!panel || !launcher) return;

        panel.classList.toggle('is-hidden', !isOpen);
        panel.hidden = !isOpen;
        panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        launcher.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        launcher.classList.toggle('is-open', isOpen);
        document.body.classList.toggle('lb-assistant-open', isOpen);

        if (isOpen) {
            launcher.classList.remove('lb-assistant-launcher--attention');
            var canAutoFocus = window.matchMedia('(min-width: 920px) and (hover: hover)').matches;
            if (input && canAutoFocus) {
                window.setTimeout(function () {
                    input.focus({ preventScroll: true });
                }, 120);
            }
            scrollMessages();
        }
    }

    function scrollMessages() {
        if (!messagesEl) return;
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatPlainText(text) {
        if (window.LeadBirdChatFormat && typeof window.LeadBirdChatFormat.toHtml === 'function') {
            return window.LeadBirdChatFormat.toHtml(text);
        }

        return escapeHtml(text).replace(/\n/g, '<br>');
    }

    function appendMessage(role, text) {
        if (!messagesEl) return;

        var isBot = role === 'assistant';
        var article = document.createElement('article');
        article.className = 'lb-assistant-msg' + (isBot ? ' lb-assistant-msg--bot' : ' lb-assistant-msg--user');

        if (isBot) {
            article.innerHTML =
                '<span class="lb-assistant-msg-avatar" aria-hidden="true">' +
                '<img src="' + escapeHtml(faviconUrl) + '" width="22" height="22" alt="">' +
                '</span>' +
                '<div class="lb-assistant-msg-bubble"><p>' + formatPlainText(text) + '</p></div>';
        } else {
            article.innerHTML =
                '<div class="lb-assistant-msg-bubble"><p>' + formatPlainText(text) + '</p></div>';
        }

        messagesEl.appendChild(article);
        scrollMessages();
    }

    function appendTyping() {
        if (!messagesEl) return null;

        var article = document.createElement('article');
        article.className = 'lb-assistant-msg lb-assistant-msg--bot lb-assistant-msg--typing';
        article.setAttribute('data-lb-typing', 'true');
        article.innerHTML =
            '<span class="lb-assistant-msg-avatar" aria-hidden="true">' +
            '<img src="' + escapeHtml(faviconUrl) + '" width="22" height="22" alt="">' +
            '</span>' +
            '<div class="lb-assistant-msg-bubble">' +
            '<span class="lb-assistant-typing" aria-label="Hannah is typing">' +
            '<span></span><span></span><span></span>' +
            '</span></div>';

        messagesEl.appendChild(article);
        scrollMessages();
        return article;
    }

    function removeTyping(node) {
        if (node && node.parentNode) {
            node.parentNode.removeChild(node);
        }
    }

    function setSending(state) {
        isSending = state;
        if (sendBtn) sendBtn.disabled = state;
        if (input) input.disabled = state;
    }

    function hideSuggestions() {
        if (suggestions) {
            suggestions.classList.add('is-hidden');
        }
    }

    function sendMessage(text) {
        var message = String(text || '').trim();
        if (message === '' || isSending) return;

        hideSuggestions();
        appendMessage('user', message);
        history.push({ role: 'user', content: message });

        if (input) input.value = '';
        setSending(true);

        var typingNode = appendTyping();

        fetch(chatUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ message: message, history: history.slice(0, -1) }),
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                removeTyping(typingNode);
                var reply = result.data && result.data.reply
                    ? result.data.reply
                    : (result.data && result.data.error) || 'Sorry, something went wrong. Please try again.';
                appendMessage('assistant', reply);
                history.push({ role: 'assistant', content: reply });
            })
            .catch(function () {
                removeTyping(typingNode);
                appendMessage('assistant', 'Sorry, I lost connection. Please check your internet and try again.');
            })
            .finally(function () {
                setSending(false);
                var canAutoFocus = window.matchMedia('(min-width: 920px) and (hover: hover)').matches;
                if (input && canAutoFocus) {
                    input.focus({ preventScroll: true });
                }
            });
    }

    if (launcher) {
        launcher.addEventListener('click', function () {
            setOpen(panel && panel.classList.contains('is-hidden'));
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            setOpen(false);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && panel && !panel.classList.contains('is-hidden')) {
            setOpen(false);
        }
    });

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            sendMessage(input ? input.value : '');
        });
    }

    if (suggestions) {
        suggestions.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof HTMLElement)) return;
            var btn = target.closest('[data-lb-assistant-suggest]');
            if (!btn) return;
            sendMessage(btn.getAttribute('data-lb-assistant-suggest') || '');
        });
    }
})();
