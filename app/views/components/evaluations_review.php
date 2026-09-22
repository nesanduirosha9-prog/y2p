<?php

// components/evaluations_review.php — Coordinator & In-Charge Junior Staff Evaluation Review Dashboard.
// Displays department KPIs, submitted appraisals list, and detail view drawer.

$evaluationHistory = [
    [
        'id' => 'eval-001',
        'staff_code' => 'TSR',
        'staff_name' => 'T. S. Rathnayake',
        'course_code' => 'SCS 1308',
        'course_name' => 'Foundations of Algorithms',
        'evaluator_code' => 'DKF',
        'evaluator_name' => 'Dr. K. Fernando',
        'date' => '2026-03-15',
        'overall_score' => 4.8,
        'punctuality' => 5,
        'technical' => 5,
        'support' => 5,
        'commitment' => 4,
        'marking' => 5,
        'strengths' => 'Exceptional algorithm demonstration. Students consistently praise his step-by-step trace explanations.',
        'improvements' => 'Could encourage quieter students to participate more during group tutorials.',
        'comments' => 'High potential for continuation into assistant lecturing.',
    ],
    [
        'id' => 'eval-002',
        'staff_code' => 'BMC',
        'staff_name' => 'B. M. Cooray',
        'course_code' => 'SCS 2310',
        'course_name' => 'Digital Signal Processing',
        'evaluator_code' => 'ASA',
        'evaluator_name' => 'Dr. A. S. Alahakoon',
        'date' => '2026-03-12',
        'overall_score' => 4.4,
        'punctuality' => 4,
        'technical' => 5,
        'support' => 4,
        'commitment' => 5,
        'marking' => 4,
        'strengths' => 'Strong theoretical command of MATLAB and Fourier transform practicals.',
        'improvements' => 'Complete assignment grading 1-2 days earlier when batches are large.',
        'comments' => 'Reliable and proactive in lab setup.',
    ],
    [
        'id' => 'eval-003',
        'staff_code' => 'DUH',
        'staff_name' => 'D. U. Hettiarachchi',
        'course_code' => 'IS 1214',
        'course_name' => 'Data Structures and Algorithms',
        'evaluator_code' => 'NAS',
        'evaluator_name' => 'Dr. N. A. Silva',
        'date' => '2026-03-10',
        'overall_score' => 4.6,
        'punctuality' => 5,
        'technical' => 4,
        'support' => 5,
        'commitment' => 5,
        'marking' => 4,
        'strengths' => 'Very approachable and patient with first-year students struggling with C pointers.',
        'improvements' => 'Needs to adhere strictly to the automated grading rubric criteria.',
        'comments' => 'Consistently dependable.',
    ],
    [
        'id' => 'eval-004',
        'staff_code' => 'AMJ',
        'staff_name' => 'A. M. Jayasuriya',
        'course_code' => 'IS 1208',
        'course_name' => 'Systems Analysis and Design',
        'evaluator_code' => 'CRW',
        'evaluator_name' => 'Dr. C. Wickramasinghe',
        'date' => '2026-03-05',
        'overall_score' => 3.8,
        'punctuality' => 3,
        'technical' => 4,
        'support' => 4,
        'commitment' => 4,
        'marking' => 4,
        'strengths' => 'Well-versed in UML modeling diagrams and agile case studies.',
        'improvements' => 'Arrived late to 2 practical sessions due to conflicting assignments.',
        'comments' => 'Workload balancing needed to resolve timetable overlap.',
    ],
];
?>

<div class="eval-component-root">
    <!-- KPI Cards -->
    <div class="wm-kpi-grid">
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-blue"><i class="fa-solid fa-clipboard-check"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num"><?= count($evaluationHistory) ?></p>
                <p class="wm-kpi-label">Submitted Evaluations</p>
            </div>
        </div>
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-purple"><i class="fa-solid fa-award"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num">4.4 / 5.0</p>
                <p class="wm-kpi-label">Department Average</p>
            </div>
        </div>
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-yellow"><i class="fa-solid fa-user-check"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num">6</p>
                <p class="wm-kpi-label">Evaluated Staff</p>
            </div>
        </div>
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-red"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num">92%</p>
                <p class="wm-kpi-label">Positive Feedback</p>
            </div>
        </div>
    </div>

    <!-- Evaluation Filter & Search -->
    <div class="dir-controls">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="evalSearchInput" placeholder="Search staff, course, lecturer..." autocomplete="off">
        </div>
        <div class="seg" id="evalRatingFilter" role="group">
            <button type="button" class="seg-btn active" data-score="all">All Ratings</button>
            <button type="button" class="seg-btn" data-score="high">4.5+ (Excellent)</button>
            <button type="button" class="seg-btn" data-score="med">3.5 - 4.4 (Good)</button>
        </div>
    </div>

    <!-- Evaluations Table -->
    <div class="dir-card">
        <div class="dir-scroll">
            <table class="dir-table" id="evaluationsTable">
                <thead>
                    <tr>
                        <th>Junior Staff Member</th>
                        <th>Course Module</th>
                        <th>Evaluated By</th>
                        <th>Overall Score</th>
                        <th>Criteria Breakdown</th>
                        <th>Evaluation Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evaluationHistory as $ev): ?>
                        <tr class="eval-row" data-search="<?= htmlspecialchars(strtolower($ev['staff_name'] . ' ' . $ev['staff_code'] . ' ' . $ev['course_code'] . ' ' . $ev['evaluator_name'])) ?>">
                            <td>
                                <div class="lec-identity">
                                    <span class="lec-avatar"><?= htmlspecialchars(substr($ev['staff_code'], 0, 2)) ?></span>
                                    <div>
                                        <span class="lec-name"><?= htmlspecialchars($ev['staff_name']) ?></span>
                                        <span class="wm-sum-courses"><?= htmlspecialchars($ev['staff_code']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="wm-course-code"><?= htmlspecialchars($ev['course_code']) ?></span>
                                <p class="page-head-sub" style="margin: 0;"><?= htmlspecialchars($ev['course_name']) ?></p>
                            </td>
                            <td>
                                <span class="pill pill-muted"><i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($ev['evaluator_name']) ?></span>
                            </td>
                            <td>
                                <div class="eval-score-pill score-high">
                                    <i class="fa-solid fa-star"></i>
                                    <strong><?= number_format($ev['overall_score'], 1) ?></strong> / 5.0
                                </div>
                            </td>
                            <td>
                                <div class="eval-breakdown-mini">
                                    <span title="Punctuality: <?= $ev['punctuality'] ?>/5">P: <?= $ev['punctuality'] ?></span>
                                    <span title="Technical: <?= $ev['technical'] ?>/5">T: <?= $ev['technical'] ?></span>
                                    <span title="Student Support: <?= $ev['support'] ?>/5">S: <?= $ev['support'] ?></span>
                                    <span title="Commitment: <?= $ev['commitment'] ?>/5">C: <?= $ev['commitment'] ?></span>
                                    <span title="Marking: <?= $ev['marking'] ?>/5">M: <?= $ev['marking'] ?></span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($ev['date']) ?></td>
                            <td style="text-align: right;">
                                <button type="button" class="btn-secondary-sm view-eval-btn" data-eval='<?= htmlspecialchars(json_encode($ev), ENT_QUOTES) ?>'>
                                    <i class="fa-solid fa-eye"></i> View Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Evaluation Details Slide-In Right Panel (Drawer) -->
    <div class="eval-drawer-backdrop" id="evalDrawerBackdrop"></div>
    <aside class="eval-drawer" id="evalDetailsDrawer" aria-hidden="true">
        <div class="eval-drawer-header">
            <div>
                <span class="eval-drawer-tag">Evaluation Summary</span>
                <h3 id="drawerStaffName" class="eval-drawer-title">—</h3>
                <p id="drawerCourseInfo" class="eval-drawer-sub">—</p>
            </div>
            <button type="button" class="eval-drawer-close" id="closeEvalDrawerBtn" title="Close panel">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="eval-drawer-body" id="drawerEvalContent">
            <!-- Populated dynamically by evaluations.js -->
        </div>
    </aside>
</div>

