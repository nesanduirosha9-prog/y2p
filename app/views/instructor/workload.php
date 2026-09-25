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
        'title' => 'Operating Systems',
        'staff_on_leave' => 'Mr. Kojo Amoah',
        'staff_code' => 'MKO',
        'lecturer_name' => 'Dr. Elena Petrov',
        'lecturer_code' => 'DEP',
        'date' => '2026-09-30',
        'time_from' => '14:00',
        'time_to' => '16:00',
        'credits' => 3,
        'year' => 2,
        'program' => 'CS',
        'role' => 'Lab Assistant',
        'hours' => 2,
        'sessions' => ['Lectures', 'Practicals', 'Lab Sessions'],
    ],
    [
        'id' => 2,
        'code' => 'IS1103',
        'title' => 'Spreadsheet Applications',
        'staff_on_leave' => 'Ms. Yaw Bediako',
        'staff_code' => 'MYB',
        'lecturer_name' => 'Dr. Linda Osei',
        'lecturer_code' => 'DLO',
        'date' => '2026-10-01',
        'time_from' => '09:00',
        'time_to' => '12:00',
        'credits' => 3,
        'year' => 1,
        'program' => 'IS',
        'role' => 'Practical Support',
        'hours' => 3,
        'sessions' => ['Lectures', 'Practicals', 'Lab Sessions'],
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
        <!-- Cover Staff Requests -->
        <div class="wk-table-card">
            <div class="wk-table-header">
                <div>
                    <p>Cover Staff Requests</p>
                    <span class="wk-table-header-hint">Sessions you have been asked to cover while a colleague is on leave</span>
                </div>
                <span class="wk-table-header-hint" id="wkPendingPill"><?= count($coverRequests) ?> pending</span>
            </div>
            <div class="dir-scroll">
                <table class="dir-table" id="coverRequestsTable">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Course Code</th>
                            <th style="min-width: 180px;">Course Name</th>
                            <th style="width: 150px;">Date</th>
                            <th style="width: 120px;">Time</th>
                            <th style="min-width: 180px;">Covering For</th>
                            <th style="min-width: 180px;">Lecturer in Charge</th>
                            <th style="width: 140px;">Role</th>
                            <th style="width: 170px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="wkCoverList">
                        <?php foreach ($coverRequests as $cr):
                            $day = new DateTime($cr['date']);
                            $schedule = $day->format('D j M') . ', ' . $cr['time_from'] . '–' . $cr['time_to'];
                        ?>
                            <tr class="wk-cover-item" data-id="<?= $cr['id'] ?>"
                                data-code="<?= htmlspecialchars($cr['code']) ?>"
                                data-name="<?= htmlspecialchars($cr['title']) ?>"
                                data-lecturer-name="<?= htmlspecialchars($cr['lecturer_name']) ?>"
                                data-lecturer-code="<?= htmlspecialchars($cr['lecturer_code']) ?>"
                                data-colleague="<?= htmlspecialchars($cr['staff_on_leave']) ?>"
                                data-schedule="<?= htmlspecialchars($schedule) ?>"
                                data-credits="<?= (int)$cr['credits'] ?>"
                                data-year="<?= (int)$cr['year'] ?>"
                                data-program="<?= htmlspecialchars($cr['program']) ?>"
                                data-role="<?= htmlspecialchars($cr['role']) ?>"
                                data-hours="<?= (int)$cr['hours'] ?>"
                                data-sessions="<?= htmlspecialchars(implode(',', $cr['sessions'] ?? [])) ?>">
                                <td><?= \app\core\ViewHelpers::codeBadge($cr['code'], 'course', $cr['title']) ?></td>
                                <td><strong><?= htmlspecialchars($cr['title']) ?></strong></td>
                                <td style="white-space: nowrap;"><?= $day->format('D, j M Y') ?></td>
                                <td style="white-space: nowrap;"><?= htmlspecialchars($cr['time_from']) ?> – <?= htmlspecialchars($cr['time_to']) ?></td>
                                <td>
                                    <div class="tag-row">
                                        <?= \app\core\ViewHelpers::codeBadge($cr['staff_code'], 'staff', $cr['staff_on_leave']) ?>
                                        <span><?= htmlspecialchars($cr['staff_on_leave']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="tag-row">
                                        <?= \app\core\ViewHelpers::codeBadge($cr['lecturer_code'], 'lecturer', $cr['lecturer_name']) ?>
                                        <span><?= htmlspecialchars($cr['lecturer_name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($cr['role']) ?></td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <button type="button" class="btn-primary-sm" data-accept="<?= $cr['id'] ?>">Accept</button>
                                    <button type="button" class="btn-secondary-sm" data-reject="<?= $cr['id'] ?>">Decline</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

            <!-- Filter Controls for Assigned Courses (Matching Lecturer's View) -->
            <div class="dir-controls" style="margin-bottom: 0;">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="wkCourseSearch" placeholder="Search courses, staff..." autocomplete="off">
                </div>

                <div class="seg" id="wkProgramFilter" role="group" aria-label="Filter by program">
                    <button type="button" class="seg-btn active" data-value="">All Programs</button>
                    <button type="button" class="seg-btn" data-value="CS">CS</button>
                    <button type="button" class="seg-btn" data-value="IS">IS</button>
                </div>

                <div class="seg seg-dark" id="wkYearFilter" role="group" aria-label="Filter by year">
                    <button type="button" class="seg-btn active" data-value="">All Years</button>
                    <button type="button" class="seg-btn" data-value="1">Year 1</button>
                    <button type="button" class="seg-btn" data-value="2">Year 2</button>
                    <button type="button" class="seg-btn" data-value="3">Year 3</button>
                    <button type="button" class="seg-btn" data-value="4">Year 4</button>
                </div>

                <div class="session-dropdown-wrapper">
                    <label for="wkSessionTypeFilter" class="session-filter-label">
                        <i class="fa-solid fa-layer-group"></i> Session:
                    </label>
                    <div class="session-select-box">
                        <select id="wkSessionTypeFilter" class="session-dropdown">
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

            <div class="dir-scroll">
                <table class="dir-table" id="instructorAssignedCoursesTable">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Course Code</th>
                            <th style="min-width: 220px;">Course Name</th>
                            <th style="width: 80px;">Credits</th>
                            <th style="width: 90px;">Year</th>
                            <th style="width: 90px;">Program</th>
                            <th style="min-width: 130px;">Lecturers</th>
                            <th style="min-width: 150px;">Other Instructors</th>
                            <th style="width: 110px; text-align: right;">Weekly Hours</th>
                        </tr>
                    </thead>
                    <tbody id="assignedCoursesTbody">
                        <?php foreach (($assignedCourses ?? []) as $ac): ?>
                            <?php
                            $lecCodes = array_column($ac['lecturers'] ?? [], 'code');
                            $lecNames = array_column($ac['lecturers'] ?? [], 'name');
                            $instCodes = array_column($ac['other_instructors'] ?? [], 'code');
                            $instNames = array_column($ac['other_instructors'] ?? [], 'name');
                            $sessionsList = (array)($ac['sessions'] ?? []);
                            $searchStr = strtolower($ac['code'] . ' ' . $ac['name'] . ' ' . implode(' ', $lecCodes) . ' ' . implode(' ', $lecNames) . ' ' . implode(' ', $instCodes) . ' ' . implode(' ', $instNames) . ' ' . implode(' ', $sessionsList));
                            ?>
                            <tr class="assigned-course-row"
                                data-code="<?= htmlspecialchars($ac['code']) ?>"
                                data-name="<?= htmlspecialchars($ac['name']) ?>"
                                data-year="<?= (int)$ac['year'] ?>"
                                data-program="<?= htmlspecialchars($ac['program']) ?>"
                                data-sessions="<?= htmlspecialchars(implode(',', $sessionsList)) ?>"
                                data-search="<?= htmlspecialchars($searchStr) ?>">
                                <td><?= \app\core\ViewHelpers::codeBadge($ac['code'], 'course', $ac['name']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($ac['name']) ?></strong>
                                    <div class="course-sessions-hint" style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                                        <i class="fa-regular fa-clock"></i> <?= htmlspecialchars(implode(' · ', $sessionsList)) ?>
                                    </div>
                                </td>
                                <td><?= (int)$ac['credits'] ?></td>
                                <td><span class="pill pill-year-<?= (int)$ac['year'] ?>">Year <?= (int)$ac['year'] ?></span></td>
                                <td><span class="pill pill-muted"><?= htmlspecialchars($ac['program']) ?></span></td>
                                <td>
                                    <div class="tag-row">
                                        <?php foreach (($ac['lecturers'] ?? []) as $lec): ?>
                                            <span class="code-badge code-badge--lecturer" title="<?= htmlspecialchars($lec['name']) ?>"><?= htmlspecialchars($lec['code']) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="tag-row">
                                        <?php if (!empty($ac['other_instructors'])): ?>
                                            <?php foreach ($ac['other_instructors'] as $oInst): ?>
                                                <span class="code-badge code-badge--staff" title="<?= htmlspecialchars($oInst['name']) ?>"><?= htmlspecialchars($oInst['code']) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 13px;">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #1a3a6b;">
                                    <?= (int)$ac['hours'] ?> hrs/wk
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="dir-empty" id="wkCoursesEmptyMsg" style="display: none; padding: 24px; text-align: center; color: #64748b;">No assigned courses match your filter criteria.</p>
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

            <!-- Calendar Stepper Navigator (Matching Image 4 tt-week-nav) -->
            <div class="tt-week-nav" id="wkHistoryPeriodNav" style="display: none;">
                <button type="button" class="tt-week-nav-btn" id="wkHistoryPrevBtn" aria-label="Previous period"><i class="fa-solid fa-chevron-left"></i></button>
                <span class="tt-week-label" id="wkHistoryPeriodLabel" style="min-width: 170px;">16 – 20 Mar 2026 (Week 5)</span>
                <button type="button" class="tt-week-nav-btn" id="wkHistoryNextBtn" aria-label="Next period"><i class="fa-solid fa-chevron-right"></i></button>
            </div>

            <!-- Course Filter Dropdown -->
            <div class="session-dropdown-wrapper">
                <label for="wkHistoryCourseFilter" class="session-filter-label">
                    <i class="fa-solid fa-book-bookmark"></i> Course:
                </label>
                <div class="session-select-box">
                    <select id="wkHistoryCourseFilter" class="session-dropdown">
                        <option value="">All Courses</option>
                        <option value="CS1101">CS1101 — Introduction to Programming</option>
                        <option value="CS2201">CS2201 — Data Structures &amp; Algorithms</option>
                        <option value="CS3301">CS3301 — Software Engineering</option>
                        <option value="CS3401">CS3401 — Fundamentals of Computing Lab</option>
                        <option value="CS2203">CS2203 — Operating Systems</option>
                        <option value="IS1103">IS1103 — Spreadsheet Applications</option>
                        <option value="other">Other (Cover Duties &amp; Departmental Tasks)</option>
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
                            <th style="width: 150px;">Date &amp; Period</th>
                            <th style="width: 130px;">Course Code</th>
                            <th style="min-width: 230px;">Course Name</th>
                            <th style="width: 150px;">Session Type</th>
                            <th style="width: 170px;">Performance Rating</th>
                            <th style="width: 140px; text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="wkHistoryTbody">
                        <?php foreach (($evaluationHistory ?? []) as $h): ?>
                            <?php
                            $isEval = ($h['status'] ?? '') === 'Evaluated';
                            $isOther = !empty($h['is_other']);
                            $histSearch = strtolower($h['course_code'] . ' ' . $h['course_name'] . ' ' . $h['session_type'] . ' ' . $h['week'] . ' ' . $h['month'] . ' ' . $h['semester'] . ' ' . $h['year'] . ' ' . ($isEval ? 'evaluated' : 'not evaluated') . ($isOther ? ' other cover' : ''));
                            ?>
                            <tr class="wk-history-row"
                                data-week="<?= htmlspecialchars($h['week']) ?>"
                                data-month="<?= htmlspecialchars($h['month']) ?>"
                                data-sem="<?= htmlspecialchars($h['semester']) ?>"
                                data-year="<?= htmlspecialchars($h['year']) ?>"
                                data-course="<?= htmlspecialchars($h['course_code']) ?>"
                                data-is-other="<?= $isOther ? '1' : '0' ?>"
                                data-search="<?= htmlspecialchars($histSearch) ?>">
                                <td>
                                    <div style="font-weight: 600; color: #0f1c2e;"><?= htmlspecialchars($h['date']) ?></div>
                                    <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($h['week']) ?> &middot; <?= htmlspecialchars($h['semester']) ?></div>
                                </td>
                                <td>
                                    <?= \app\core\ViewHelpers::codeBadge($h['course_code'], 'course', $h['course_name'] ?? '') ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #0f1c2e;"><?= htmlspecialchars($h['course_name']) ?></div>
                                </td>
                                <td>
                                    <?php if ($isOther): ?>
                                        <span class="pill pill-muted"><i class="fa-solid fa-user-clock" style="margin-right: 3px;"></i> <?= htmlspecialchars($h['session_type']) ?></span>
                                    <?php else: ?>
                                        <span class="pill pill-muted"><?= htmlspecialchars($h['session_type']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isEval && !empty($h['rating'])): ?>
                                        <span class="rating-badge rating-badge-active">
                                            <i class="fa-solid fa-star"></i> <?= (int)round((float)$h['rating']) ?> / 5
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-weight: 500;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($isEval): ?>
                                        <span class="pill pill-active" style="background: #e6f9ed; color: #166534; font-size: 11px;">
                                            Evaluated
                                        </span>
                                    <?php else: ?>
                                        <span class="pill pill-pending" style="background: #fef3c7; color: #92400e; font-size: 11px;">
                                            Not Evaluated
                                        </span>
                                    <?php endif; ?>
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
