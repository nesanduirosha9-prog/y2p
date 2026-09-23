<?php

// Instructor Workload View — Overview / Assigned Work / History tabs, matching
// the Figma "MyWorkload" wireframes. Tab switching + Accept/Reject are
// DOM-only demo behavior (js/instructor/workload.js) — nothing persists.
$title = "My Workload";

// Dummy data for the UI
$semesterOptions = ["Semester 1 - 2026", "Semester 2 - 2025", "Semester 1 - 2025"];
$monthOptions = ["March 2026", "February 2026", "January 2026"];
$weekOptions = ["Week 5", "Week 4", "Week 3"];

$curSem = ['assigned' => 320, 'completed' => 248];
$curMonth = ['assigned' => 42, 'completed' => 39];
$curWeek = ['assigned' => 12, 'completed' => 10];

$semesterBreakdown = [
    ['label' => 'Semester 1 - 2026', 'assigned' => 320, 'completed' => 248],
    ['label' => 'Semester 2 - 2025', 'assigned' => 280, 'completed' => 280],
    ['label' => 'Semester 1 - 2025', 'assigned' => 300, 'completed' => 298],
];
$monthlyBreakdown = [
    ['label' => 'March 2026', 'assigned' => 42, 'completed' => 39],
    ['label' => 'February 2026', 'assigned' => 38, 'completed' => 35],
];

$coverRequests = [
    [
        'id' => 1,
        'code' => 'CS2203',
        'title' => 'Operating Systems Lab Session',
        'staff' => 'Dr. Elena Petrov',
        'date' => 'Wed, 14:00 - 16:00',
        'duration' => '2 hrs/wk',
        'credits' => 3,
        'year' => 2,
        'program' => 'CS',
        'role' => 'Lab Assistant',
        'hours' => 2,
    ],
    [
        'id' => 2,
        'code' => 'IS1103',
        'title' => 'Spreadsheet Applications Practical',
        'staff' => 'Dr. Linda Osei',
        'date' => 'Thu, 09:00 - 12:00',
        'duration' => '3 hrs/wk',
        'credits' => 3,
        'year' => 1,
        'program' => 'IS',
        'role' => 'Practical Support',
        'hours' => 3,
    ],
];
?>

<div class="wk-container">
    <!-- Tab Bar -->
    <div class="wk-tabs" id="wkTabs">
        <button type="button" class="wk-tab active" data-tab="overview">
            <i class="fa-solid fa-chart-bar"></i> Overview
        </button>
        <button type="button" class="wk-tab" data-tab="assigned">
            <i class="fa-solid fa-layer-group"></i> Assigned Work
            <span class="wk-tab-dot" id="wkAssignedDot"></span>
        </button>
        <button type="button" class="wk-tab" data-tab="history">
            <i class="fa-solid fa-list-check"></i> History
        </button>
    </div>

    <div class="wk-body" id="wk-panel-overview">
        <!-- Filter Bar -->
        <div class="wk-filter-bar">
            <div class="wk-filter-title">
                <i class="fa-solid fa-chart-bar" style="color: #1a3a6b;"></i>
                <span>Filter Workload</span>
            </div>
            <div class="v-divider"></div>
            <button class="wk-btn-reset" id="wkBtnReset" type="button"><i class="fa-solid fa-xmark"></i> Reset</button>
            <div class="v-divider"></div>
            
            <div class="wk-filter-group">
                <label>SEMESTER</label>
                <div class="wk-select-wrapper">
                    <select class="wk-select">
                        <option value="">Select...</option>
                        <?php foreach($semesterOptions as $opt): ?>
                            <option><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </div>
            </div>

            <div class="wk-filter-group">
                <label>MONTH</label>
                <div class="wk-select-wrapper">
                    <select class="wk-select">
                        <option value="">Select...</option>
                        <?php foreach($monthOptions as $opt): ?>
                            <option><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </div>
            </div>

            <div class="wk-filter-group">
                <label>WEEK</label>
                <div class="wk-select-wrapper">
                    <select class="wk-select">
                        <option value="">Select...</option>
                        <?php foreach($weekOptions as $opt): ?>
                            <option><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </div>
            </div>
        </div>

        <!-- Overview Cards -->
        <div class="wk-overview-grid">
            <!-- Semester Workload -->
            <div class="wk-card">
                <div class="wk-card-header" style="background: #f4f7fc;">
                    <div class="wk-icon-box" style="background: #e8edf5; color: #1a3a6b;">
                        <i class="fa-solid fa-book-open"></i>
                    </div>
                    <div class="wk-card-titles">
                        <p class="wk-card-label">SEMESTER WORKLOAD</p>
                        <p class="wk-card-sub">Semester 1 - 2026</p>
                    </div>
                </div>
                <div class="wk-card-body">
                    <div class="wk-stat">
                        <p class="wk-stat-label">ASSIGNED</p>
                        <p class="wk-stat-value text-blue"><?= $curSem['assigned'] ?></p>
                        <p class="wk-stat-unit">hrs</p>
                    </div>
                    <div class="v-divider"></div>
                    <div class="wk-stat">
                        <p class="wk-stat-label">COMPLETED</p>
                        <p class="wk-stat-value text-green"><?= $curSem['completed'] ?></p>
                        <p class="wk-stat-unit">hrs</p>
                    </div>
                </div>
                <div class="wk-card-footer">
                    <div class="wk-progress-track">
                        <div class="wk-progress-fill bg-blue" style="width: <?= round(($curSem['completed']/$curSem['assigned'])*100) ?>%;"></div>
                    </div>
                    <p class="wk-progress-text"><?= round(($curSem['completed']/$curSem['assigned'])*100) ?>% completed</p>
                </div>
            </div>

            <!-- Monthly Workload -->
            <div class="wk-card">
                <div class="wk-card-header" style="background: #f7f4fd;">
                    <div class="wk-icon-box" style="background: #ede9fe; color: #7c3aed;">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                    <div class="wk-card-titles">
                        <p class="wk-card-label">MONTHLY WORKLOAD</p>
                        <p class="wk-card-sub">March 2026</p>
                    </div>
                </div>
                <div class="wk-card-body">
                    <div class="wk-stat">
                        <p class="wk-stat-label">ASSIGNED</p>
                        <p class="wk-stat-value text-purple"><?= $curMonth['assigned'] ?></p>
                        <p class="wk-stat-unit">hrs</p>
                    </div>
                    <div class="v-divider"></div>
                    <div class="wk-stat">
                        <p class="wk-stat-label">COMPLETED</p>
                        <p class="wk-stat-value text-green"><?= $curMonth['completed'] ?></p>
                        <p class="wk-stat-unit">hrs</p>
                    </div>
                </div>
                <div class="wk-card-footer">
                    <div class="wk-progress-track">
                        <div class="wk-progress-fill bg-purple" style="width: <?= round(($curMonth['completed']/$curMonth['assigned'])*100) ?>%;"></div>
                    </div>
                    <p class="wk-progress-text"><?= round(($curMonth['completed']/$curMonth['assigned'])*100) ?>% completed</p>
                </div>
            </div>

            <!-- Weekly Workload -->
            <div class="wk-card">
                <div class="wk-card-header" style="background: #fefce8;">
                    <div class="wk-icon-box" style="background: #fef9c3; color: #ca8a04;">
                        <i class="fa-solid fa-calendar-week"></i>
                    </div>
                    <div class="wk-card-titles">
                        <p class="wk-card-label">WEEKLY WORKLOAD</p>
                        <p class="wk-card-sub">Week 5</p>
                    </div>
                </div>
                <div class="wk-card-body">
                    <div class="wk-stat">
                        <p class="wk-stat-label">ASSIGNED</p>
                        <p class="wk-stat-value text-yellow"><?= $curWeek['assigned'] ?></p>
                        <p class="wk-stat-unit">hrs</p>
                    </div>
                    <div class="v-divider"></div>
                    <div class="wk-stat">
                        <p class="wk-stat-label">COMPLETED</p>
                        <p class="wk-stat-value text-green"><?= $curWeek['completed'] ?></p>
                        <p class="wk-stat-unit">hrs</p>
                    </div>
                </div>
                <div class="wk-card-footer">
                    <div class="wk-progress-track">
                        <div class="wk-progress-fill bg-yellow" style="width: <?= round(($curWeek['completed']/$curWeek['assigned'])*100) ?>%;"></div>
                    </div>
                    <p class="wk-progress-text"><?= round(($curWeek['completed']/$curWeek['assigned'])*100) ?>% completed</p>
                </div>
            </div>
        </div>

        <div class="wk-table-card">
            <div class="wk-table-header">
                <p><i class="fa-regular fa-calendar" style="color:#1a3a6b;margin-right:6px;"></i>Semester Breakdown</p>
                <span class="wk-table-header-hint"><?= count($semesterBreakdown) ?> periods</span>
            </div>
            <div class="wk-table-scroll">
            <table class="wk-table">
                <thead>
                    <tr><th>SEMESTER</th><th>ASSIGNED</th><th>COMPLETED</th><th>PROGRESS</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($semesterBreakdown as $row): $pct = round(($row['completed'] / $row['assigned']) * 100); ?>
                        <tr>
                            <td class="wk-td-label"><?= htmlspecialchars($row['label']) ?></td>
                            <td><?= $row['assigned'] ?> hrs</td>
                            <td><?= $row['completed'] ?> hrs</td>
                            <td>
                                <div class="wk-row-progress">
                                    <div class="wk-progress-track"><div class="wk-progress-fill bg-blue" style="width: <?= $pct ?>%;"></div></div>
                                    <span><?= $pct ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div class="wk-table-card">
            <div class="wk-table-header">
                <p><i class="fa-regular fa-calendar-days" style="color:#7c3aed;margin-right:6px;"></i>Monthly Breakdown</p>
                <span class="wk-table-header-hint"><?= count($monthlyBreakdown) ?> periods</span>
            </div>
            <div class="wk-table-scroll">
            <table class="wk-table">
                <thead>
                    <tr><th>MONTH</th><th>ASSIGNED</th><th>COMPLETED</th><th>PROGRESS</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($monthlyBreakdown as $row): $pct = round(($row['completed'] / $row['assigned']) * 100); ?>
                        <tr>
                            <td class="wk-td-label"><?= htmlspecialchars($row['label']) ?></td>
                            <td><?= $row['assigned'] ?> hrs</td>
                            <td><?= $row['completed'] ?> hrs</td>
                            <td>
                                <div class="wk-row-progress">
                                    <div class="wk-progress-track"><div class="wk-progress-fill bg-purple" style="width: <?= $pct ?>%;"></div></div>
                                    <span><?= $pct ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="wk-body" id="wk-panel-assigned" hidden>
        <!-- Cover Staff Requests Card -->
        <div class="wk-cover-card">
            <div class="wk-cover-header">
                <div>
                    <i class="fa-solid fa-bell"></i>
                    <strong>Cover Staff Requests</strong>
                    <span class="wk-pending-pill" id="wkPendingPill"><?= count($coverRequests) ?> pending</span>
                </div>
                <span class="wk-cover-hint">A colleague is on leave — please respond</span>
            </div>
            <div class="wk-cover-list" id="wkCoverList">
                <?php foreach ($coverRequests as $cr): ?>
                    <div class="wk-cover-item" data-id="<?= $cr['id'] ?>"
                         data-code="<?= htmlspecialchars($cr['code']) ?>"
                         data-name="<?= htmlspecialchars($cr['title']) ?>"
                         data-staff="<?= htmlspecialchars($cr['staff']) ?>"
                         data-schedule="<?= htmlspecialchars($cr['date']) ?>"
                         data-credits="<?= (int)$cr['credits'] ?>"
                         data-year="<?= (int)$cr['year'] ?>"
                         data-program="<?= htmlspecialchars($cr['program']) ?>"
                         data-role="<?= htmlspecialchars($cr['role']) ?>"
                         data-hours="<?= (int)$cr['hours'] ?>">
                        <div class="wk-cover-icon"><i class="fa-solid fa-user-clock"></i></div>
                        <div class="wk-cover-info">
                            <p class="wk-cover-title"><span class="pill pill-muted" style="margin-right: 6px;"><?= htmlspecialchars($cr['code']) ?></span> <?= htmlspecialchars($cr['title']) ?></p>
                            <p class="wk-cover-meta">Staff on leave: <strong><?= htmlspecialchars($cr['staff']) ?></strong> · Schedule: <?= htmlspecialchars($cr['date']) ?> · Duration: <?= htmlspecialchars($cr['duration']) ?></p>
                        </div>
                        <div class="wk-cover-actions">
                            <button type="button" class="wk-btn-accept" data-accept="<?= $cr['id'] ?>"><i class="fa-solid fa-check"></i> Accept</button>
                            <button type="button" class="wk-btn-reject" data-reject="<?= $cr['id'] ?>"><i class="fa-solid fa-xmark"></i> Reject</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Assigned Courses Table -->
        <div class="wk-table-card">
            <div class="wk-table-header">
                <div>
                    <p><i class="fa-solid fa-book-open" style="color:#1a3a6b;margin-right:6px;"></i>My Assigned Courses</p>
                    <span class="wk-table-header-hint">Active course modules and accepted cover duties</span>
                </div>
                <span class="wk-table-header-hint" id="wkAssignedCoursesCount"><?= count($assignedCourses ?? []) ?> assigned courses</span>
            </div>
            <div class="dir-scroll">
                <table class="dir-table" id="instructorAssignedCoursesTable">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Course Code</th>
                            <th style="min-width: 220px;">Course Name</th>
                            <th style="width: 80px;">Credits</th>
                            <th style="width: 90px;">Year</th>
                            <th style="width: 90px;">Program</th>
                            <th style="min-width: 160px;">Lecturer</th>
                            <th style="min-width: 180px;">Assigned Role &amp; Sessions</th>
                            <th style="width: 110px; text-align: right;">Weekly Hours</th>
                        </tr>
                    </thead>
                    <tbody id="assignedCoursesTbody">
                        <?php foreach (($assignedCourses ?? []) as $ac): ?>
                            <tr class="assigned-course-row">
                                <td><span class="pill pill-muted"><?= htmlspecialchars($ac['code']) ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($ac['name']) ?></strong>
                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                                        <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($ac['schedule'] ?? 'Allocated') ?>
                                    </div>
                                </td>
                                <td><?= (int)$ac['credits'] ?></td>
                                <td><span class="pill pill-year-<?= (int)$ac['year'] ?>">Year <?= (int)$ac['year'] ?></span></td>
                                <td><span class="pill pill-muted"><?= htmlspecialchars($ac['program']) ?></span></td>
                                <td>
                                    <span class="tag tag-lecturer"><?= htmlspecialchars($ac['lecturer']) ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($ac['role']) ?></strong>
                                    <div style="font-size: 11.5px; color: #64748b;"><?= htmlspecialchars($ac['sessions']) ?></div>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #1a3a6b;">
                                    <?= (int)$ac['hours'] ?> hrs/wk
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="wk-footnote"><i class="fa-regular fa-circle-question"></i> All regular course assignments and accepted cover duties count toward your semester workload total.</p>
        </div>
    </div>

    <div class="wk-body" id="wk-panel-history" hidden>
        <!-- Filter Controls for Instructor Evaluation History -->
        <div class="dir-controls" style="margin-bottom: 16px;">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="wkHistorySearch" placeholder="Search course, feedback remarks, session..." autocomplete="off">
            </div>

            <!-- Time Filter (Week, Month, Sem, Year) -->
            <div class="seg" id="wkHistoryTimeFilter" role="group" aria-label="Filter by time">
                <button type="button" class="seg-btn active" data-value="">All Time</button>
                <button type="button" class="seg-btn" data-value="week">Week</button>
                <button type="button" class="seg-btn" data-value="month">Month</button>
                <button type="button" class="seg-btn" data-value="sem">Semester</button>
                <button type="button" class="seg-btn" data-value="year">Year</button>
            </div>

            <!-- Course Filter Dropdown (No Lecturer filter) -->
            <div class="session-dropdown-wrapper">
                <label for="wkHistoryCourseFilter" class="session-filter-label">
                    <i class="fa-solid fa-book-bookmark"></i> Course:
                </label>
                <div class="session-select-box">
                    <select id="wkHistoryCourseFilter" class="session-dropdown">
                        <option value="">All Courses</option>
                        <?php
                        $uniqueCourses = [];
                        foreach (($evaluationHistory ?? []) as $eh) {
                            $uniqueCourses[$eh['course_code']] = $eh['course_name'];
                        }
                        ?>
                        <?php foreach ($uniqueCourses as $cCode => $cName): ?>
                            <option value="<?= htmlspecialchars($cCode) ?>"><?= htmlspecialchars($cCode) ?> — <?= htmlspecialchars($cName) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down select-chevron"></i>
                </div>
            </div>
        </div>

        <div class="wk-table-card">
            <div class="wk-table-header">
                <p><i class="fa-solid fa-clipboard-check" style="color:#1a3a6b;margin-right:6px;"></i>Course Evaluation &amp; Performance History</p>
                <span class="wk-table-header-hint" id="wkHistoryCountDisplay"><?= count($evaluationHistory ?? []) ?> evaluation records</span>
            </div>
            <div class="dir-scroll">
                <table class="dir-table" id="instructorEvaluationHistoryTable">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Date &amp; Period</th>
                            <th style="min-width: 200px;">Course Module</th>
                            <th style="width: 140px;">Session Type</th>
                            <th style="width: 140px;">Performance Rating</th>
                            <th style="min-width: 280px;">Lecturer Observations &amp; Feedback</th>
                            <th style="width: 110px; text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="wkHistoryTbody">
                        <?php foreach (($evaluationHistory ?? []) as $h): ?>
                            <?php
                            $histSearch = strtolower($h['course_code'] . ' ' . $h['course_name'] . ' ' . $h['session_type'] . ' ' . $h['comment'] . ' ' . $h['week'] . ' ' . $h['month'] . ' ' . $h['semester'] . ' ' . $h['year']);
                            ?>
                            <tr class="wk-history-row"
                                data-week="<?= htmlspecialchars($h['week']) ?>"
                                data-month="<?= htmlspecialchars($h['month']) ?>"
                                data-sem="<?= htmlspecialchars($h['semester']) ?>"
                                data-year="<?= htmlspecialchars($h['year']) ?>"
                                data-course="<?= htmlspecialchars($h['course_code']) ?>"
                                data-search="<?= htmlspecialchars($histSearch) ?>">
                                <td>
                                    <div style="font-weight: 600; color: #0f1c2e;"><?= htmlspecialchars($h['date']) ?></div>
                                    <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($h['week']) ?> &middot; <?= htmlspecialchars($h['semester']) ?></div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($h['course_code']) ?></strong>
                                    <div style="font-size: 11.5px; color: #64748b;"><?= htmlspecialchars($h['course_name']) ?></div>
                                </td>
                                <td>
                                    <span class="pill pill-muted"><?= htmlspecialchars($h['session_type']) ?></span>
                                </td>
                                <td>
                                    <span class="rating-badge rating-badge-active">
                                        <i class="fa-solid fa-star"></i> <?= number_format((float)$h['rating'], 1) ?> / 5.0
                                    </span>
                                </td>
                                <td style="font-size: 12.5px; color: #334155; line-height: 1.45;">
                                    <?= htmlspecialchars($h['comment']) ?>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                                        Evaluated by <strong><?= htmlspecialchars($h['evaluator_name'] ?? 'Course Lecturer') ?></strong>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <span class="pill pill-active" style="background: #e6f9ed; color: #166534; font-size: 11px;">
                                        <i class="fa-solid fa-check"></i> <?= htmlspecialchars($h['status'] ?? 'Evaluated') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="dir-empty" id="wkHistoryEmptyMsg" style="display: none;">No evaluation records match your filter criteria.</p>
            </div>
        </div>
    </div>
</div>

<script src="/js/instructor/workload.js"></script>
