(function () {
    var page = document.querySelector('.bill-page');
    if (!page) return;

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var addCardUrl = (window.LeadBirdBilling && window.LeadBirdBilling.addCardUrl) || '';
    var csrfToken = (window.LeadBirdBilling && window.LeadBirdBilling.csrfToken)
        || window.LeadBirdCsrf
        || '';

    if (!prefersReducedMotion && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -32px 0px',
        });

        page.querySelectorAll('[data-reveal]').forEach(function (el) {
            observer.observe(el);
        });
    } else {
        page.querySelectorAll('[data-reveal]').forEach(function (el) {
            el.classList.add('is-visible');
        });
    }

    page.querySelectorAll('[data-billing-accordion]').forEach(function (accordion) {
        var trigger = accordion.querySelector('[data-billing-accordion-trigger]');
        var panel = accordion.querySelector('[data-billing-accordion-panel]');
        if (!(trigger instanceof HTMLButtonElement) || !(panel instanceof HTMLElement)) {
            return;
        }

        trigger.addEventListener('click', function () {
            var isOpen = accordion.classList.contains('is-open');

            page.querySelectorAll('[data-billing-accordion].is-open').forEach(function (openItem) {
                if (openItem === accordion) {
                    return;
                }
                openItem.classList.remove('is-open');
                var openTrigger = openItem.querySelector('[data-billing-accordion-trigger]');
                var openPanel = openItem.querySelector('[data-billing-accordion-panel]');
                if (openTrigger instanceof HTMLButtonElement) {
                    openTrigger.setAttribute('aria-expanded', 'false');
                }
                if (openPanel instanceof HTMLElement) {
                    openPanel.classList.add('is-hidden');
                    openPanel.hidden = true;
                }
            });

            if (isOpen) {
                accordion.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
                panel.classList.add('is-hidden');
                panel.hidden = true;
                return;
            }

            accordion.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            panel.classList.remove('is-hidden');
            panel.hidden = false;
        });
    });

    var planPicker = page.querySelector('[data-bill-plan-picker]');
    var planToggle = page.querySelector('[data-bill-toggle-plans]');
    if (planPicker instanceof HTMLElement && planToggle instanceof HTMLButtonElement) {
        planToggle.addEventListener('click', function () {
            var isHidden = planPicker.hasAttribute('hidden');
            if (isHidden) {
                planPicker.removeAttribute('hidden');
                planToggle.setAttribute('aria-expanded', 'true');
                planToggle.textContent = 'Hide plans';
                return;
            }

            planPicker.setAttribute('hidden', 'hidden');
            planToggle.setAttribute('aria-expanded', 'false');
            planToggle.textContent = 'Update subscription';
        });
    }

    page.querySelectorAll('[data-confirm-cancel-subscription]').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            var form = btn.closest('form');

            window.LeadBirdModal.confirm({
                title: 'Cancel your plan?',
                message: 'You will not be charged again and replies will stop.',
                confirmLabel: 'Cancel plan',
                cancelLabel: 'Keep plan',
                tone: 'danger',
            }).then(function (ok) {
                if (ok && form) {
                    form.submit();
                }
            });
        });
    });

    page.querySelectorAll('[data-confirm-remove-card]').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            var form = btn.closest('form');
            var cardLabel = btn.getAttribute('data-card-label') || 'this card';
            var message = btn.getAttribute('data-card-only') === '1'
                ? 'This is your only saved card. Add another card before your next renewal so your plan keeps running.'
                : (btn.getAttribute('data-card-default') === '1'
                    ? 'Your most recently added card will become the default for renewals.'
                    : 'Renewals will keep using your default card.');

            window.LeadBirdModal.confirm({
                title: 'Remove ' + cardLabel + '?',
                message: message,
                confirmLabel: 'Remove card',
                cancelLabel: 'Keep card',
                tone: 'danger',
            }).then(function (ok) {
                if (ok && form) {
                    form.submit();
                }
            });
        });
    });

    var topupSectionId = (window.LeadBirdBilling && window.LeadBirdBilling.topupSectionId) || 'tokens';
    if (window.location.hash === '#' + topupSectionId) {
        var topupSection = document.getElementById(topupSectionId);
        if (topupSection) {
            window.setTimeout(function () {
                topupSection.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'start' });
            }, 120);
        }
    }

    page.querySelectorAll('[data-billing-pending-label]').forEach(function (btn) {
        var pendingForm = btn.closest('form');
        if (!pendingForm) return;

        pendingForm.addEventListener('submit', function () {
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            btn.textContent = btn.getAttribute('data-billing-pending-label') || 'Working…';
        });
    });

    page.querySelectorAll('.bill-topup-form').forEach(function (topupForm) {
        topupForm.addEventListener('submit', function () {
            var btn = topupForm.querySelector('[data-topup-submit]');
            if (!btn) return;

            btn.disabled = true;
            btn.textContent = 'Redirecting…';
        });
    });

    var overlay = document.querySelector('[data-billing-card-overlay]');
    var form = document.querySelector('[data-billing-card-form]');
    if (!overlay || !form || !addCardUrl) return;

    var openButtons = document.querySelectorAll('[data-billing-add-card]');
    var closeButtons = document.querySelectorAll('[data-billing-card-close]');
    var submitButton = form.querySelector('[data-billing-card-submit]');
    var errorBox = form.querySelector('[data-billing-card-error]');
    var countrySelect = form.querySelector('[data-card-verify-country]');
    var amountLabel = document.querySelector('[data-card-verify-amount]');
    var submitLabel = submitButton ? submitButton.textContent.trim() : 'Continue to Flutterwave';
    var isSubmitting = false;

    function setError(message) {
        if (!errorBox) return;
        errorBox.textContent = message || '';
        errorBox.classList.toggle('is-hidden', !message);
    }

    function updateAmountLabel() {
        if (!amountLabel || !(countrySelect instanceof HTMLSelectElement)) return;
        amountLabel.textContent = countrySelect.value === 'NG' ? '₦100' : '$1';
    }

    function setOpen(isOpen) {
        overlay.classList.toggle('is-hidden', !isOpen);
        overlay.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        document.body.style.overflow = isOpen ? 'hidden' : '';
        setError('');
        if (!isOpen) return;

        var firstInput = form.querySelector('[name="address_line1"]');
        var canAutoFocus = window.matchMedia('(min-width: 920px) and (hover: hover)').matches;
        if (firstInput && canAutoFocus) {
            firstInput.focus({ preventScroll: true });
        }
    }

    if (countrySelect) {
        countrySelect.addEventListener('change', updateAmountLabel);
    }

    openButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setOpen(true);
        });
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setOpen(false);
        });
    });

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay && !isSubmitting) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (overlay.classList.contains('is-hidden') || isSubmitting) return;
        if (event.key === 'Escape') setOpen(false);
    });

    function fieldValue(name) {
        var field = form.querySelector('[name="' + name + '"]');
        return field ? String(field.value || '').trim() : '';
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (isSubmitting) return;
        setError('');

        var fields = ['address_line1', 'city', 'state', 'postal_code', 'country', 'phone'];
        var payload = new FormData();
        var missing = false;
        fields.forEach(function (name) {
            var value = fieldValue(name);
            if (!value) missing = true;
            payload.append(name, value);
        });

        if (missing) {
            setError('Please complete all billing details.');
            return;
        }

        if (csrfToken) {
            payload.append('_csrf', csrfToken);
        }

        isSubmitting = true;
        form.classList.add('is-loading');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Opening secure checkout…';
        }

        fetch(addCardUrl, {
            method: 'POST',
            body: payload,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-Token': csrfToken,
            },
        })
            .then(function (response) {
                return response.text().then(function (raw) {
                    var data = null;
                    try {
                        data = raw ? JSON.parse(raw) : null;
                    } catch (parseError) {
                        data = null;
                    }
                    return { status: response.status, data: data };
                });
            })
            .then(function (result) {
                if (result.data && result.data.ok && result.data.redirect) {
                    window.location.href = result.data.redirect;
                    return;
                }

                throw new Error(
                    (result.data && (result.data.message || result.data.error))
                    || (result.status === 419 ? 'Session expired. Refresh the page and try again.' : '')
                    || 'Could not open secure card checkout. Please try again.'
                );
            })
            .catch(function (error) {
                isSubmitting = false;
                form.classList.remove('is-loading');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = submitLabel;
                }
                setError(error instanceof TypeError || !error || !error.message ? 'Network error. Please try again.' : error.message);
            });
    });
})();
