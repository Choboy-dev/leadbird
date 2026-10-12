(function () {
    const shell = document.querySelector('.dashboard-shell');
    if (!shell) return;

    const sidebar = shell.querySelector('[data-dashboard-sidebar]');
    const overlay = shell.querySelector('[data-dashboard-overlay]');
    const openButtons = shell.querySelectorAll('[data-dashboard-open]');
    const closeBtn = shell.querySelector('[data-dashboard-close]');

    function closeNavGroup(group) {
        if (!group) return;
        group.classList.remove('is-open', 'is-active-group');
        const panel = group.querySelector('.dashboard-nav-group-panel');
        const toggle = group.querySelector('.dashboard-nav-group-toggle');
        if (panel) panel.hidden = true;
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    }

    function openNavGroup(group) {
        if (!group) return;
        group.classList.add('is-open', 'is-active-group');
        const panel = group.querySelector('.dashboard-nav-group-panel');
        const toggle = group.querySelector('.dashboard-nav-group-toggle');
        if (panel) panel.hidden = false;
        if (toggle) toggle.setAttribute('aria-expanded', 'true');
    }

    function resetNavState() {
        if (!sidebar) return;
        sidebar.querySelectorAll('.dashboard-nav-link, .dashboard-nav-sub-link').forEach(function (link) {
            link.classList.remove('is-active');
            link.removeAttribute('aria-current');
        });
        sidebar.querySelectorAll('[data-dashboard-nav-group]').forEach(closeNavGroup);
    }

    function syncDashboardNavState() {
        if (!sidebar) return;

        resetNavState();

        const navSection = document.body.getAttribute('data-dashboard-nav')
            || document.body.getAttribute('data-admin-nav')
            || '';
        const currentUrl = new URL(window.location.href);
        const currentHref = currentUrl.pathname.replace(/\/$/, '') + currentUrl.search;

        function hrefKey(href) {
            try {
                const url = new URL(href, window.location.origin);
                return url.pathname.replace(/\/$/, '') + url.search;
            } catch (error) {
                return '';
            }
        }

        let matchedLink = null;
        sidebar.querySelectorAll('a.dashboard-nav-link[href], a.dashboard-nav-sub-link[href]').forEach(function (link) {
            if (link.classList.contains('dashboard-nav-link--section')) {
                return;
            }
            const href = link.getAttribute('href');
            if (!href) return;
            if (hrefKey(href) === currentHref) {
                matchedLink = link;
            }
        });

        if (matchedLink) {
            matchedLink.classList.add('is-active');
            matchedLink.setAttribute('aria-current', 'page');
            const parentGroup = matchedLink.closest('[data-dashboard-nav-group]');
            if (parentGroup) {
                openNavGroup(parentGroup);
            }
            return;
        }

        const sectionGroup = navSection
            ? sidebar.querySelector('[data-dashboard-nav-section="' + navSection + '"]')
            : null;

        if (sectionGroup) {
            openNavGroup(sectionGroup);
            const activeSub = sectionGroup.querySelector('.dashboard-nav-sub-link[data-nav-sub-active="true"]')
                || sectionGroup.querySelector('.dashboard-nav-sub-link');
            if (activeSub) {
                activeSub.classList.add('is-active');
                activeSub.setAttribute('aria-current', 'page');
            }
            return;
        }

        const flatLink = navSection
            ? sidebar.querySelector('.dashboard-nav-link[data-nav-key="' + navSection + '"]:not(.dashboard-nav-link--section)')
            : null;
        if (flatLink) {
            flatLink.classList.add('is-active');
            flatLink.setAttribute('aria-current', 'page');
            const flatGroup = flatLink.closest('[data-dashboard-nav-group]');
            if (flatGroup) {
                openNavGroup(flatGroup);
            }
        }
    }

    function setMenuButtonsOpen(isOpen) {
        openButtons.forEach(function (button) {
            button.classList.toggle('is-open', isOpen);
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    function openMenu() {
        syncDashboardNavState();
        shell.classList.add('is-menu-open');
        if (overlay) overlay.hidden = false;
        document.body.classList.add('dashboard-menu-open');
        setMenuButtonsOpen(true);
    }

    function closeMenu() {
        shell.classList.remove('is-menu-open');
        if (overlay) overlay.hidden = true;
        document.body.classList.remove('dashboard-menu-open');
        setMenuButtonsOpen(false);
    }

    openButtons.forEach(function (openBtn) {
        openBtn.addEventListener('click', function () {
            if (shell.classList.contains('is-menu-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });
    });
    function dismissCollapsedNavHover() {
        if (!shell.classList.contains('dashboard-shell--nav-collapsed')) {
            return false;
        }
        shell.classList.add('is-nav-hover-dismissed');
        if (sidebar && document.activeElement && sidebar.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        return true;
    }

    function clearCollapsedNavHoverDismiss() {
        shell.classList.remove('is-nav-hover-dismissed');
    }

    closeBtn?.addEventListener('click', function (event) {
        event.preventDefault();
        if (window.matchMedia('(min-width: 1024px)').matches && dismissCollapsedNavHover()) {
            return;
        }
        closeMenu();
    });
    overlay?.addEventListener('click', closeMenu);

    sidebar?.addEventListener('mouseleave', function () {
        if (shell.classList.contains('dashboard-shell--nav-collapsed')) {
            clearCollapsedNavHoverDismiss();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && shell.classList.contains('is-menu-open')) {
            closeMenu();
        }
    });

    shell.querySelectorAll('.dashboard-nav-link, .dashboard-nav-sub-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (!window.matchMedia('(max-width: 1023px)').matches) {
                return;
            }
            resetNavState();
            if (!link.classList.contains('dashboard-nav-group-toggle')) {
                link.classList.add('is-active');
                link.setAttribute('aria-current', 'page');
                const parentGroup = link.closest('[data-dashboard-nav-group]');
                if (parentGroup) {
                    sidebar.querySelectorAll('[data-dashboard-nav-group]').forEach(function (group) {
                        if (group !== parentGroup) {
                            closeNavGroup(group);
                        }
                    });
                    openNavGroup(parentGroup);
                } else {
                    sidebar.querySelectorAll('[data-dashboard-nav-group]').forEach(closeNavGroup);
                }
            }
            closeMenu();
        });
    });

    shell.querySelectorAll('[data-dashboard-nav-group]').forEach(function (group) {
        const toggle = group.querySelector('.dashboard-nav-group-toggle');
        const panel = group.querySelector('.dashboard-nav-group-panel');
        if (!toggle || !panel) return;

        toggle.addEventListener('click', function () {
            const isOpen = group.classList.contains('is-open');
            const singleAccordion = document.body.classList.contains('admin-page')
                || sidebar?.hasAttribute('data-admin-nav-accordion');
            if ((singleAccordion || window.matchMedia('(max-width: 1023px)').matches) && !isOpen) {
                sidebar.querySelectorAll('[data-dashboard-nav-group]').forEach(function (other) {
                    if (other !== group) {
                        closeNavGroup(other);
                    }
                });
            }
            if (isOpen) {
                closeNavGroup(group);
            } else {
                openNavGroup(group);
            }
        });
    });

    syncDashboardNavState();
    window.addEventListener('pageshow', syncDashboardNavState);

    document.querySelectorAll('[data-filter-group]').forEach(function (group) {
        const buttons = group.querySelectorAll('[data-filter]');
        const targetId = group.getAttribute('data-filter-target');
        const target = targetId ? document.getElementById(targetId) : null;

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                buttons.forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                if (target) {
                    target.setAttribute('data-range', btn.getAttribute('data-filter') || '7d');
                }
            });
        });
    });

    document.querySelectorAll('[data-tabs]').forEach(function (tabRoot) {
        const buttons = tabRoot.querySelectorAll('[data-tab]');
        const panels = tabRoot.querySelectorAll('[data-tab-panel]');

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const name = btn.getAttribute('data-tab');
                buttons.forEach(function (b) { b.classList.remove('is-active'); });
                panels.forEach(function (p) {
                    p.hidden = p.getAttribute('data-tab-panel') !== name;
                });
                btn.classList.add('is-active');
            });
        });
    });

    document.querySelectorAll('[data-business-switcher]').forEach(function (switcher) {
        const form = switcher.querySelector('[data-business-switcher-form]');
        const trigger = switcher.querySelector('.dash-business-switcher-trigger');
        const menu = switcher.querySelector('.dash-business-switcher-menu');
        const input = switcher.querySelector('[data-business-switcher-input]');
        if (!form || !trigger || !menu || !input) return;

        function closeMenu() {
            switcher.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            menu.hidden = true;
        }

        function openMenu() {
            document.querySelectorAll('[data-business-switcher].is-open').forEach(function (other) {
                if (other === switcher) return;
                const otherTrigger = other.querySelector('.dash-business-switcher-trigger');
                const otherMenu = other.querySelector('.dash-business-switcher-menu');
                other.classList.remove('is-open');
                if (otherTrigger) otherTrigger.setAttribute('aria-expanded', 'false');
                if (otherMenu) otherMenu.hidden = true;
            });
            switcher.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            menu.hidden = false;
        }

        trigger.addEventListener('click', function () {
            if (switcher.classList.contains('is-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        switcher.querySelectorAll('[data-business-id]').forEach(function (option) {
            option.addEventListener('click', function () {
                const businessId = option.getAttribute('data-business-id');
                if (!businessId || businessId === input.value) {
                    closeMenu();
                    return;
                }
                input.value = businessId;
                form.submit();
            });
        });

        document.addEventListener('click', function (event) {
            if (!switcher.contains(event.target)) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });
    });

    document.querySelectorAll('[data-dashboard-user-card]').forEach(function (card) {
        const toggle = card.querySelector('[data-dashboard-user-menu-toggle]');
        const menu = card.querySelector('[data-dashboard-user-menu]');
        if (!toggle || !menu) return;

        function closeUserMenu() {
            card.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            menu.hidden = true;
        }

        function openUserMenu() {
            document.querySelectorAll('[data-dashboard-user-card].is-open').forEach(function (other) {
                if (other === card) return;
                const otherToggle = other.querySelector('[data-dashboard-user-menu-toggle]');
                const otherMenu = other.querySelector('[data-dashboard-user-menu]');
                other.classList.remove('is-open');
                if (otherToggle) otherToggle.setAttribute('aria-expanded', 'false');
                if (otherMenu) otherMenu.hidden = true;
            });
            card.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            menu.hidden = false;
        }

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            if (card.classList.contains('is-open')) {
                closeUserMenu();
            } else {
                openUserMenu();
            }
        });

        document.addEventListener('click', function (event) {
            if (!card.contains(event.target)) {
                closeUserMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeUserMenu();
            }
        });
    });
})();
