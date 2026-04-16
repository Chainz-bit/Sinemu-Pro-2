export function initActions() {
    const actionButtons = document.querySelectorAll('[data-action]');
    const claimButtons = document.querySelectorAll('.claim-button');
    const claimBarangId = document.getElementById('claimBarangId');
    const claimBarangName = document.getElementById('claimBarangName');
    const claimBuktiFoto = document.getElementById('claimBuktiFoto');
    const claimBuktiPreview = document.getElementById('claimBuktiPreview');

    function renderClaimFilePreview(files) {
        if (!claimBuktiPreview) return;
        claimBuktiPreview.innerHTML = '';

        Array.from(files || []).forEach(function (file) {
            const chip = document.createElement('span');
            chip.className = 'claim-modal-file-chip';
            chip.innerHTML = '<iconify-icon icon="mdi:image-outline" aria-hidden="true"></iconify-icon>';

            const name = document.createElement('span');
            name.textContent = file.name;
            chip.appendChild(name);
            claimBuktiPreview.appendChild(chip);
        });
    }

    function openModal(modalId) {
        const modalEl = document.getElementById(modalId);
        if (!modalEl || typeof bootstrap === 'undefined') return;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    actionButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const action = this.dataset.action;
            if (action === 'open-lost-report') {
                openModal('lostReportModal');
                return;
            }
            if (action === 'open-found-report') {
                openModal('foundReportModal');
            }
        });
    });

    claimButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (claimBarangId) {
                claimBarangId.value = this.dataset.barangId || '';
            }
            if (claimBarangName) {
                claimBarangName.value = this.dataset.barangName || '';
            }
            if (claimBuktiFoto) {
                claimBuktiFoto.value = '';
                renderClaimFilePreview([]);
            }
            openModal('claimModal');
        });
    });

    if (claimBuktiFoto) {
        claimBuktiFoto.addEventListener('change', function () {
            renderClaimFilePreview(this.files);
        });
    }

}
