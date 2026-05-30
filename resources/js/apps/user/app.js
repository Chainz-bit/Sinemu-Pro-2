/*
 * FILE: user/app.js
 * Tujuan:
 * - Titik masuk interaksi halaman dashboard user.
 * - Mengaktifkan sidebar mobile, dropdown profil, dan panel notifikasi user.
 */

import { createProfileMenu } from '../manager/modules/profile-menu.js';
import { createRowMenu } from '../manager/modules/row-menu.js';
import { createSidebar } from '../manager/modules/sidebar.js';
import { createNotificationModal } from './modules/notification-modal.js';
import { createUserConfirmModal } from './modules/confirm-modal.js';

document.addEventListener('DOMContentLoaded', function () {
    const profileWrap = document.querySelector('.profile-menu-wrap');
    const profileTrigger = document.querySelector('.profile-menu-trigger');
    const profileMenu = document.getElementById('profile-menu');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebarBackdrop = document.querySelector('.sidebar-backdrop');
    const notificationTrigger = document.querySelector('.notification-trigger');
    const notificationModal = document.getElementById('user-notification-modal');
    const rowMenuTriggers = document.querySelectorAll('.row-menu-trigger');
    const confirmBackdrop = document.getElementById('confirm-modal-backdrop');
    const confirmTitle = document.getElementById('confirm-modal-title');
    const confirmMessage = document.getElementById('confirm-modal-message');
    const confirmCancel = document.getElementById('confirm-modal-cancel');
    const confirmSubmit = document.getElementById('confirm-modal-submit');
    const deleteForms = document.querySelectorAll('form[data-confirm-delete]');

    const profile = createProfileMenu(profileWrap, profileTrigger, profileMenu);
    const rowMenu = createRowMenu(rowMenuTriggers);
    const sidebar = createSidebar(sidebarToggle, sidebarBackdrop);
    const notification = createNotificationModal(notificationTrigger, notificationModal);
    const confirmModal = createUserConfirmModal({
        backdrop: confirmBackdrop,
        title: confirmTitle,
        message: confirmMessage,
        cancel: confirmCancel,
        submit: confirmSubmit,
        forms: deleteForms,
    });

    profile.bind();
    rowMenu.bind({
        closeProfile: profile.close,
        closeNotification: notification.close,
    });
    sidebar.bind();
    notification.bind({
        closeProfile: profile.close,
    });
    confirmModal.bind({
        beforeOpen: notification.close,
    });

    document.addEventListener('click', function () {
        rowMenu.close();
        profile.close();
        notification.close();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            rowMenu.close();
            profile.close();
            sidebar.close();
            notification.close();
            confirmModal.close();
        }
    });
});
