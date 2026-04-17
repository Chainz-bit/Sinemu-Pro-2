/*
 * FILE: user/app.js
 * Tujuan:
 * - Titik masuk interaksi halaman dashboard user.
 * - Mengaktifkan sidebar mobile, dropdown profil, dan panel notifikasi user.
 */

import { createProfileMenu } from '../admin/modules/profile-menu.js';
import { createRowMenu } from '../admin/modules/row-menu.js';
import { createSidebar } from '../admin/modules/sidebar.js';
import { createNotificationModal } from './modules/notification-modal.js';

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
    let pendingDeleteForm = null;

    const profile = createProfileMenu(profileWrap, profileTrigger, profileMenu);
    const rowMenu = createRowMenu(rowMenuTriggers);
    const sidebar = createSidebar(sidebarToggle, sidebarBackdrop);
    const notification = createNotificationModal(notificationTrigger, notificationModal);

    profile.bind();
    rowMenu.bind({
        closeProfile: profile.close,
        closeNotification: notification.close,
    });
    sidebar.bind();
    notification.bind({
        closeProfile: profile.close,
    });

    function openConfirmModal(form) {
        if (!confirmBackdrop || !confirmMessage) return;

        pendingDeleteForm = form;
        const title = form.getAttribute('data-confirm-title') || 'Konfirmasi Hapus';
        const submitLabel = form.getAttribute('data-confirm-submit-label') || 'Hapus';
        const submitVariant = form.getAttribute('data-confirm-submit-variant') || 'danger';

        if (confirmTitle) {
            confirmTitle.textContent = title;
        }
        confirmMessage.textContent = form.getAttribute('data-confirm-message') || 'Yakin ingin menghapus data ini?';
        if (confirmSubmit) {
            confirmSubmit.textContent = submitLabel;
            confirmSubmit.classList.toggle('confirm-btn-danger', submitVariant === 'danger');
            confirmSubmit.classList.toggle('confirm-btn-primary', submitVariant !== 'danger');
        }
        confirmBackdrop.hidden = false;
    }

    function closeConfirmModal() {
        if (!confirmBackdrop) return;
        confirmBackdrop.hidden = true;
        pendingDeleteForm = null;
    }

    deleteForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.confirmed === '1') {
                form.dataset.confirmed = '0';
                return;
            }

            event.preventDefault();
            notification.close();
            openConfirmModal(form);
        });
    });

    confirmSubmit?.addEventListener('click', function () {
        if (!pendingDeleteForm) return;
        const form = pendingDeleteForm;
        form.dataset.confirmed = '1';
        closeConfirmModal();
        form.requestSubmit();
    });

    confirmCancel?.addEventListener('click', function () {
        closeConfirmModal();
    });

    confirmBackdrop?.addEventListener('click', function (event) {
        if (event.target === confirmBackdrop) {
            closeConfirmModal();
        }
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
            closeConfirmModal();
        }
    });
});
