(function () {
    var form = document.querySelector('[data-obw-form]');
    if (!form) return;

    var submit = form.querySelector('[data-obw-submit]');

    function ready() {
        var seen = {};
        var fields = form.querySelectorAll('[data-obw-required]');
        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            if (field.type === 'radio' || field.type === 'checkbox') {
                if (seen[field.name]) continue;
                seen[field.name] = true;
                if (!form.querySelector('input[name="' + field.name + '"]:checked')) return false;
            } else if (String(field.value || '').trim() === '') {
                return false;
            }
        }
        return true;
    }

    function update() {
        submit.disabled = !ready();
    }

    form.addEventListener('input', update);
    form.addEventListener('change', update);
    form.addEventListener('submit', function (event) {
        if (!ready()) {
            event.preventDefault();
            return;
        }
        submit.disabled = true;
        submit.textContent = 'Saving…';
    });

    update();
})();
