// scheduler.js — Modern Workload Scheduler with Tab Navigation, Batch Operations, and Auto-Allocation.

document.addEventListener('DOMContentLoaded', function () {
    // ---- Tab Switcher (Weekly Duty Schedule vs Incoming SM Requests) ----
    const tabBtnSchedule = document.getElementById('tabBtnSchedule');
    const tabBtnRequests = document.getElementById('tabBtnRequests');
    const panelSchedule = document.getElementById('panelSchedule');
    const panelRequests = document.getElementById('panelRequests');

    function switchView(view) {
        if (view === 'schedule') {
            if (tabBtnSchedule) tabBtnSchedule.classList.add('active');
            if (tabBtnRequests) tabBtnRequests.classList.remove('active');
            if (panelSchedule) panelSchedule.style.display = 'block';
            if (panelRequests) panelRequests.style.display = 'none';
        } else if (view === 'requests') {
            if (tabBtnRequests) tabBtnRequests.classList.add('active');
            if (tabBtnSchedule) tabBtnSchedule.classList.remove('active');
            if (panelRequests) panelRequests.style.display = 'block';
            if (panelSchedule) panelSchedule.style.display = 'none';
        }
    }

    if (tabBtnSchedule) {
        tabBtnSchedule.addEventListener('click', () => switchView('schedule'));
    }
    if (tabBtnRequests) {
        tabBtnRequests.addEventListener('click', () => switchView('requests'));
    }

    // ---- SM Request Modal Controls ----
    const openSmModalBtn = document.getElementById('newSmRequestBtn');
    const smModal = document.getElementById('smRequestModal');
    const closeSmModalBtn = document.getElementById('closeSmModalBtn');
    const cancelSmModalBtn = document.getElementById('cancelSmModalBtn');
    const decBtn = document.getElementById('decrementStaffCount');
    const incBtn = document.getElementById('incrementStaffCount');
    const countInput = document.getElementById('smStaffCount');

    if (openSmModalBtn && smModal) {
        openSmModalBtn.addEventListener('click', () => smModal.style.display = 'flex');
    }
    if (closeSmModalBtn && smModal) {
        closeSmModalBtn.addEventListener('click', () => smModal.style.display = 'none');
    }
    if (cancelSmModalBtn && smModal) {
        cancelSmModalBtn.addEventListener('click', () => smModal.style.display = 'none');
    }

    if (decBtn && incBtn && countInput) {
        decBtn.addEventListener('click', () => {
            let val = parseInt(countInput.value, 10) || 1;
            if (val > 1) countInput.value = val - 1;
        });
        incBtn.addEventListener('click', () => {
            let val = parseInt(countInput.value, 10) || 1;
            if (val < 15) countInput.value = val + 1;
        });
    }

    window.handleSmRequestSubmit = function () {
        const course = document.getElementById('smCourseSelect')?.value || 'Course';
        const duty = document.getElementById('smDutyTitle')?.value || 'Duty';
        alert(`Support request submitted for ${course} (${duty})!\n\nThe Course Coordinator will review and allocate available junior staff.`);
        if (smModal) smModal.style.display = 'none';
        const form = document.getElementById('smRequestForm');
        if (form) form.reset();
    };

    // ---- Requests Queue: Batch Selection & Move to Schedule ----
    const selectAllCheckbox = document.getElementById('selectAllRequests');
    const requestCheckboxes = document.querySelectorAll('.request-select-cb');
    const batchStartBtn = document.getElementById('batchStartBtn');
    const selectedCountSpan = document.getElementById('selectedRequestsCount');

    function updateBatchButton() {
        const checked = document.querySelectorAll('.request-select-cb:checked');
        if (selectedCountSpan) selectedCountSpan.textContent = checked.length;
        if (batchStartBtn) {
            batchStartBtn.disabled = checked.length === 0;
            batchStartBtn.style.opacity = checked.length === 0 ? '0.6' : '1';
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            requestCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
            updateBatchButton();
        });
    }

    requestCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBatchButton);
    });

    if (batchStartBtn) {
        batchStartBtn.addEventListener('click', function () {
            const checked = document.querySelectorAll('.request-select-cb:checked');
            if (checked.length === 0) return;
            alert(`Approved and transferred ${checked.length} request(s) into the Weekly Duty Schedule!`);
            checked.forEach(cb => {
                const tr = cb.closest('tr');
                if (tr) {
                    const statusCell = tr.querySelector('.pill');
                    if (statusCell) {
                        statusCell.className = 'pill pill-active';
                        statusCell.textContent = 'Scheduled';
                    }
                    cb.checked = false;
                }
            });
            updateBatchButton();
            // Switch back to schedule tab so user sees their updated duties
            setTimeout(() => switchView('schedule'), 300);
        });
    }

    // ---- Auto-Allocate (Lowest Workload) Demo ----
    const autoAllocBtn = document.getElementById('autoAllocateBtn');
    if (autoAllocBtn) {
        autoAllocBtn.addEventListener('click', function () {
            const unassignedCards = document.querySelectorAll('.duty-session-card.is-pending');
            if (unassignedCards.length === 0) {
                alert('All duties are already fulfilled!');
                return;
            }

            let allocatedCount = 0;
            unassignedCards.forEach(card => {
                const assigneesBox = card.querySelector('.duty-assignees-box');
                const header = card.querySelector('.session-assignees-header');

                if (assigneesBox && header) {
                    assigneesBox.innerHTML = `
                        <span class="wm-inst-chip" title="Lowest accumulated weekly workload">TSR</span>
                        <span class="wm-inst-chip" title="Lowest accumulated weekly workload">BMC</span>
                        <span class="wm-inst-chip" title="Lowest accumulated weekly workload">PRL</span>
                    `;
                    const statusChip = header.querySelector('.status-chip');
                    if (statusChip) {
                        statusChip.className = 'status-chip chip-success';
                        statusChip.innerHTML = '<i class="fa-solid fa-check"></i> 3 of 3 Assigned';
                    }
                    card.classList.remove('is-pending');
                    card.classList.add('is-fulfilled');
                    allocatedCount++;
                }
            });

            alert(`Auto-Allocation Complete!\n\nFulfilled ${allocatedCount} session(s) by deploying active junior staff with the lowest accumulated hours.`);
        });
    }

    // ---- Email Preview & Calendar Dispatch Modal ----
    const previewEmailBtn = document.getElementById('previewEmailBtn');
    const emailModal = document.getElementById('emailPreviewModal');
    const closeEmailModalBtn = document.getElementById('closeEmailModalBtn');
    const sendEmailConfirmBtn = document.getElementById('sendEmailConfirmBtn');

    if (previewEmailBtn && emailModal) {
        previewEmailBtn.addEventListener('click', () => emailModal.style.display = 'flex');
    }
    if (closeEmailModalBtn && emailModal) {
        closeEmailModalBtn.addEventListener('click', () => emailModal.style.display = 'none');
    }
    if (sendEmailConfirmBtn && emailModal) {
        sendEmailConfirmBtn.addEventListener('click', () => {
            alert('Duty invitations and Google Calendar notifications dispatched to the Course Coordinator and assigned supportive staff members!');
            emailModal.style.display = 'none';
        });
    }
});
