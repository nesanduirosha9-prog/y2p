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
        const remaining = document.querySelectorAll('.wk-cover-item').length;
        if (pendingPill) pendingPill.textContent = `${remaining} pending`;
        if (assignedDot) assignedDot.classList.toggle('show', remaining > 0);
        if (remaining === 0 && coverList && !document.getElementById('wkCoverEmptyMsg')) {
            const emptyMsg = document.createElement('div');
            emptyMsg.id = 'wkCoverEmptyMsg';
            emptyMsg.className = 'wk-cover-empty';
            emptyMsg.style.cssText = 'padding: 24px 20px; text-align: center; color: #64748b; font-size: 13px; background: #fff; border-top: 1px solid #fde68a;';
            emptyMsg.innerHTML = '<i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 6px;"></i>No pending cover requests. You are all caught up!';
            coverList.appendChild(emptyMsg);
        }
    }

    // 2. Cover Staff Requests Accept / Reject
    const coverList = document.getElementById('wkCoverList');
    const assignedCoursesTbody = document.getElementById('assignedCoursesTbody');
    const assignedCoursesCount = document.getElementById('wkAssignedCoursesCount');
    const courseSearch = document.getElementById('wkCourseSearch');
    const programFilter = document.getElementById('wkProgramFilter');
    const yearFilter = document.getElementById('wkYearFilter');
    const sessionTypeFilter = document.getElementById('wkSessionTypeFilter');
    const coursesEmptyMsg = document.getElementById('wkCoursesEmptyMsg');

    let currentProgram = '';
    let currentYear = '';
    let currentSession = '';

    function applyAssignedCoursesFilters() {
        if (!assignedCoursesTbody) return;
        const query = courseSearch ? courseSearch.value.trim().toLowerCase() : '';
        const rows = assignedCoursesTbody.querySelectorAll('tr.assigned-course-row');
        let visibleCount = 0;

        rows.forEach(function (row) {
            const rowSearch = (row.dataset.search || '').toLowerCase();
            const rowProgram = row.dataset.program || '';
            const rowYear = row.dataset.year || '';
            const rowSessions = (row.dataset.sessions || '').split(',');

            const matchSearch = !query || rowSearch.includes(query);
            const matchProgram = !currentProgram || rowProgram === currentProgram;
            const matchYear = !currentYear || rowYear === currentYear;
            const matchSession = !currentSession || rowSessions.includes(currentSession);

            const show = matchSearch && matchProgram && matchYear && matchSession;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        if (coursesEmptyMsg) coursesEmptyMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
    }

    if (courseSearch) {
        courseSearch.addEventListener('input', applyAssignedCoursesFilters);
    }

    function setupAssignedSegment(groupEl, callback) {
        if (!groupEl) return;
        groupEl.addEventListener('click', function (e) {
            const btn = e.target.closest('.seg-btn');
            if (!btn) return;
            groupEl.querySelectorAll('.seg-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            callback(btn.dataset.value || '');
            applyAssignedCoursesFilters();
        });
    }

    setupAssignedSegment(programFilter, val => { currentProgram = val; });
    setupAssignedSegment(yearFilter, val => { currentYear = val; });

    if (sessionTypeFilter) {
        sessionTypeFilter.addEventListener('change', function () {
            currentSession = sessionTypeFilter.value.trim();
            applyAssignedCoursesFilters();
        });
    }

    coverList?.addEventListener('click', (e) => {
        const acceptBtn = e.target.closest('[data-accept]');
        const rejectBtn = e.target.closest('[data-reject]');
        if (!acceptBtn && !rejectBtn) return;

        const item = e.target.closest('.wk-cover-item');
        if (!item) return;

        const isAccept = !!acceptBtn;
        const code = item.dataset.code || '';
        const title = item.dataset.name || '';
        const lecturerName = item.dataset.lecturerName || 'Course Lecturer';
        const lecturerCode = item.dataset.lecturerCode || 'LEC';
        const colleague = item.dataset.colleague || '';
        const schedule = item.dataset.schedule || '';
        const credits = item.dataset.credits || '3';
        const year = item.dataset.year || '1';
        const program = item.dataset.program || 'CS';
        const hours = item.dataset.hours || '2';
        const sessions = (item.dataset.sessions || 'Lectures,Practicals,Lab Sessions').split(',');

        // Animate and remove from the cover requests list
        item.style.transition = 'all 0.25s ease';
        item.style.opacity = '0';
        item.style.transform = 'translateX(20px)';
        setTimeout(() => {
            item.remove();
            refreshPendingCount();
        }, 250);

        if (isAccept) {
            const searchStr = (code + ' ' + title + ' ' + lecturerCode + ' ' + lecturerName + ' ' + sessions.join(' ')).toLowerCase();

            // 1. Add to My Assigned Courses table
            if (assignedCoursesTbody) {
                const newRow = document.createElement('tr');
                newRow.className = 'assigned-course-row';
                newRow.style.background = '#f0fdf4';
                newRow.style.transition = 'background 1.5s ease';
                newRow.dataset.code = code;
                newRow.dataset.name = title;
                newRow.dataset.year = year;
                newRow.dataset.program = program;
                newRow.dataset.sessions = sessions.join(',');
                newRow.dataset.search = searchStr;

                newRow.innerHTML = `
                    <td>${codeBadge(code, 'course', { title: title })}</td>
                    <td>
                        <strong>${esc(title)}</strong>
                        <div class="course-sessions-hint" style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                            <i class="fa-regular fa-clock"></i> ${esc(schedule)} · <span class="pill pill-pending" style="font-size: 10px; padding: 1px 6px; background: #fef3c7; color: #92400e; font-weight: 600;"><i class="fa-regular fa-clock"></i> To be evaluated</span>
                        </div>
                    </td>
                    <td>${esc(credits)}</td>
                    <td><span class="pill pill-year-${esc(year)}">Year ${esc(year)}</span></td>
                    <td><span class="pill pill-muted">${esc(program)}</span></td>
                    <td>
                        <div class="tag-row">
                            <span class="code-badge code-badge--lecturer" title="${esc(lecturerName)}">${esc(lecturerCode)}</span>
                        </div>
                    </td>
                    <td>
                        <div class="tag-row">
                            <span style="color: #94a3b8; font-size: 13px;">—</span>
                        </div>
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
                    const currentCount = assignedCoursesTbody.querySelectorAll('tr.assigned-course-row').length;
                    assignedCoursesCount.textContent = `${currentCount} assigned courses`;
                }

                applyAssignedCoursesFilters();
            }

            // 2. Also add entry to History table as "Not Evaluated" with rating "—"
            if (historyTbody) {
                const today = new Date();
                const todayStr = today.toISOString().slice(0, 10);
                const newHistRow = document.createElement('tr');
                newHistRow.className = 'wk-history-row';
                newHistRow.style.background = '#f0fdf4';
                newHistRow.style.transition = 'background 1.5s ease';
                newHistRow.dataset.week = 'Week 5';
                newHistRow.dataset.month = 'March 2026';
                newHistRow.dataset.sem = 'Semester 1 - 2026';
                newHistRow.dataset.year = '2026';
                newHistRow.dataset.course = code;
                newHistRow.dataset.isOther = '1';
                newHistRow.dataset.search = (code + ' ' + title + ' cover duty week 5 march 2026 not evaluated other cover ' + colleague).toLowerCase();

                newHistRow.innerHTML = `
                    <td>
                        <div style="font-weight: 600; color: #0f1c2e;">${todayStr}</div>
                        <div style="font-size: 11px; color: #64748b;">Week 5 &middot; Semester 1 - 2026</div>
                    </td>
                    <td>
                        <span class="pill pill-muted" style="font-weight: 700;">${esc(code)}</span>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #0f1c2e;">${esc(title)}</div>
                    </td>
                    <td>
                        <span class="pill pill-muted"><i class="fa-solid fa-user-clock" style="margin-right: 3px;"></i> Cover Duty</span>
                    </td>
                    <td>
                        <span style="color: #94a3b8; font-weight: 500;">—</span>
                    </td>
                    <td style="text-align: right;">
                        <span class="pill pill-pending" style="background: #fef3c7; color: #92400e; font-size: 11px;">
                            Not Evaluated
                        </span>
                    </td>
                `;

                historyTbody.insertBefore(newHistRow, historyTbody.firstChild);
                setTimeout(() => { newHistRow.style.background = ''; }, 1200);

                if (historyCountDisplay) {
                    const totalHist = historyTbody.querySelectorAll('tr.wk-history-row').length;
                    historyCountDisplay.textContent = `${totalHist} evaluation records`;
                }

                applyInstructorHistoryFilters();
            }

            notify(`Cover request accepted — ${code} added to assigned courses & history.`, true);
        } else {
            notify('Cover request declined and removed.', false);
        }
    });

    // 3. Filter Controls for History Tab
    const historySearch = document.getElementById('wkHistorySearch');
    const historyTimeFilter = document.getElementById('wkHistoryTimeFilter');
    const historyCourseFilter = document.getElementById('wkHistoryCourseFilter');
    const historyPeriodNav = document.getElementById('wkHistoryPeriodNav');
    const historyPrevBtn = document.getElementById('wkHistoryPrevBtn');
    const historyNextBtn = document.getElementById('wkHistoryNextBtn');
    const historyPeriodLabel = document.getElementById('wkHistoryPeriodLabel');
    const historyTbody = document.getElementById('wkHistoryTbody');
    const historyEmptyMsg = document.getElementById('wkHistoryEmptyMsg');
    const historyCountDisplay = document.getElementById('wkHistoryCountDisplay');

    const WEEKS = [
        { label: '16 – 20 Feb 2026 (Week 1)', value: 'Week 1' },
        { label: '23 – 27 Feb 2026 (Week 2)', value: 'Week 2' },
        { label: '2 – 6 Mar 2026 (Week 3)', value: 'Week 3' },
        { label: '9 – 13 Mar 2026 (Week 4)', value: 'Week 4' },
        { label: '16 – 20 Mar 2026 (Week 5)', value: 'Week 5' },
        { label: '23 – 27 Mar 2026 (Week 6)', value: 'Week 6' },
        { label: '30 Mar – 3 Apr 2026 (Week 7)', value: 'Week 7' },
    ];
    let currentWeekIndex = 4; // Week 5 is default

    const MONTHS = [
        { label: 'January 2026', value: 'January' },
        { label: 'February 2026', value: 'February' },
        { label: 'March 2026', value: 'March' },
        { label: 'April 2026', value: 'April' },
        { label: 'May 2026', value: 'May' },
    ];
    let currentMonthIndex = 2; // March 2026 is default

    const SEMESTERS = [
        { label: 'Semester 1 - 2025', value: 'Semester 1 - 2025' },
        { label: 'Semester 2 - 2025', value: 'Semester 2 - 2025' },
        { label: 'Semester 1 - 2026', value: 'Semester 1 - 2026' },
        { label: 'Semester 2 - 2026', value: 'Semester 2 - 2026' },
    ];
    let currentSemIndex = 2; // Semester 1 - 2026 is default

    const YEARS = [
        { label: '2024', value: '2024' },
        { label: '2025', value: '2025' },
        { label: '2026', value: '2026' },
        { label: '2027', value: '2027' },
    ];
    let currentYearIndex = 2; // 2026 is default

    let currentHistoryTime = '';

    function updatePeriodNav() {
        if (!historyPeriodNav || !historyPeriodLabel || !historyPrevBtn || !historyNextBtn) return;
        if (currentHistoryTime === 'week') {
            historyPeriodNav.style.display = 'inline-flex';
            historyPeriodLabel.textContent = WEEKS[currentWeekIndex].label;
            historyPrevBtn.disabled = (currentWeekIndex === 0);
            historyNextBtn.disabled = (currentWeekIndex === WEEKS.length - 1);
        } else if (currentHistoryTime === 'month') {
            historyPeriodNav.style.display = 'inline-flex';
            historyPeriodLabel.textContent = MONTHS[currentMonthIndex].label;
            historyPrevBtn.disabled = (currentMonthIndex === 0);
            historyNextBtn.disabled = (currentMonthIndex === MONTHS.length - 1);
        } else if (currentHistoryTime === 'sem') {
            historyPeriodNav.style.display = 'inline-flex';
            historyPeriodLabel.textContent = SEMESTERS[currentSemIndex].label;
            historyPrevBtn.disabled = (currentSemIndex === 0);
            historyNextBtn.disabled = (currentSemIndex === SEMESTERS.length - 1);
        } else if (currentHistoryTime === 'year') {
            historyPeriodNav.style.display = 'inline-flex';
            historyPeriodLabel.textContent = YEARS[currentYearIndex].label;
            historyPrevBtn.disabled = (currentYearIndex === 0);
            historyNextBtn.disabled = (currentYearIndex === YEARS.length - 1);
        } else {
            historyPeriodNav.style.display = 'none';
        }
    }

    historyPrevBtn?.addEventListener('click', () => {
        if (currentHistoryTime === 'week' && currentWeekIndex > 0) {
            currentWeekIndex--;
            updatePeriodNav();
            applyInstructorHistoryFilters();
        } else if (currentHistoryTime === 'month' && currentMonthIndex > 0) {
            currentMonthIndex--;
            updatePeriodNav();
            applyInstructorHistoryFilters();
        } else if (currentHistoryTime === 'sem' && currentSemIndex > 0) {
            currentSemIndex--;
            updatePeriodNav();
            applyInstructorHistoryFilters();
        } else if (currentHistoryTime === 'year' && currentYearIndex > 0) {
            currentYearIndex--;
            updatePeriodNav();
            applyInstructorHistoryFilters();
        }
    });

    historyNextBtn?.addEventListener('click', () => {
        if (currentHistoryTime === 'week' && currentWeekIndex < WEEKS.length - 1) {
            currentWeekIndex++;
            updatePeriodNav();
            applyInstructorHistoryFilters();
        } else if (currentHistoryTime === 'month' && currentMonthIndex < MONTHS.length - 1) {
            currentMonthIndex++;
            updatePeriodNav();
            applyInstructorHistoryFilters();
        } else if (currentHistoryTime === 'sem' && currentSemIndex < SEMESTERS.length - 1) {
            currentSemIndex++;
            updatePeriodNav();
            applyInstructorHistoryFilters();
        } else if (currentHistoryTime === 'year' && currentYearIndex < YEARS.length - 1) {
            currentYearIndex++;
            updatePeriodNav();
            applyInstructorHistoryFilters();
        }
    });

    function applyInstructorHistoryFilters() {
        if (!historyTbody) return;
        const query = historySearch ? historySearch.value.trim().toLowerCase() : '';
        const courseVal = historyCourseFilter ? historyCourseFilter.value.trim() : '';
        const rows = historyTbody.querySelectorAll('tr.wk-history-row');
        let visibleCount = 0;

        rows.forEach(function (row) {
            const searchKey = (row.dataset.search || '').toLowerCase();
            const rowCourse = row.dataset.course || '';
            const isOther = (row.dataset.isOther === '1');
            const rowWeek = row.dataset.week || '';
            const rowMonth = row.dataset.month || '';
            const rowSem = row.dataset.sem || '';
            const rowYear = row.dataset.year || '';

            const matchQuery = !query || searchKey.includes(query);

            let matchCourse = true;
            if (courseVal === 'other') {
                matchCourse = isOther;
            } else if (courseVal) {
                matchCourse = (rowCourse === courseVal);
            }

            let matchTime = true;
            if (currentHistoryTime === 'week') {
                const targetWeek = WEEKS[currentWeekIndex].value;
                matchTime = rowWeek.toLowerCase().includes(targetWeek.toLowerCase());
            } else if (currentHistoryTime === 'month') {
                const targetMonth = MONTHS[currentMonthIndex].value;
                matchTime = rowMonth.toLowerCase().includes(targetMonth.toLowerCase());
            } else if (currentHistoryTime === 'sem') {
                const targetSem = SEMESTERS[currentSemIndex].value;
                matchTime = rowSem.toLowerCase().includes(targetSem.toLowerCase());
            } else if (currentHistoryTime === 'year') {
                const targetYear = YEARS[currentYearIndex].value;
                matchTime = rowYear.includes(targetYear);
            }

            const show = matchQuery && matchCourse && matchTime;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        if (historyEmptyMsg) historyEmptyMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
        if (historyCountDisplay) {
            historyCountDisplay.textContent = `${visibleCount} evaluation record${visibleCount === 1 ? '' : 's'}`;
        }
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
            updatePeriodNav();
            applyInstructorHistoryFilters();
        });
    }

    // Reset button in Overview
    document.getElementById('wkBtnReset')?.addEventListener('click', () => {
        document.querySelectorAll('.wk-select').forEach(sel => { sel.selectedIndex = 0; });
    });
});
