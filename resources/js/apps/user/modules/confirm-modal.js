export function createUserConfirmModal(elements) {
    const {
        backdrop,
        title,
        message,
        cancel,
        submit,
        forms,
    } = elements;
    let pendingForm = null;

    function open(form) {
        if (!backdrop || !message) return;

        pendingForm = form;
        const submitVariant = form.getAttribute('data-confirm-submit-variant') || 'danger';

        if (title) {
            title.textContent = form.getAttribute('data-confirm-title') || 'Konfirmasi Hapus';
        }

        message.textContent = form.getAttribute('data-confirm-message') || 'Yakin ingin menghapus data ini?';

        if (submit) {
            submit.textContent = form.getAttribute('data-confirm-submit-label') || 'Hapus';
            submit.classList.toggle('confirm-btn-danger', submitVariant === 'danger');
            submit.classList.toggle('confirm-btn-primary', submitVariant !== 'danger');
        }

        backdrop.hidden = false;
    }

    function close() {
        if (!backdrop) return;
        backdrop.hidden = true;
        pendingForm = null;
    }

    function bind(options = {}) {
        forms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.confirmed === '1') {
                    form.dataset.confirmed = '0';
                    return;
                }

                event.preventDefault();
                options.beforeOpen?.();
                open(form);
            });
        });

        submit?.addEventListener('click', function () {
            if (!pendingForm) return;
            const form = pendingForm;
            form.dataset.confirmed = '1';
            close();
            form.requestSubmit();
        });

        cancel?.addEventListener('click', close);

        backdrop?.addEventListener('click', function (event) {
            if (event.target === backdrop) {
                close();
            }
        });
    }

    return { bind, close };
}
