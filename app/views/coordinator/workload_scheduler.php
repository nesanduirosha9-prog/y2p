<?php

// coordinator/workload_scheduler.php — Modernized, User-Friendly Duty Scheduler & Requests Manager.
// Translates the spreadsheet "Request", "Main", and script.js automation workflows.

use app\core\Application;

// Mock lecturer requests waiting in the queue (from spreadsheet "Request" sheet)
$pendingRequests = [
    ['id' => 'req-1', 'requester' => 'AYS', 'lecturer_name' => 'W. M. A. Sanahari', 'course' => 'IS 4115', 'duty' => 'In-class Assignment', 'date' => '2026-03-23', 'time_slots' => ['10-11', '11-12'], 'count' => 3, 'status' => 'Pending'],
    ['id' => 'req-2', 'requester' => 'AMD', 'lecturer_name' => 'Amod Pathirana', 'course' => 'SCS 2314', 'duty' => 'Middleware In-class Assessment', 'date' => '2026-03-24', 'time_slots' => ['1-2', '2-3'], 'count' => 4, 'status' => 'Pending'],
    ['id' => 'req-3', 'requester' => 'NPK', 'lecturer_name' => 'Dr. N. P. Karunaratne', 'course' => 'IS 1212', 'duty' => 'Probability & Statistics Lab Quiz', 'date' => '2026-03-25', 'time_slots' => ['8-9', '9-10'], 'count' => 3, 'status' => 'Pending'],
    ['id' => 'req-4', 'requester' => 'TSR', 'lecturer_name' => 'T. S. Rathnayake', 'course' => 'SCS 2313', 'duty' => 'Computer Architecture Lab Test', 'date' => '2026-03-26', 'time_slots' => ['1-2', '2-3'], 'count' => 4, 'status' => 'Pending'],
    ['id' => 'req-5', 'requester' => 'PDW', 'lecturer_name' => 'Prof. D. Wijesekara', 'course' => 'SCS 2312', 'duty' => 'Computational Models Evaluation', 'date' => '2026-03-27', 'time_slots' => ['8-9', '9-10'], 'count' => 6, 'status' => 'Pending'],
    ['id' => 'req-6', 'requester' => 'MAS', 'lecturer_name' => 'Dr. M. A. Silva', 'course' => 'IS 4101', 'duty' => 'Final Year Projects Viva', 'date' => '2026-03-27', 'time_slots' => ['8-9', '9-10', '10-11', '11-12', '12-1', '1-2', '2-3'], 'count' => 10, 'status' => 'Pending'],
];

// Mock scheduled duties for the active week (from spreadsheet "Main" sheet)
$scheduledDuties = [
    [
        'id' => 'duty-1',
        'course' => 'SCS 1308',
        'course_name' => 'Foundations of Algorithms',
        'title' => 'Tutorial Session (Recursion & Divide-and-Conquer)',
        'day' => 'Monday',
        'date' => '23 Mar 2026',
        'time_slots' => ['10-11', '11-12'],
        'time_label' => '10:00 AM – 12:00 PM',
        'needed' => 3,
        'assigned' => ['TSR', 'BMC', 'PRL'],
        'conflict' => false,
    ],
    [
        'id' => 'duty-2',
        'course' => 'SCS 1312',
        'course_name' => 'Operating System Concepts',
        'title' => 'Practical Lab Session (Process Scheduling in C)',
        'day' => 'Tuesday',
        'date' => '24 Mar 2026',
        'time_slots' => ['8-9', '9-10'],
        'time_label' => '08:00 AM – 10:00 AM',
        'needed' => 3,
        'assigned' => ['TSH', 'MVT', 'NNE'],
        'conflict' => true,
        'conflict_desc' => 'TSH is double-booked with SCS 1309 Lab at 08:00 - 09:00 AM',
    ],
    [
        'id' => 'duty-3',
        'course' => 'IS 1214',
        'course_name' => 'Data Structures and Algorithms',
        'title' => 'Lab Exam & Practical Evaluation',
        'day' => 'Wednesday',
        'date' => '25 Mar 2026',
        'time_slots' => ['1-2', '2-3'],
        'time_label' => '01:00 PM – 03:00 PM',
        'needed' => 3,
        'assigned' => [],
        'conflict' => false,
    ],
    [
        'id' => 'duty-4',
        'course' => 'SCS 2314',
        'course_name' => 'Middleware Architecture',
        'title' => 'RPC & WebSockets Practical Supervision',
        'day' => 'Thursday',
        'date' => '26 Mar 2026',
        'time_slots' => ['10-11', '11-12'],
        'time_label' => '10:00 AM – 12:00 PM',
        'needed' => 3,
        'assigned' => [],
        'conflict' => false,
    ],
];
?>

<div class="scheduler-container">
    <!-- Top Modern Header -->
    <div class="page-head">
        <div>
            <h2>Duty Scheduling & Allocation Center</h2>
            <p class="page-head-sub">Allocate supportive members for weekly sessions, balance workloads automatically, and dispatch invites</p>
        </div>
        <div class="page-head-actions">
            <button type="button" class="btn-primary" id="autoAllocateBtn">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Allocate (Lowest Workload)
            </button>
            <button type="button" class="btn-secondary" id="previewEmailBtn">
                <i class="fa-solid fa-envelope-open-text"></i> Preview & Send Invites
            </button>
        </div>
    </div>

    <!-- Quick Stat KPI Strip -->
    <div class="wm-kpi-grid sched-kpi-grid">
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-purple"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num">Week 5</p>
                <p class="wm-kpi-label">Current Academic Week</p>
            </div>
        </div>
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-yellow"><i class="fa-solid fa-inbox"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num" id="kpiPendingCount"><?= count($pendingRequests) ?></p>
                <p class="wm-kpi-label">Incoming Duty Requests</p>
            </div>
        </div>
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-blue"><i class="fa-solid fa-user-check"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num">18</p>
                <p class="wm-kpi-label">Active Junior Staff</p>
            </div>
        </div>
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num">1</p>
                <p class="wm-kpi-label">Schedule Overlap Alert</p>
            </div>
        </div>
    </div>

    <!-- View Switcher Tabs (Modern Segmented Navigation) -->
    <div class="sched-nav-tabs" role="tablist">
        <button type="button" class="sched-nav-tab active" id="tabBtnSchedule" data-view="schedule">
            <i class="fa-solid fa-calendar-week"></i>
            <span>Weekly Duty Schedule</span>
            <span class="sched-tab-badge">4 Sessions</span>
        </button>
        <button type="button" class="sched-nav-tab" id="tabBtnRequests" data-view="requests">
            <i class="fa-solid fa-inbox"></i>
            <span>Incoming SM Requests</span>
            <span class="sched-tab-badge alert-badge" id="requestsCountBadge"><?= count($pendingRequests) ?> Pending</span>
        </button>
    </div>

    <!-- VIEW 1: Weekly Duty Schedule -->
    <div class="sched-view-panel active" id="panelSchedule">
        <div class="dir-card sched-card">
            <div class="sched-panel-header">
                <div>
                    <h3>Weekly Sessions (23 Mar – 27 Mar 2026)</h3>
                    <p class="page-head-sub">Sessions needing supportive member coverage. Conflicts are clearly flagged in red.</p>
                </div>
                <div class="sched-legend">
                    <span class="legend-item"><span class="legend-dot dot-green"></span> Fulfilled</span>
                    <span class="legend-item"><span class="legend-dot dot-yellow"></span> Pending Allocation</span>
                    <span class="legend-item"><span class="legend-dot dot-red"></span> Conflict</span>
                </div>
            </div>

            <div class="duty-cards-grid">
                <?php foreach ($scheduledDuties as $d): ?>
                    <?php
                    $isConflict = $d['conflict'];
                    $isFulfilled = count($d['assigned']) >= $d['needed'];
                    $cardClass = 'duty-session-card';
                    if ($isConflict) $cardClass .= ' is-conflict';
                    elseif ($isFulfilled) $cardClass .= ' is-fulfilled';
                    else $cardClass .= ' is-pending';
                    ?>
                    <div class="<?= $cardClass ?>" id="<?= htmlspecialchars($d['id']) ?>">
                        <div class="session-card-top">
                            <div class="session-day-badge">
                                <span class="session-day"><?= htmlspecialchars($d['day']) ?></span>
                                <span class="session-date"><?= htmlspecialchars($d['date']) ?></span>
                            </div>
                            <div class="session-time-pill">
                                <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($d['time_label']) ?>
                            </div>
                        </div>

                        <div class="session-card-middle">
                            <span class="wm-course-code"><?= htmlspecialchars($d['course']) ?></span>
                            <h4 class="session-title"><?= htmlspecialchars($d['title']) ?></h4>
                            <p class="session-course-sub"><?= htmlspecialchars($d['course_name']) ?></p>
                        </div>

                        <?php if ($isConflict): ?>
                            <div class="session-conflict-alert">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span><?= htmlspecialchars($d['conflict_desc']) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="session-card-bottom">
                            <div class="session-assignees-header">
                                <span class="assignees-title">Assigned Staff</span>
                                <?php if ($isFulfilled): ?>
                                    <span class="status-chip chip-success"><i class="fa-solid fa-check"></i> <?= count($d['assigned']) ?> of <?= $d['needed'] ?> Assigned</span>
                                <?php else: ?>
                                    <span class="status-chip chip-warning"><i class="fa-solid fa-user-clock"></i> 0 of <?= $d['needed'] ?> Needed</span>
                                <?php endif; ?>
                            </div>

                            <div class="session-staff-pills duty-assignees-box">
                                <?php if (!empty($d['assigned'])): ?>
                                    <?php foreach ($d['assigned'] as $inst): ?>
                                        <span class="wm-inst-chip <?= ($isConflict && $inst === 'TSH') ? 'wm-inst-conflict' : '' ?>">
                                            <?= htmlspecialchars($inst) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="unassigned-notice">No supportive members allocated yet. Click Auto-Allocate above.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- VIEW 2: Incoming SM Requests Queue (Matching Screenshot 1 & 4) -->
    <div class="sched-view-panel" id="panelRequests" style="display: none;">
        <div class="dir-card sched-card">
            <div class="sched-panel-header">
                <div>
                    <h3>Lecturer Duty Requests Queue</h3>
                    <p class="page-head-sub">Select requests to batch-move them into the weekly scheduler for automatic lowest-workload matching</p>
                </div>
                <div class="sched-batch-actions">
                    <button type="button" class="btn-batch-start" id="batchStartBtn" disabled style="opacity: 0.6;">
                        <i class="fa-solid fa-play"></i> Approve & Schedule (<span id="selectedRequestsCount">0</span> selected)
                    </button>
                </div>
            </div>

            <div class="dir-scroll">
                <table class="dir-table" id="requestsQueueTable">
                    <thead>
                        <tr>
                            <th style="width: 44px; text-align: center;">
                                <input type="checkbox" id="selectAllRequests" title="Select all requests">
                            </th>
                            <th style="min-width: 140px;">Requested By</th>
                            <th style="min-width: 120px;">Course</th>
                            <th style="min-width: 220px;">Duty / Task</th>
                            <th style="min-width: 110px;">Date</th>
                            <th style="min-width: 130px;">Time Slots</th>
                            <th style="width: 100px;">Required</th>
                            <th style="width: 100px; text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRequests as $r): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="checkbox" class="request-select-cb" data-id="<?= htmlspecialchars($r['id']) ?>">
                                </td>
                                <td>
                                    <div class="wm-lecturer-chip" title="<?= htmlspecialchars($r['lecturer_name']) ?>">
                                        <span class="wm-lec-avatar"><?= htmlspecialchars(substr($r['requester'], 0, 2)) ?></span>
                                        <span class="wm-lec-name"><?= htmlspecialchars($r['requester']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="wm-course-code"><?= htmlspecialchars($r['course']) ?></span>
                                </td>
                                <td>
                                    <strong style="color: #0f1c2e; font-size: 13px;"><?= htmlspecialchars($r['duty']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($r['date']) ?></td>
                                <td>
                                    <div class="duty-item-slots">
                                        <?php foreach ($r['time_slots'] as $slot): ?>
                                            <span class="slot-tag"><?= htmlspecialchars($slot) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="pill pill-muted"><i class="fa-solid fa-user-group"></i> <?= $r['count'] ?> Staff</span>
                                </td>
                                <td style="text-align: right;">
                                    <span class="pill pill-pending"><?= htmlspecialchars($r['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Email & Calendar Invitation Preview Modal (Matching script.js previewEmail() & sendDutyInvitation()) -->
    <div class="modal-backdrop" id="emailPreviewModal" style="display: none;">
        <div class="modal-card" style="max-width: 680px; width: 92%;">
            <div class="modal-head">
                <div>
                    <h3><i class="fa-solid fa-envelope-open-text text-primary"></i> Preview Duty Assignment Invitation</h3>
                    <p class="modal-sub">Formatted email notification and Google Calendar invite to course coordinator and assigned staff</p>
                </div>
                <button type="button" class="modal-close" id="closeEmailModalBtn"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="modal-body">
                <div class="email-preview-box">
                    <div class="email-preview-header">
                        <strong>Subject:</strong> [Duty] - SCS 1308 Foundations of Algorithms
                    </div>

                    <p>Dear <strong>Dr. K. Fernando (DKF)</strong>,</p>
                    <p>Please find below the details of the assigned supportive member(s) for the upcoming session scheduled as follows:</p>

                    <div style="background: #ffffff; padding: 10px; border-radius: 6px; border: 1px solid #fed7aa; margin: 10px 0;">
                        <p style="margin: 2px 0;"><strong>Course Code:</strong> SCS 1308</p>
                        <p style="margin: 2px 0;"><strong>Date:</strong> 2026-03-23 (Monday)</p>
                        <p style="margin: 2px 0;"><strong>Time:</strong> 10:00 AM - 12:00 PM</p>
                    </div>

                    <h4 style="margin: 14px 0 6px 0; color: #9a3412;">Assigned Supportive Member(s)</h4>
                    <table class="email-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Contact Number</th>
                                <th>University Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>TSR</strong></td>
                                <td>T. S. Rathnayake</td>
                                <td>077-1234567</td>
                                <td>tsr@ucsc.cmb.ac.lk</td>
                            </tr>
                            <tr>
                                <td><strong>BMC</strong></td>
                                <td>B. M. Cooray</td>
                                <td>071-9876543</td>
                                <td>bmc@ucsc.cmb.ac.lk</td>
                            </tr>
                            <tr>
                                <td><strong>PRL</strong></td>
                                <td>P. R. Liyanage</td>
                                <td>076-5554321</td>
                                <td>prl@ucsc.cmb.ac.lk</td>
                            </tr>
                        </tbody>
                    </table>

                    <p style="font-size: 12px; color: #475569; font-style: italic; background: #fff; padding: 8px; border-left: 3px solid #f59e0b;">
                        Dear assigned members: Kindly meet Dr. K. Fernando in advance to clarify your duties. If you are unavailable for this task, please inform the coordination team immediately.
                    </p>

                    <p style="font-size: 11px; color: #94a3b8; margin-top: 14px;">
                        Coordination Team: Amod Pathirana (772836442) & Lakshani Gayanthika (712020986)
                    </p>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn-outline" onclick="document.getElementById('emailPreviewModal').style.display='none'">Close</button>
                <button type="submit" class="btn-primary" id="sendEmailConfirmBtn">
                    <i class="fa-solid fa-paper-plane"></i> Send Email & Calendar Invites
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/js/scheduler.js"></script>
