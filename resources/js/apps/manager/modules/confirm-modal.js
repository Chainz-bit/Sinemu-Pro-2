export function createConfirmModal(elements) {
    const {
        backdrop,
        title,
        message,
        cancel,
        submit,
        forms,
    } = elements;
    let pendingForm = null;
    let pendingSubmitter = null;

    function open(form, submitter) {
        if (!backdrop || !message) return;

        pendingForm = form;
        pendingSubmitter = submitter || null;
        const source = submitter || form;
        const modalTitle = source.getAttribute('data-confirm-title')
            || form.getAttribute('data-confirm-title')
            || 'Konfirmasi Hapus';
        const submitLabel = source.getAttribute('data-confirm-submit-label')
            || form.getAttribute('data-confirm-submit-label')
            || 'Hapus';
        const submitVariant = source.getAttribute('data-confirm-submit-variant')
            || form.getAttribute('data-confirm-submit-variant')
            || 'danger';

        if (title) {
            title.textContent = modalTitle;
        }

        message.textContent = source.getAttribute('data-confirm-message')
            || form.getAttribute('data-confirm-message')
            || 'Yakin ingin menghapus data ini?';

        if (submit) {
            submit.textContent = submitLabel;
            submit.classList.toggle('confirm-btn-danger', submitVariant === 'danger');
            submit.classList.toggle('confirm-btn-primary', submitVariant !== 'danger');
        }

        backdrop.hidden = false;
    }

    function close() {
        if (!backdrop) return;
        backdrop.hidden = true;
        pendingForm = null;
        pendingSubmitter = null;
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
                open(form, event.submitter);
            });
        });

        submit?.addEventListener('click', function () {
            if (!pendingForm) return;
            const form = pendingForm;
            const submitter = pendingSubmitter;
            form.dataset.confirmed = '1';
            close();

            if (submitter) {
                form.requestSubmit(submitter);
                return;
            }

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
