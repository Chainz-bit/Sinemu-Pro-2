export function initActions() {
    const actionButtons = document.querySelectorAll('[data-action]');
    const claimButtons = document.querySelectorAll('.claim-button');
    const claimBarangId = document.getElementById('claimBarangId');
    const claimBarangName = document.getElementById('claimBarangName');
    const claimBarangStatus = document.getElementById('claimBarangStatus');
    const claimWithoutReport = document.getElementById('claimWithoutReport');
    const claimLostReportId = document.getElementById('claimLostReportId');
    const claimReportSelectorWrap = document.getElementById('claimReportSelectorWrap');
    const claimLostReportSummary = document.getElementById('claimLostReportSummary');
    const claimManualFields = document.getElementById('claimManualFields');
    const claimSummaryName = document.getElementById('claimSummaryName');
    const claimSummaryLocation = document.getElementById('claimSummaryLocation');
    const claimSummaryDate = document.getElementById('claimSummaryDate');
    const claimKontakPelapor = document.getElementById('claimKontakPelapor');
    const claimBuktiKepemilikan = document.getElementById('claimBuktiKepemilikan');
    const claimBuktiFoto = document.getElementById('claimBuktiFoto');
    const claimBuktiPreview = document.getElementById('claimBuktiPreview');
    const claimLostNamaBarang = document.getElementById('claimLostNamaBarang');
    const claimPersetujuanKlaim = document.getElementById('claimPersetujuanKlaim');
    const claimSubmitButton = document.getElementById('claimSubmitButton');
    const manualRequiredFields = [
        claimLostNamaBarang,
        document.getElementById('claimLostLokasiHilang'),
        document.getElementById('claimLostTanggalHilang'),
        document.getElementById('claimLostKeterangan'),
    ];

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

    function syncSelectedLostReport() {
        if (!claimLostReportId) return;

        const selectedOption = claimLostReportId.options[claimLostReportId.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            if (claimLostReportSummary) {
                claimLostReportSummary.hidden = true;
            }
            if (claimSummaryName) claimSummaryName.textContent = '-';
            if (claimSummaryLocation) claimSummaryLocation.textContent = '-';
            if (claimSummaryDate) claimSummaryDate.textContent = '-';
            return;
        }

        const reportName = selectedOption.dataset.reportName || '-';
        const reportLocation = selectedOption.dataset.reportLocation || '-';
        const reportDate = selectedOption.dataset.reportDate || '-';
        const reportContact = selectedOption.dataset.reportContact || '';
        const reportOwnership = selectedOption.dataset.reportOwnership || '';

        if (claimSummaryName) claimSummaryName.textContent = reportName;
        if (claimSummaryLocation) claimSummaryLocation.textContent = reportLocation;
        if (claimSummaryDate) claimSummaryDate.textContent = reportDate;
        if (claimLostReportSummary) {
            claimLostReportSummary.hidden = false;
        }

        if (claimKontakPelapor && claimKontakPelapor.value.trim() === '' && reportContact !== '') {
            claimKontakPelapor.value = reportContact;
        }

        if (claimBuktiKepemilikan && claimBuktiKepemilikan.value.trim() === '' && reportOwnership !== '') {
            claimBuktiKepemilikan.value = reportOwnership;
        }
    }

    function syncClaimMode() {
        const isManualClaim = !!(claimWithoutReport && claimWithoutReport.checked);

        if (claimReportSelectorWrap) {
            claimReportSelectorWrap.hidden = isManualClaim;
        }

        if (claimLostReportId) {
            claimLostReportId.required = !isManualClaim;
            claimLostReportId.disabled = isManualClaim;
            if (isManualClaim) {
                claimLostReportId.value = '';
            }
        }

        if (claimLostReportSummary) {
            claimLostReportSummary.hidden = isManualClaim || !claimLostReportId || claimLostReportId.value === '';
        }

        if (claimManualFields) {
            claimManualFields.hidden = !isManualClaim;
        }

        manualRequiredFields.forEach(function (field) {
            if (!field) return;
            field.required = isManualClaim;
        });
    }

    function syncClaimSubmitState() {
        if (!claimSubmitButton) return;
        const consentChecked = !!(claimPersetujuanKlaim && claimPersetujuanKlaim.checked);
        claimSubmitButton.disabled = !consentChecked;
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
            if (claimLostNamaBarang) {
                claimLostNamaBarang.value = this.dataset.barangName || '';
            }
            if (claimBarangStatus) {
                claimBarangStatus.value = this.dataset.barangStatus || 'Status Tidak Diketahui';
            }
            if (claimBuktiFoto) {
                claimBuktiFoto.value = '';
                renderClaimFilePreview([]);
            }
            if (claimWithoutReport) {
                syncClaimMode();
            }
            if (claimLostReportId && claimLostReportId.value !== '' && !(claimWithoutReport && claimWithoutReport.checked)) {
                syncSelectedLostReport();
            }
            syncClaimSubmitState();
            openModal('claimModal');
        });
    });

    if (claimWithoutReport) {
        claimWithoutReport.addEventListener('change', syncClaimMode);
        syncClaimMode();
    }

    if (claimLostReportId) {
        claimLostReportId.addEventListener('change', syncSelectedLostReport);
        syncSelectedLostReport();
    }

    if (claimBuktiFoto) {
        claimBuktiFoto.addEventListener('change', function () {
            renderClaimFilePreview(this.files);
        });
    }

    if (claimPersetujuanKlaim) {
        claimPersetujuanKlaim.addEventListener('change', syncClaimSubmitState);
    }

    syncClaimSubmitState();

}
