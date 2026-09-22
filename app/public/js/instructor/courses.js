// courses.js — Interactive filtering, session filtering, and master-detail sliding evaluation flow
document.addEventListener('DOMContentLoaded', function () {
    const flowWrapper = document.getElementById('coursesFlowWrapper');
    if (!flowWrapper) return;

    const isLecturer = flowWrapper.dataset.isLecturer === '1';

    // ---- Elements: Courses Catalog Pane ----
    const searchInput = document.getElementById('courseSearch');
    const programFilter = document.getElementById('programFilter');
    const yearFilter = document.getElementById('yearFilter');
    const sessionTypeFilter = document.getElementById('sessionTypeFilter');
    const coursesTable = document.getElementById('assignedCoursesTable');
    const tbody = coursesTable ? coursesTable.querySelector('tbody') : null;
    const countDisplay = document.getElementById('courseCountDisplay');
    const emptyMsg = document.getElementById('coursesEmptyMsg');

    // ---- Elements: Evaluation Detail Pane (Lecturers / In-Charge) ----
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
    let activeCourseCode = null;
    let activeCourseButton = null;

    // Helper: Escaping HTML
    function esc(s) {
        if (s == null) return '';
        const d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }

    // ---- Filtering Courses Table ----
    function applyFilters() {
        if (!tbody) return;
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const rows = tbody.querySelectorAll('tr.course-row');
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

        if (countDisplay) countDisplay.textContent = visibleCount;
        if (emptyMsg) emptyMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    function setupSegment(groupEl, callback) {
        if (!groupEl) return;
        groupEl.addEventListener('click', function (e) {
            const btn = e.target.closest('.seg-btn');
            if (!btn) return;
            groupEl.querySelectorAll('.seg-btn').forEach(function (b) {
                b.classList.remove('active');
            });
            btn.classList.add('active');
            callback(btn.dataset.value || '');
            applyFilters();
        });
    }

    setupSegment(programFilter, function (val) {
        currentProgram = val;
    });

    setupSegment(yearFilter, function (val) {
        currentYear = val;
    });

    if (sessionTypeFilter) {
        sessionTypeFilter.addEventListener('change', function () {
            currentSession = sessionTypeFilter.value.trim();
            applyFilters();
        });
    }

    // ---- Master-Detail Slide Transition (Lecturer / In-Charge) ----
    if (isLecturer && evalPane) {
        const ratingLabels = {
            1: '1 / 5 — Unsatisfactory',
            2: '2 / 5 — Needs Improvement',
            3: '3 / 5 — Satisfactory',
            4: '4 / 5 — Very Good',
            5: '5 / 5 — Excellent'
        };

        // Open Evaluation View
        function openCourseEvaluation(courseCode, courseName, courseYear, courseProg, instructors, triggerBtn) {
            activeCourseCode = courseCode;
            activeCourseButton = triggerBtn;

            // Update Header Meta
            if (evalCourseAvatar) evalCourseAvatar.textContent = courseProg || 'CS';
            if (evalCourseHeading) evalCourseHeading.textContent = courseCode + ' — ' + courseName;
            
            if (evalCourseYearPill) {
                evalCourseYearPill.textContent = 'Year ' + courseYear;
                evalCourseYearPill.className = 'pill pill-year-' + courseYear;
            }
            if (evalCourseProgPill) {
                evalCourseProgPill.textContent = courseProg || 'CS';
            }
            if (evalAssignedCount) {
                evalAssignedCount.textContent = instructors ? instructors.length : 0;
            }

            // Hide previous submission banner
            if (submissionBanner) {
                submissionBanner.style.display = 'none';
                submissionBanner.innerHTML = '';
            }

            // Populate Instructors Cards
            if (evalContainer) {
                evalContainer.innerHTML = '';

                if (!instructors || instructors.length === 0) {
                    evalContainer.innerHTML = 
                        '<div class="eval-empty-card">' +
                            '<i class="fa-solid fa-user-slash"></i>' +
                            '<h4>No Junior Staff Assigned</h4>' +
                            '<p>There are currently no demonstrators or instructors linked to ' + esc(courseCode) + '.</p>' +
                        '</div>';
                } else {
                    instructors.forEach(function (inst) {
                        const card = document.createElement('div');
                        card.className = 'instructor-eval-card';
                        card.dataset.instCode = inst.code;

                        const initials = inst.name ? inst.name.split(' ').map(function (n) { return n[0]; }).join('').substring(0, 2).toUpperCase() : inst.code;
                        const role = inst.role || 'Course Supportive Staff';
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
                                            '<span>Department of Computer Science</span>' +
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
                                        '<i class="fa-solid fa-star-half-stroke"></i> Performance Rating' +
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
                                        '<i class="fa-regular fa-comment-dots"></i> Observations & Qualitative Feedback (Optional)' +
                                    '</label>' +
                                    '<textarea class="inst-comment-textarea" rows="3" placeholder="Enter comments on student guidance, lab supervision, assignment evaluation, or punctuality..."></textarea>' +
                                '</div>' +
                            '</div>';

                        evalContainer.appendChild(card);

                        // Attach star interactions
                        wireCardStarPicker(card);
                    });
                }
            }

            // Transition: Slide to Evaluation View
            flowWrapper.classList.add('show-eval');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Return Back to Courses Table
        function returnToCoursesList() {
            flowWrapper.classList.remove('show-eval');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        if (backBtn) {
            backBtn.addEventListener('click', returnToCoursesList);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && flowWrapper.classList.contains('show-eval')) {
                returnToCoursesList();
            }
        });

        // Wire Star Picker in an Instructor Card
        function wireCardStarPicker(cardEl) {
            const picker = cardEl.querySelector('.modern-star-picker');
            if (!picker) return;

            const instCode = cardEl.dataset.instCode;
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

                btn.addEventListener('mouseenter', function () {
                    updateDisplay(val, true);
                    if (hint) hint.textContent = ratingLabels[val] || (val + ' / 5');
                });

                btn.addEventListener('mouseleave', function () {
                    stars.forEach(function (b) { b.classList.remove('hover-active'); });
                    if (hint) hint.textContent = ratingLabels[currentRating] || (currentRating + ' / 5');
                });

                btn.addEventListener('click', function () {
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

        // Table "Evaluate" Button Click
        if (tbody) {
            tbody.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-evaluate-course');
                if (!btn) return;

                const code = btn.dataset.courseCode || '';
                const name = btn.dataset.courseName || '';
                const year = btn.dataset.courseYear || '1';
                const prog = btn.dataset.courseProgram || 'CS';

                let instructors = [];
                try {
                    instructors = JSON.parse(btn.dataset.instructors || '[]');
                } catch (err) {
                    instructors = [];
                }

                openCourseEvaluation(code, name, year, prog, instructors, btn);
            });
        }

        // Top Single "Submit Evaluations" Button
        if (submitAllBtn) {
            submitAllBtn.addEventListener('click', function () {
                const cards = evalContainer ? evalContainer.querySelectorAll('.instructor-eval-card') : [];
                if (cards.length === 0) {
                    alert('There are no junior staff members to evaluate for this course.');
                    return;
                }

                // Gather ratings & comments
                const payload = [];
                cards.forEach(function (card) {
                    const instCode = card.dataset.instCode;
                    const picker = card.querySelector('.modern-star-picker');
                    const rating = picker ? parseInt(picker.dataset.rating, 10) : 4;
                    const commentArea = card.querySelector('.inst-comment-textarea');
                    const comment = commentArea ? commentArea.value.trim() : '';

                    payload.push({
                        instructor_code: instCode,
                        rating: rating,
                        comment: comment
                    });
                });

                // Display success banner
                if (submissionBanner) {
                    submissionBanner.innerHTML = 
                        '<i class="fa-solid fa-circle-check"></i>' +
                        '<div>' +
                            '<strong>Evaluations Submitted Successfully!</strong>' +
                            '<p>Performance appraisal records for <b>' + esc(activeCourseCode) + '</b> have been saved. Notifications have been dispatched to the <b>Course Coordinator</b> and <b>Department In-Charge</b>.</p>' +
                        '</div>';
                    submissionBanner.style.display = 'flex';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }

                // Update Course Row Action button state
                if (activeCourseButton) {
                    activeCourseButton.classList.add('evaluated');
                    activeCourseButton.innerHTML = '<i class="fa-solid fa-check"></i> Evaluated';
                }

                // Disable submit button briefly
                submitAllBtn.disabled = true;

                // Auto-return after 2 seconds
                setTimeout(function () {
                    submitAllBtn.disabled = false;
                    returnToCoursesList();
                }, 2000);
            });
        }
    }
});
