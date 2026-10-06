(function () {
    const landingNav = document.querySelector('.landing-nav');

    const updateNavScrollState = () => {
        landingNav?.classList.toggle('is-scrolled', window.scrollY > 8);
    };

    updateNavScrollState();
    window.addEventListener('scroll', updateNavScrollState, { passive: true });

    const drawer = document.querySelector('[data-landing-drawer]');
    const backdrop = document.querySelector('[data-landing-drawer-backdrop]');
    const mobileTrigger = document.getElementById('landing-mobile-menu-trigger');
    const closeButtons = document.querySelectorAll('[data-landing-drawer-close]');
    const drawerLinks = document.querySelectorAll('[data-landing-drawer-link]');
    const productsTrigger = document.querySelector('[data-mobile-products-trigger]');
    const productsPanel = document.querySelector('[data-mobile-products-panel]');
    const productsWrap = document.querySelector('[data-landing-drawer-products]');

    const setDrawerOpen = (isOpen) => {
        drawer?.classList.toggle('is-open', isOpen);
        backdrop?.classList.toggle('is-open', isOpen);
        mobileTrigger?.classList.toggle('is-open', isOpen);
        drawer?.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        backdrop?.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        mobileTrigger?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        mobileTrigger?.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
        document.body.classList.toggle('landing-drawer-open', isOpen);
    };

    const openDrawer = () => setDrawerOpen(true);
    const closeDrawer = () => {
        setDrawerOpen(false);
        productsWrap?.classList.remove('is-open');
        productsPanel?.classList.add('hidden');
        productsTrigger?.setAttribute('aria-expanded', 'false');
    };

    mobileTrigger?.addEventListener('click', () => {
        const isOpen = drawer?.classList.contains('is-open');
        if (isOpen) {
            closeDrawer();
        } else {
            openDrawer();
        }
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeDrawer);
    });

    backdrop?.addEventListener('click', closeDrawer);

    drawerLinks.forEach((link) => {
        link.addEventListener('click', closeDrawer);
    });

    drawer?.querySelector('.landing-drawer-logo a')?.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && drawer?.classList.contains('is-open')) {
            closeDrawer();
        }
    });

    productsTrigger?.addEventListener('click', () => {
        const isOpen = productsWrap?.classList.contains('is-open');
        productsWrap?.classList.toggle('is-open', !isOpen);
        productsPanel?.classList.toggle('hidden', isOpen);
        productsTrigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    });

    const featureCards = document.querySelectorAll('[data-feature-card]');
    const maxTilt = 14;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const resetFeatureCard = (card) => {
        card.style.setProperty('--rx', '0deg');
        card.style.setProperty('--ry', '0deg');
        card.classList.remove('is-active');
    };

    featureCards.forEach((card) => {
        if (prefersReducedMotion) {
            return;
        }

        card.addEventListener('mousemove', (event) => {
            const rect = card.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            const px = x / rect.width - 0.5;
            const py = y / rect.height - 0.5;
            const rotateY = px * maxTilt * 2;
            const rotateX = -py * maxTilt * 2;

            card.style.setProperty('--rx', `${rotateX}deg`);
            card.style.setProperty('--ry', `${rotateY}deg`);

            const shine = card.querySelector('.landing-feature-card-shine');
            if (shine) {
                shine.style.setProperty('--mx', `${(x / rect.width) * 100}%`);
                shine.style.setProperty('--my', `${(y / rect.height) * 100}%`);
            }

            card.classList.add('is-active');
        });

        card.addEventListener('mouseleave', () => {
            resetFeatureCard(card);
        });

        card.addEventListener('blur', () => {
            resetFeatureCard(card);
        });

        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                card.classList.toggle('is-active');
            }
        });
    });

    document.querySelectorAll('[data-nav-dropdown-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            if (window.matchMedia('(min-width: 768px)').matches) {
                return;
            }

            event.preventDefault();
            const dropdown = trigger.closest('.landing-nav-dropdown');
            if (!dropdown) {
                return;
            }

            const isOpen = dropdown.classList.contains('is-open');
            document.querySelectorAll('.landing-nav-dropdown.is-open').forEach((item) => {
                if (item !== dropdown) {
                    item.classList.remove('is-open');
                }
            });
            dropdown.classList.toggle('is-open', !isOpen);
            trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        });
    });

    document.querySelectorAll('[data-faq-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const item = trigger.closest('[data-faq-item]');
            const panel = item?.querySelector('[data-faq-panel]');
            if (!item || !panel) {
                return;
            }

            const isOpen = item.classList.contains('is-open');
            item.classList.toggle('is-open', !isOpen);
            trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            panel.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
        });
    });
})();
