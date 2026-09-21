// Coordinator "Staff" screen — approve/reject pending registrations, and
// search/filter the active staff directory (client-side, same pattern as
// lecturers.js).
document.addEventListener('DOMContentLoaded', function () {
    const view = document.querySelector('.staff-view');
    if (!view) return;

    // --- Pending Registration Requests: Confirm / Reject ---
    const pendingBody = document.getElementById('pendingTableBody');
    const pendingEmpty = document.getElementById('pendingEmpty');

    function removeRow(row) {
        row.remove();
        if (pendingBody && !pendingBody.querySelector('tr') && pendingEmpty) {
            pendingEmpty.hidden = false;
        }
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
                fetch('/coordinator/staff/' + encodeURIComponent(code) + '/approve', {
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
                fetch('/coordinator/staff/' + encodeURIComponent(code) + '/reject', {
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
    let roleValue = '';

    function applyFilters() {
        if (!tbody) return;
        const q = (searchInput.value || '').trim().toLowerCase();
        let visible = 0;
        tbody.querySelectorAll('tr').forEach(function (row) {
            const matchesSearch = !q || row.dataset.search.includes(q);
            const matchesRole = !roleValue || row.dataset.role === roleValue;
            const show = matchesSearch && matchesRole;
            row.hidden = !show;
            if (show) visible++;
        });
        if (emptyMsg) emptyMsg.hidden = visible !== 0;
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
});
