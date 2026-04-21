/*
 * FILE: modules/profile-menu.js
 * Tujuan:
 * - Mengatur buka/tutup dropdown profil pada sidebar admin.
 * - Menjaga atribut aria-expanded tetap sinkron dengan state visual.
 */

export function createProfileMenu(profileWrap, profileTrigger, profileMenu) {
    function close() {
        if (!profileMenu || !profileTrigger) return;
        profileMenu.classList.remove('open');
        profileTrigger.setAttribute('aria-expanded', 'false');
        if (profileWrap) {
            profileWrap.classList.remove('open');
        }
    }

    function open() {
        if (!profileMenu || !profileTrigger) return;
        profileMenu.classList.add('open');
        profileTrigger.setAttribute('aria-expanded', 'true');
        if (profileWrap) {
            profileWrap.classList.add('open');
        }
    }

    function bind(options) {
        if (!profileTrigger || !profileMenu) return;

        profileTrigger.addEventListener('click', function (event) {
            event.stopPropagation();
            options?.closeRowMenus?.();
            options?.closeNotification?.();

            if (profileMenu.classList.contains('open')) {
                close();
            } else {
                open();
            }
        });
    }

    return { close, open, bind };
}
