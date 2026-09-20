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
    ['id' => 1, 'title' => 'Database Systems Lecture – CS2201', 'staff' => 'Dr. N. Perera', 'date' => '2025-07-21', 'duration' => '2 hrs'],
    ['id' => 2, 'title' => 'Network Admin Practical – IT3201', 'staff' => 'Prof. A. Silva', 'date' => '2025-07-23', 'duration' => '3 hrs'],
];

$assignedWork = [
    ['code' => 'CS3401', 'title' => 'Fundamentals of Computing Lab', 'sub' => 'Lab Supervisor · Y1 CS · Dr. Perera', 'hours' => 4, 'date' => '2 Jul 2025'],
    ['code' => 'IT2301', 'title' => 'Web Technologies Practical', 'sub' => 'Practical Supervisor · Y2 IT · Prof. Silva', 'hours' => 3, 'date' => '2 Jul 2025'],
    ['code' => 'CS2201', 'title' => 'Database Systems Lab', 'sub' => 'Lab Supervisor · Y3 CS · Dr. Perera', 'hours' => 4, 'date' => '8 Jul 2025'],
    ['code' => 'IT3201', 'title' => 'Network Administration Practical', 'sub' => 'Practical Supervisor · Y3 IT · Prof. Silva', 'hours' => 2, 'date' => '9 Jul 2025'],
];

$workHistory = [
    ['date' => '2026-03-10', 'task' => 'Database Lab', 'course' => 'CS2201', 'type' => 'Lab Supervision', 'hours' => 3],
    ['date' => '2026-03-07', 'task' => 'Lecture Session', 'course' => 'CS3401', 'type' => 'Lecture', 'hours' => 2],
    ['date' => '2026-03-02', 'task' => 'Project Review', 'course' => 'CS3402', 'type' => 'Review', 'hours' => 4],
    ['date' => '2026-02-28', 'task' => 'Lab Supervision', 'course' => 'IT2301', 'type' => 'Lab Supervision', 'hours' => 3],
    ['date' => '2026-02-21', 'task' => 'Practical Session', 'course' => 'IT3201', 'type' => 'Practical', 'hours' => 2],
    ['date' => '2026-01-15', 'task' => 'Data Structures Lab', 'course' => 'CS3402', 'type' => 'Lab Supervision', 'hours' => 4],
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

        <div class="wk-table-card">
            <div class="wk-table-header">
                <p><i class="fa-regular fa-calendar-days" style="color:#7c3aed;margin-right:6px;"></i>Monthly Breakdown</p>
                <span class="wk-table-header-hint"><?= count($monthlyBreakdown) ?> periods</span>
            </div>
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

    <div class="wk-body" id="wk-panel-assigned" hidden>
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
                    <div class="wk-cover-item" data-id="<?= $cr['id'] ?>">
                        <div class="wk-cover-icon"><i class="fa-solid fa-user-clock"></i></div>
                        <div class="wk-cover-info">
                            <p class="wk-cover-title"><?= htmlspecialchars($cr['title']) ?></p>
                            <p class="wk-cover-meta">Staff on leave: <strong><?= htmlspecialchars($cr['staff']) ?></strong> · Date: <?= htmlspecialchars($cr['date']) ?> · Duration: <?= htmlspecialchars($cr['duration']) ?></p>
                        </div>
                        <div class="wk-cover-actions">
                            <button type="button" class="wk-btn-accept" data-accept="<?= $cr['id'] ?>"><i class="fa-solid fa-check"></i> Accept</button>
                            <button type="button" class="wk-btn-reject" data-reject="<?= $cr['id'] ?>"><i class="fa-solid fa-xmark"></i> Reject</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="wk-table-card">
            <div class="wk-table-header">
                <div>
                    <p><i class="fa-regular fa-square-check" style="color:#1a3a6b;margin-right:6px;"></i>Assigned Work</p>
                    <span class="wk-table-header-hint">All auto-accepted · included in workload total</span>
                </div>
                <span class="wk-table-header-hint">This week: <strong>13 hrs</strong></span>
            </div>
            <div class="wk-assigned-list">
                <?php foreach ($assignedWork as $w): ?>
                    <div class="wk-assigned-item">
                        <div class="wk-assigned-icon"><i class="fa-regular fa-file-lines"></i></div>
                        <div class="wk-assigned-info">
                            <p class="wk-assigned-title"><span class="wk-code-pill"><?= htmlspecialchars($w['code']) ?></span> <span class="wk-assigned-badge">ASSIGNED</span></p>
                            <p class="wk-assigned-sub"><?= htmlspecialchars($w['title']) ?></p>
                            <p class="wk-assigned-meta"><?= htmlspecialchars($w['sub']) ?></p>
                        </div>
                        <div class="wk-assigned-hours">
                            <p><?= $w['hours'] ?><span>h/wk</span></p>
                            <p class="wk-assigned-date"><?= htmlspecialchars($w['date']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="wk-footnote"><i class="fa-regular fa-circle-question"></i> All regular assignments are automatically accepted and count toward your semester workload total.</p>
        </div>
    </div>

    <div class="wk-body" id="wk-panel-history" hidden>
        <div class="wk-table-card">
            <div class="wk-table-header">
                <p><i class="fa-solid fa-list-check" style="color:#1a3a6b;margin-right:6px;"></i>Work History</p>
            </div>
            <table class="wk-table">
                <thead>
                    <tr><th>DATE</th><th>TASK</th><th>COURSE</th><th>TYPE</th><th>HOURS</th><th>STATUS</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($workHistory as $h): ?>
                        <tr>
                            <td><?= htmlspecialchars($h['date']) ?></td>
                            <td class="wk-td-label"><?= htmlspecialchars($h['task']) ?></td>
                            <td><span class="wk-code-pill"><?= htmlspecialchars($h['course']) ?></span></td>
                            <td><?= htmlspecialchars($h['type']) ?></td>
                            <td><?= $h['hours'] ?> hrs</td>
                            <td><span class="wk-history-status">Completed</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="/js/instructor/workload.js"></script>
