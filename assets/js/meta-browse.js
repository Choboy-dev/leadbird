(function () {
    'use strict';

    var root = document.querySelector('[data-meta-browse]');
    if (!root) {
        return;
    }

    var addUrl = root.getAttribute('data-add-url') || '';
    var csrf = root.getAttribute('data-csrf') || '';
    var businessId = root.getAttribute('data-business-id') || '';
    var platform = root.getAttribute('data-platform') || '';
    var canAddMore = root.getAttribute('data-can-add-more') === '1';
    var searchInput = root.querySelector('[data-meta-browse-search]');
    var items = root.querySelectorAll('[data-meta-browse-item]');
    var counterValue = root.querySelector('[data-meta-browse-counter-value]');
    var addedCount = parseInt(root.getAttribute('data-initial-added') || '0', 10);

    function filterList(query) {
        var q = (query || '').trim().toLowerCase();
        items.forEach(function (item) {
            if (!(item instanceof HTMLElement)) {
                return;
            }
            var haystack = item.getAttribute('data-search') || '';
            item.hidden = q !== '' && haystack.indexOf(q) === -1;
        });
    }

    function updateCounter() {
        if (!(counterValue instanceof HTMLElement)) {
            return;
        }
        counterValue.textContent = String(addedCount);
    }

    if (searchInput instanceof HTMLInputElement) {
        searchInput.addEventListener('input', function () {
            filterList(searchInput.value);
        });
    }

    function setButtonAdded(button) {
        button.textContent = 'Added';
        button.classList.remove('meta-browse-btn--add');
        button.classList.add('meta-browse-btn--added');
        button.disabled = true;
        button.removeAttribute('data-meta-browse-add');
        button.removeAttribute('data-option-key');
        var row = button.closest('[data-meta-browse-item]');
        if (row instanceof HTMLElement) {
            row.classList.add('is-added');
        }
    }

    function applyPlanLimitReached() {
        canAddMore = false;
        root.setAttribute('data-can-add-more', '0');

        root.querySelectorAll('[data-meta-browse-add]').forEach(function (button) {
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            button.textContent = 'Limit reached';
            button.classList.remove('meta-browse-btn--add');
            button.classList.add('meta-browse-btn--disabled');
            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
            button.title = 'Plan limit reached';
            button.removeAttribute('data-meta-browse-add');
            button.removeAttribute('data-option-key');
        });
    }

    function showError(message) {
        if (window.LeadBirdModal && typeof window.LeadBirdModal.alert === 'function') {
            window.LeadBirdModal.alert({
                title: 'Could not add account',
                message: message,
                tone: 'error',
            });
        }
    }

    root.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        var button = target.closest('[data-meta-browse-add]');
        if (!(button instanceof HTMLButtonElement) || button.disabled) {
            return;
        }

        if (!canAddMore) {
            showError('Your plan only allows one ' + platform + ' account per business. Disconnect one first or upgrade your plan.');
            return;
        }

        var optionKey = button.getAttribute('data-option-key') || '';
        if (!optionKey || !addUrl) {
            return;
        }

        button.disabled = true;
        button.textContent = 'Adding…';

        var body = new URLSearchParams();
        body.set('_csrf', csrf);
        body.set('business_id', businessId);
        body.set('platform', platform);
        body.set('option_key', optionKey);

        fetch(addUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-CSRF-Token': csrf,
            },
            body: body.toString(),
            credentials: 'same-origin',
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.ok) {
                    var message = (result.data && result.data.error) || 'Could not add this account. Please try again.';
                    throw new Error(message);
                }

                var wasAlreadyAdded = button.classList.contains('meta-browse-btn--added');
                setButtonAdded(button);
                if (!wasAlreadyAdded) {
                    addedCount += 1;
                    updateCounter();
                }

                if (result.data.can_add_more === false) {
                    applyPlanLimitReached();
                }
            })
            .catch(function (error) {
                button.disabled = false;
                button.textContent = 'Add';
                showError(error && error.message ? error.message : 'Could not add this account. Please try again.');
            });
    });

    updateCounter();
})();
