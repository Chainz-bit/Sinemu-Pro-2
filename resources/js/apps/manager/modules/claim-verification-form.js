export function bindClaimVerificationForm() {
    const form = document.querySelector('form[data-claim-verification-form]');
    if (!form) return;

    const checklistSelects = Array.from(form.querySelectorAll('.claim-verification-grid select[required]'));
    const approveButton = form.querySelector('[data-approve-btn]');
    const approveHint = form.querySelector('[data-approve-hint]');
    if (!approveButton || checklistSelects.length === 0) return;

    const updateApproveState = function () {
        const missingCount = checklistSelects.reduce(function (count, select) {
            return count + (String(select.value || '').trim() === '' ? 1 : 0);
        }, 0);
        const isComplete = missingCount === 0;

        approveButton.disabled = !isComplete;
        approveButton.setAttribute('aria-disabled', isComplete ? 'false' : 'true');

        if (approveHint) {
            approveHint.textContent = isComplete
                ? 'Checklist lengkap. Anda bisa menyetujui klaim.'
                : `Lengkapi ${missingCount} checklist wajib sebelum menyetujui klaim.`;
            approveHint.classList.toggle('is-ready', isComplete);
        }
    };

    checklistSelects.forEach(function (select) {
        select.addEventListener('change', updateApproveState);
    });

    updateApproveState();
}
