/*
 * FILE: modules/notification-modal.js
 * Tujuan:
 * - Mengatur panel notifikasi di topbar (buka/tutup + event bubbling).
 */

export function createNotificationModal(notificationTrigger, notificationModal) {
    function close() {
        if (!notificationModal || !notificationTrigger) return;
        notificationModal.classList.remove('open');
        notificationTrigger.setAttribute('aria-expanded', 'false');
    }

    function open() {
        if (!notificationModal || !notificationTrigger) return;
        notificationModal.classList.add('open');
        notificationTrigger.setAttribute('aria-expanded', 'true');
    }

    function bind(options) {
        if (!notificationTrigger || !notificationModal) return;

        notificationTrigger.addEventListener('click', function (event) {
            event.stopPropagation();
            options?.closeRowMenus?.();
            options?.closeProfile?.();

            if (notificationModal.classList.contains('open')) {
                close();
            } else {
                open();
            }
        });

        notificationModal.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    }

    return { close, open, bind };
}
