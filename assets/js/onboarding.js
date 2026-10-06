(function () {
    function focusEditingForm(formId, inputId) {
        var form = document.getElementById(formId);
        if (!form || form.getAttribute('data-editing') !== 'true') {
            return;
        }

        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var input = document.getElementById(inputId);
        if (input) {
            input.focus();
        }
    }

    function setupOfferingImagePreview() {
        var field = document.querySelector('[data-offering-image-field]');
        if (!field) {
            return;
        }

        var input = field.querySelector('[data-offering-image-input]');
        var preview = field.querySelector('[data-offering-image-preview]');
        var image = field.querySelector('[data-offering-image-preview-img]');
        var empty = field.querySelector('[data-offering-image-empty]');
        var status = field.querySelector('[data-offering-image-status]');
        var remove = field.querySelector('[data-offering-image-remove]');
        var originalSrc = image && image.getAttribute('src') ? image.getAttribute('src') : '';
        var objectUrl = '';

        if (!input || !preview || !image || !empty || !status) {
            return;
        }

        function revokeObjectUrl() {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = '';
            }
        }

        function showEmpty(message) {
            preview.classList.remove('has-image');
            image.classList.add('is-hidden');
            empty.classList.remove('is-hidden');
            if (message) {
                status.textContent = message;
            }
        }

        function showImage(src, message) {
            preview.classList.add('has-image');
            image.src = src;
            image.classList.remove('is-hidden');
            empty.classList.add('is-hidden');
            status.textContent = message;
        }

        function syncPreview() {
            var file = input.files && input.files[0] ? input.files[0] : null;

            if (file) {
                revokeObjectUrl();
                objectUrl = URL.createObjectURL(file);
                showImage(objectUrl, 'Selected photo: ' + file.name);
                return;
            }

            revokeObjectUrl();

            if (remove && remove.checked && originalSrc) {
                showEmpty('Current photo will be removed when you save.');
                return;
            }

            if (originalSrc) {
                showImage(originalSrc, 'Current photo is ready. Upload a new image to replace it.');
                return;
            }

            showEmpty('No photo selected yet.');
        }

        input.addEventListener('change', syncPreview);
        if (remove) {
            remove.addEventListener('change', syncPreview);
        }

        window.addEventListener('beforeunload', revokeObjectUrl);
        syncPreview();
    }

    focusEditingForm('offering-form', 'name');
    focusEditingForm('faq-form', 'faq_question');
    setupOfferingImagePreview();
})();
