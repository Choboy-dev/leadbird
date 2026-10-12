/*!
 * Lead Bird WhatsApp chat widget
 * Configure with window.leadBirdChatWidget = { phone: '2348012345678', ... } before loading this script.
 * Sets no cookies and stores nothing in the browser.
 */
(function () {
    'use strict';
    if (window.LeadBirdChatWidget && window.LeadBirdChatWidget.mount) {
        return;
    }

    var script = document.currentScript;
    var HOME = script && script.src ? script.src.replace(/assets\/widget\/chat-widget\.js.*$/, '') : '';

    var DEFAULTS = {
        phone: '',
        brandName: '',
        brandImage: '',
        welcome: 'Hi there! How can I help you?',
        message: '',
        buttonStyle: 'pill',
        buttonText: 'Chat with us',
        color: '#25D366',
        position: 'right',
        openByDefault: false,
        branding: true
    };
    var STYLES = ['pill', 'round', 'square', 'bubble'];
    var ICON = '<svg viewBox="0 0 32 32" aria-hidden="true" focusable="false"><path d="M16 3.5A12.5 12.5 0 0 0 5.2 22.3L3.6 28.4l6.3-1.6A12.5 12.5 0 1 0 16 3.5z" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linejoin="round"/><path d="M11.6 9.6c.4-.4 1-.4 1.3.1l1.5 2.1c.3.4.2.9-.1 1.2l-.8.8a7.7 7.7 0 0 0 3.7 3.7l.8-.8c.3-.3.8-.4 1.2-.1l2.1 1.5c.5.3.5.9.1 1.3l-1 1c-.8.8-2 1-3 .6a13.2 13.2 0 0 1-7-7c-.4-1-.2-2.2.6-3z" fill="currentColor"/></svg>';
    var CLOSE = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>';

    // Runs inside a shadow root on other people's websites, so it can't use the host page's CSS variables.
    var CSS = [
        ':host{all:initial}',
        '*{box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}',
        '.w{position:fixed;bottom:20px;z-index:2147483000;display:flex;flex-direction:column;gap:14px;align-items:flex-end;pointer-events:none}',
        '.w.left{align-items:flex-start}',
        '.w.inline{position:absolute;bottom:14px;left:14px;right:14px}',
        '.inline .card{max-width:100%}',
        '.w>*{pointer-events:auto}',
        '.btn{display:inline-flex;align-items:center;gap:8px;border:0;cursor:pointer;background:var(--c);color:var(--t);font-size:15px;font-weight:600;line-height:1;box-shadow:0 6px 18px rgba(0,0,0,.18);transition:transform .15s}',
        '.btn:hover{transform:translateY(-2px)}',
        '.btn:focus-visible,.start:focus-visible,.x:focus-visible{outline:3px solid #2563eb;outline-offset:3px}',
        '.btn svg{width:26px;height:26px;flex:none}',
        '.pill{padding:12px 20px 12px 14px;border-radius:999px}',
        '.square{padding:12px 18px 12px 14px;border-radius:12px}',
        '.round{width:60px;height:60px;justify-content:center;border-radius:50%}',
        '.round svg{width:32px;height:32px}',
        '.bubble-wrap{display:flex;align-items:center;gap:10px}',
        '.left .bubble-wrap{flex-direction:row-reverse}',
        '.bubble-wrap .tip{padding:9px 14px;border-radius:10px;background:#fff;color:#1d1d1b;font-size:14px;font-weight:600;box-shadow:0 4px 14px rgba(0,0,0,.15);white-space:nowrap}',
        '.card{width:320px;max-width:calc(100vw - 32px);border-radius:16px;overflow:hidden;background:#fff;box-shadow:0 12px 40px rgba(0,0,0,.22);transform-origin:bottom right;animation:in .18s ease-out}',
        '.left .card{transform-origin:bottom left}',
        '.card[hidden]{display:none}',
        '@keyframes in{from{opacity:0;transform:translateY(8px) scale(.97)}}',
        '@media (prefers-reduced-motion:reduce){.card{animation:none}.btn{transition:none}}',
        '.head{display:flex;align-items:center;gap:12px;padding:16px;background:var(--c);color:var(--t)}',
        '.av{flex:none;width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,.9);color:#1d1d1b;display:grid;place-items:center;font-weight:700;font-size:17px;overflow:hidden}',
        '.av img{width:100%;height:100%;object-fit:cover}',
        '.av svg{width:24px;height:24px}',
        '.name{flex:1;min-width:0;font-size:16px;font-weight:700;line-height:1.25;overflow-wrap:anywhere}',
        '.name small{display:block;font-size:12.5px;font-weight:400;opacity:.85}',
        '.x{flex:none;width:32px;height:32px;display:grid;place-items:center;border:0;border-radius:50%;background:rgba(0,0,0,.12);color:inherit;cursor:pointer}',
        '.x svg{width:18px;height:18px}',
        '.body{padding:20px 16px 26px;background:#efe7dd}',
        '.msg{position:relative;max-width:85%;padding:9px 12px 10px;border-radius:0 10px 10px 10px;background:#fff;color:#1d1d1b;font-size:14.5px;line-height:1.45;box-shadow:0 1px 1px rgba(0,0,0,.08);overflow-wrap:anywhere}',
        '.msg b{display:block;margin-bottom:2px;font-size:12.5px;color:#666}',
        '.foot{padding:14px 16px 12px;text-align:center}',
        '.start{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:12px;border-radius:999px;background:#25D366;color:#fff;font-size:15px;font-weight:700;text-decoration:none}',
        '.start svg{width:22px;height:22px}',
        '.by{display:inline-block;margin-top:10px;font-size:11.5px;color:#888;text-decoration:none}',
        '.by:hover{text-decoration:underline}'
    ].join('');

    function textColour(hex) {
        var m = /^#?([0-9a-f]{6})$/i.exec(hex || '');
        if (!m) {
            return '#fff';
        }
        var n = parseInt(m[1], 16);
        var lum = (0.299 * (n >> 16) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) / 255;
        return lum > 0.62 ? '#111' : '#fff';
    }

    function el(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls) {
            e.className = cls;
        }
        if (text) {
            e.textContent = text;
        }
        return e;
    }

    function normalise(input) {
        var c = {};
        for (var k in DEFAULTS) {
            c[k] = input && input[k] !== undefined && input[k] !== null ? input[k] : DEFAULTS[k];
        }
        c.phone = String(c.phone).replace(/\D+/g, '');
        c.brandName = String(c.brandName).slice(0, 60);
        c.welcome = String(c.welcome).slice(0, 40);
        c.buttonText = String(c.buttonText).slice(0, 24);
        c.message = String(c.message).slice(0, 500);
        c.color = /^#[0-9a-f]{6}$/i.test(c.color) ? c.color : DEFAULTS.color;
        c.buttonStyle = STYLES.indexOf(c.buttonStyle) >= 0 ? c.buttonStyle : DEFAULTS.buttonStyle;
        c.position = c.position === 'left' ? 'left' : 'right';
        c.brandImage = /^https:\/\/\S+$/i.test(String(c.brandImage)) ? String(c.brandImage) : '';
        return c;
    }

    function chatUrl(c) {
        if (!c.phone) {
            return '#';
        }
        var text = c.message
            .replace(/\{\{\s*page_link\s*\}\}/g, window.location.href)
            .replace(/\{\{\s*page_title\s*\}\}/g, document.title);
        return 'https://wa.me/' + c.phone + (text ? '?text=' + encodeURIComponent(text) : '');
    }

    /**
     * Renders the widget. Pass a container to render inside it (for previews) instead of fixed to the page.
     * Returns { open(), close(), destroy() }.
     */
    function mount(input, container) {
        var c = normalise(input);
        var host = el('div');
        host.setAttribute('data-leadbird-chat-widget', '');
        if (container) {
            host.style.cssText = 'position:absolute;inset:0;pointer-events:none';
        }
        var root = host.attachShadow ? host.attachShadow({ mode: 'open' }) : host;
        var style = el('style');
        style.textContent = CSS;
        root.appendChild(style);

        var wrap = el('div', 'w ' + c.position + (container ? ' inline' : ''));
        wrap.style.setProperty('--c', c.color);
        wrap.style.setProperty('--t', textColour(c.color));
        if (!container) {
            wrap.style[c.position] = '20px';
        }

        var title = c.brandName || 'us';
        var card = el('div', 'card');
        card.setAttribute('role', 'dialog');
        card.setAttribute('aria-label', 'Chat with ' + title + ' on WhatsApp');
        card.hidden = true;

        var head = el('div', 'head');
        var av = el('span', 'av');
        if (c.brandImage) {
            var img = el('img');
            img.src = c.brandImage;
            img.alt = '';
            av.appendChild(img);
        } else {
            av.textContent = (c.brandName.trim()[0] || '').toUpperCase();
            if (!av.textContent) {
                av.innerHTML = ICON;
            }
        }
        var name = el('div', 'name', c.brandName || 'Chat with us');
        name.appendChild(el('small', '', 'Usually replies on WhatsApp'));
        var close = el('button', 'x');
        close.type = 'button';
        close.setAttribute('aria-label', 'Close chat');
        close.innerHTML = CLOSE;
        head.appendChild(av);
        head.appendChild(name);
        head.appendChild(close);

        var body = el('div', 'body');
        var msg = el('div', 'msg');
        if (c.brandName) {
            msg.appendChild(el('b', '', c.brandName));
        }
        msg.appendChild(document.createTextNode(c.welcome));
        body.appendChild(msg);

        var foot = el('div', 'foot');
        var start = el('a', 'start');
        start.innerHTML = ICON;
        start.appendChild(document.createTextNode('Start chat'));
        start.target = '_blank';
        start.rel = 'noopener';
        start.href = chatUrl(c);
        foot.appendChild(start);
        if (c.branding && HOME) {
            var by = el('a', 'by', 'Powered by Lead Bird');
            by.href = HOME + '?utm_source=chat-widget';
            by.target = '_blank';
            by.rel = 'noopener';
            foot.appendChild(by);
        }
        card.appendChild(head);
        card.appendChild(body);
        card.appendChild(foot);

        var btn = el('button', 'btn ' + (c.buttonStyle === 'bubble' ? 'round' : c.buttonStyle));
        btn.type = 'button';
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-label', c.buttonText || 'Chat on WhatsApp');
        btn.innerHTML = ICON;
        if (c.buttonStyle === 'pill' || c.buttonStyle === 'square') {
            btn.appendChild(document.createTextNode(c.buttonText));
        }

        var launcher = btn;
        if (c.buttonStyle === 'bubble' && c.buttonText) {
            launcher = el('div', 'bubble-wrap');
            launcher.appendChild(el('span', 'tip', c.buttonText));
            launcher.appendChild(btn);
        }

        function setOpen(open) {
            card.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                start.href = chatUrl(c);
            }
        }
        btn.addEventListener('click', function () { setOpen(card.hidden); });
        close.addEventListener('click', function () { setOpen(false); btn.focus(); });
        start.addEventListener('click', function (e) {
            if (!c.phone) {
                e.preventDefault();
            } else {
                start.href = chatUrl(c);
            }
        });
        function onKey(e) {
            if (e.key === 'Escape' && !card.hidden) {
                setOpen(false);
                btn.focus();
            }
        }
        document.addEventListener('keydown', onKey);

        wrap.appendChild(card);
        wrap.appendChild(launcher);
        root.appendChild(wrap);
        (container || document.body).appendChild(host);
        if (c.openByDefault) {
            setOpen(true);
        }

        return {
            isOpen: function () { return !card.hidden; },
            open: function () { setOpen(true); },
            close: function () { setOpen(false); },
            destroy: function () {
                document.removeEventListener('keydown', onKey);
                host.remove();
            }
        };
    }

    window.LeadBirdChatWidget = { mount: mount, chatUrl: function (cfg) { return chatUrl(normalise(cfg)); } };

    var cfg = window.leadBirdChatWidget;
    if (cfg && typeof cfg === 'object') {
        var boot = function () {
            if (!normalise(cfg).phone) {
                if (window.console) {
                    console.warn('Lead Bird chat widget: no phone number configured.');
                }
                return;
            }
            mount(cfg);
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
    }
})();
