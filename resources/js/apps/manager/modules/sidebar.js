/*
 * FILE: modules/sidebar.js
 * Tujuan:
 * - Mengatur sidebar mobile (toggle, close via backdrop, reset saat resize).
 */

export function createSidebar(sidebarToggle, sidebarBackdrop) {
    function close() {
        document.body.classList.remove('sidebar-open');
        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', 'false');
        }
    }

    function toggle() {
        const willOpen = !document.body.classList.contains('sidebar-open');
        if (willOpen) {
            document.body.classList.add('sidebar-open');
            if (sidebarToggle) {
                sidebarToggle.setAttribute('aria-expanded', 'true');
            }
            return;
        }
        close();
    }

    function bind() {
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function (event) {
                event.stopPropagation();
                toggle();
            });
        }

        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', function () {
                close();
            });
        }

        window.addEventListener('resize', function () {
            if (window.innerWidth > 1024) {
                close();
            }
        });
    }

    return { close, toggle, bind };
}
