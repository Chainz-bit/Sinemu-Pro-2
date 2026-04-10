export function initActions() {
    const detailButtons = document.querySelectorAll('.detail-button');
    const actionButtons = document.querySelectorAll('[data-action]');
    const claimButtons = document.querySelectorAll('.claim-button');
    const claimBarangId = document.getElementById('claimBarangId');
    const claimBarangName = document.getElementById('claimBarangName');

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
            openModal('claimModal');
        });
    });

    detailButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const item = this.dataset.item || 'Barang';
            const list = this.dataset.list || 'Laporan';
            alert(list + ': ' + item);
        });
    });
}
