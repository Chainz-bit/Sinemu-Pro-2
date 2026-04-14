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
    const confirmBackdrop = document.getElementById('confirm-modal-backdrop');
    const confirmTitle = document.getElementById('confirm-modal-title');
    const confirmMessage = document.getElementById('confirm-modal-message');
    const confirmCancel = document.getElementById('confirm-modal-cancel');
    const confirmSubmit = document.getElementById('confirm-modal-submit');
    const deleteForms = document.querySelectorAll('form[data-confirm-delete]');
    let alertCloseButtons = [];
    let pendingDeleteForm = null;

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

    function layoutFeedbackPopups() {
        let offsetTop = 72;
        const gap = 10;
        const popups = Array.from(document.querySelectorAll('.feedback-alert.feedback-alert-popup'))
            .filter(function (alertEl) {
                return !alertEl.classList.contains('is-fading');
            });

        popups.forEach(function (alertEl) {
            alertEl.style.top = offsetTop + 'px';
            offsetTop += alertEl.offsetHeight + gap;
        });
    }

    function closeFeedbackAlert(alertEl) {
        if (!alertEl || alertEl.dataset.feedbackClosing === '1') {
            return;
        }

        alertEl.dataset.feedbackClosing = '1';
        alertEl.classList.add('is-fading');
        window.setTimeout(function () {
            alertEl.remove();
            layoutFeedbackPopups();
        }, 280);
    }

    function bindFeedbackCloseButtons() {
        alertCloseButtons = Array.from(document.querySelectorAll('[data-alert-close]'));
        alertCloseButtons.forEach(function (buttonEl) {
            if (buttonEl.dataset.feedbackCloseBound === '1') {
                return;
            }

            buttonEl.dataset.feedbackCloseBound = '1';
            buttonEl.addEventListener('click', function () {
                const alertEl = buttonEl.closest('.feedback-alert');
                closeFeedbackAlert(alertEl);
            });
        });
    }

    function scheduleFeedbackAutoClose(alertEl) {
        if (!alertEl || alertEl.dataset.feedbackAutoCloseBound === '1') {
            return;
        }

        const delayRaw = Number(alertEl.getAttribute('data-autoclose'));
        const delay = Number.isFinite(delayRaw) && delayRaw > 0 ? delayRaw : 3200;
        alertEl.dataset.feedbackAutoCloseBound = '1';

        window.setTimeout(function () {
            closeFeedbackAlert(alertEl);
        }, delay);
    }

    function normalizeFeedbackAlerts() {
        const feedbackAlerts = Array.from(document.querySelectorAll('.feedback-alert'));

        feedbackAlerts.forEach(function (alertEl) {
            const isError = alertEl.classList.contains('error');
            const titleText = isError ? 'Gagal' : 'Berhasil';

            if (!alertEl.classList.contains('feedback-alert-toast')) {
                const messageText = alertEl.textContent.trim();
                alertEl.textContent = '';

                const iconWrap = document.createElement('span');
                iconWrap.className = 'feedback-alert-icon';
                const iconEl = document.createElement('iconify-icon');
                iconEl.setAttribute('icon', isError ? 'mdi:alert-circle' : 'mdi:check-circle');
                iconWrap.appendChild(iconEl);

                const bodyWrap = document.createElement('div');
                bodyWrap.className = 'feedback-alert-body';
                const bodyTitle = document.createElement('strong');
                bodyTitle.textContent = titleText;
                const bodyMessage = document.createElement('span');
                bodyMessage.textContent = messageText;
                bodyWrap.appendChild(bodyTitle);
                bodyWrap.appendChild(bodyMessage);

                alertEl.appendChild(iconWrap);
                alertEl.appendChild(bodyWrap);
            }

            if (!alertEl.querySelector('[data-alert-close]')) {
                const closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'feedback-alert-close';
                closeBtn.setAttribute('data-alert-close', '');
                closeBtn.setAttribute('aria-label', 'Tutup notifikasi');

                const closeIcon = document.createElement('iconify-icon');
                closeIcon.setAttribute('icon', 'mdi:close');
                closeBtn.appendChild(closeIcon);
                alertEl.appendChild(closeBtn);
            }

            if (!alertEl.querySelector('.feedback-alert-progress')) {
                const progressBar = document.createElement('span');
                progressBar.className = 'feedback-alert-progress';
                progressBar.setAttribute('aria-hidden', 'true');
                alertEl.appendChild(progressBar);
            }

            alertEl.classList.add('feedback-alert-toast', 'feedback-alert-popup');
            if (!alertEl.hasAttribute('data-autoclose')) {
                alertEl.setAttribute('data-autoclose', '3200');
            }

            scheduleFeedbackAutoClose(alertEl);
        });

        bindFeedbackCloseButtons();
        layoutFeedbackPopups();
    }

    normalizeFeedbackAlerts();
    window.addEventListener('pageshow', normalizeFeedbackAlerts);

    const feedbackObserver = new MutationObserver(function (mutations) {
        let hasNewAlert = false;

        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (!(node instanceof HTMLElement)) {
                    return;
                }

                if (node.classList.contains('feedback-alert') || node.querySelector('.feedback-alert')) {
                    hasNewAlert = true;
                }
            });
        });

        if (hasNewAlert) {
            normalizeFeedbackAlerts();
        }
    });

    feedbackObserver.observe(document.body, { childList: true, subtree: true });

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
            closeConfirmModal();
        }
    });
});
