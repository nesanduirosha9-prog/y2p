<?php

// instructor/my_courses.php — Courses & Evaluations Management Hub for Lecturers.
// Divided into 3 tabs:
// 1. Course Details & Evaluations — Master course list with session filters and master-detail sliding evaluation flow.
// 2. Evaluate by Instructor — Roster of junior instructors assigned to the lecturer's own courses, with right-side slide drawer.
// 3. Evaluation History — Searchable audit trail, navigated by week, month or
//    semester (js/period_nav.js, the same calendar as the Coordinator's
//    Evaluations page), and filterable by course and instructor.

use app\core\ViewHelpers;

$totalCourses = count($assignedCourses ?? []);
$totalInstructors = count($assignedInstructors ?? []);
// The history tab's badge counts the weeks the lecturer missed, not every row:
// the history holds a row per instructor per week, so a total is just noise.
$missedHistory = count(array_filter($evaluationHistory ?? [], fn($h) => ($h['status'] ?? '') === 'Not evaluated'));
?>

<div class="courses-hub-container">

    <!-- 3-Tab Bar for Lecturers -->
    <div class="courses-tabs" id="coursesTabs" role="tablist">
        <button type="button" class="course-tab active" data-tab="courses" role="tab" aria-selected="true" id="tab-courses">
            <i class="fa-solid fa-book-open"></i>
            <span>Course Details</span>
            <span class="course-tab-badge" id="coursesCountBadge"><?= $totalCourses ?></span>
        </button>
        <button type="button" class="course-tab" data-tab="instructors" role="tab" aria-selected="false" id="tab-instructors">
            <i class="fa-solid fa-users"></i>
            <span>Evaluate by Instructor</span>
            <span class="course-tab-badge" id="instructorsCountBadge"><?= $totalInstructors ?></span>
        </button>
        <button type="button" class="course-tab" data-tab="history" role="tab" aria-selected="false" id="tab-history">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Evaluation History</span>
            <?php if ($missedHistory > 0): ?>
                <span class="course-tab-badge course-tab-badge-warn" id="historyCountBadge" title="Evaluations you missed"><?= $missedHistory ?> missed</span>
            <?php endif; ?>
        </button>
    </div>

    <!-- TAB PANEL 1: COURSE DETAILS & SLIDE EVALUATION (Current Week) -->
    <div class="courses-panel active" id="courses-panel-courses" role="tabpanel" aria-labelledby="tab-courses">
        <div class="courses-flow-wrapper" id="coursesFlowWrapper" data-is-lecturer="<?= !empty($isLecturer) ? '1' : '0' ?>">

            <!-- PANE 1: COURSES CATALOG / LIST (Master View) -->
            <div class="courses-flow-pane active" id="coursesListPane">
                <!-- Filter & Search Controls -->
                <div class="dir-controls">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="courseSearch" placeholder="Search courses, staff..." autocomplete="off">
                    </div>

                    <div class="seg" id="programFilter" role="group" aria-label="Filter by program">
                        <button type="button" class="seg-btn active" data-value="">All Programs</button>
                        <button type="button" class="seg-btn" data-value="CS">CS</button>
                        <button type="button" class="seg-btn" data-value="IS">IS</button>
                    </div>

                    <div class="seg seg-dark" id="yearFilter" role="group" aria-label="Filter by year">
                        <button type="button" class="seg-btn active" data-value="">All Years</button>
                        <button type="button" class="seg-btn" data-value="1">Year 1</button>
                        <button type="button" class="seg-btn" data-value="2">Year 2</button>
                        <button type="button" class="seg-btn" data-value="3">Year 3</button>
                        <button type="button" class="seg-btn" data-value="4">Year 4</button>
                    </div>

                    <!-- Session Type Dropdown Filter for Lecturers -->
                    <div class="session-dropdown-wrapper">
                        <label for="sessionTypeFilter" class="session-filter-label">
                            <i class="fa-solid fa-layer-group"></i> Session:
                        </label>
                        <div class="session-select-box">
                            <select id="sessionTypeFilter" class="session-dropdown">
                                <option value="">All Session Types</option>
                                <option value="Lectures">Lectures</option>
                                <option value="Tutorials">Tutorials</option>
                                <option value="Lab Sessions">Lab Sessions</option>
                                <option value="Practicals">Practicals</option>
                                <option value="Assignments">Assignments</option>
                            </select>
                            <i class="fa-solid fa-chevron-down select-chevron"></i>
                        </div>
                    </div>
                </div>

                <!-- Main Course Table -->
                <div class="dir-card">
                    <div class="dir-scroll">
                        <table class="dir-table" id="assignedCoursesTable">
                            <thead>
                                <tr>
                                    <th style="width: 130px;">Course Code</th>
                                    <th style="min-width: 220px;">Course Name</th>
                                    <th style="width: 80px;">Credits</th>
                                    <th style="width: 100px;">Year</th>
                                    <th style="width: 100px;">Program</th>
                                    <th style="min-width: 160px;">Lecturers</th>
                                    <th style="min-width: 180px;">Instructors</th>
                                    <th style="width: 130px; text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($assignedCourses ?? []) as $c): ?>
                                    <tr class="course-row"
                                        data-code="<?= htmlspecialchars($c['code']) ?>"
                                        data-name="<?= htmlspecialchars($c['name']) ?>"
                                        data-credits="<?= (int)$c['credits'] ?>"
                                        data-year="<?= (int)$c['year'] ?>"
                                        data-program="<?= htmlspecialchars($c['program']) ?>"
                                        data-sessions="<?= htmlspecialchars(implode(',', $c['sessions'] ?? [])) ?>"
                                        data-lecturers="<?= htmlspecialchars(implode(',', $c['lecturers'])) ?>"
                                        data-instructors="<?= htmlspecialchars(implode(',', $c['instructors'])) ?>"
                                        data-search="<?= htmlspecialchars(strtolower($c['code'] . ' ' . $c['name'] . ' ' . implode(' ', $c['lecturers']) . ' ' . implode(' ', $c['instructors']) . ' ' . implode(' ', $c['sessions'] ?? []))) ?>">
                                        
                                        <td><?= ViewHelpers::codeBadge($c['code'], 'course', $c['name']) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($c['name']) ?></strong>
                                            <?php if (!empty($c['sessions'])): ?>
                                                <div class="course-sessions-hint">
                                                    <i class="fa-regular fa-clock"></i> <?= htmlspecialchars(implode(' · ', $c['sessions'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= (int)$c['credits'] ?></td>
                                        <td><span class="pill pill-year-<?= (int)$c['year'] ?>">Year <?= (int)$c['year'] ?></span></td>
                                        <td><span class="pill pill-muted"><?= htmlspecialchars($c['program']) ?></span></td>
                                        <td>
                                            <div class="tag-row">
                                                <?php foreach ($c['lecturers'] as $lCode): ?>
                                                    <span class="code-badge code-badge--lecturer" title="<?= htmlspecialchars($c['lecturer_names'][$lCode] ?? $lCode) ?>">
                                                        <?= htmlspecialchars($lCode) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="tag-row">
                                                <?php 
                                                $instNameMap = [];
                                                foreach (($c['instructor_details'] ?? []) as $iDet) {
                                                    $instNameMap[$iDet['code']] = $iDet['name'];
                                                }
                                                ?>
                                                <?php foreach ($c['instructors'] as $iCode): ?>
                                                    <span class="code-badge code-badge--staff" title="<?= htmlspecialchars($instNameMap[$iCode] ?? $iCode) ?>"><?= htmlspecialchars($iCode) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td style="text-align: right;">
                                            <button type="button" class="btn-evaluate-course" 
                                                    data-course-code="<?= htmlspecialchars($c['code']) ?>"
                                                    data-course-name="<?= htmlspecialchars($c['name']) ?>"
                                                    data-course-year="<?= (int)$c['year'] ?>"
                                                    data-course-program="<?= htmlspecialchars($c['program']) ?>"
                                                    data-instructors='<?= htmlspecialchars(json_encode($c['instructor_details'] ?? []), ENT_QUOTES) ?>'>
                                                <i class="fa-solid fa-star-half-stroke"></i> Evaluate
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="dir-empty" id="coursesEmptyMsg" style="display: none;">No courses match your filter criteria.</p>
                    </div>
                </div>
            </div>

            <!-- PANE 2: COURSE INSTRUCTORS EVALUATION COMPONENT -->
            <?php require \app\core\Application::$ROOT_DIR . '/views/components/evaluation_panel.php'; ?>

        </div>
    </div>

    <!-- TAB PANEL 2: EVALUATE BY INSTRUCTOR (Current Week) -->
    <div class="courses-panel" id="courses-panel-instructors" role="tabpanel" aria-labelledby="tab-instructors" hidden>
        <div class="dir-controls">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="instructorSearch" placeholder="Search instructor name, code, email, courses..." autocomplete="off">
            </div>

            <div class="session-dropdown-wrapper">
                <label for="instructorCourseFilter" class="session-filter-label">
                    <i class="fa-solid fa-book-bookmark"></i> Course:
                </label>
                <div class="session-select-box">
                    <select id="instructorCourseFilter" class="session-dropdown">
                        <option value="">All Assigned Courses</option>
                        <?php foreach (($assignedCourses ?? []) as $c): ?>
                            <option value="<?= htmlspecialchars($c['code']) ?>"><?= htmlspecialchars($c['code']) ?> — <?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down select-chevron"></i>
                </div>
            </div>
        </div>

        <div class="dir-card">
            <div class="dir-scroll">
                <table class="dir-table" id="instructorsRosterTable">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Staff Code</th>
                            <th style="min-width: 220px;">Instructor Name</th>
                            <th style="min-width: 200px;">Email</th>
                            <th style="width: 150px;">Phone Number</th>
                            <th style="min-width: 180px;">Assigned Courses</th>
                            <th style="width: 140px;">Status</th>
                            <th style="width: 130px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($assignedInstructors ?? []) as $inst): ?>
                            <?php
                            $searchKey = strtolower($inst['code'] . ' ' . $inst['name'] . ' ' . $inst['email'] . ' ' . ($inst['phone'] ?? '') . ' ' . implode(' ', $inst['courses']));
                            ?>
                            <tr class="inst-row"
                                data-code="<?= htmlspecialchars($inst['code']) ?>"
                                data-name="<?= htmlspecialchars($inst['name']) ?>"
                                data-email="<?= htmlspecialchars($inst['email']) ?>"
                                data-phone="<?= htmlspecialchars($inst['phone']) ?>"
                                data-dept="<?= htmlspecialchars($inst['department'] ?? 'Computer Science') ?>"
                                data-courses='<?= htmlspecialchars(json_encode($inst['courses'] ?? []), ENT_QUOTES) ?>'
                                data-search="<?= htmlspecialchars($searchKey) ?>">
                                <td><?= ViewHelpers::codeBadge($inst['code'], 'staff') ?></td>
                                <td>
                                    <div class="lec-identity">
                                        <span class="lec-avatar"><?= htmlspecialchars(ViewHelpers::staffInitials($inst['name'])) ?></span>
                                        <span>
                                            <span class="lec-name"><?= htmlspecialchars($inst['name']) ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="lec-email"><?= htmlspecialchars($inst['email']) ?></td>
                                <td><?= htmlspecialchars($inst['phone'] ?: '—') ?></td>
                                <td>
                                    <div class="tag-row">
                                        <?php foreach ($inst['courses'] as $cCode): ?>
                                            <span class="code-badge code-badge--course"><?= htmlspecialchars($cCode) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php $isEval = !empty($inst['evaluated_this_week']) || ($inst['status'] ?? '') === 'Evaluated'; ?>
                                    <?php if ($isEval): ?>
                                        <span class="pill pill-active status-pill" id="instStatus_<?= htmlspecialchars($inst['code']) ?>">
                                            Evaluated (<?= (int)round((float)($inst['rating'] ?? 4)) ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="pill pill-pending status-pill" id="instStatus_<?= htmlspecialchars($inst['code']) ?>">
                                            Pending Evaluation
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($isEval): ?>
                                        <button type="button" class="btn-evaluate-instructor btn-evaluated" id="btnEval_<?= htmlspecialchars($inst['code']) ?>" disabled
                                                data-code="<?= htmlspecialchars($inst['code']) ?>">
                                            Evaluated
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn-evaluate-instructor" id="btnEval_<?= htmlspecialchars($inst['code']) ?>"
                                                data-code="<?= htmlspecialchars($inst['code']) ?>"
                                                data-name="<?= htmlspecialchars($inst['name']) ?>"
                                                data-email="<?= htmlspecialchars($inst['email']) ?>"
                                                data-phone="<?= htmlspecialchars($inst['phone']) ?>"
                                                data-dept="<?= htmlspecialchars($inst['department'] ?? 'Computer Science') ?>"
                                                data-courses='<?= htmlspecialchars(json_encode($inst['courses'] ?? []), ENT_QUOTES) ?>'>
                                            <i class="fa-solid fa-star-half-stroke"></i> Evaluate
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="dir-empty" id="instructorsEmptyMsg" style="display: none;">No supportive instructors match your search criteria.</p>
            </div>
        </div>
    </div>

    <!-- TAB PANEL 3: EVALUATION HISTORY -->
    <div class="courses-panel" id="courses-panel-history" role="tabpanel" aria-labelledby="tab-history" hidden>
        <!-- Which week / month / semester is shown; this week by default -->
        <div class="dir-card history-period-card">
            <div id="historyPeriodNav"></div>
        </div>

        <!-- Filter Controls for History -->
        <div class="dir-controls">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="historySearch" placeholder="Search evaluation comments, course, instructor..." autocomplete="off">
            </div>

            <div class="seg" id="historyStatusFilter" role="group" aria-label="Status">
                <button type="button" class="seg-btn active" data-status="">All</button>
                <button type="button" class="seg-btn" data-status="evaluated">Evaluated</button>
                <button type="button" class="seg-btn" data-status="missing">Not evaluated</button>
            </div>

            <!-- Filter by Course -->
            <div class="session-dropdown-wrapper">
                <label for="historyCourseFilter" class="session-filter-label">
                    <i class="fa-solid fa-book-bookmark"></i> Course:
                </label>
                <div class="session-select-box">
                    <select id="historyCourseFilter" class="session-dropdown">
                        <option value="">All Courses</option>
                        <?php foreach (($assignedCourses ?? []) as $c): ?>
                            <option value="<?= htmlspecialchars($c['code']) ?>"><?= htmlspecialchars($c['code']) ?> — <?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down select-chevron"></i>
                </div>
            </div>

            <!-- Filter by Instructor -->
            <div class="session-dropdown-wrapper">
                <label for="historyInstructorFilter" class="session-filter-label">
                    <i class="fa-solid fa-user-check"></i> Instructor:
                </label>
                <div class="session-select-box">
                    <select id="historyInstructorFilter" class="session-dropdown">
                        <option value="">All Instructors</option>
                        <?php foreach (($assignedInstructors ?? []) as $inst): ?>
                            <option value="<?= htmlspecialchars($inst['code']) ?>"><?= htmlspecialchars($inst['name']) ?> (<?= htmlspecialchars($inst['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down select-chevron"></i>
                </div>
            </div>
        </div>

        <div class="dir-card">
            <div class="dir-scroll">
                <table class="dir-table" id="evaluationHistoryTable">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Date &amp; Period</th>
                            <th style="min-width: 180px;">Course Module</th>
                            <th style="min-width: 200px;">Instructor</th>
                            <th style="width: 140px;">Performance Rating</th>
                            <th style="min-width: 280px;">Observations &amp; Comments</th>
                            <th style="width: 130px; text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="evaluationHistoryTbody">
                        <?php foreach (($evaluationHistory ?? []) as $h): ?>
                            <?php
                            $histSearch = strtolower($h['course_code'] . ' ' . $h['course_name'] . ' ' . $h['instructor_code'] . ' ' . $h['instructor_name'] . ' ' . $h['comment']);
                            $missed = ($h['status'] ?? '') === 'Not evaluated';
                            ?>
                            <tr class="history-row <?= $missed ? 'is-missing' : '' ?>"
                                data-id="<?= htmlspecialchars($h['id']) ?>"
                                data-status="<?= $missed ? 'missing' : 'evaluated' ?>"
                                data-week-start="<?= htmlspecialchars($h['week_start']) ?>"
                                data-course="<?= htmlspecialchars($h['course_code']) ?>"
                                data-instructor="<?= htmlspecialchars($h['instructor_code']) ?>"
                                data-search="<?= htmlspecialchars($histSearch) ?>">
                                <td>
                                    <div style="font-weight: 600; color: #0f1c2e;"><?= $missed ? 'No evaluation' : htmlspecialchars($h['date']) ?></div>
                                    <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($h['week_label']) ?></div>
                                </td>
                                <td>
                                    <?= ViewHelpers::codeBadge($h['course_code'], 'course') ?>
                                    <div style="font-size: 11.5px; color: #64748b;"><?= htmlspecialchars($h['course_name']) ?></div>
                                </td>
                                <td>
                                    <div class="lec-identity">
                                        <?= ViewHelpers::codeBadge($h['instructor_code'], 'staff', $h['instructor_name']) ?>
                                        <span class="lec-name" style="font-size: 13px;"><?= htmlspecialchars($h['instructor_name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($missed): ?>
                                        <span class="text-muted">&mdash;</span>
                                    <?php else: ?>
                                        <span class="rating-badge rating-badge-active">
                                            <i class="fa-solid fa-star"></i> <?= (int)round((float)$h['rating']) ?> / 5
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 12.5px; color: #334155; line-height: 1.45;">
                                    <?php if ($missed): ?>
                                        <span class="history-missed-note">This week passed without an evaluation of <?= htmlspecialchars($h['instructor_name']) ?>.</span>
                                    <?php elseif ($h['comment'] !== ''): ?>
                                        <?= htmlspecialchars($h['comment']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">No observations recorded.</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <?php if ($missed): ?>
                                        <span class="pill history-pill-missing">Not evaluated</span>
                                    <?php else: ?>
                                        <span class="pill pill-active" style="background: #e6f9ed; color: #166534; font-size: 11px;">
                                            Evaluated
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="dir-empty" id="historyEmptyMsg" style="display: none;">No evaluations in this period match your filters.</p>
            </div>
        </div>
    </div>

</div>

<!-- SIDE DRAWER OVERLAY: EVALUATE INSTRUCTOR (Panel from Right Side) -->
<div class="side-drawer-overlay" id="evaluateInstructorDrawerOverlay" hidden>
    <div class="side-drawer" role="dialog" aria-modal="true" aria-labelledby="evalDrawerTitle">
        <div class="side-drawer-header">
            <div>
                <h3 class="side-drawer-title" id="evalDrawerTitle">Evaluate Instructor</h3>
                <p class="side-drawer-subtitle" id="evalDrawerSubtitle">Current Week Performance Appraisal</p>
            </div>
            <button type="button" class="side-drawer-close" id="closeEvalDrawerBtn" aria-label="Close drawer"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form class="side-drawer-body" id="evaluateInstructorForm">
            <!-- Selected Instructor Profile Card -->
            <div class="drawer-inst-card">
                <div class="drawer-inst-avatar" id="drawerInstAvatar">--</div>
                <div class="drawer-inst-info">
                    <h4 id="drawerInstName">Instructor Name</h4>
                    <p style="font-size: 12px; margin-top: 2px;">
                        <span class="pill pill-muted" id="drawerInstCode">--</span>
                    </p>
                    <p style="font-size: 11.5px; color: #64748b; margin-top: 3px;">
                        <i class="fa-regular fa-envelope"></i> <span id="drawerInstEmail">--</span>
                    </p>
                </div>
            </div>

            <!-- Course Selection -->
            <div class="form-row">
                <label for="drawerCourseSelect" class="form-label">
                    <i class="fa-solid fa-book-bookmark" style="color: #1a3a6b;"></i> Course Module
                </label>
                <select id="drawerCourseSelect" class="form-select" required>
                    <!-- Populated dynamically based on instructor's assigned courses -->
                </select>
            </div>

            <!-- Rating Picker -->
            <div class="form-row">
                <label class="form-label">
                    <i class="fa-solid fa-star-half-stroke" style="color: #1a3a6b;"></i> Performance Rating (Current Week)
                </label>
                <div class="modern-star-picker" id="drawerStarPicker" data-rating="4">
                    <div class="star-picker-btns">
                        <button type="button" class="star-btn active" data-val="1" title="1 - Unsatisfactory"><i class="fa-solid fa-star"></i></button>
                        <button type="button" class="star-btn active" data-val="2" title="2 - Needs Improvement"><i class="fa-solid fa-star"></i></button>
                        <button type="button" class="star-btn active" data-val="3" title="3 - Satisfactory"><i class="fa-solid fa-star"></i></button>
                        <button type="button" class="star-btn active" data-val="4" title="4 - Very Good"><i class="fa-solid fa-star"></i></button>
                        <button type="button" class="star-btn" data-val="5" title="5 - Excellent"><i class="fa-solid fa-star"></i></button>
                    </div>
                    <span class="star-rating-hint" id="drawerRatingHint">4 / 5 — Very Good</span>
                </div>
            </div>

            <!-- Comment Box -->
            <div class="form-row">
                <label for="drawerComment" class="form-label">
                    <i class="fa-regular fa-comment-dots" style="color: #1a3a6b;"></i> Observations &amp; Feedback
                </label>
                <textarea id="drawerComment" class="form-textarea" rows="4" placeholder="Enter observations on student guidance, lab supervision, assignment evaluation, or punctuality..."></textarea>
            </div>
        </form>

        <div class="side-drawer-footer">
            <button type="button" class="btn-drawer-cancel" id="cancelEvalDrawerBtn">Cancel</button>
            <button type="button" class="btn-drawer-submit" id="submitEvalDrawerBtn">
                <i class="fa-solid fa-paper-plane"></i> Submit Evaluation
            </button>
        </div>
    </div>
</div>

<script type="application/json" id="historyCalendar"><?= json_encode($calendar) ?></script>
<script src="/js/period_nav.js"></script>
<script src="/js/instructor/courses.js"></script>
