// Instructor Workload JS: Tab switching, Cover Requests acceptance dynamically adding to Assigned Courses table, and History filtering.
document.addEventListener('DOMContentLoaded', () => {
    // Helper: Escaping HTML
    function esc(s) {
        if (s == null) return '';
        const d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }

    // Helper: Toast notification
    function notify(msg, isSuccess = true) {
        if (typeof window.ttToast === 'function') {
            window.ttToast(msg, { icon: isSuccess ? 'fa-circle-check' : 'fa-circle-xmark', type: isSuccess ? undefined : 'error' });
            return;
        }
        let toast = document.getElementById('hubToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'hubToast';
            toast.style.cssText = 'position: fixed; bottom: 24px; right: 24px; z-index: 9999; background: #0f1c2e; color: #fff; padding: 12px 20px; border-radius: 8px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); transition: opacity 0.3s;';
            document.body.appendChild(toast);
        }
        toast.innerHTML = `<i class="fa-solid ${isSuccess ? 'fa-circle-check' : 'fa-circle-exclamation'}" style="color: ${isSuccess ? '#10b981' : '#f59e0b'};"></i> <span>${esc(msg)}</span>`;
        toast.style.display = 'flex';
        toast.style.opacity = '1';
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => { toast.style.display = 'none'; }, 300);
        }, 3500);
    }

    // 1. Tab Switching
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
        Object.entries(panels).forEach(([key, panel]) => {
            if (panel) panel.hidden = key !== btn.dataset.tab;
        });
    });

    // Check hash on load (e.g. #assigned)
    if (window.location.hash) {
        const hash = window.location.hash.replace('#', '');
        if (panels[hash]) {
            tabs?.querySelectorAll('.wk-tab').forEach(t => {
                t.classList.toggle('active', t.dataset.tab === hash);
            });
            Object.entries(panels).forEach(([key, panel]) => {
                if (panel) panel.hidden = key !== hash;
            });
        }
    }

    const assignedDot = document.getElementById('wkAssignedDot');
    if (assignedDot) assignedDot.classList.add('show');

    const pendingPill = document.getElementById('wkPendingPill');
    function refreshPendingCount() {
        const remaining = document.querySelectorAll('.wk-cover-item:not(.resolved)').length;
        if (pendingPill) pendingPill.textContent = `${remaining} pending`;
        if (assignedDot) assignedDot.classList.toggle('show', remaining > 0);
    }

    // 2. Cover Staff Requests Accept / Reject
    const coverList = document.getElementById('wkCoverList');
    const assignedCoursesTbody = document.getElementById('assignedCoursesTbody');
    const assignedCoursesCount = document.getElementById('wkAssignedCoursesCount');

    coverList?.addEventListener('click', (e) => {
        const acceptBtn = e.target.closest('[data-accept]');
        const rejectBtn = e.target.closest('[data-reject]');
        if (!acceptBtn && !rejectBtn) return;

        const item = e.target.closest('.wk-cover-item');
        if (!item) return;

        item.classList.add('resolved');
        refreshPendingCount();

        if (acceptBtn) {
            const code = item.dataset.code || '';
            const title = item.dataset.name || '';
            const staff = item.dataset.staff || '';
            const schedule = item.dataset.schedule || '';
            const credits = item.dataset.credits || '3';
            const year = item.dataset.year || '1';
            const program = item.dataset.program || 'CS';
            const role = item.dataset.role || 'Cover Support';
            const hours = item.dataset.hours || '2';

            if (assignedCoursesTbody) {
                const newRow = document.createElement('tr');
                newRow.className = 'assigned-course-row';
                newRow.style.background = '#f0fdf4';
                newRow.style.transition = 'background 1.5s ease';

                newRow.innerHTML = `
                    <td><span class="pill pill-muted">${esc(code)}</span></td>
                    <td>
                        <strong>${esc(title)}</strong>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                            <i class="fa-regular fa-clock"></i> ${esc(schedule)} · <span style="color: #166534; font-weight: 600;">Cover Duty</span>
                        </div>
                    </td>
                    <td>${esc(credits)}</td>
                    <td><span class="pill pill-year-${esc(year)}">Year ${esc(year)}</span></td>
                    <td><span class="pill pill-muted">${esc(program)}</span></td>
                    <td>
                        <span class="tag tag-lecturer">${esc(staff)}</span>
                    </td>
                    <td>
                        <strong>${esc(role)}</strong>
                        <div style="font-size: 11.5px; color: #166534; font-weight: 500;">Accepted Cover Request</div>
                    </td>
                    <td style="text-align: right; font-weight: 700; color: #1a3a6b;">
                        ${esc(hours)} hrs/wk
                    </td>
                `;

                assignedCoursesTbody.appendChild(newRow);

                setTimeout(() => {
                    newRow.style.background = '';
                }, 1200);

                if (assignedCoursesCount) {
                    const currentCount = assignedCoursesTbody.querySelectorAll('tr').length;
                    assignedCoursesCount.textContent = `${currentCount} assigned courses`;
                }
            }

            notify(`Cover request accepted — ${code} added to your assigned courses.`, true);
        } else {
            notify('Cover request declined.', false);
        }
    });

    // 3. Filter Controls for History Tab
    const historySearch = document.getElementById('wkHistorySearch');
    const historyTimeFilter = document.getElementById('wkHistoryTimeFilter');
    const historyCourseFilter = document.getElementById('wkHistoryCourseFilter');
    const historyTbody = document.getElementById('wkHistoryTbody');
    const historyEmptyMsg = document.getElementById('wkHistoryEmptyMsg');

    let currentHistoryTime = '';

    function applyInstructorHistoryFilters() {
        if (!historyTbody) return;
        const query = historySearch ? historySearch.value.trim().toLowerCase() : '';
        const courseVal = historyCourseFilter ? historyCourseFilter.value.trim() : '';
        const rows = historyTbody.querySelectorAll('tr.wk-history-row');
        let visibleCount = 0;

        rows.forEach(function (row) {
            const searchKey = row.dataset.search || '';
            const rowCourse = row.dataset.course || '';
            const rowWeek = row.dataset.week || '';
            const rowMonth = row.dataset.month || '';
            const rowSem = row.dataset.sem || '';
            const rowYear = row.dataset.year || '';

            const matchQuery = !query || searchKey.includes(query);
            const matchCourse = !courseVal || rowCourse === courseVal;

            let matchTime = true;
            if (currentHistoryTime === 'week') {
                matchTime = rowWeek.toLowerCase().includes('week 5'); // Current week
            } else if (currentHistoryTime === 'month') {
                matchTime = rowMonth.toLowerCase().includes('march');
            } else if (currentHistoryTime === 'sem') {
                matchTime = rowSem.toLowerCase().includes('semester 1');
            } else if (currentHistoryTime === 'year') {
                matchTime = rowYear.includes('2026');
            }

            const show = matchQuery && matchCourse && matchTime;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        if (historyEmptyMsg) historyEmptyMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
    }

    if (historySearch) historySearch.addEventListener('input', applyInstructorHistoryFilters);
    if (historyCourseFilter) historyCourseFilter.addEventListener('change', applyInstructorHistoryFilters);

    if (historyTimeFilter) {
        historyTimeFilter.addEventListener('click', function (e) {
            const btn = e.target.closest('.seg-btn');
            if (!btn) return;
            historyTimeFilter.querySelectorAll('.seg-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentHistoryTime = btn.dataset.value || '';
            applyInstructorHistoryFilters();
        });
    }

    // Reset button in Overview
    document.getElementById('wkBtnReset')?.addEventListener('click', () => {
        document.querySelectorAll('.wk-select').forEach(sel => { sel.selectedIndex = 0; });
    });
});
