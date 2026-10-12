(function () {
    'use strict';

    var form = document.querySelector('[data-wtc-form]');
    if (!form) {
        return;
    }

    var nameInput = form.querySelector('[data-wtc-name]');
    var language = form.querySelector('[data-wtc-language]');
    var counter = nameInput.parentNode.querySelector('.wtb-counter');

    function setError(field, message) {
        var wrap = form.querySelector('[data-wtc-field="' + field + '"]');
        var hint = wrap.querySelector('[data-wtc-hint]');
        form.querySelector('[data-wtc-error="' + field + '"]').textContent = message;
        wrap.classList.toggle('is-invalid', message !== '');
        if (hint) {
            hint.hidden = message !== '';
        }
    }

    function nameError() {
        var name = nameInput.value;
        var rule = 'Template names can only contain lowercase alphanumeric characters and underscores. No other characters or spaces are allowed.';
        if (name === '') {
            return 'Template name is required. ' + rule;
        }
        if (!/^[a-z0-9_]+$/.test(name)) {
            return rule;
        }
        return '';
    }

    nameInput.addEventListener('input', function () {
        counter.textContent = nameInput.value.length + '/' + nameInput.getAttribute('maxlength');
        if (nameInput.getAttribute('aria-invalid') === 'true') {
            var message = nameError();
            setError('name', message);
            nameInput.setAttribute('aria-invalid', message ? 'true' : 'false');
        }
    });

    language.addEventListener('change', function () {
        setError('language', '');
    });

    form.addEventListener('change', function (event) {
        if (event.target.hasAttribute('data-wtc-type')) {
            setError('type', '');
            form.querySelector('[data-wtc-channels]').hidden = false;
            form.querySelectorAll('[data-wtc-channels-for]').forEach(function (set) {
                set.hidden = set.getAttribute('data-wtc-channels-for') !== event.target.value;
            });
        }
    });

    form.addEventListener('submit', function (event) {
        var message = nameError();
        var checked = form.querySelector('[data-wtc-type]:checked');
        setError('name', message);
        nameInput.setAttribute('aria-invalid', message ? 'true' : 'false');
        setError('language', language.value ? '' : 'A language must be selected for the template.');
        setError('type', checked ? '' : 'A content type must be selected for the template.');
        if (message || !language.value || !checked) {
            event.preventDefault();
            if (message) {
                nameInput.focus();
            }
            return;
        }
        if (checked.hasAttribute('data-wtc-soon')) {
            event.preventDefault();
            window.LeadBirdModal.alert({
                title: checked.parentNode.querySelector('span').textContent + ' isn’t ready yet',
                message: 'We’re still building this message type. For now, choose Text, Media, List Picker, Quick Reply or Call to action.',
            });
        }
    });
})();
