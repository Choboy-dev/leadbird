(function () {
    var form = document.querySelector('[data-ws-profile]');
    if (!form) return;

    var save = document.querySelector('[data-ws-save]');
    var maxPhotoBytes = 5 * 1024 * 1024;

    function markDirty() {
        if (save && !form.querySelector('[name="about"]').disabled) {
            save.disabled = false;
        }
    }

    if (form.hasAttribute('data-ws-dirty')) {
        markDirty();
    }

    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);

    form.querySelectorAll('[data-ws-count]').forEach(function (field) {
        var counter = document.querySelector('[data-ws-count-for="' + field.id + '"]');
        if (!counter) return;
        field.addEventListener('input', function () {
            counter.textContent = String(field.value.length);
        });
    });

    var photoInput = form.querySelector('[data-ws-photo-input]');
    var frame = form.querySelector('[data-ws-photo-frame]');
    var photoName = form.querySelector('[data-ws-photo-name]');
    if (photoInput && frame) {
        photoInput.addEventListener('change', function () {
            var file = photoInput.files && photoInput.files[0];
            if (!file) return;
            if (['image/jpeg', 'image/png'].indexOf(file.type) === -1 || file.size > maxPhotoBytes) {
                photoInput.value = '';
                if (window.LeadBirdModal) {
                    window.LeadBirdModal.alert({
                        title: 'Photo not added',
                        message: 'Use a JPG or PNG photo under 5 MB.',
                        tone: 'error'
                    });
                }
                return;
            }
            var img = frame.querySelector('img');
            if (!img) {
                frame.innerHTML = '';
                img = document.createElement('img');
                img.alt = 'New profile photo';
                frame.appendChild(img);
            }
            img.src = URL.createObjectURL(file);
            if (photoName) photoName.textContent = file.name;
        });
    }

    form.addEventListener('submit', function () {
        if (save) {
            save.disabled = true;
            save.textContent = 'Updating…';
        }
    });
})();
