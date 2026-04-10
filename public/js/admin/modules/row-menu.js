/*
 * FILE: modules/row-menu.js
 * Tujuan:
 * - Mengelola dropdown aksi per baris tabel (ikon titik tiga).
 */

export function createRowMenu(triggers) {
    // Menutup semua menu, kecuali id tertentu (jika diberikan).
    function close(exceptId) {
        document.querySelectorAll('.row-menu').forEach(function (menu) {
            if (!exceptId || menu.id !== exceptId) {
                menu.classList.remove('open');
            }
        });
    }

    // Pasang event click ke semua trigger menu baris.
    function bind(options) {
        triggers.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                const targetId = button.getAttribute('data-menu-target');
                const menu = targetId ? document.getElementById(targetId) : null;
                const willOpen = menu && !menu.classList.contains('open');

                close(targetId);
                options?.closeProfile?.();
                options?.closeNotification?.();

                if (menu && willOpen) {
                    menu.classList.add('open');
                }
            });
        });
    }

    return { close, bind };
}
