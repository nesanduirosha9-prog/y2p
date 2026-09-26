// courses.js — 3-Tab Hub for Lecturers (Course Details, Evaluate by Instructor, Evaluation History)
document.addEventListener('DOMContentLoaded', function () {
    const isLecturer = true; // /courses is now dedicated to Lecturers

    // Helper: Escaping HTML
    function esc(s) {
        if (s == null) return '';
        const d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }

    // The shared teaching calendar (WorkloadPrototypeData::calendar()). An
    // evaluation submitted here belongs to its `current` week.
    const HISTORY_CAL = (function () {
        const node = document.getElementById('historyCalendar');
        try { return node ? JSON.parse(node.textContent) : null; } catch (e) { return null; }
    })();

    /** "Week 5 · Semester 2" for the current week. */
    function currentWeekLabel() {
        const w = HISTORY_CAL && window.PeriodNav ? PeriodNav.weekOf(HISTORY_CAL, HISTORY_CAL.current) : null;
        return w ? 'Week ' + w.number + ' · ' + w.semName : 'This week';
    }

    // Helper: Toast notification
    function notify(msg, isSuccess = true) {
        if (typeof window.ttToast === 'function') {
            window.ttToast(msg, { icon: isSuccess ? 'fa-circle-check' : 'fa-circle-exclamation' });
            return;
        }
        // Fallback toast
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

    // =========================================================================
    // 1. HUB TAB SWITCHING
    // =========================================================================
    const tabsContainer = document.getElementById('coursesTabs');
    const tabPanels = {
        courses: document.getElementById('courses-panel-courses'),
        instructors: document.getElementById('courses-panel-instructors'),
        history: document.getElementById('courses-panel-history'),
    };

    if (tabsContainer) {
        tabsContainer.addEventListener('click', function (e) {
            const btn = e.target.closest('.course-tab');
            if (!btn) return;
            const targetTab = btn.dataset.tab;
            if (!targetTab || !tabPanels[targetTab]) return;

            tabsContainer.querySelectorAll('.course-tab').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');

            Object.entries(tabPanels).forEach(([key, panel]) => {
                if (panel) {
                    if (key === targetTab) {
                        panel.hidden = false;
                        panel.classList.add('active');
                    } else {
                        panel.hidden = true;
                        panel.classList.remove('active');
                    }
                }
            });
        });
    }

    // =========================================================================
    // 2. TAB 1: COURSE DETAILS & SLIDING EVALUATION FLOW
    // =========================================================================
    const flowWrapper = document.getElementById('coursesFlowWrapper');
    const searchInput = document.getElementById('courseSearch');
    const programFilter = document.getElementById('programFilter');
    const yearFilter = document.getElementById('yearFilter');
    const sessionTypeFilter = document.getElementById('sessionTypeFilter');
    const coursesTable = document.getElementById('assignedCoursesTable');
    const coursesTbody = coursesTable ? coursesTable.querySelector('tbody') : null;
    const coursesCountDisplay = document.getElementById('coursesCountBadge');
    const coursesEmptyMsg = document.getElementById('coursesEmptyMsg');

    const backBtn = document.getElementById('backToCoursesBtn');
    const evalCourseHeading = document.getElementById('evalCourseHeading');
    const evalCourseYearPill = document.getElementById('evalCourseYearPill');
    const evalCourseProgPill = document.getElementById('evalCourseProgPill');
    const evalCourseSubText = document.getElementById('evalCourseSubText');
    const evalContainer = document.getElementById('evalInstructorsContainer');
    const submitAllBtn = document.getElementById('submitAllCourseEvaluationsBtn');

    let currentProgram = '';
    let currentYear = '';
    let currentSession = '';

    function applyCourseFilters() {
        if (!coursesTbody) return;
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const rows = coursesTbody.querySelectorAll('tr.course-row');
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

    if (searchInput) {
        searchInput.addEventListener('input', applyCourseFilters);
    }

    function setupSegment(groupEl, callback) {
        if (!groupEl) return;
        groupEl.addEventListener('click', function (e) {
            const btn = e.target.closest('.seg-btn');
            if (!btn) return;
            groupEl.querySelectorAll('.seg-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            callback(btn.dataset.value || '');
            applyCourseFilters();
        });
    }

    setupSegment(programFilter, val => { currentProgram = val; });
    setupSegment(yearFilter, val => { currentYear = val; });

    if (sessionTypeFilter) {
        sessionTypeFilter.addEventListener('change', function () {
            currentSession = sessionTypeFilter.value.trim();
            applyCourseFilters();
        });
    }

    const ratingLabels = {
        1: 'Unsatisfactory',
        2: 'Needs improvement',
        3: 'Satisfactory',
        4: 'Very good',
        5: 'Excellent'
    };

    /** The 1–5 rating dropdown, empty until the lecturer picks one. */
    function ratingOptions() {
        return '<option value="">Select…</option>' +
            [5, 4, 3, 2, 1].map(n => '<option value="' + n + '">' + n + ' — ' + ratingLabels[n] + '</option>').join('');
    }

    /** "4 / 5" as a coloured pill — the Evaluations page's score bands. */
    function scoreBand(n) {
        return n >= 4 ? 'high' : n === 3 ? 'mid' : 'low';
    }
    function scorePill(n) {
        n = Math.round(Number(n));
        return '<span class="eval-score-pill score-' + scoreBand(n) + '">' + n + ' / 5</span>';
    }

    // This week's submitted evaluations, by staff code: { rating, date, course }.
    const EVAL_KEY = 'staffsync_eval_week5';
    function loadEvals() {
        try { return JSON.parse(sessionStorage.getItem(EVAL_KEY) || '{}'); } catch (e) { return {}; }
    }
    function saveEval(code, record) {
        try {
            const current = loadEvals();
            current[code] = record;
            sessionStorage.setItem(EVAL_KEY, JSON.stringify(current));
        } catch (e) {}
    }
    /** Evaluated for this course this week? */
    function isDoneFor(evals, code, courseCode) {
        return !!(evals[code] && evals[code].course === courseCode);
    }

    /** Tab 2: flip an instructor's pill and button to "Evaluated". */
    function markInstructorEvaluated(code, rating) {
        const statusPill = document.getElementById('instStatus_' + code);
        if (statusPill) {
            statusPill.className = 'pill pill-active status-pill';
            statusPill.innerHTML = 'Evaluated (' + Math.round(rating) + ')';
        }
        const evalBtn = document.getElementById('btnEval_' + code);
        if (evalBtn) {
            evalBtn.className = 'btn-evaluate-instructor btn-evaluated';
            evalBtn.disabled = true;
            evalBtn.innerHTML = 'Evaluated';
        }
    }

    /** Tab 3: put a just-submitted evaluation at the top of the history. */
    function addHistoryRow(courseCode, code, instName, rating, comment, dateStr) {
        const historyTbody = document.getElementById('evaluationHistoryTbody');
        if (!historyTbody) return;
        const newRow = document.createElement('tr');
        newRow.className = 'history-row';
        newRow.dataset.status = 'evaluated';
        newRow.dataset.id = 'eval-' + Date.now() + '-' + code;
        newRow.dataset.weekStart = HISTORY_CAL ? HISTORY_CAL.current : '';
        newRow.dataset.course = courseCode;
        newRow.dataset.instructor = code;
        newRow.dataset.search = (courseCode + ' ' + code + ' ' + instName + ' ' + comment).toLowerCase();

        newRow.innerHTML =
            '<td>' +
                '<div style="font-weight: 600; color: #0f1c2e;">' + esc(dateStr) + '</div>' +
                '<div style="font-size: 11px; color: #64748b;">' + esc(currentWeekLabel()) + '</div>' +
            '</td>' +
            '<td>' + codeBadge(courseCode, 'course') + '</td>' +
            '<td>' +
                '<div class="lec-identity">' +
                    codeBadge(code, 'staff', { title: instName }) +
                    '<span class="lec-name" style="font-size: 13px;">' + esc(instName) + '</span>' +
                '</div>' +
            '</td>' +
            '<td>' + scorePill(rating) + '</td>' +
            '<td style="font-size: 12.5px; color: #334155; line-height: 1.45;">' +
                (comment ? esc(comment) : '<span class="text-muted">No observations recorded.</span>') +
            '</td>' +
            '<td style="text-align: right;">' +
                '<span class="pill pill-active" style="background: #e6f9ed; color: #166534; font-size: 11px;">Evaluated</span>' +
            '</td>';

        historyTbody.insertBefore(newRow, historyTbody.firstChild);
    }

    /** Tab 1: a course's button reads Evaluate → "Continue (1/3)" → Evaluated. */
    function refreshCourseButton(btn, evals) {
        let instructors = [];
        try { instructors = JSON.parse(btn.dataset.instructors || '[]'); } catch (e) { instructors = []; }
        const total = instructors.length;
        const done = instructors.filter(i => isDoneFor(evals, i.code, btn.dataset.courseCode)).length;
        if (total && done === total) {
            btn.className = 'btn-evaluate-course btn-evaluated';
            btn.innerHTML = 'Evaluated';
        } else {
            btn.className = 'btn-evaluate-course';
            btn.innerHTML = done ? 'Continue (' + done + '/' + total + ')' : 'Evaluate';
        }
    }

    // The course open in the evaluation view, so it can be redrawn after a
    // partial submit.
    let activeCourse = null;

    // Open Course Evaluation View (Sliding Master-Detail)
    function openCourseEvaluation(courseCode, courseName, courseYear, courseProg, instructors) {
        if (!flowWrapper) return;

        activeCourse = { code: courseCode, name: courseName, year: courseYear, prog: courseProg, instructors: instructors || [] };
        const count = instructors ? instructors.length : 0;
        if (evalCourseHeading) evalCourseHeading.textContent = courseCode + ' — ' + courseName;
        if (evalCourseYearPill) {
            evalCourseYearPill.textContent = 'Year ' + courseYear;
            evalCourseYearPill.className = 'pill pill-year-' + courseYear;
        }
        if (evalCourseProgPill) evalCourseProgPill.textContent = courseProg || 'CS';
        if (evalCourseSubText) {
            evalCourseSubText.textContent = currentWeekLabel() + ' · ' + count + ' junior staff · sent to the Coordinator and the Department In-Charge';
        }

        if (evalContainer) {
            if (count === 0) {
                evalContainer.innerHTML = '<tr><td colspan="3" class="text-muted">No junior staff are assigned to ' + esc(courseCode) + '.</td></tr>';
            } else {
                // Staff already evaluated for this course this week show their
                // score and are locked; the rest can be rated now or later.
                const evals = loadEvals();
                evalContainer.innerHTML = instructors.map(function (inst) {
                    const identity =
                        '<td>' +
                            '<div class="lec-identity">' +
                                codeBadge(inst.code, 'staff', { title: inst.name }) +
                                '<span class="lec-name">' + esc(inst.name) + '</span>' +
                            '</div>' +
                        '</td>';
                    if (isDoneFor(evals, inst.code, courseCode)) {
                        return '<tr class="inst-eval-done">' + identity +
                            '<td>' + scorePill(evals[inst.code].rating) + '</td>' +
                            '<td class="text-muted" style="font-size: 12.5px;">Submitted ' + esc(evals[inst.code].date) + '</td>' +
                        '</tr>';
                    }
                    return '<tr class="inst-eval-row" data-inst-code="' + esc(inst.code) + '" data-inst-name="' + esc(inst.name) + '">' + identity +
                        '<td><select class="form-select inst-rating-select" aria-label="Rating for ' + esc(inst.name) + '">' + ratingOptions() + '</select></td>' +
                        '<td><textarea class="form-textarea inst-comment-textarea" rows="2" aria-label="Comments on ' + esc(inst.name) + '"></textarea></td>' +
                    '</tr>';
                }).join('');
            }
        }

        flowWrapper.classList.add('show-eval');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function returnToCoursesList() {
        if (flowWrapper) {
            flowWrapper.classList.remove('show-eval');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    if (backBtn) backBtn.addEventListener('click', returnToCoursesList);

    if (coursesTbody) {
        coursesTbody.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-evaluate-course');
            if (!btn) return;
            const code = btn.dataset.courseCode || '';
            const name = btn.dataset.courseName || '';
            const year = btn.dataset.courseYear || '1';
            const prog = btn.dataset.courseProgram || 'CS';
            let instructors = [];
            try {
                instructors = JSON.parse(btn.dataset.instructors || '[]');
            } catch (err) { instructors = []; }
            openCourseEvaluation(code, name, year, prog, instructors);
        });
    }

    // Submit whoever has a rating. Unrated staff stay open, so the lecturer can
    // evaluate one now and the others later in the week.
    if (submitAllBtn) {
        submitAllBtn.addEventListener('click', function () {
            if (!activeCourse) return;
            const rows = evalContainer ? Array.from(evalContainer.querySelectorAll('.inst-eval-row')) : [];
            if (!activeCourse.instructors.length) {
                notify('There are no junior staff to evaluate on this course.', false);
                return;
            }
            const rated = rows.filter(row => row.querySelector('.inst-rating-select').value);
            if (!rated.length) {
                notify(rows.length ? 'Give at least one staff member a rating to submit.' : 'Everyone on this course is already evaluated this week.', false);
                if (rows.length) rows[0].querySelector('.inst-rating-select').focus();
                return;
            }

            const dateStr = new Date().toISOString().split('T')[0];
            const courseCode = activeCourse.code;

            rated.forEach(row => {
                const code = row.dataset.instCode;
                const instName = row.dataset.instName || code;
                const rating = parseInt(row.querySelector('.inst-rating-select').value, 10);
                const commentEl = row.querySelector('.inst-comment-textarea');
                const comment = commentEl ? commentEl.value.trim() : '';

                saveEval(code, { rating: rating, date: dateStr, course: courseCode });
                markInstructorEvaluated(code, rating);
                addHistoryRow(courseCode, code, instName, rating, comment, dateStr);
            });

            const courseBtn = coursesTbody ? coursesTbody.querySelector('.btn-evaluate-course[data-course-code="' + courseCode + '"]') : null;
            if (courseBtn) refreshCourseButton(courseBtn, loadEvals());

            const left = rows.length - rated.length;
            if (left === 0) {
                notify('Evaluations for ' + courseCode + ' submitted — everyone is done this week.');
                returnToCoursesList();
            } else {
                notify(rated.length + ' submitted. ' + left + ' still to evaluate — come back any time this week.');
                const c = activeCourse;
                openCourseEvaluation(c.code, c.name, c.year, c.prog, c.instructors);
            }
        });
    }

    // =========================================================================
    // 3. TAB 2: EVALUATE BY INSTRUCTOR & RIGHT-SIDE SLIDE DRAWER
    // =========================================================================
    const instructorSearch = document.getElementById('instructorSearch');
    const instructorCourseFilter = document.getElementById('instructorCourseFilter');
    const instructorsTable = document.getElementById('instructorsRosterTable');
    const instructorsTbody = instructorsTable ? instructorsTable.querySelector('tbody') : null;
    const instructorsEmptyMsg = document.getElementById('instructorsEmptyMsg');

    // Drawer Elements
    const drawerOverlay = document.getElementById('evaluateInstructorDrawerOverlay');
    const drawerTitle = document.getElementById('evalDrawerTitle');
    const drawerSubtitle = document.getElementById('evalDrawerSubtitle');
    const drawerCourseSelect = document.getElementById('drawerCourseSelect');
    const drawerRatingSelect = document.getElementById('drawerRatingSelect');
    const drawerComment = document.getElementById('drawerComment');
    const closeDrawerBtn = document.getElementById('closeEvalDrawerBtn');
    const cancelDrawerBtn = document.getElementById('cancelEvalDrawerBtn');
    const submitDrawerBtn = document.getElementById('submitEvalDrawerBtn');

    let activeEvaluatingInstructor = null;

    function applyInstructorFilters() {
        if (!instructorsTbody) return;
        const query = instructorSearch ? instructorSearch.value.trim().toLowerCase() : '';
        const courseSelected = instructorCourseFilter ? instructorCourseFilter.value.trim() : '';
        const rows = instructorsTbody.querySelectorAll('tr.inst-row');
        let visibleCount = 0;

        rows.forEach(function (row) {
            const searchKey = row.dataset.search || '';
            let courses = [];
            try { courses = JSON.parse(row.dataset.courses || '[]'); } catch (e) { courses = []; }

            const matchQuery = !query || searchKey.includes(query);
            const matchCourse = !courseSelected || courses.includes(courseSelected);

            const show = matchQuery && matchCourse;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        if (instructorsEmptyMsg) instructorsEmptyMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
    }

    if (instructorSearch) instructorSearch.addEventListener('input', applyInstructorFilters);
    if (instructorCourseFilter) instructorCourseFilter.addEventListener('change', applyInstructorFilters);

    if (drawerRatingSelect) drawerRatingSelect.innerHTML = ratingOptions();

    function openInstructorDrawer(data) {
        activeEvaluatingInstructor = data;

        if (drawerTitle) drawerTitle.textContent = data.name + ' (' + data.code + ')';
        if (drawerSubtitle) drawerSubtitle.textContent = currentWeekLabel();

        // Populate courses dropdown
        if (drawerCourseSelect) {
            drawerCourseSelect.innerHTML = '';
            let courses = [];
            try { courses = JSON.parse(data.courses || '[]'); } catch (e) { courses = []; }
            if (courses.length === 0) {
                drawerCourseSelect.innerHTML = '<option value="">No shared courses</option>';
            } else {
                courses.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c;
                    opt.textContent = c;
                    drawerCourseSelect.appendChild(opt);
                });
            }
        }

        // Reset inputs
        if (drawerRatingSelect) drawerRatingSelect.value = '';
        if (drawerComment) drawerComment.value = '';

        if (drawerOverlay) {
            drawerOverlay.hidden = false;
            const drawerEl = drawerOverlay.querySelector('.side-drawer');
            if (drawerEl) drawerEl.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeInstructorDrawer() {
        if (drawerOverlay) {
            const drawerEl = drawerOverlay.querySelector('.side-drawer');
            if (drawerEl) drawerEl.classList.remove('open');
            drawerOverlay.hidden = true;
            document.body.style.overflow = '';
        }
        activeEvaluatingInstructor = null;
    }

    if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', closeInstructorDrawer);
    if (cancelDrawerBtn) cancelDrawerBtn.addEventListener('click', closeInstructorDrawer);

    if (drawerOverlay) {
        drawerOverlay.addEventListener('click', function (e) {
            if (e.target === drawerOverlay) closeInstructorDrawer();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (drawerOverlay && !drawerOverlay.hidden) closeInstructorDrawer();
            else if (flowWrapper && flowWrapper.classList.contains('show-eval')) returnToCoursesList();
        }
    });

    if (instructorsTbody) {
        instructorsTbody.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-evaluate-instructor');
            if (!btn) return;
            const data = {
                code: btn.dataset.code || '',
                name: btn.dataset.name || '',
                email: btn.dataset.email || '',
                phone: btn.dataset.phone || '',
                dept: btn.dataset.dept || 'Computer Science',
                courses: btn.dataset.courses || '[]',
            };
            openInstructorDrawer(data);
        });
    }

    if (submitDrawerBtn) {
        submitDrawerBtn.addEventListener('click', function () {
            if (!activeEvaluatingInstructor) return;

            if (!drawerRatingSelect || !drawerRatingSelect.value) {
                notify('Choose a rating before submitting.', false);
                if (drawerRatingSelect) drawerRatingSelect.focus();
                return;
            }

            const instCode = activeEvaluatingInstructor.code;
            const instName = activeEvaluatingInstructor.name;
            const courseCode = drawerCourseSelect ? drawerCourseSelect.value : '';
            const rating = parseInt(drawerRatingSelect.value, 10);
            const comment = drawerComment ? drawerComment.value.trim() : '';
            const dateStr = new Date().toISOString().split('T')[0];

            saveEval(instCode, { rating: rating, date: dateStr, course: courseCode });
            markInstructorEvaluated(instCode, rating);
            addHistoryRow(courseCode, instCode, instName, rating, comment, dateStr);

            // The course's button in Tab 1 counts this evaluation too.
            const courseBtn = coursesTbody ? coursesTbody.querySelector('.btn-evaluate-course[data-course-code="' + courseCode + '"]') : null;
            if (courseBtn) refreshCourseButton(courseBtn, loadEvals());

            closeInstructorDrawer();
            notify('Performance evaluation for ' + instName + ' submitted successfully.');
        });
    }

    // =========================================================================
    // 4. TAB 3: EVALUATION HISTORY FILTERING
    // =========================================================================
    const historySearch = document.getElementById('historySearch');
    const historyCourseFilter = document.getElementById('historyCourseFilter');
    const historyInstructorFilter = document.getElementById('historyInstructorFilter');
    const historyTbody = document.getElementById('evaluationHistoryTbody');
    const historyEmptyMsg = document.getElementById('historyEmptyMsg');

    // The Monday ISO dates of the weeks in the chosen period (PeriodNav).
    let historyWeeks = null;
    // '' | 'evaluated' | 'missing'
    let historyStatus = '';
    const historyStatusFilter = document.getElementById('historyStatusFilter');

    function applyHistoryFilters() {
        if (!historyTbody) return;
        const query = historySearch ? historySearch.value.trim().toLowerCase() : '';
        const courseVal = historyCourseFilter ? historyCourseFilter.value.trim() : '';
        const instVal = historyInstructorFilter ? historyInstructorFilter.value.trim() : '';
        const rows = historyTbody.querySelectorAll('tr.history-row');
        let visibleCount = 0;

        rows.forEach(function (row) {
            const matchQuery = !query || (row.dataset.search || '').includes(query);
            const matchCourse = !courseVal || row.dataset.course === courseVal;
            const matchInst = !instVal || row.dataset.instructor === instVal;
            const matchTime = !historyWeeks || historyWeeks.has(row.dataset.weekStart || '');
            const matchStatus = !historyStatus || row.dataset.status === historyStatus;

            const show = matchQuery && matchCourse && matchInst && matchTime && matchStatus;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        if (historyEmptyMsg) historyEmptyMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
    }

    if (historySearch) historySearch.addEventListener('input', applyHistoryFilters);
    if (historyCourseFilter) historyCourseFilter.addEventListener('change', applyHistoryFilters);
    if (historyInstructorFilter) historyInstructorFilter.addEventListener('change', applyHistoryFilters);
    if (historyStatusFilter) {
        historyStatusFilter.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-status]');
            if (!btn) return;
            historyStatus = btn.dataset.status;
            historyStatusFilter.querySelectorAll('.seg-btn').forEach(function (b) { b.classList.toggle('active', b === btn); });
            applyHistoryFilters();
        });
    }

    // Week / Month / Semester with previous-next arrows; this week by default.
    const historyNavEl = document.getElementById('historyPeriodNav');
    if (historyNavEl && HISTORY_CAL && window.PeriodNav) {
        PeriodNav.create(historyNavEl, {
            calendar: HISTORY_CAL,
            units: ['week', 'month', 'semester'],
            onChange: function (p) {
                historyWeeks = new Set(p.weeks.map(function (w) { return w.start; }));
                applyHistoryFilters();
            }
        });
    }

    // Restore this week's evaluated state from sessionStorage
    const savedEvals = loadEvals();
    Object.keys(savedEvals).forEach(code => markInstructorEvaluated(code, savedEvals[code].rating));
    if (coursesTbody) {
        coursesTbody.querySelectorAll('.btn-evaluate-course').forEach(btn => refreshCourseButton(btn, savedEvals));
    }

});
