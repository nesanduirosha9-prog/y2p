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

    // Helper: Toast notification
    function notify(msg, isSuccess = true) {
        if (typeof window.ttToast === 'function') {
            window.ttToast(msg, { icon: isSuccess ? 'fa-circle-check' : 'fa-circle-exmark' });
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

    const evalPane = document.getElementById('coursesEvalPane');
    const backBtn = document.getElementById('backToCoursesBtn');
    const evalCourseAvatar = document.getElementById('evalCourseAvatar');
    const evalCourseHeading = document.getElementById('evalCourseHeading');
    const evalCourseYearPill = document.getElementById('evalCourseYearPill');
    const evalCourseProgPill = document.getElementById('evalCourseProgPill');
    const evalAssignedCount = document.getElementById('evalAssignedCount');
    const evalContainer = document.getElementById('evalInstructorsContainer');
    const submitAllBtn = document.getElementById('submitAllCourseEvaluationsBtn');
    const submissionBanner = document.getElementById('evalSubmissionBanner');

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
        1: '1 / 5 — Unsatisfactory',
        2: '2 / 5 — Needs Improvement',
        3: '3 / 5 — Satisfactory',
        4: '4 / 5 — Very Good',
        5: '5 / 5 — Excellent'
    };

    // Open Course Evaluation View (Sliding Master-Detail)
    function openCourseEvaluation(courseCode, courseName, courseYear, courseProg, instructors) {
        if (!flowWrapper) return;

        if (evalCourseAvatar) evalCourseAvatar.textContent = courseProg || 'CS';
        if (evalCourseHeading) evalCourseHeading.textContent = courseCode + ' — ' + courseName;
        if (evalCourseYearPill) {
            evalCourseYearPill.textContent = 'Year ' + courseYear;
            evalCourseYearPill.className = 'pill pill-year-' + courseYear;
        }
        if (evalCourseProgPill) evalCourseProgPill.textContent = courseProg || 'CS';
        if (evalAssignedCount) evalAssignedCount.textContent = instructors ? instructors.length : 0;
        if (submissionBanner) {
            submissionBanner.style.display = 'none';
            submissionBanner.innerHTML = '';
        }

        if (evalContainer) {
            evalContainer.innerHTML = '';
            if (!instructors || instructors.length === 0) {
                evalContainer.innerHTML = 
                    '<div class="eval-empty-card">' +
                        '<i class="fa-solid fa-user-slash"></i>' +
                        '<h4>No Supportive Staff Assigned</h4>' +
                        '<p>There are currently no instructors linked to ' + esc(courseCode) + '.</p>' +
                    '</div>';
            } else {
                instructors.forEach(function (inst) {
                    const card = document.createElement('div');
                    card.className = 'instructor-eval-card';
                    card.dataset.instCode = inst.code;

                    const initials = inst.name ? inst.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : inst.code;
                    const role = inst.role || 'Supportive Staff';
                    const email = inst.email || (inst.code.toLowerCase() + '@ucsc.cmb.ac.lk');

                    card.innerHTML = 
                        '<div class="inst-card-header">' +
                            '<div class="inst-profile">' +
                                '<div class="inst-avatar">' + esc(initials) + '</div>' +
                                '<div class="inst-details">' +
                                    '<div class="inst-name-row">' +
                                        '<h3 class="inst-name">' + esc(inst.name) + '</h3>' +
                                        '<span class="tag tag-instructor">' + esc(inst.code) + '</span>' +
                                        '<span class="tag-role"><i class="fa-solid fa-chalkboard-user"></i> ' + esc(role) + '</span>' +
                                    '</div>' +
                                    '<div class="inst-meta">' +
                                        '<span><i class="fa-regular fa-envelope"></i> ' + esc(email) + '</span>' +
                                        '<span class="meta-dot">·</span>' +
                                        '<span>Computer Science</span>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="inst-score-summary">' +
                                '<span class="rating-badge rating-badge-active" id="badge_' + esc(inst.code) + '">' +
                                    '<i class="fa-solid fa-star"></i> 4.0 / 5.0' +
                                '</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="inst-card-body">' +
                            '<div class="inst-rating-col">' +
                                '<label class="inst-field-label">' +
                                    '<i class="fa-solid fa-star-half-stroke"></i> Performance Rating (Current Week)' +
                                '</label>' +
                                '<div class="modern-star-picker" data-rating="4">' +
                                    '<div class="star-picker-btns">' +
                                        '<button type="button" class="star-btn active" data-val="1" title="1 - Unsatisfactory"><i class="fa-solid fa-star"></i></button>' +
                                        '<button type="button" class="star-btn active" data-val="2" title="2 - Needs Improvement"><i class="fa-solid fa-star"></i></button>' +
                                        '<button type="button" class="star-btn active" data-val="3" title="3 - Satisfactory"><i class="fa-solid fa-star"></i></button>' +
                                        '<button type="button" class="star-btn active" data-val="4" title="4 - Very Good"><i class="fa-solid fa-star"></i></button>' +
                                        '<button type="button" class="star-btn" data-val="5" title="5 - Excellent"><i class="fa-solid fa-star"></i></button>' +
                                    '</div>' +
                                    '<span class="star-rating-hint">4 / 5 — Very Good</span>' +
                                '</div>' +
                            '</div>' +
                            '<div class="inst-feedback-col">' +
                                '<label class="inst-field-label">' +
                                    '<i class="fa-regular fa-comment-dots"></i> Observations & Qualitative Feedback' +
                                '</label>' +
                                '<textarea class="inst-comment-textarea" rows="3" placeholder="Enter comments on student guidance, lab supervision, assignment evaluation, or punctuality..."></textarea>' +
                            '</div>' +
                        '</div>';

                    evalContainer.appendChild(card);
                    wireCardStarPicker(card);
                });
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

    function wireCardStarPicker(cardEl) {
        const picker = cardEl.querySelector('.modern-star-picker');
        if (!picker) return;

        const stars = picker.querySelectorAll('.star-btn');
        const hint = picker.querySelector('.star-rating-hint');
        const summaryBadge = cardEl.querySelector('.rating-badge');
        let currentRating = parseInt(picker.dataset.rating, 10) || 4;

        function updateDisplay(val, isHover) {
            stars.forEach(function (btn) {
                const btnVal = parseInt(btn.dataset.val, 10);
                if (isHover) {
                    btn.classList.toggle('hover-active', btnVal <= val);
                } else {
                    btn.classList.toggle('active', btnVal <= val);
                    btn.classList.remove('hover-active');
                }
            });
        }

        stars.forEach(function (btn) {
            const val = parseInt(btn.dataset.val, 10);
            btn.addEventListener('mouseenter', () => {
                updateDisplay(val, true);
                if (hint) hint.textContent = ratingLabels[val] || (val + ' / 5');
            });
            btn.addEventListener('mouseleave', () => {
                stars.forEach(b => b.classList.remove('hover-active'));
                if (hint) hint.textContent = ratingLabels[currentRating] || (currentRating + ' / 5');
            });
            btn.addEventListener('click', () => {
                currentRating = val;
                picker.dataset.rating = currentRating;
                updateDisplay(currentRating, false);
                if (hint) hint.textContent = ratingLabels[currentRating] || (currentRating + ' / 5');
                if (summaryBadge) {
                    summaryBadge.className = 'rating-badge rating-badge-active';
                    summaryBadge.innerHTML = '<i class="fa-solid fa-star"></i> ' + currentRating.toFixed(1) + ' / 5.0';
                }
            });
        });
    }

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

    if (submitAllBtn) {
        submitAllBtn.addEventListener('click', function () {
            const cards = evalContainer ? evalContainer.querySelectorAll('.instructor-eval-card') : [];
            if (cards.length === 0) {
                alert('There are no supportive staff members to evaluate.');
                return;
            }

            cards.forEach(card => {
                const code = card.dataset.instCode;
                const statusPill = document.getElementById('instStatus_' + code);
                if (statusPill) {
                    statusPill.className = 'pill pill-active status-pill';
                    statusPill.innerHTML = '<i class="fa-solid fa-check"></i> Evaluated';
                }
            });

            notify('Evaluations for ' + (evalCourseHeading ? evalCourseHeading.textContent : 'course') + ' submitted successfully.');
            returnToCoursesList();
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
    const drawerInstAvatar = document.getElementById('drawerInstAvatar');
    const drawerInstName = document.getElementById('drawerInstName');
    const drawerInstCode = document.getElementById('drawerInstCode');
    const drawerInstDept = document.getElementById('drawerInstDept');
    const drawerInstEmail = document.getElementById('drawerInstEmail');
    const drawerCourseSelect = document.getElementById('drawerCourseSelect');
    const drawerStarPicker = document.getElementById('drawerStarPicker');
    const drawerRatingHint = document.getElementById('drawerRatingHint');
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

    // Star Picker in Slide Drawer
    if (drawerStarPicker) {
        const dStars = drawerStarPicker.querySelectorAll('.star-btn');
        let dCurrentRating = 4;

        function updateDrawerStars(val, isHover) {
            dStars.forEach(function (btn) {
                const btnVal = parseInt(btn.dataset.val, 10);
                if (isHover) {
                    btn.classList.toggle('hover-active', btnVal <= val);
                } else {
                    btn.classList.toggle('active', btnVal <= val);
                    btn.classList.remove('hover-active');
                }
            });
        }

        dStars.forEach(function (btn) {
            const val = parseInt(btn.dataset.val, 10);
            btn.addEventListener('mouseenter', () => {
                updateDrawerStars(val, true);
                if (drawerRatingHint) drawerRatingHint.textContent = ratingLabels[val] || (val + ' / 5');
            });
            btn.addEventListener('mouseleave', () => {
                dStars.forEach(b => b.classList.remove('hover-active'));
                if (drawerRatingHint) drawerRatingHint.textContent = ratingLabels[dCurrentRating] || (dCurrentRating + ' / 5');
            });
            btn.addEventListener('click', () => {
                dCurrentRating = val;
                drawerStarPicker.dataset.rating = dCurrentRating;
                updateDrawerStars(dCurrentRating, false);
                if (drawerRatingHint) drawerRatingHint.textContent = ratingLabels[dCurrentRating] || (dCurrentRating + ' / 5');
            });
        });
    }

    function openInstructorDrawer(data) {
        activeEvaluatingInstructor = data;

        if (drawerInstAvatar) {
            const initials = data.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            drawerInstAvatar.textContent = initials;
        }
        if (drawerInstName) drawerInstName.textContent = data.name;
        if (drawerInstCode) drawerInstCode.textContent = data.code;
        if (drawerInstDept) drawerInstDept.textContent = data.dept || 'Computer Science';
        if (drawerInstEmail) drawerInstEmail.textContent = data.email || (data.code.toLowerCase() + '@ucsc.cmb.ac.lk');

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
        if (drawerStarPicker) {
            drawerStarPicker.dataset.rating = '4';
            const stars = drawerStarPicker.querySelectorAll('.star-btn');
            stars.forEach(b => {
                const v = parseInt(b.dataset.val, 10);
                b.classList.toggle('active', v <= 4);
                b.classList.remove('hover-active');
            });
            if (drawerRatingHint) drawerRatingHint.textContent = ratingLabels[4];
        }
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

            const instCode = activeEvaluatingInstructor.code;
            const instName = activeEvaluatingInstructor.name;
            const courseCode = drawerCourseSelect ? drawerCourseSelect.value : '';
            const rating = drawerStarPicker ? (parseInt(drawerStarPicker.dataset.rating, 10) || 4) : 4;
            const comment = drawerComment ? drawerComment.value.trim() : '';

            // Update status pill on instructor row
            const statusPill = document.getElementById('instStatus_' + instCode);
            if (statusPill) {
                statusPill.className = 'pill pill-active status-pill';
                statusPill.innerHTML = '<i class="fa-solid fa-check"></i> Evaluated (' + rating.toFixed(1) + ')';
            }

            // Append row to History table
            const historyTbody = document.getElementById('evaluationHistoryTbody');
            const historyBadge = document.getElementById('historyCountBadge');
            if (historyTbody) {
                const now = new Date();
                const dateStr = now.toISOString().split('T')[0];
                const newRow = document.createElement('tr');
                newRow.className = 'history-row';
                newRow.dataset.id = 'eval-' + Date.now();
                newRow.dataset.week = 'Week 5';
                newRow.dataset.month = 'March 2026';
                newRow.dataset.sem = 'Semester 1 - 2026';
                newRow.dataset.course = courseCode;
                newRow.dataset.instructor = instCode;
                newRow.dataset.search = (courseCode + ' ' + instCode + ' ' + instName + ' ' + comment + ' week 5 march 2026').toLowerCase();

                const initials = instName.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

                newRow.innerHTML = 
                    '<td>' +
                        '<div style="font-weight: 600; color: #0f1c2e;">' + esc(dateStr) + '</div>' +
                        '<div style="font-size: 11px; color: #64748b;">Week 5 &middot; Current Week</div>' +
                    '</td>' +
                    '<td>' +
                        '<strong>' + esc(courseCode) + '</strong>' +
                    '</td>' +
                    '<td>' +
                        '<div class="lec-identity">' +
                            '<span class="lec-avatar" style="width: 32px; height: 32px; font-size: 11px;">' + esc(initials) + '</span>' +
                            '<span>' +
                                '<span class="lec-name" style="font-size: 13px;">' + esc(instName) + '</span>' +
                                '<span class="pill pill-muted" style="font-size: 10px; padding: 1px 5px;">' + esc(instCode) + '</span>' +
                            '</span>' +
                        '</div>' +
                    '</td>' +
                    '<td>' +
                        '<span class="rating-badge rating-badge-active">' +
                            '<i class="fa-solid fa-star"></i> ' + rating.toFixed(1) + ' / 5.0' +
                        '</span>' +
                    '</td>' +
                    '<td style="font-size: 12.5px; color: #334155; line-height: 1.45;">' +
                        (comment ? esc(comment) : '<span class="text-muted">No observations recorded.</span>') +
                    '</td>' +
                    '<td style="text-align: right;">' +
                        '<span class="pill pill-active" style="background: #e6f9ed; color: #166534; font-size: 11px;">' +
                            '<i class="fa-solid fa-check"></i> Submitted' +
                        '</span>' +
                    '</td>';

                historyTbody.insertBefore(newRow, historyTbody.firstChild);
                if (historyBadge) {
                    historyBadge.textContent = parseInt(historyBadge.textContent || '0', 10) + 1;
                }
            }

            closeInstructorDrawer();
            notify('Performance evaluation for ' + instName + ' submitted successfully.');
        });
    }

    // =========================================================================
    // 4. TAB 3: EVALUATION HISTORY FILTERING
    // =========================================================================
    const historySearch = document.getElementById('historySearch');
    const historyTimeFilter = document.getElementById('historyTimeFilter');
    const historyCourseFilter = document.getElementById('historyCourseFilter');
    const historyInstructorFilter = document.getElementById('historyInstructorFilter');
    const historyTable = document.getElementById('evaluationHistoryTable');
    const historyTbody = document.getElementById('evaluationHistoryTbody');
    const historyEmptyMsg = document.getElementById('historyEmptyMsg');

    let currentHistoryTime = '';

    function applyHistoryFilters() {
        if (!historyTbody) return;
        const query = historySearch ? historySearch.value.trim().toLowerCase() : '';
        const courseVal = historyCourseFilter ? historyCourseFilter.value.trim() : '';
        const instVal = historyInstructorFilter ? historyInstructorFilter.value.trim() : '';
        const rows = historyTbody.querySelectorAll('tr.history-row');
        let visibleCount = 0;

        rows.forEach(function (row) {
            const searchKey = row.dataset.search || '';
            const rowCourse = row.dataset.course || '';
            const rowInstructor = row.dataset.instructor || '';
            const rowWeek = row.dataset.week || '';
            const rowMonth = row.dataset.month || '';
            const rowSem = row.dataset.sem || '';

            const matchQuery = !query || searchKey.includes(query);
            const matchCourse = !courseVal || rowCourse === courseVal;
            const matchInst = !instVal || rowInstructor === instVal;

            let matchTime = true;
            if (currentHistoryTime === 'week') {
                matchTime = rowWeek.toLowerCase().includes('week 5'); // Current active week
            } else if (currentHistoryTime === 'month') {
                matchTime = rowMonth.toLowerCase().includes('march');
            } else if (currentHistoryTime === 'sem') {
                matchTime = rowSem.toLowerCase().includes('semester 1');
            }

            const show = matchQuery && matchCourse && matchInst && matchTime;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        if (historyEmptyMsg) historyEmptyMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
    }

    if (historySearch) historySearch.addEventListener('input', applyHistoryFilters);
    if (historyCourseFilter) historyCourseFilter.addEventListener('change', applyHistoryFilters);
    if (historyInstructorFilter) historyInstructorFilter.addEventListener('change', applyHistoryFilters);

    if (historyTimeFilter) {
        historyTimeFilter.addEventListener('click', function (e) {
            const btn = e.target.closest('.seg-btn');
            if (!btn) return;
            historyTimeFilter.querySelectorAll('.seg-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentHistoryTime = btn.dataset.value || '';
            applyHistoryFilters();
        });
    }

});
