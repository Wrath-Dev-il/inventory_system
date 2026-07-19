document.addEventListener('DOMContentLoaded', function () {
    const app = document.querySelector('.admin-app');
    const sidebar = document.getElementById('adminSidebar');
    const backdrop = document.getElementById('adminSidebarBackdrop');
    const menuButton = document.getElementById('adminMenuButton');
    const closeButtons = document.querySelectorAll('[data-sidebar-close]');
    const sidebarToggles = document.querySelectorAll('[data-sidebar-toggle]');
    const profile = document.querySelector('[data-admin-profile]');
    const profileToggle = document.querySelector('[data-admin-profile-toggle]');
    const profileMenu = document.querySelector('[data-admin-profile-menu]');
    const desktopHoverQuery = window.matchMedia('(hover: hover) and (pointer: fine) and (min-width: 901px)');
    let collapseTimer = null;

    function isDesktopHoverMode() {
        return desktopHoverQuery.matches;
    }

    function clearCollapseTimer() {
        if (collapseTimer) {
            window.clearTimeout(collapseTimer);
            collapseTimer = null;
        }
    }

    function setDesktopSidebarExpanded(open) {
        if (!app || !isDesktopHoverMode()) {
            return;
        }

        clearCollapseTimer();
        app.classList.toggle('is-sidebar-expanded', open);
    }

    function closeAllSidebarGroups() {
        sidebarToggles.forEach(function (toggle) {
            const group = toggle.closest('[data-sidebar-group]');
            if (group) {
                group.classList.remove('is-open');
            }
            toggle.setAttribute('aria-expanded', 'false');
        });
    }

    function scheduleDesktopSidebarCollapse(options) {
        if (!app || !sidebar || !isDesktopHoverMode()) {
            return;
        }

        const respectFocus = !options || options.respectFocus !== false;

        clearCollapseTimer();
        collapseTimer = window.setTimeout(function () {
            if (!sidebar.matches(':hover') && (!respectFocus || !sidebar.contains(document.activeElement))) {
                if (!respectFocus && sidebar.contains(document.activeElement) && typeof document.activeElement.blur === 'function') {
                    document.activeElement.blur();
                }

                app.classList.remove('is-sidebar-expanded');
                closeAllSidebarGroups();
            }
        }, 140);
    }

    function setMobileSidebar(open) {
        if (!sidebar || !backdrop || !menuButton) {
            return;
        }

        sidebar.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-open', open);
        menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.style.overflow = open ? 'hidden' : '';
    }

    function setProfileMenu(open) {
        if (!profile || !profileToggle || !profileMenu) {
            return;
        }

        profile.classList.toggle('is-open', open);
        profileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        profileMenu.hidden = !open;
    }

    if (sidebar) {
        sidebar.addEventListener('mouseenter', function () {
            setDesktopSidebarExpanded(true);
        });

        sidebar.addEventListener('mouseleave', function () {
            scheduleDesktopSidebarCollapse({ respectFocus: false });
        });

        sidebar.addEventListener('focusin', function () {
            setDesktopSidebarExpanded(true);
        });

        sidebar.addEventListener('focusout', function () {
            scheduleDesktopSidebarCollapse();
        });
    }

    if (menuButton) {
        menuButton.addEventListener('click', function () {
            setMobileSidebar(true);
        });
    }

    closeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setMobileSidebar(false);
        });
    });

    sidebarToggles.forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            if (isDesktopHoverMode() && app && !app.classList.contains('is-sidebar-expanded')) {
                setDesktopSidebarExpanded(true);
            }

            const group = toggle.closest('[data-sidebar-group]');
            if (!group) {
                return;
            }

            const isOpen = group.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });

    if (profileToggle) {
        profileToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            setProfileMenu(profileToggle.getAttribute('aria-expanded') !== 'true');
        });
    }

    if (profileMenu) {
        profileMenu.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    }

    document.addEventListener('click', function (event) {
        if (profile && !profile.contains(event.target)) {
            setProfileMenu(false);
        }
    });

    desktopHoverQuery.addEventListener('change', function () {
        if (!isDesktopHoverMode()) {
            clearCollapseTimer();
            if (app) {
                app.classList.remove('is-sidebar-expanded');
            }
            closeAllSidebarGroups();
        } else {
            setMobileSidebar(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        setMobileSidebar(false);
        setProfileMenu(false);
        scheduleDesktopSidebarCollapse();
    });
});
