// Instructor Workload JS: tab switching + cover-staff-request Accept/Reject.
// DOM-only demo state — nothing persists.
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.getElementById('wkTabs');
    const panels = {
        overview: document.getElementById('wk-panel-overview'),
        assigned: document.getElementById('wk-panel-assigned'),
        history: document.getElementById('wk-panel-history'),
    };

    tabs?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-tab]');
        if (!btn) return;
        tabs.querySelectorAll('.wk-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        Object.entries(panels).forEach(([key, panel]) => { panel.hidden = key !== btn.dataset.tab; });
    });

    const assignedDot = document.getElementById('wkAssignedDot');
    if (assignedDot) assignedDot.classList.add('show');

    const pendingPill = document.getElementById('wkPendingPill');
    function refreshPendingCount() {
        const remaining = document.querySelectorAll('.wk-cover-item:not(.resolved)').length;
        if (pendingPill) pendingPill.textContent = `${remaining} pending`;
        if (assignedDot) assignedDot.classList.toggle('show', remaining > 0);
    }

    document.getElementById('wkCoverList')?.addEventListener('click', (e) => {
        const acceptBtn = e.target.closest('[data-accept]');
        const rejectBtn = e.target.closest('[data-reject]');
        if (!acceptBtn && !rejectBtn) return;

        const item = e.target.closest('.wk-cover-item');
        item.classList.add('resolved');
        refreshPendingCount();
        window.ttToast?.(acceptBtn ? 'Cover request accepted — added to your assigned work.' : 'Cover request declined.',
            { icon: acceptBtn ? 'fa-circle-check' : 'fa-circle-xmark', type: acceptBtn ? undefined : 'error' });
    });

    document.getElementById('wkBtnReset')?.addEventListener('click', () => {
        document.querySelectorAll('.wk-select').forEach(sel => { sel.selectedIndex = 0; });
    });
});
