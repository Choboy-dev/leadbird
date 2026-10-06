(function () {
    'use strict';

    var STYLED_COMMANDS = ['foreColor', 'hiliteColor', 'fontName', 'fontSize'];
    var POP_GAP = 6;
    var POP_EDGE = 8;
    var CHIP_EXTRA = 28; // chip padding + border + the gap in front of it
    var IMAGE_MAX_WIDTH = 600; // keeps inline images inside a typical email column
    var POLL_VISIBLE_MS = 15000;
    var POLL_HIDDEN_MS = 60000;
    var SOUND_KEY = 'lb-mail-sound';
    var measureContext = null;

    function fontOf(element) {
        var style = window.getComputedStyle(element);
        return style.font || [style.fontStyle, style.fontWeight, style.fontSize, style.fontFamily].join(' ');
    }

    function textWidth(text, font) {
        if (!measureContext) measureContext = document.createElement('canvas').getContext('2d');
        measureContext.font = font;
        return measureContext.measureText(text).width;
    }

    /** Accepts a FileList or a DataTransferItemList and returns image files only. */
    function imageFilesFrom(source) {
        return [].slice.call(source || []).map(function (entry) {
            if (typeof entry.getAsFile === 'function') return entry.kind === 'file' ? entry.getAsFile() : null;
            return entry;
        }).filter(function (file) {
            return file && /^image\/(png|jpeg|jpg|gif|webp)$/i.test(file.type || '');
        });
    }

    /**
     * Pasted markup keeps its formatting and images but loses anything scriptable;
     * the server sanitizes again before the message is stored or sent.
     */
    function cleanPastedHtml(html) {
        var parsed = new DOMParser().parseFromString(html, 'text/html');
        parsed.querySelectorAll('script,style,link,meta,base,iframe,object,embed,form,input,button,select,textarea,svg').forEach(function (node) {
            node.remove();
        });
        parsed.querySelectorAll('*').forEach(function (node) {
            [].slice.call(node.attributes).forEach(function (attribute) {
                var name = attribute.name.toLowerCase();
                var value = attribute.value || '';
                var unsafe = name.indexOf('on') === 0
                    || name === 'srcset'
                    || (name === 'href' && /^\s*javascript:/i.test(value))
                    || (name === 'src' && !/^\s*(https?:|data:image\/)/i.test(value));
                if (unsafe) node.removeAttribute(attribute.name);
            });
        });
        parsed.querySelectorAll('img:not([src])').forEach(function (node) { node.remove(); });
        return parsed.body.innerHTML;
    }

    /** Put the caret where a dropped image landed, when the browser supports it. */
    function caretFromPoint(editor, x, y) {
        var range = null;
        if (document.caretRangeFromPoint) {
            range = document.caretRangeFromPoint(x, y);
        } else if (document.caretPositionFromPoint) {
            var position = document.caretPositionFromPoint(x, y);
            if (position) {
                range = document.createRange();
                range.setStart(position.offsetNode, position.offset);
                range.collapse(true);
            }
        }
        if (!range || !editor.contains(range.commonAncestorContainer)) return;
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
    }

    function csrfToken(scope) {
        var field = (scope || document).querySelector('input[name="_csrf"]');
        return field ? field.value : '';
    }

    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
            body: new URLSearchParams(data),
        });
    }

    function emailList(value) {
        return String(value || '').split(',').map(function (part) {
            return part.trim();
        }).filter(function (part) {
            return part !== '';
        });
    }

    function confirmAction(options) {
        if (window.LeadBirdModal && typeof window.LeadBirdModal.confirm === 'function') {
            return window.LeadBirdModal.confirm(options);
        }
        return Promise.resolve(true);
    }

    function alertMessage(options) {
        if (window.LeadBirdModal && typeof window.LeadBirdModal.alert === 'function') {
            window.LeadBirdModal.alert(options);
        }
    }

    /* ---------------------------------------------------------------- message list */

    /** Guard against binding the same element twice after a live list refresh. */
    function bindOnce(element, key) {
        if (element.dataset[key] === '1') return false;
        element.dataset[key] = '1';
        return true;
    }

    function initList() {
        var selectAll = document.querySelector('[data-mail-select-all]');
        if (selectAll && bindOnce(selectAll, 'boundSelectAll')) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('[data-mail-checkbox]').forEach(function (box) {
                    box.checked = selectAll.checked;
                });
            });
        }

        document.querySelectorAll('[data-mail-row]').forEach(function (row) {
            if (!bindOnce(row, 'boundRow')) return;
            row.addEventListener('click', function (event) {
                if (!event.target.closest('a,button,input,label') && row.dataset.href) {
                    window.location.href = row.dataset.href;
                }
            });
        });

        document.querySelectorAll('[data-mail-star]').forEach(function (button) {
            if (!bindOnce(button, 'boundStar')) return;
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                post('/mail/actions', {
                    action: 'star',
                    message_id: button.dataset.mailStar || '',
                    _csrf: button.dataset.csrf || '',
                }).then(function (response) {
                    if (response.ok) button.classList.toggle('is-starred');
                });
            });
        });

        document.querySelectorAll('[data-mail-cancel-schedule]').forEach(function (button) {
            if (!bindOnce(button, 'boundCancelSchedule')) return;
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                confirmAction({
                    title: 'Cancel this scheduled send?',
                    message: 'The message moves back to Drafts and will not be sent.',
                    confirmLabel: 'Cancel send',
                    cancelLabel: 'Keep schedule',
                    tone: 'danger',
                }).then(function (ok) {
                    if (!ok) return;
                    post('/mail/actions', {
                        action: 'cancel_schedule',
                        message_id: button.dataset.mailCancelSchedule || '',
                        _csrf: button.dataset.csrf || '',
                    }).then(function (response) {
                        return response.json().catch(function () { return { ok: response.ok }; });
                    }).then(function (result) {
                        if (result && result.ok) {
                            window.location.reload();
                            return;
                        }
                        alertMessage({
                            title: 'Could not cancel',
                            message: (result && result.error) || 'Please try again.',
                            tone: 'error',
                        });
                    });
                });
            });
        });
    }

    /* ------------------------------------------------------------------- live mail */

    var audioContext = null;

    function primeAudio() {
        var Context = window.AudioContext || window.webkitAudioContext;
        if (!Context) return;
        if (!audioContext) audioContext = new Context();
        if (audioContext.state === 'suspended') audioContext.resume();
    }

    function soundEnabled() {
        try {
            return window.localStorage.getItem(SOUND_KEY) !== 'off';
        } catch (error) {
            return true;
        }
    }

    /** Two-note chime, synthesised so no audio file has to be shipped. */
    function playChime() {
        if (!soundEnabled() || !audioContext) return;
        try {
            if (audioContext.state === 'suspended') audioContext.resume();
            var start = audioContext.currentTime;
            [[880, 0], [1174.66, 0.15]].forEach(function (note) {
                var oscillator = audioContext.createOscillator();
                var gain = audioContext.createGain();
                var at = start + note[1];
                oscillator.type = 'sine';
                oscillator.frequency.value = note[0];
                gain.gain.setValueAtTime(0.0001, at);
                gain.gain.exponentialRampToValueAtTime(0.14, at + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, at + 0.32);
                oscillator.connect(gain);
                gain.connect(audioContext.destination);
                oscillator.start(at);
                oscillator.stop(at + 0.34);
            });
        } catch (error) { /* audio unavailable on this device */ }
    }

    var toastTimer = null;

    function showToast(message) {
        var toast = document.querySelector('[data-mail-toast]');
        if (!toast) return;
        toast.textContent = message;
        toast.hidden = false;
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(function () { toast.hidden = true; }, 6000);
    }

    function initSoundToggle() {
        var toggle = document.querySelector('[data-mail-sound-toggle]');
        if (!toggle) return;

        var onIcon = toggle.querySelector('[data-mail-sound-on]');
        var offIcon = toggle.querySelector('[data-mail-sound-off]');
        var render = function () {
            var on = soundEnabled();
            toggle.setAttribute('aria-pressed', on ? 'true' : 'false');
            toggle.title = on ? 'New mail sound is on' : 'New mail sound is off';
            if (onIcon) onIcon.hidden = !on;
            if (offIcon) offIcon.hidden = on;
        };

        toggle.addEventListener('click', function () {
            try {
                window.localStorage.setItem(SOUND_KEY, soundEnabled() ? 'off' : 'on');
            } catch (error) { /* storage blocked; toggle applies to this page only */ }
            render();
            if (soundEnabled()) {
                primeAudio();
                playChime();
            }
        });

        render();
    }

    /**
     * Swap the message list, folder counts and total in place rather than
     * reloading, so an open compose window and anything typed in it survive.
     */
    function refreshList() {
        var current = document.querySelector('[data-mail-list]');
        if (!current) return Promise.resolve();
        var selected = document.querySelector('[data-mail-checkbox]:checked');
        if (selected) return Promise.resolve();

        return fetch(window.location.href, { headers: { 'X-Requested-With': 'fetch' }, cache: 'no-store' })
            .then(function (response) { return response.text(); })
            .then(function (html) {
                var fresh = new DOMParser().parseFromString(html, 'text/html');
                var freshList = fresh.querySelector('[data-mail-list]');
                if (freshList) current.innerHTML = freshList.innerHTML;
                [['[data-mail-nav]', 'innerHTML'], ['[data-mail-total]', 'textContent']].forEach(function (pair) {
                    var target = document.querySelector(pair[0]);
                    var source = fresh.querySelector(pair[0]);
                    if (target && source) target[pair[1]] = source[pair[1]];
                });
                initList();
            })
            .catch(function () { /* offline; the next poll will retry */ });
    }

    function initLiveMail() {
        var body = document.body;
        if (!body.dataset.mailLatestId) return;

        var known = parseInt(body.dataset.mailLatestId, 10) || 0;
        var baseTitle = document.title.replace(/^\(\d+\)\s*/, '');

        var setTitle = function (unread) {
            document.title = unread > 0 ? '(' + unread + ') ' + baseTitle : baseTitle;
        };
        setTitle(parseInt(body.dataset.mailUnread, 10) || 0);

        var timer = null;
        var running = false;

        var schedule = function (delay) {
            window.clearTimeout(timer);
            timer = window.setTimeout(poll, delay);
        };

        function poll() {
            if (running) return;
            running = true;
            fetch('/mail/updates', { headers: { Accept: 'application/json' }, cache: 'no-store' })
                .then(function (response) {
                    if (!response.ok) throw new Error('poll failed');
                    return response.json();
                })
                .then(function (state) {
                    if (!state || !state.ok) return;
                    setTitle(state.unread || 0);
                    var latest = parseInt(state.latest_id, 10) || 0;
                    if (latest <= known) return;
                    known = latest;
                    body.dataset.mailLatestId = String(latest);
                    playChime();
                    showToast('New message received.');
                    return refreshList();
                })
                .catch(function () { /* transient network error; retry on the next tick */ })
                .then(function () {
                    running = false;
                    schedule(document.hidden ? POLL_HIDDEN_MS : POLL_VISIBLE_MS);
                });
        }

        schedule(POLL_VISIBLE_MS);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) schedule(500);
        });
        ['pointerdown', 'keydown'].forEach(function (type) {
            document.addEventListener(type, primeAudio, { once: true });
        });
    }

    function initRecipientReveal() {
        document.querySelectorAll('[data-mail-recipient-toggle]').forEach(function (button) {
            var list = button.closest('[data-mail-recipient-list]');
            var rest = list ? list.querySelector('[data-mail-recipient-rest]') : null;
            if (!rest) return;
            button.addEventListener('click', function () {
                var expanded = button.getAttribute('aria-expanded') === 'true';
                rest.hidden = expanded;
                button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                button.textContent = expanded ? (button.dataset.moreLabel || '') : 'Show less';
            });
        });
    }

    function initSidebar() {
        var menu = document.querySelector('[data-mail-menu]');
        var sidebar = document.querySelector('[data-mail-sidebar]');
        if (menu && sidebar) {
            menu.addEventListener('click', function () { sidebar.classList.toggle('is-open'); });
        }
    }

    function initFrames() {
        document.querySelectorAll('.mail-body-frame').forEach(function (frame) {
            frame.addEventListener('load', function () {
                try {
                    frame.style.height = Math.max(450, frame.contentDocument.documentElement.scrollHeight + 20) + 'px';
                } catch (error) { /* cross-origin body, keep default height */ }
            });
        });
    }

    /* ------------------------------------------------------------------- popovers */

    /**
     * Menus are positioned as fixed elements so they escape the compose window's
     * overflow clipping, and they flip above/below and left/right to stay on screen.
     */
    function placePanel(toggle, panel, alignRight) {
        panel.style.position = 'fixed';
        panel.style.left = '0px';
        panel.style.top = '0px';
        panel.style.visibility = 'hidden';
        panel.hidden = false;

        var rect = toggle.getBoundingClientRect();
        var width = panel.offsetWidth;
        var height = panel.offsetHeight;

        var left = alignRight ? rect.right - width : rect.left;
        left = Math.min(left, window.innerWidth - width - POP_EDGE);
        left = Math.max(POP_EDGE, left);

        var top = rect.top - height - POP_GAP;
        if (top < POP_EDGE) {
            top = Math.min(rect.bottom + POP_GAP, window.innerHeight - height - POP_EDGE);
            top = Math.max(POP_EDGE, top);
        }

        panel.style.left = Math.round(left) + 'px';
        panel.style.top = Math.round(top) + 'px';
        panel.style.visibility = '';
    }

    function closePopover(pop) {
        pop.classList.remove('is-open');
        var toggle = pop.querySelector('[data-mail-pop-toggle]');
        var panel = pop.querySelector('[data-mail-pop-panel]');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
        if (panel) {
            panel.hidden = true;
            panel.style.left = '';
            panel.style.top = '';
        }
    }

    function closeAllPopovers(except) {
        document.querySelectorAll('[data-mail-pop].is-open').forEach(function (pop) {
            if (pop !== except) closePopover(pop);
        });
    }

    function initPopovers() {
        document.querySelectorAll('[data-mail-pop]').forEach(function (pop) {
            var toggle = pop.querySelector('[data-mail-pop-toggle]');
            var panel = pop.querySelector('[data-mail-pop-panel]');
            if (!toggle || !panel) return;

            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                var wasOpen = pop.classList.contains('is-open');
                closeAllPopovers(null);
                if (wasOpen) return;

                pop.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
                placePanel(toggle, panel, panel.classList.contains('mail-pop-panel--right'));

                var firstField = panel.querySelector('input,textarea');
                if (firstField) firstField.focus();
            });

            panel.addEventListener('click', function (event) {
                if (event.target.closest('[data-mail-pop-keep]')) return;
                if (event.target.closest('button,a')) closePopover(pop);
            });
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('[data-mail-pop]')) closeAllPopovers(null);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeAllPopovers(null);
        });

        window.addEventListener('resize', function () { closeAllPopovers(null); });
    }

    /* --------------------------------------------------------------- rich editor */

    function createEditorController(toolbar) {
        var editor = document.getElementById(toolbar.dataset.mailToolbar);
        if (!editor) return null;

        var savedRange = null;

        function rememberSelection() {
            var selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) return;
            var range = selection.getRangeAt(0);
            if (editor.contains(range.commonAncestorContainer)) savedRange = range.cloneRange();
        }

        function caretToEnd() {
            var range = document.createRange();
            range.selectNodeContents(editor);
            range.collapse(false);
            var selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            savedRange = range.cloneRange();
        }

        function restoreSelection() {
            editor.focus();
            if (!savedRange) {
                caretToEnd();
                return;
            }
            var selection = window.getSelection();
            if (!selection) return;
            selection.removeAllRanges();
            selection.addRange(savedRange);
        }

        function syncStates() {
            toolbar.querySelectorAll('[data-mail-state]').forEach(function (button) {
                var active = false;
                try { active = document.queryCommandState(button.dataset.mailState); } catch (error) { active = false; }
                button.classList.toggle('is-active', !!active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }

        function run(command, value) {
            restoreSelection();
            if (STYLED_COMMANDS.indexOf(command) !== -1) {
                try { document.execCommand('styleWithCSS', false, true); } catch (error) { /* older engines */ }
            }
            document.execCommand(command, false, value || null);
            rememberSelection();
            syncStates();
            editor.dispatchEvent(new Event('input', { bubbles: true }));
        }

        document.addEventListener('selectionchange', function () {
            var selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) return;
            if (editor.contains(selection.getRangeAt(0).commonAncestorContainer)) {
                rememberSelection();
                syncStates();
            }
        });

        // Keep the caret inside the editor while clicking toolbar controls.
        toolbar.addEventListener('mousedown', function (event) {
            if (!event.target.closest('input,textarea,select,a')) event.preventDefault();
        });

        toolbar.querySelectorAll('[data-mail-cmd]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                run(button.dataset.mailCmd, button.dataset.mailValue);
                var label = button.dataset.mailLabel;
                if (label) {
                    var target = toolbar.querySelector('[data-mail-fmt-label="' + button.dataset.mailCmd + '"]');
                    if (target) target.textContent = label;
                }
            });
        });

        editor.addEventListener('keyup', syncStates);
        editor.addEventListener('mouseup', rememberSelection);
        editor.addEventListener('blur', rememberSelection);

        editor.addEventListener('keydown', function (event) {
            if (!(event.metaKey || event.ctrlKey)) return;
            var key = event.key.toLowerCase();
            if (key === 'b' || key === 'i' || key === 'u') {
                event.preventDefault();
                run({ b: 'bold', i: 'italic', u: 'underline' }[key]);
            }
        });

        /**
         * Images must be uploaded and referenced by URL: a pasted image arrives as
         * a blob or data URL, which email clients refuse to render.
         */
        function insertImageUrl(url) {
            return new Promise(function (resolve) {
                var probe = new window.Image();
                var insert = function (attributes) {
                    run('insertHTML', '<img src="' + url + '" alt=""' + attributes + ' style="max-width:100%;height:auto">');
                    resolve();
                };
                probe.onload = function () {
                    insert(' width="' + Math.min(probe.naturalWidth || IMAGE_MAX_WIDTH, IMAGE_MAX_WIDTH) + '"');
                };
                probe.onerror = function () { insert(''); };
                probe.src = url;
            });
        }

        function uploadImage(file) {
            var payload = new FormData();
            payload.append('image', file);
            payload.append('_csrf', csrfToken(toolbar.closest('form')) || csrfToken(document));

            return fetch('/mail/upload-image', { method: 'POST', body: payload, headers: { Accept: 'application/json' } })
                .then(function (response) {
                    return response.json().catch(function () { return { ok: false }; });
                })
                .then(function (result) {
                    if (result && result.ok && result.url) return insertImageUrl(result.url);
                    alertMessage({
                        title: 'Could not add image',
                        message: (result && result.error) || 'Use a PNG, JPEG, GIF or WebP under 5 MB.',
                        tone: 'error',
                    });
                });
        }

        function insertImages(candidates) {
            var images = imageFilesFrom(candidates);
            if (images.length === 0) return false;
            rememberSelection();
            images.reduce(function (chain, file) {
                return chain.then(function () { return uploadImage(file); });
            }, Promise.resolve());
            return true;
        }

        var imageInput = toolbar.querySelector('[data-mail-image-input]');
        var imageButton = toolbar.querySelector('[data-mail-insert-image]');
        if (imageInput && imageButton) {
            imageButton.addEventListener('click', function () { imageInput.click(); });
            imageInput.addEventListener('change', function () {
                insertImages(imageInput.files);
                imageInput.value = '';
            });
        }

        editor.addEventListener('paste', function (event) {
            var data = event.clipboardData;
            if (!data) return;
            if (insertImages(data.files && data.files.length ? data.files : data.items)) {
                event.preventDefault();
                return;
            }
            var html = data.getData('text/html');
            if (!html) return;
            event.preventDefault();
            run('insertHTML', cleanPastedHtml(html));
        });

        editor.addEventListener('dragover', function (event) {
            if (imageFilesFrom(event.dataTransfer && event.dataTransfer.items).length > 0) event.preventDefault();
        });

        editor.addEventListener('drop', function (event) {
            var files = event.dataTransfer && event.dataTransfer.files;
            if (imageFilesFrom(files).length === 0) return;
            event.preventDefault();
            caretFromPoint(editor, event.clientX, event.clientY);
            insertImages(files);
        });

        return { editor: editor, run: run, rememberSelection: rememberSelection, insertImages: insertImages };
    }

    function initEditors() {
        var controllers = {};
        document.querySelectorAll('[data-mail-toolbar]').forEach(function (toolbar) {
            var controller = createEditorController(toolbar);
            if (controller) controllers[toolbar.dataset.mailToolbar] = controller;
        });
        return controllers;
    }

    /* ------------------------------------------------------------------ recipients */

    /**
     * Gmail behaviour: Cc and Bcc stay hidden until asked for, and once the user
     * moves on, every recipient collapses onto a single line with a Bcc count.
     */
    function initRecipients(panel, form) {
        var wrap = panel.querySelector('[data-mail-recipients]');
        if (!wrap) return null;

        var summary = wrap.querySelector('[data-mail-recipients-summary]');
        var fields = wrap.querySelector('[data-mail-recipients-fields]');
        var names = wrap.querySelector('[data-mail-recipients-names]');
        var bccChip = wrap.querySelector('[data-mail-recipients-bcc]');
        var inputs = {
            to: form.querySelector('[name="to"]'),
            cc: form.querySelector('[name="cc"]'),
            bcc: form.querySelector('[name="bcc"]'),
        };
        var shown = null;

        function row(name) {
            return wrap.querySelector('[data-mail-field-row="' + name + '"]');
        }

        function chipLabel(overflow, bccCount) {
            if (overflow > 0 && bccCount > 0) return overflow + ' more (' + bccCount + ' Bcc)';
            if (overflow > 0) return overflow + ' more';
            if (bccCount > 0) return bccCount + ' Bcc';
            return '';
        }

        /**
         * Show as many names as fit on the line and roll the rest into a
         * "3 more (2 Bcc)" chip, the way Gmail summarises long recipient lists.
         */
        function renderSummary() {
            if (summary.hidden || !shown) return;

            var people = shown.people;
            var bccCount = shown.bcc.length;
            var style = window.getComputedStyle(summary);
            var available = summary.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight);
            var nameFont = fontOf(names);
            var chipFont = fontOf(bccChip);

            for (var visible = people.length; visible >= 1; visible--) {
                var label = chipLabel(people.length - visible, bccCount);
                var chipWidth = label === '' ? 0 : textWidth(label, chipFont) + CHIP_EXTRA;
                var text = people.slice(0, visible).join(', ');
                var fits = available <= 0 || textWidth(text, nameFont) + chipWidth <= available;
                if (fits || visible === 1) {
                    names.textContent = text;
                    bccChip.textContent = label;
                    bccChip.hidden = label === '';
                    return;
                }
            }
        }

        function collapse() {
            var people = emailList(inputs.to ? inputs.to.value : '').concat(emailList(inputs.cc ? inputs.cc.value : ''));
            if (people.length === 0) return false;

            shown = { people: people, bcc: emailList(inputs.bcc ? inputs.bcc.value : '') };
            summary.hidden = false;
            fields.hidden = true;
            renderSummary();
            return true;
        }

        function expand(focusField) {
            summary.hidden = true;
            fields.hidden = false;
            ['cc', 'bcc'].forEach(function (name) {
                var fieldRow = row(name);
                if (!fieldRow) return;
                var filled = inputs[name] && inputs[name].value.trim() !== '';
                fieldRow.hidden = !filled && name !== focusField;
            });
            var target = inputs[focusField] || inputs.to;
            if (target) target.focus();
        }

        if (summary) summary.addEventListener('click', function () { expand(); });

        wrap.querySelectorAll('[data-mail-toggle-field]').forEach(function (button) {
            button.addEventListener('click', function () {
                var fieldRow = row(button.dataset.mailToggleField);
                if (!fieldRow) return;
                fieldRow.hidden = false;
                var input = inputs[button.dataset.mailToggleField];
                if (input) input.focus();
            });
        });

        wrap.addEventListener('focusout', function () {
            window.setTimeout(function () {
                if (wrap.contains(document.activeElement)) return;
                collapse();
            }, 0);
        });

        // Fallback for clicks that never move focus (toolbar buttons, padding).
        document.addEventListener('mousedown', function (event) {
            if (fields.hidden || wrap.contains(event.target)) return;
            window.setTimeout(collapse, 0);
        });

        Object.keys(inputs).forEach(function (name) {
            var input = inputs[name];
            if (!input) return;
            input.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                if (collapse()) {
                    var subject = form.querySelector('[name="subject"]');
                    if (subject) subject.focus();
                }
            });
        });

        if (window.ResizeObserver) {
            new window.ResizeObserver(renderSummary).observe(summary);
        } else {
            window.addEventListener('resize', renderSummary);
        }

        // Recipients rendered collapsed by the server still need measuring.
        if (!summary.hidden) collapse();

        return { collapse: collapse, expand: expand, render: renderSummary };
    }

    /* ---------------------------------------------------------------- compose panel */

    function initCompose(controllers) {
        var panel = document.querySelector('[data-mail-compose]');
        if (!panel) return;

        var form = panel.querySelector('[data-mail-compose-form]');
        var editor = panel.querySelector('[data-mail-editor]');
        var bodyField = panel.querySelector('[data-mail-body-field]');
        var actionField = panel.querySelector('[data-mail-action-field]');
        var scheduledField = panel.querySelector('[data-mail-scheduled-field]');
        var returnField = panel.querySelector('[data-mail-return-field]');
        var draftField = panel.querySelector('[data-mail-draft-id]');
        var toInput = form.querySelector('[name="to"]');
        var controller = controllers[editor ? editor.id : ''] || null;
        var recipients = initRecipients(panel, form);

        if (returnField) returnField.value = window.location.pathname + window.location.search;

        function open() {
            panel.hidden = false;
            panel.classList.add('is-open');
            panel.classList.remove('is-minimized');
            window.requestAnimationFrame(function () {
                if (toInput && toInput.value.trim() === '') toInput.focus();
                else if (editor) editor.focus();
            });
        }

        function hide() {
            panel.classList.remove('is-open', 'is-minimized', 'is-full');
            panel.hidden = true;
        }

        function hasContent() {
            var filled = ['to', 'cc', 'bcc', 'subject'].some(function (name) {
                var field = form.querySelector('[name="' + name + '"]');
                return field && field.value.trim() !== '';
            });
            return filled || (editor ? editor.textContent.replace(/\s+/g, '') !== '' : false);
        }

        function submitWith(action) {
            if (bodyField && editor) bodyField.value = editor.innerHTML;
            if (actionField) actionField.value = action;
            form.submit();
        }

        if (panel.classList.contains('is-open')) open();

        panel.querySelectorAll('[data-mail-compose-minimize]').forEach(function (button) {
            button.addEventListener('click', function () {
                panel.classList.toggle('is-minimized');
                panel.classList.remove('is-full');
                closeAllPopovers(null);
            });
        });

        panel.querySelectorAll('[data-mail-compose-expand]').forEach(function (button) {
            button.addEventListener('click', function () {
                panel.classList.toggle('is-full');
                panel.classList.remove('is-minimized');
                closeAllPopovers(null);
            });
        });

        var bar = panel.querySelector('[data-mail-compose-bar]');
        if (bar) {
            bar.addEventListener('click', function (event) {
                if (event.target.closest('button')) return;
                if (panel.classList.contains('is-minimized')) panel.classList.remove('is-minimized');
            });
        }

        panel.querySelectorAll('[data-mail-compose-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (hasContent()) {
                    submitWith('draft');
                    return;
                }
                hide();
            });
        });

        var toggleFormat = panel.querySelector('[data-mail-toggle-format]');
        var formatBar = panel.querySelector('.mail-fmt-bar');
        if (toggleFormat && formatBar) {
            toggleFormat.addEventListener('click', function () {
                var hidden = formatBar.classList.toggle('is-hidden');
                toggleFormat.classList.toggle('is-active', !hidden);
                toggleFormat.setAttribute('aria-pressed', hidden ? 'false' : 'true');
                closeAllPopovers(null);
            });
        }

        var fileInput = panel.querySelector('[data-mail-file-input]');
        var attachList = panel.querySelector('[data-mail-attach-list]');
        var attachButton = panel.querySelector('[data-mail-attach]');
        var attachments = fileInput && attachList ? createAttachments(fileInput, attachList) : null;
        if (attachButton && fileInput) {
            attachButton.addEventListener('click', function () { fileInput.click(); });
        }

        var linkInput = panel.querySelector('[data-mail-link-input]');
        var linkApply = panel.querySelector('[data-mail-link-apply]');
        if (linkInput && linkApply && controller) {
            var applyLink = function () {
                var url = linkInput.value.trim();
                if (url === '') return;
                if (!/^(https?:|mailto:|tel:)/i.test(url)) url = 'https://' + url;
                controller.run('createLink', url);
                linkInput.value = '';
                closeAllPopovers(null);
            };
            linkApply.addEventListener('click', applyLink);
            linkInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyLink();
                }
            });
        }

        panel.querySelectorAll('[data-mail-pop-dismiss]').forEach(function (button) {
            button.addEventListener('click', function () { closeAllPopovers(null); });
        });

        panel.querySelectorAll('[data-mail-insert-signature]').forEach(function (button) {
            button.addEventListener('click', function () {
                var template = panel.querySelector('[data-mail-signature-html="' + button.dataset.mailInsertSignature + '"]');
                if (!template || !editor) return;
                editor.querySelectorAll('[data-lb-signature]').forEach(function (existing) { existing.remove(); });
                editor.insertAdjacentHTML('beforeend', template.innerHTML);
                editor.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });

        var discard = panel.querySelector('[data-mail-discard]');
        if (discard) {
            discard.addEventListener('click', function () {
                confirmAction({
                    title: 'Discard this draft?',
                    message: 'The message and anything you typed will be deleted.',
                    confirmLabel: 'Discard',
                    cancelLabel: 'Keep editing',
                    tone: 'danger',
                }).then(function (ok) {
                    if (!ok) return;
                    var draftId = draftField ? parseInt(draftField.value, 10) : 0;
                    if (draftId > 0) {
                        post('/mail/actions', { action: 'discard_draft', message_id: draftId, _csrf: csrfToken(form) })
                            .then(function () { window.location.reload(); });
                        return;
                    }
                    if (editor) editor.innerHTML = '';
                    ['to', 'cc', 'bcc', 'subject'].forEach(function (name) {
                        var field = form.querySelector('[name="' + name + '"]');
                        if (field) field.value = '';
                    });
                    if (attachments) attachments.reset();
                    if (recipients) recipients.expand();
                    hide();
                });
            });
        }

        var sendButton = panel.querySelector('[data-mail-send]');
        if (sendButton) {
            sendButton.addEventListener('click', function (event) {
                event.preventDefault();
                if (scheduledField) scheduledField.value = '';
                if (!toInput || toInput.value.trim() === '') {
                    if (recipients) recipients.expand();
                    alertMessage({ title: 'Add a recipient', message: 'Enter at least one email address in the To field.', tone: 'error' });
                    return;
                }
                submitWith('send');
            });
        }

        panel.querySelectorAll('[data-mail-save-draft]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                submitWith('draft');
            });
        });

        initSchedule(panel, scheduledField, submitWith, toInput, recipients);
    }

    /**
     * A file input replaces its whole FileList on every pick, so attachments are
     * kept in our own list and written back to the input before submit.
     */
    function createAttachments(input, list) {
        var files = [];

        function identity(file) {
            return file.name + '|' + file.size + '|' + file.lastModified;
        }

        function render() {
            list.innerHTML = '';
            list.hidden = files.length === 0;
            files.forEach(function (file, index) {
                var chip = document.createElement('span');
                chip.className = 'mail-attach-chip';
                chip.textContent = file.name + ' (' + Math.max(1, Math.round(file.size / 1024)) + ' KB)';

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'mail-attach-chip__remove';
                remove.setAttribute('aria-label', 'Remove ' + file.name);
                remove.textContent = '\u00d7';
                remove.addEventListener('click', function () {
                    files.splice(index, 1);
                    sync();
                });

                chip.appendChild(remove);
                list.appendChild(chip);
            });
        }

        function sync() {
            var transfer = new DataTransfer();
            files.forEach(function (file) { transfer.items.add(file); });
            input.files = transfer.files;
            render();
        }

        function add(incoming) {
            [].slice.call(incoming || []).forEach(function (file) {
                var duplicate = files.some(function (existing) {
                    return identity(existing) === identity(file);
                });
                if (!duplicate) files.push(file);
            });
            sync();
        }

        // Assigning input.files does not fire change, so this cannot loop.
        input.addEventListener('change', function () { add(input.files); });

        return {
            add: add,
            reset: function () {
                files = [];
                sync();
            },
        };
    }

    /* -------------------------------------------------------------- schedule send */

    function initSchedule(panel, scheduledField, submitWith, toInput, recipients) {
        var sheet = panel.querySelector('[data-mail-schedule]');
        if (!sheet || !scheduledField) return;

        var fields = sheet.querySelector('[data-mail-schedule-fields]');
        var dateInput = sheet.querySelector('[data-mail-schedule-date]');
        var timeInput = sheet.querySelector('[data-mail-schedule-time]');
        var error = sheet.querySelector('[data-mail-schedule-error]');

        function showError(message) {
            if (!error) return;
            error.textContent = message;
            error.hidden = message === '';
        }

        function closeSheet() {
            sheet.hidden = true;
            if (fields) fields.hidden = true;
            showError('');
        }

        panel.querySelectorAll('[data-mail-schedule-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!toInput || toInput.value.trim() === '') {
                    if (recipients) recipients.expand();
                    alertMessage({ title: 'Add a recipient', message: 'Enter at least one email address before scheduling.', tone: 'error' });
                    return;
                }
                sheet.hidden = false;
                showError('');
            });
        });

        sheet.querySelectorAll('[data-mail-schedule-close]').forEach(function (button) {
            button.addEventListener('click', closeSheet);
        });

        sheet.querySelectorAll('[data-mail-schedule-preset]').forEach(function (button) {
            button.addEventListener('click', function () {
                scheduledField.value = button.dataset.mailSchedulePreset || '';
                submitWith('send');
            });
        });

        var custom = sheet.querySelector('[data-mail-schedule-custom]');
        if (custom && fields) {
            custom.addEventListener('click', function () {
                fields.hidden = false;
                if (dateInput && dateInput.value === '') {
                    var tomorrow = new Date(Date.now() + 86400000);
                    dateInput.value = tomorrow.getFullYear() + '-'
                        + String(tomorrow.getMonth() + 1).padStart(2, '0') + '-'
                        + String(tomorrow.getDate()).padStart(2, '0');
                }
                if (timeInput && timeInput.value === '') timeInput.value = '08:00';
                if (dateInput) dateInput.focus();
            });
        }

        var submit = sheet.querySelector('[data-mail-schedule-submit]');
        if (submit) {
            submit.addEventListener('click', function () {
                var date = dateInput ? dateInput.value : '';
                var time = timeInput ? timeInput.value : '';
                if (date === '' || time === '') {
                    showError('Choose both a date and a time.');
                    return;
                }
                if (new Date(date + 'T' + time).getTime() <= Date.now()) {
                    showError('Pick a time in the future.');
                    return;
                }
                scheduledField.value = date + ' ' + time;
                submitWith('send');
            });
        }
    }

    /* ------------------------------------------------------------------ signatures */

    function initSignatureForms() {
        document.querySelectorAll('[data-mail-signature-form]').forEach(function (form) {
            var editor = form.querySelector('[data-mail-editor]');
            var field = form.querySelector('[data-mail-signature-field]');
            if (!editor || !field) return;
            form.addEventListener('submit', function () { field.value = editor.innerHTML; });
        });

        document.querySelectorAll('[data-mail-delete-signature]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                confirmAction({
                    title: 'Delete this signature?',
                    message: 'It will be removed from your account. Sent emails keep the signature they already have.',
                    confirmLabel: 'Delete',
                    cancelLabel: 'Cancel',
                    tone: 'danger',
                }).then(function (ok) {
                    if (ok) button.closest('form').submit();
                });
            });
        });
    }

    function initAvatarForm() {
        var input = document.querySelector('[data-mail-avatar-input]');
        if (input) {
            var preview = document.querySelector('[data-mail-avatar-preview]');
            var note = document.querySelector('[data-mail-avatar-note]');
            var defaultNote = note ? note.textContent : '';
            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (note) note.textContent = file ? file.name : defaultNote;
                if (!file || !preview) return;
                var url = URL.createObjectURL(file);
                var circle = preview.querySelector('.mail-avatar');
                var image = circle && circle.querySelector('img');
                if (!image) {
                    if (circle) circle.textContent = '';
                    image = document.createElement('img');
                    image.alt = '';
                    if (circle) {
                        circle.classList.add('mail-avatar--photo');
                        circle.appendChild(image);
                    }
                }
                image.addEventListener('load', function () { URL.revokeObjectURL(url); }, { once: true });
                image.src = url;
            });
        }

        var remove = document.querySelector('[data-mail-remove-avatar]');
        var removeForm = document.querySelector('[data-mail-remove-avatar-form]');
        if (!remove || !removeForm) return;
        remove.addEventListener('click', function (event) {
            event.preventDefault();
            confirmAction({
                title: 'Remove your profile picture?',
                message: 'Your initials will be shown instead.',
                confirmLabel: 'Remove',
                cancelLabel: 'Cancel',
                tone: 'danger',
            }).then(function (ok) {
                if (ok) removeForm.submit();
            });
        });
    }

    function init() {
        initList();
        initSoundToggle();
        initLiveMail();
        initRecipientReveal();
        initSidebar();
        initFrames();
        initPopovers();
        initCompose(initEditors());
        initSignatureForms();
        initAvatarForm();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
