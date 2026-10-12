(function () {
    const nav = document.querySelector('[data-site-nav]');
    if (!nav) {
        return;
    }

    const toggle = nav.querySelector('[data-site-menu-toggle]');
    const panel = nav.querySelector('[data-site-mobile-menu]');
    const backdrop = nav.querySelector('[data-site-menu-backdrop]');

    if (panel) {
        panel.hidden = false;
        panel.inert = true;
    }

    const updateScrollState = () => {
        nav.classList.toggle('is-scrolled', window.scrollY > 4);
    };

    updateScrollState();
    window.addEventListener('scroll', updateScrollState, { passive: true });

    const setMenuOpen = (isOpen) => {
        if (!toggle || !panel) {
            return;
        }
        panel.inert = !isOpen;
        nav.classList.toggle('is-menu-open', isOpen);
        document.body.classList.toggle('site-menu-open', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        toggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
    };

    toggle?.addEventListener('click', () => {
        setMenuOpen(!nav.classList.contains('is-menu-open'));
    });

    backdrop?.addEventListener('click', () => setMenuOpen(false));

    panel?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setMenuOpen(false));
    });

    window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
        if (event.matches) {
            setMenuOpen(false);
        }
    });

    document.querySelectorAll('.site-has-menu').forEach((menu) => {
        const trigger = menu.querySelector('.site-menu-trigger');
        if (!trigger) {
            return;
        }
        const sync = () => {
            trigger.setAttribute('aria-expanded', menu.matches(':hover, :focus-within') ? 'true' : 'false');
        };
        menu.addEventListener('mouseenter', sync);
        menu.addEventListener('mouseleave', sync);
        menu.addEventListener('focusin', sync);
        menu.addEventListener('focusout', () => window.setTimeout(sync, 0));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        if (nav.classList.contains('is-menu-open')) {
            setMenuOpen(false);
            toggle?.focus();
            return;
        }
        const active = document.activeElement;
        if (active instanceof HTMLElement && active.closest('.site-has-menu')) {
            active.blur();
        }
    });
})();
