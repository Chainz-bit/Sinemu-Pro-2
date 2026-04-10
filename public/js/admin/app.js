/*
 * FILE: app.js
 * Tujuan:
 * - Titik masuk interaksi halaman admin.
 * - Menginisialisasi modul per fitur dan mengatur koordinasi antar modul.
 */

import { createRowMenu } from './modules/row-menu.js';
import { createProfileMenu } from './modules/profile-menu.js';
import { createNotificationModal } from './modules/notification-modal.js';
import { createSidebar } from './modules/sidebar.js';

document.addEventListener('DOMContentLoaded', function () {
    // Kumpulkan elemen UI yang dibutuhkan modul.
    const rowMenuTriggers = document.querySelectorAll('.row-menu-trigger');
    const profileWrap = document.querySelector('.profile-menu-wrap');
    const profileTrigger = document.querySelector('.profile-menu-trigger');
    const profileMenu = document.getElementById('profile-menu');
    const notificationTrigger = document.querySelector('.notification-trigger');
    const notificationModal = document.getElementById('notification-modal');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebarBackdrop = document.querySelector('.sidebar-backdrop');

    // Inisialisasi modul dengan dependency elemen yang relevan.
    const rowMenu = createRowMenu(rowMenuTriggers);
    const profile = createProfileMenu(profileWrap, profileTrigger, profileMenu);
    const notification = createNotificationModal(notificationTrigger, notificationModal);
    const sidebar = createSidebar(sidebarToggle, sidebarBackdrop);

    // Wiring antar modul: saat satu menu dibuka, menu lain ditutup.
    rowMenu.bind({
        closeProfile: profile.close,
        closeNotification: notification.close,
    });

    profile.bind({
        closeRowMenus: rowMenu.close,
        closeNotification: notification.close,
    });

    notification.bind({
        closeRowMenus: rowMenu.close,
        closeProfile: profile.close,
    });

    sidebar.bind();

    // Klik area luar menutup semua popover/dropdown.
    document.addEventListener('click', function () {
        rowMenu.close();
        profile.close();
        notification.close();
    });

    // ESC sebagai shortcut menutup semua panel aktif.
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            rowMenu.close();
            profile.close();
            notification.close();
            sidebar.close();
        }
    });
});
