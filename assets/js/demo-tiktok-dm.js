(function () {
    var root = document.querySelector('[data-tkd-demo]');
    if (!root) return;

    var rail = root.querySelector('[data-tkd-rail]');
    var stepLabel = root.querySelector('[data-tkd-step]');
    var nextBtn = root.querySelector('[data-tkd-next]');
    var toast = root.querySelector('[data-tkd-toast]');
    var aiToggle = root.querySelector('[data-tkd-ai-toggle]');
    var status = root.querySelector('[data-tkd-status]');
    var compose = root.querySelector('[data-tkd-compose]');
    var input = root.querySelector('[data-tkd-input]');
    var row = root.querySelector('[data-tkd-active-row]');
    var intro = rail.innerHTML;

    /* Each step is one customer message and Hannah's answer. */
    var steps = [
        {
            customer: 'Hi 👋 I saw the red gown in your TikTok video. Is it still available in size 12?',
            ai: 'Hi Ada! Yes, the Red Satin Gown is available in size 12 🙌 It costs ₦28,000. Would you like delivery or pickup from our shop in Lekki?',
        },
        {
            customer: 'Delivery please. I’m in Yaba',
            ai: 'Great choice! Delivery to Yaba is ₦2,500 and takes 1–2 days, so your total is ₦30,500.\n\nYou can pay by transfer to:\nGTBank · 0123456789 · Tolu’s Closet\n\nSend me a message once you’ve paid and I’ll pass it to the team.',
        },
        {
            customer: 'Just paid! 🙏 Please send it fast',
            ai: 'Thank you, Ada! 💛 I’ve told the team so they can confirm your payment and send your gown. You’ll get an update right here once it’s on the way.',
            toast: 'Owner alert sent: Ada says she has paid ₦30,500 for the Red Satin Gown.',
        },
        {
            owner: 'Hi Ada, this is Tolu 👋 Payment received ✅ Your gown leaves our shop today.',
            takeover: true,
        },
        {
            customer: 'Wow, thank you so much! ❤️',
        },
        {
            customer: 'Do you have the same gown in black?',
            aiResume: true,
            ai: 'Yes we do! The Black Satin Gown is ₦28,000 and size 12 is in stock. Would you like me to add it to a new order?',
        },
    ];
    var current = 0;
    var busy = false;

    function now() {
        return new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    function scrollDown() {
        rail.scrollTop = rail.scrollHeight;
    }

    function addMessage(kind, text) {
        var tpl = root.querySelector('[data-tkd-tpl-' + kind + ']');
        var node = tpl.content.firstElementChild.cloneNode(true);
        var p = node.querySelector('[data-tkd-text]');
        text.split('\n').forEach(function (line, i) {
            if (i > 0) p.appendChild(document.createElement('br'));
            p.appendChild(document.createTextNode(line));
        });
        node.querySelector('[data-tkd-time]').textContent = now();
        node.classList.add('tkd-enter');
        rail.appendChild(node);
        scrollDown();

        row.querySelector('[data-tkd-row-tag]').textContent = kind === 'ai' ? 'AI · ' : (kind === 'owner' ? 'You · ' : '');
        row.querySelector('[data-tkd-row-preview]').textContent = text.split('\n')[0];
        row.querySelector('[data-tkd-row-time]').textContent = 'now';
    }

    function typing(then) {
        var node = document.createElement('div');
        node.className = 'tkd-typing';
        node.innerHTML = '<span></span><span></span><span></span><em>Hannah is typing</em>';
        rail.appendChild(node);
        scrollDown();
        setTimeout(function () {
            node.remove();
            then();
        }, 1600);
    }

    function setAi(on) {
        aiToggle.setAttribute('aria-pressed', on ? 'true' : 'false');
        aiToggle.setAttribute('aria-label', on ? 'Turn off AI replies for this chat' : 'Turn on AI replies for this chat');
        status.textContent = on
            ? 'Hannah replies automatically. You can jump in any time.'
            : 'AI is paused for this chat. Your replies go straight to the customer.';
    }

    function showToast(text) {
        toast.textContent = text;
        toast.hidden = false;
        clearTimeout(showToast.timer);
        showToast.timer = setTimeout(function () { toast.hidden = true; }, 5000);
    }

    function updateStep() {
        stepLabel.textContent = 'Step ' + current + ' of ' + steps.length;
        nextBtn.disabled = busy || current >= steps.length;
    }

    function next() {
        if (busy || current >= steps.length) return;
        var step = steps[current++];
        busy = true;
        updateStep();

        function done() {
            busy = false;
            updateStep();
        }

        if (step.takeover) setAi(false);
        if (step.aiResume) setAi(true);

        if (step.owner) {
            addMessage('owner', step.owner);
            done();
            return;
        }

        addMessage('customer', step.customer);
        if (!step.ai) {
            done();
            return;
        }
        setTimeout(function () {
            typing(function () {
                addMessage('ai', step.ai);
                if (step.toast) showToast(step.toast);
                done();
            });
        }, 500);
    }

    function restart() {
        rail.innerHTML = intro;
        current = 0;
        busy = false;
        toast.hidden = true;
        setAi(true);
        updateStep();
    }

    nextBtn.addEventListener('click', next);
    root.querySelector('[data-tkd-restart]').addEventListener('click', restart);
    aiToggle.addEventListener('click', function () {
        setAi(aiToggle.getAttribute('aria-pressed') !== 'true');
    });
    compose.addEventListener('submit', function (event) {
        event.preventDefault();
        var text = input.value.trim();
        if (text === '') return;
        setAi(false);
        addMessage('owner', text);
        input.value = '';
    });
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            compose.requestSubmit();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowRight' && document.activeElement !== input) {
            event.preventDefault();
            next();
        }
    });

    updateStep();
})();
