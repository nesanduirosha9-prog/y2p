// Coordinator & In-Charge "Staff" screen — tab switching, approve/reject pending registrations,
// active staff table actions (deactivate/activate toggle, delete), and directory search/filter.
document.addEventListener('DOMContentLoaded', function () {
    const view = document.querySelector('.staff-view');
    if (!view) return;

    const basePath = view.dataset.basePath || '/staff';

    // --- Tab Switching: Active Staff vs Pending Requests ---
    const tabContainer = document.getElementById('staffTabs');
    const panels = {
        active: document.getElementById('staff-panel-active'),
        pending: document.getElementById('staff-panel-pending')
    };

    function switchTab(targetTab) {
        if (!panels[targetTab] || !tabContainer) return;
        tabContainer.querySelectorAll('.staff-tab').forEach(function (t) {
            const isActive = t.dataset.tab === targetTab;
            t.classList.toggle('active', isActive);
            t.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        Object.entries(panels).forEach(function ([key, panel]) {
            if (panel) panel.hidden = (key !== targetTab);
        });
    }

    if (tabContainer) {
        tabContainer.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-tab]');
            if (!btn) return;
            switchTab(btn.dataset.tab);
        });

        // Hash deep-linking (e.g. #pending)
        if (window.location.hash === '#pending') {
            switchTab('pending');
        }
    }

    // --- Pending Registration Requests: Confirm / Reject ---
    const pendingBody = document.getElementById('pendingTableBody');
    const pendingEmpty = document.getElementById('pendingEmpty');

    function updatePendingCounters() {
        const remaining = pendingBody ? pendingBody.querySelectorAll('tr').length : 0;
        const badge = document.getElementById('pendingBadge');
        const pill = document.getElementById('pendingSectionPill');
        const summaryCount = document.getElementById('pendingCountSummary');
        const subText = document.getElementById('pendingRequestsSub');

        if (badge) {
            badge.textContent = remaining;
            badge.style.display = remaining > 0 ? '' : 'none';
        }
        if (pill) {
            pill.textContent = remaining + ' Pending';
            pill.style.display = remaining > 0 ? '' : 'none';
        }
        if (summaryCount) {
            summaryCount.textContent = remaining;
        }
        if (subText) {
            subText.textContent = remaining + ' requests awaiting role assignment';
        }
        if (pendingEmpty) {
            pendingEmpty.hidden = remaining > 0;
        }
    }

    function removeRow(row) {
        row.remove();
        updatePendingCounters();
    }

    if (pendingBody) {
        pendingBody.addEventListener('click', function (e) {
            const row = e.target.closest('tr');
            if (!row) return;
            const code = row.dataset.code;

            const confirmBtn = e.target.closest('.btn-confirm');
            if (confirmBtn) {
                const role = row.querySelector('.assign-role-select').value;
                if (!role) {
                    alert('Please select a role to assign before confirming.');
                    return;
                }
                confirmBtn.disabled = true;
                fetch(basePath + '/' + encodeURIComponent(code) + '/approve', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ role: role })
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            removeRow(row);
                        } else {
                            alert(data.message || 'Could not approve this registration.');
                            confirmBtn.disabled = false;
                        }
                    })
                    .catch(function () {
                        alert('Something went wrong. Please try again.');
                        confirmBtn.disabled = false;
                    });
                return;
            }

            const rejectBtn = e.target.closest('.btn-reject');
            if (rejectBtn) {
                if (!confirm('Reject this registration request? This cannot be undone.')) return;
                rejectBtn.disabled = true;
                fetch(basePath + '/' + encodeURIComponent(code) + '/reject', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({})
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            removeRow(row);
                        } else {
                            alert(data.message || 'Could not reject this registration.');
                            rejectBtn.disabled = false;
                        }
                    })
                    .catch(function () {
                        alert('Something went wrong. Please try again.');
                        rejectBtn.disabled = false;
                    });
            }
        });
    }

    // --- Active Staff Members: search + role filter ---
    const tbody = document.querySelector('#activeStaffTable tbody');
    const emptyMsg = document.getElementById('activeStaffEmpty');
    const searchInput = document.getElementById('activeStaffSearch');
    const roleFilter = document.getElementById('activeStaffFilter');
    const shownCount = document.getElementById('activeStaffShownCount');
    let roleValue = '';

    function applyFilters() {
        if (!tbody) return;
        const q = (searchInput ? searchInput.value : '').trim().toLowerCase();
        let visible = 0;
        const allRows = tbody.querySelectorAll('tr');
        allRows.forEach(function (row) {
            const matchesSearch = !q || (row.dataset.search && row.dataset.search.includes(q));
            const matchesRole = !roleValue || row.dataset.role === roleValue;
            const show = matchesSearch && matchesRole;
            row.hidden = !show;
            if (show) visible++;
        });
        if (emptyMsg) emptyMsg.hidden = visible !== 0;
        if (shownCount) {
            shownCount.textContent = visible + ' of ' + allRows.length + ' members shown';
        }
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (roleFilter) {
        roleFilter.addEventListener('click', function (e) {
            const btn = e.target.closest('.seg-btn');
            if (!btn) return;
            roleFilter.querySelectorAll('.seg-btn').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            roleValue = btn.dataset.value;
            applyFilters();
        });
    }

    // --- Active Staff Table: Deactivate / Activate & Delete UI actions ---
    const activeStaffTable = document.getElementById('activeStaffTable');
    if (activeStaffTable) {
        activeStaffTable.addEventListener('click', function (e) {
            const row = e.target.closest('tr');
            if (!row) return;
            const statusBtn = e.target.closest('.btn-action-status');
            const deleteBtn = e.target.closest('.btn-delete-staff');

            // 1. Deactivate / Activate Toggle
            if (statusBtn) {
                const name = statusBtn.dataset.name || 'this staff member';
                const currentStatus = row.dataset.status || 'active';
                const isDeactivating = currentStatus === 'active';

                if (isDeactivating) {
                    if (!confirm('Are you sure you want to deactivate ' + name + '\'s account? Their status will be set to pending.')) {
                        return;
                    }
                    // Toggle UI to pending / deactivated state
                    row.dataset.status = 'pending';
                    const pill = row.querySelector('.status-indicator-pill');
                    if (pill) {
                        pill.className = 'pill pill-pending status-indicator-pill';
                        pill.textContent = 'Pending';
                    }
                    statusBtn.className = 'btn-action-status btn-activate';
                    statusBtn.title = 'Activate account';
                    statusBtn.innerHTML = '<i class="fa-solid fa-user-check"></i> <span>Activate</span>';
                } else {
                    // Toggle UI back to active state
                    row.dataset.status = 'active';
                    const pill = row.querySelector('.status-indicator-pill');
                    if (pill) {
                        pill.className = 'pill pill-active status-indicator-pill';
                        pill.textContent = 'Active';
                    }
                    statusBtn.className = 'btn-action-status btn-deactivate';
                    statusBtn.title = 'Deactivate account';
                    statusBtn.innerHTML = '<i class="fa-solid fa-user-slash"></i> <span>Deactivate</span>';
                }
                return;
            }

            // 2. Delete Staff Member
            if (deleteBtn) {
                const name = deleteBtn.dataset.name || 'this staff member';
                if (!confirm('Are you sure you want to delete ' + name + '\'s account? This action cannot be undone and they will have to sign up again.')) {
                    return;
                }

                row.style.opacity = '0.3';
                setTimeout(function () {
                    row.remove();
                    // Update counter badges
                    const currentRows = activeStaffTable.querySelectorAll('tbody tr').length;
                    const badge = document.getElementById('activeStaffBadge');
                    const summary = document.getElementById('activeStaffCountSummary');
                    if (badge) badge.textContent = currentRows;
                    if (summary) summary.textContent = currentRows;
                    applyFilters();
                }, 200);
            }
        });
    }
});
