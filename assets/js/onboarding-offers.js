(function () {
    var TYPE_COPY = {
        product: {
            name: 'e.g. Ankara Two-Piece Set',
            description: 'Materials, sizes, colours, delivery time…',
            hint: 'Your Salesgirl quotes variants and delivery in DMs.'
        },
        service: {
            name: 'e.g. Social Media Management',
            description: 'What you deliver, timelines, who it is for…',
            hint: 'Your Salesgirl explains scope and collects requirements.'
        },
        membership: {
            name: 'e.g. The Bloom Tribe Membership',
            description: 'Who it is for, what members get, community vibe…',
            hint: 'When someone wants to join, your Salesgirl sends the registration link.'
        },
        consultation: {
            name: 'e.g. 30-Min Brand Strategy Call',
            description: 'Who it helps, what happens on the call…',
            hint: 'Your Salesgirl collects name, phone, and preferred time to book.'
        },
        course: {
            name: 'e.g. Instagram Growth Masterclass',
            description: 'Outcomes, format, who should enroll…',
            hint: 'Your Salesgirl sends the enrollment link when they are ready.'
        },
        event: {
            name: 'e.g. Lagos Creators Workshop',
            description: 'What happens at the event, who should attend…',
            hint: 'Your Salesgirl shares date, location, and ticket link.'
        }
    };

    var BILLING_BY_TYPE = {
        product: ['one_time'],
        service: ['one_time', 'monthly', 'per_session'],
        membership: ['monthly', 'one_time'],
        consultation: ['per_session', 'one_time'],
        course: ['one_time', 'monthly'],
        event: ['one_time']
    };

    var WIZARD_STEPS = {
        1: 'Type',
        2: 'Basics',
        3: 'Pricing',
        4: 'Details',
        5: 'Photo'
    };

    var TOTAL_STEPS = 5;

    function selectedType(form) {
        var checked = form.querySelector('[data-offer-type-input]:checked');
        return checked ? checked.value : 'product';
    }

    function syncTypePanels(form, type) {
        form.querySelectorAll('[data-offer-panel]').forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-offer-panel') !== type;
        });

        form.querySelectorAll('[data-offer-type-card]').forEach(function (card) {
            card.classList.toggle('is-selected', card.getAttribute('data-offer-type-card') === type);
        });

        var copy = TYPE_COPY[type] || TYPE_COPY.product;
        var nameInput = form.querySelector('[data-offer-name-placeholder]');
        var descInput = form.querySelector('[data-offer-description-placeholder]');
        var hint = form.querySelector('[data-offer-description-hint]');
        if (nameInput) {
            nameInput.placeholder = copy.name;
        }
        if (descInput) {
            descInput.placeholder = copy.description;
        }
        if (hint) {
            hint.textContent = copy.hint;
            hint.hidden = !copy.hint;
        }

        syncBillingOptions(form, type);
    }

    function syncBillingOptions(form, type) {
        var select = form.querySelector('[data-offer-billing-select]');
        if (!select) {
            return;
        }

        var allowed = BILLING_BY_TYPE[type] || BILLING_BY_TYPE.product;
        var current = select.value;
        var firstAllowed = null;

        Array.prototype.forEach.call(select.options, function (option) {
            var ok = allowed.indexOf(option.value) !== -1;
            option.hidden = !ok;
            option.disabled = !ok;
            if (ok && firstAllowed === null) {
                firstAllowed = option.value;
            }
        });

        if (allowed.indexOf(current) === -1 && firstAllowed !== null) {
            select.value = firstAllowed;
        }

        if (window.LeadBirdDropdown && typeof window.LeadBirdDropdown.refresh === 'function') {
            var wrapper = select.closest('.lb-dropdown');
            if (wrapper) {
                window.LeadBirdDropdown.refresh(wrapper, select);
            }
        }
    }

    function syncFreeToggle(form) {
        var toggle = form.querySelector('[data-offer-free-toggle]');
        var priceFields = form.querySelector('[data-offer-price-fields]');
        var priceInput = form.querySelector('[data-offer-price-input]');
        if (!toggle || !priceFields) {
            return;
        }

        var isFree = toggle.checked;
        priceFields.classList.toggle('is-hidden', isFree);
        if (priceInput) {
            priceInput.required = !isFree;
            if (isFree) {
                priceInput.value = '';
            }
        }
    }

    function syncBookingMode(form) {
        var checked = form.querySelector('[data-booking-mode-input]:checked');
        var mode = checked ? checked.value : 'leadbird_calendar';
        var calendarPanel = form.querySelector('[data-calendar-connect-panel]');
        var externalPanel = form.querySelector('[data-external-booking-panel]');
        if (calendarPanel) {
            calendarPanel.hidden = mode !== 'leadbird_calendar';
        }
        if (externalPanel) {
            externalPanel.hidden = mode !== 'external_link';
        }
    }

    function isVisible(el) {
        return !!(el && !el.hidden && el.offsetParent !== null);
    }

    function validateWizardStep(form, step) {
        var stepEl = form.querySelector('[data-offer-wizard-step="' + step + '"]');
        if (!stepEl) {
            return null;
        }

        var fields = stepEl.querySelectorAll('input, select, textarea');
        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            if (!isVisible(field) || field.disabled) {
                continue;
            }
            if (field.type === 'radio' || field.type === 'checkbox') {
                continue;
            }
            if (!field.checkValidity()) {
                field.reportValidity();
                field.focus();
                return 'Please fill in the required fields before continuing.';
            }
        }

        if (step === 4) {
            var type = selectedType(form);
            var panel = form.querySelector('[data-offer-panel="' + type + '"]');
            if (!panel) {
                return null;
            }

            var requiredInputs = panel.querySelectorAll('[data-offer-step-required]');
            for (var j = 0; j < requiredInputs.length; j++) {
                var input = requiredInputs[j];
                if (!isVisible(input)) {
                    continue;
                }

                var when = input.getAttribute('data-offer-required-when');
                if (when) {
                    var parts = when.split('=');
                    var radio = form.querySelector('input[name="' + parts[0] + '"]:checked');
                    if (!radio || radio.value !== parts[1]) {
                        continue;
                    }
                }

                if (input.value.trim() === '') {
                    input.focus();
                    return 'Please complete the required details for this offer type.';
                }
            }
        }

        return null;
    }

    function setWizardStep(form, step) {
        var root = form.querySelector('[data-offer-wizard-root]');
        if (!root) {
            return;
        }

        step = Math.max(1, Math.min(TOTAL_STEPS, step));
        form.dataset.offerWizardStep = String(step);

        root.querySelectorAll('[data-offer-wizard-step]').forEach(function (el) {
            var num = parseInt(el.getAttribute('data-offer-wizard-step'), 10);
            el.classList.toggle('is-active', num === step);
        });

        root.querySelectorAll('[data-offer-wizard-dot]').forEach(function (dot) {
            var num = parseInt(dot.getAttribute('data-offer-wizard-dot'), 10);
            dot.classList.toggle('is-current', num === step);
            dot.classList.toggle('is-done', num < step);
            var mark = dot.querySelector('.lb-offer-wizard-dot-mark');
            if (mark) {
                mark.textContent = num < step ? '✓' : String(num);
            }
        });

        var fill = root.querySelector('[data-offer-wizard-progress-fill]');
        if (fill) {
            fill.style.width = ((step / TOTAL_STEPS) * 100) + '%';
        }

        var backBtn = root.querySelector('[data-offer-wizard-back]');
        var nextBtn = root.querySelector('[data-offer-wizard-next]');
        var submitBtn = root.querySelector('[data-offer-wizard-submit]');
        var exitUrl = form.getAttribute('data-offer-exit-url') || '';
        function setNavButtonVisible(btn, visible) {
            if (!btn) {
                return;
            }
            btn.hidden = !visible;
            btn.classList.toggle('is-hidden', !visible);
            btn.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }

        if (backBtn) {
            var showBack = !(step <= 1 && exitUrl === '' && form.getAttribute('data-offer-wizard-modal') !== 'true');
            setNavButtonVisible(backBtn, showBack);
        }
        if (nextBtn) {
            setNavButtonVisible(nextBtn, step < TOTAL_STEPS);
        }
        if (submitBtn) {
            setNavButtonVisible(submitBtn, step >= TOTAL_STEPS);
        }

        var error = root.querySelector('[data-offer-wizard-error]');
        if (error) {
            error.hidden = true;
            error.textContent = '';
        }

        var scrollTarget = root.querySelector('[data-offer-wizard-progress]') || root;
        if (scrollTarget && scrollTarget.scrollIntoView) {
            scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function showWizardError(form, message) {
        var error = form.querySelector('[data-offer-wizard-error]');
        if (!error) {
            return;
        }
        error.textContent = message;
        error.hidden = false;
    }

    function initWizard(form) {
        var currentStep = 1;
        setWizardStep(form, currentStep);

        var root = form.querySelector('[data-offer-wizard-root]');
        if (!root) {
            return;
        }

        root.querySelector('[data-offer-wizard-next]').addEventListener('click', function () {
            var error = validateWizardStep(form, currentStep);
            if (error) {
                showWizardError(form, error);
                return;
            }
            currentStep += 1;
            setWizardStep(form, currentStep);
        });

        root.querySelector('[data-offer-wizard-back]').addEventListener('click', function () {
            var exitUrl = form.getAttribute('data-offer-exit-url') || '';
            var isModal = form.getAttribute('data-offer-wizard-modal') === 'true';
            if (currentStep <= 1 && isModal) {
                form.dispatchEvent(new CustomEvent('lb-offer-modal-close'));
                return;
            }
            if (currentStep <= 1 && exitUrl !== '') {
                window.location.href = exitUrl;
                return;
            }

            currentStep -= 1;
            setWizardStep(form, currentStep);
        });

        form.addEventListener('submit', function (event) {
            for (var step = 1; step <= TOTAL_STEPS; step++) {
                var error = validateWizardStep(form, step);
                if (error) {
                    event.preventDefault();
                    currentStep = step;
                    setWizardStep(form, currentStep);
                    showWizardError(form, error);
                    return;
                }
            }
        });
    }

    function initForm(form) {
        syncTypePanels(form, selectedType(form));
        syncFreeToggle(form);
        syncBookingMode(form);

        if (form.getAttribute('data-offer-wizard') === 'true') {
            initWizard(form);
        }

        form.addEventListener('change', function (event) {
            var target = event.target;
            if (!target || !target.matches) {
                return;
            }

            if (target.matches('[data-offer-type-input]')) {
                syncTypePanels(form, target.value);
            }

            if (target.matches('[data-offer-free-toggle]')) {
                syncFreeToggle(form);
            }

            if (target.matches('[data-booking-mode-input]')) {
                syncBookingMode(form);
            }
        });
    }

    document.querySelectorAll('[data-offer-form]').forEach(initForm);

    if (window.LeadBirdDropdown && typeof window.LeadBirdDropdown.init === 'function') {
        window.LeadBirdDropdown.init(document);
    }
})();
