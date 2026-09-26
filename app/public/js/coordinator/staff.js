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
        const subText = document.getElementById('pendingRequestsSub');

        if (badge) {
            badge.textContent = remaining;
            badge.style.display = remaining > 0 ? '' : 'none';
        }
        if (pill) {
            pill.textContent = remaining + ' Pending';
            pill.style.display = remaining > 0 ? '' : 'none';
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

    // --- Add staff: email + role -> POST /staff/create ---
    // The directory is server-rendered, so after creating one or more accounts
    // the page reloads on close rather than re-building the row markup here.
    const addPanel = document.getElementById('addStaffPanel');
    const addBackdrop = document.getElementById('addStaffBackdrop');
    if (addPanel) {
        const form = document.getElementById('addStaffForm');
        const done = document.getElementById('addStaffDone');
        const emailInput = document.getElementById('addStaffEmail');
        const submitBtn = document.getElementById('addStaffSubmit');
        const errors = {
            email: document.getElementById('addStaffEmailError'),
            role: document.getElementById('addStaffRoleError'),
            general: document.getElementById('addStaffError'),
        };
        let createdAny = false;

        function showError(field, msg) {
            const el = errors[field] || errors.general;
            el.textContent = msg;
            el.hidden = false;
            if (field === 'email') emailInput.focus();
        }

        function clearErrors() {
            Object.values(errors).forEach(function (el) { el.hidden = true; el.textContent = ''; });
        }

        function resetForm() {
            form.reset();
            clearErrors();
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create account';
            form.hidden = false;
            done.hidden = true;
        }

        function openAdd() {
            resetForm();
            addPanel.hidden = false;
            addBackdrop.hidden = false;
            emailInput.focus();
        }

        function closeAdd() {
            addPanel.hidden = true;
            addBackdrop.hidden = true;
            if (createdAny) window.location.reload();
        }

        // A field's error goes away as soon as the field is changed.
        emailInput.addEventListener('input', function () { errors.email.hidden = true; errors.general.hidden = true; });
        form.querySelectorAll('input[name="role"]').forEach(function (r) {
            r.addEventListener('change', function () { errors.role.hidden = true; });
        });

        document.getElementById('addStaffBtn').addEventListener('click', openAdd);
        addPanel.querySelectorAll('[data-add-close]').forEach(function (b) { b.addEventListener('click', closeAdd); });
        addBackdrop.addEventListener('click', closeAdd);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !addPanel.hidden) closeAdd(); });
        document.getElementById('addStaffFinish').addEventListener('click', closeAdd);
        document.getElementById('addStaffAnother').addEventListener('click', function () { resetForm(); emailInput.focus(); });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearErrors();

            const email = emailInput.value.trim();
            const roleInput = form.querySelector('input[name="role"]:checked');
            let ok = true;
            if (!email || !emailInput.checkValidity()) { showError('email', 'Enter a valid email address.'); ok = false; }
            if (!roleInput) { showError('role', 'Choose Lecturer or Junior Staff.'); ok = false; }
            if (!ok) return;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating…';

            fetch(basePath + '/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, role: roleInput.value })
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) {
                        showError(data.field || 'general', data.message || 'Could not create the account.');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Create account';
                        return;
                    }
                    createdAny = true;
                    const roleLabel = roleInput.value === 'lecturer' ? 'Lecturer' : 'Junior Staff';
                    document.getElementById('addStaffDoneTitle').textContent =
                        roleLabel + ' account created — ' + data.code;
                    document.getElementById('addStaffDoneSub').textContent = data.invited
                        ? 'We emailed ' + email + ' with how to set a password.'
                        : data.demo
                            ? 'Demo mode: no email was sent. ' + email + ' can set a password with "Forgot password" on the sign-in page.'
                            : 'The account exists, but the email could not be sent. Ask them to use "Forgot password" on the sign-in page with ' + email + '.';
                    form.hidden = true;
                    done.hidden = false;
                    document.getElementById('addStaffFinish').focus();
                })
                .catch(function () {
                    showError('general', 'Something went wrong. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Create account';
                });
        });
    }

    // Mirrors what components/staff_directory.php renders for each status.
    function setRowStatus(row, statusBtn, status) {
        const inactive = status === 'inactive';
        row.dataset.status = status;
        row.classList.toggle('is-inactive', inactive);

        const pill = row.querySelector('.status-indicator-pill');
        if (pill) {
            pill.className = 'pill ' + (inactive ? 'pill-muted' : 'pill-active') + ' status-indicator-pill';
            pill.textContent = inactive ? 'Inactive' : 'Active';
        }

        statusBtn.className = 'btn-action-status ' + (inactive ? 'btn-activate' : 'btn-deactivate');
        statusBtn.title = inactive ? 'Reactivate account' : 'Deactivate account';
        statusBtn.innerHTML = inactive
            ? '<i class="fa-solid fa-user-check"></i> <span>Reactivate</span>'
            : '<i class="fa-solid fa-user-slash"></i> <span>Deactivate</span>';

        // Deactivating removed their course assignments on the server.
        if (inactive && statusBtn.dataset.courses) {
            statusBtn.dataset.courses = '';
            const coursesCell = row.cells[4];
            if (coursesCell) coursesCell.innerHTML = '<span class="text-muted">&mdash;</span>';
        }
    }

    // --- Active Staff Table: Deactivate / Reactivate & Delete UI actions ---
    const activeStaffTable = document.getElementById('activeStaffTable');
    if (activeStaffTable) {
        activeStaffTable.addEventListener('click', function (e) {
            const row = e.target.closest('tr');
            if (!row) return;
            const statusBtn = e.target.closest('.btn-action-status');
            const deleteBtn = e.target.closest('.btn-delete-staff');

            // 1. Deactivate / Reactivate -> POST /staff/{code}/deactivate | activate.
            // Deactivating is the soft delete for someone who left the
            // university: they can no longer sign in, their history stays.
            if (statusBtn) {
                const name = statusBtn.dataset.name || 'this staff member';
                const isDeactivating = (row.dataset.status || 'active') === 'active';
                const courses = (statusBtn.dataset.courses || '').trim();

                const question = isDeactivating
                    ? 'Deactivate ' + name + '\'s account?\n\nThey will no longer be able to sign in. Their leave, messages and other records are kept.'
                        + (courses ? '\n\nThey will be removed from ' + courses.split(/\s+/).join(', ') + '. The Timetable Officer will need to assign someone else.' : '')
                    : 'Reactivate ' + name + '\'s account? They will be able to sign in again.';
                if (!confirm(question)) return;

                statusBtn.disabled = true;
                fetch(basePath + '/' + encodeURIComponent(row.dataset.code) + (isDeactivating ? '/deactivate' : '/activate'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({})
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        statusBtn.disabled = false;
                        if (!data.success) {
                            alert(data.message || 'Could not change this account.');
                            return;
                        }
                        setRowStatus(row, statusBtn, isDeactivating ? 'inactive' : 'active');
                    })
                    .catch(function () {
                        statusBtn.disabled = false;
                        alert('Something went wrong. Please try again.');
                    });
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
                    if (badge) badge.textContent = currentRows;
                    applyFilters();
                }, 200);
            }
        });
    }
});
