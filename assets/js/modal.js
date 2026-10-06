(function () {
    'use strict';

    var overlay = null;
    var dialog = null;
    var titleEl = null;
    var messageEl = null;
    var actionsEl = null;
    var fieldWrap = null;
    var fieldLabel = null;
    var fieldInput = null;
    var modalBox = null;
    var lastFocused = null;
    var activeResolve = null;
    var activeMode = null;
    var pendingQueue = [];

    function ensureShell() {
        if (overlay) {
            return;
        }

        overlay = document.createElement('div');
        overlay.className = 'lb-modal-overlay is-hidden';
        overlay.setAttribute('aria-hidden', 'true');
        overlay.innerHTML =
            '<div class="lb-modal" role="dialog" aria-modal="true" aria-labelledby="lb-modal-title" aria-describedby="lb-modal-message">' +
            '<h2 class="lb-modal-title font-heading" id="lb-modal-title"></h2>' +
            '<p class="lb-modal-message font-sans" id="lb-modal-message"></p>' +
            '<div class="lb-modal-field is-hidden" id="lb-modal-field">' +
            '<label class="lb-modal-label font-sans" for="lb-modal-input" id="lb-modal-label"></label>' +
            '<input class="lb-modal-input font-sans" id="lb-modal-input" type="text" autocomplete="off" />' +
            '</div>' +
            '<div class="lb-modal-actions"></div>' +
            '</div>';

        document.body.appendChild(overlay);

        dialog = overlay.querySelector('[role="dialog"]');
        modalBox = overlay.querySelector('.lb-modal');
        titleEl = overlay.querySelector('#lb-modal-title');
        messageEl = overlay.querySelector('#lb-modal-message');
        fieldWrap = overlay.querySelector('#lb-modal-field');
        fieldLabel = overlay.querySelector('#lb-modal-label');
        fieldInput = overlay.querySelector('#lb-modal-input');
        actionsEl = overlay.querySelector('.lb-modal-actions');

        overlay.addEventListener('click', function (event) {
            if (event.target !== overlay) {
                return;
            }
            close(false);
        });

        document.addEventListener('keydown', onDocumentKeydown);
    }

    function onDocumentKeydown(event) {
        if (!overlay || overlay.classList.contains('is-hidden')) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            close(activeMode === 'alert');
        }

        if (event.key === 'Tab' && dialog) {
            trapFocus(event);
        }
    }

    function trapFocus(event) {
        var focusable = dialog.querySelectorAll(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        if (!focusable.length) {
            return;
        }

        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    function normalizeOptions(options, mode) {
        if (typeof options === 'string') {
            return { message: options, mode: mode };
        }

        if (!options || typeof options !== 'object') {
            return { message: '', mode: mode };
        }

        return Object.assign({ mode: mode }, options);
    }

    function setOpen(isOpen) {
        overlay.classList.toggle('is-hidden', !isOpen);
        overlay.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    function close(result, promptValue) {
        if (!activeResolve) {
            return;
        }

        var mode = activeMode;
        var resolve = activeResolve;
        activeResolve = null;
        activeMode = null;
        setOpen(false);

        if (fieldWrap) {
            fieldWrap.classList.add('is-hidden');
        }
        if (fieldInput) {
            fieldInput.value = '';
            fieldInput.onkeydown = null;
        }

        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
        lastFocused = null;

        if (mode === 'prompt') {
            resolve(result === true ? String(promptValue || '').trim() : null);
        } else if (mode === 'alert') {
            resolve();
        } else {
            resolve(result === true);
        }

        if (pendingQueue.length) {
            var next = pendingQueue.shift();
            show(next.options, next.resolve);
        }
    }

    function show(options, resolve) {
        ensureShell();

        if (activeResolve) {
            pendingQueue.push({ options: options, resolve: resolve });
            return;
        }

        activeResolve = resolve;
        activeMode = options.mode || 'confirm';

        var tone = options.tone || (options.danger ? 'danger' : 'default');
        modalBox.classList.remove('lb-modal--danger', 'lb-modal--error');
        if (tone === 'danger') {
            modalBox.classList.add('lb-modal--danger');
        } else if (tone === 'error') {
            modalBox.classList.add('lb-modal--error');
        }

        var title = options.title || (activeMode === 'alert' ? 'Notice' : activeMode === 'prompt' ? 'Enter a value' : 'Are you sure?');
        titleEl.textContent = title;
        messageEl.textContent = options.message || '';
        messageEl.hidden = !options.message;
        actionsEl.innerHTML = '';

        if (fieldWrap && fieldInput && fieldLabel) {
            if (activeMode === 'prompt') {
                fieldWrap.classList.remove('is-hidden');
                fieldLabel.textContent = options.label || options.inputLabel || 'Value';
                fieldInput.type = options.inputType || 'text';
                fieldInput.placeholder = options.placeholder || '';
                fieldInput.value = options.defaultValue || '';
                fieldInput.onkeydown = function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        close(true, fieldInput.value);
                    }
                };
            } else {
                fieldWrap.classList.add('is-hidden');
                fieldInput.value = '';
                fieldInput.onkeydown = null;
            }
        }

        if (activeMode === 'alert') {
            var okBtn = document.createElement('button');
            okBtn.type = 'button';
            okBtn.className = 'lb-modal-btn lb-modal-btn--primary';
            okBtn.textContent = options.confirmLabel || options.okLabel || 'OK';
            okBtn.addEventListener('click', function () {
                close(true);
            });
            actionsEl.appendChild(okBtn);
            lastFocused = document.activeElement;
            setOpen(true);
            okBtn.focus();
            return;
        }

        var cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'lb-modal-btn lb-modal-btn--cancel';
        cancelBtn.textContent = options.cancelLabel || 'Cancel';
        cancelBtn.addEventListener('click', function () {
            close(false);
        });

        var confirmBtn = document.createElement('button');
        confirmBtn.type = 'button';
        if (activeMode === 'prompt') {
            confirmBtn.className = 'lb-modal-btn lb-modal-btn--primary';
            confirmBtn.textContent = options.confirmLabel || 'Save';
            confirmBtn.addEventListener('click', function () {
                close(true, fieldInput ? fieldInput.value : '');
            });
        } else {
            confirmBtn.className =
                'lb-modal-btn ' + (tone === 'danger' ? 'lb-modal-btn--danger' : 'lb-modal-btn--confirm');
            confirmBtn.textContent = options.confirmLabel || 'Confirm';
            confirmBtn.addEventListener('click', function () {
                close(true);
            });
        }

        actionsEl.appendChild(cancelBtn);
        actionsEl.appendChild(confirmBtn);

        lastFocused = document.activeElement;
        setOpen(true);
        if (activeMode === 'prompt' && fieldInput) {
            fieldInput.focus();
            fieldInput.select();
            return;
        }
        (tone === 'danger' ? confirmBtn : cancelBtn).focus();
    }

    function confirm(options) {
        options = normalizeOptions(options, 'confirm');
        return new Promise(function (resolve) {
            show(options, function (result) {
                resolve(result === true);
            });
        });
    }

    function alert(options) {
        options = normalizeOptions(options, 'alert');
        return new Promise(function (resolve) {
            show(options, function () {
                resolve();
            });
        });
    }

    function prompt(options) {
        options = normalizeOptions(options, 'prompt');
        return new Promise(function (resolve) {
            show(options, resolve);
        });
    }

    function bindConfirmTriggers(root) {
        (root || document).querySelectorAll('[data-lb-confirm]').forEach(function (trigger) {
            if (trigger.getAttribute('data-lb-confirm-bound') === '1') {
                return;
            }
            trigger.setAttribute('data-lb-confirm-bound', '1');

            trigger.addEventListener('click', function (event) {
                event.preventDefault();

                var message = trigger.getAttribute('data-lb-confirm') || 'Are you sure?';
                var title = trigger.getAttribute('data-lb-confirm-title') || 'Are you sure?';
                var confirmLabel = trigger.getAttribute('data-lb-confirm-label') || 'Confirm';
                var cancelLabel = trigger.getAttribute('data-lb-confirm-cancel') || 'Cancel';
                var tone = trigger.getAttribute('data-lb-confirm-tone') || 'default';
                var form = trigger.closest('form');

                confirm({
                    title: title,
                    message: message,
                    confirmLabel: confirmLabel,
                    cancelLabel: cancelLabel,
                    tone: tone,
                }).then(function (ok) {
                    if (!ok) {
                        return;
                    }

                    if (form) {
                        form.submit();
                        return;
                    }

                    var href = trigger.getAttribute('href');
                    if (href) {
                        window.location.href = href;
                    }
                });
            });
        });
    }

    function init(root) {
        bindConfirmTriggers(root);
    }

    window.LeadBirdModal = {
        confirm: confirm,
        alert: alert,
        prompt: prompt,
        init: init,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            init(document);
        });
    } else {
        init(document);
    }
})();
