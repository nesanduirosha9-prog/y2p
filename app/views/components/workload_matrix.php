<?php

// components/workload_matrix.php — Reusable, Sleek & Modern Course Workload Distribution Matrix.
// Modeled after spreadsheet sheets "Course Workload" and "Workload Allocation".

$mode = $mode ?? 'full';
$currentStaffCode = $currentStaffCode ?? null;
$showSummaryCards = $showSummaryCards ?? true;

// Flattened list of all courses with year & degree metadata (No dividing rows needed)
$allCourses = [
    [
        'code' => 'SCS 1308',
        'name' => 'Foundations of Algorithms',
        'year' => '1st Year',
        'program' => 'CS',
        'lecturer' => 'DKF',
        'lecturer_name' => 'Dr. K. Fernando',
        'duty_type' => 'Tutorials',
        'instructors' => ['TSR', 'BMC', 'PRL', 'NNE', 'WDI'],
        'conflicts' => [],
    ],
    [
        'code' => 'SCS 1309',
        'name' => 'Database Management Systems',
        'year' => '1st Year',
        'program' => 'CS',
        'lecturer' => 'ENO',
        'lecturer_name' => 'Dr. E. Osei & Dr. T. Silva',
        'duty_type' => 'Practicals',
        'instructors' => ['NJN', 'DUH', 'TSH', 'WIJ'],
        'conflicts' => [],
        'notes' => ['DUH' => 'Lab Lead', 'TSH' => 'Lab Setup Lead'],
    ],
    [
        'code' => 'SCS 1310',
        'name' => 'Object Oriented Modelling and Programming',
        'year' => '1st Year',
        'program' => 'CS',
        'lecturer' => 'LNC',
        'lecturer_name' => 'Dr. L. Nanayakkara',
        'duty_type' => 'Practicals',
        'instructors' => ['LKS', 'GND', 'ADM', 'AYS', 'RUR'],
        'conflicts' => [],
    ],
    [
        'code' => 'SCS 1311',
        'name' => 'Internet and Web Technologies',
        'year' => '1st Year',
        'program' => 'CS',
        'lecturer' => 'GSR',
        'lecturer_name' => 'Prof. G. Ranasinghe',
        'duty_type' => 'Practicals',
        'instructors' => ['UPE', 'MVT', 'ADM', 'PRL'],
        'conflicts' => [],
    ],
    [
        'code' => 'SCS 1312',
        'name' => 'Operating System Concepts',
        'year' => '1st Year',
        'program' => 'CS',
        'lecturer' => 'CIK',
        'lecturer_name' => 'Dr. C. Keppetiyagama',
        'duty_type' => 'Practicals',
        'instructors' => ['TSH', 'MVT', 'NNE', 'BMC', 'MAS'],
        'conflicts' => ['TSH' => 'Time conflict with SCS1309 lab slot'],
    ],
    [
        'code' => 'ENH 1303',
        'name' => 'Aesthetic Studies',
        'year' => '1st Year',
        'program' => 'CS',
        'lecturer' => 'SDA',
        'lecturer_name' => 'Dr. S. De Alwis',
        'duty_type' => 'Coordination (Lecture)',
        'instructors' => ['JRA', 'NJN', 'DUH', 'KST', 'VSM'],
        'conflicts' => [],
    ],
    [
        'code' => 'IS 1208',
        'name' => 'Systems Analysis and Design',
        'year' => '1st Year',
        'program' => 'IS',
        'lecturer' => 'CRW',
        'lecturer_name' => 'Dr. C. Wickramasinghe',
        'duty_type' => 'Tutorials',
        'instructors' => ['CIT', 'AMJ', 'KST'],
        'conflicts' => [],
    ],
    [
        'code' => 'IS 1209',
        'name' => 'Information Technology Project Management',
        'year' => '1st Year',
        'program' => 'IS',
        'lecturer' => 'DGS',
        'lecturer_name' => 'Dr. D. Samarasinghe',
        'duty_type' => 'Practicals',
        'instructors' => ['ADM', 'UPE'],
        'conflicts' => [],
    ],
    [
        'code' => 'IS 1210',
        'name' => 'Database Systems',
        'year' => '1st Year',
        'program' => 'IS',
        'lecturer' => 'ENO',
        'lecturer_name' => 'Dr. E. Osei',
        'duty_type' => 'Practicals',
        'instructors' => ['WDI', 'KST', 'JRA', 'VSM'],
        'conflicts' => [],
    ],
    [
        'code' => 'IS 1211',
        'name' => 'Computer Networks',
        'year' => '1st Year',
        'program' => 'IS',
        'lecturer' => 'TNB',
        'lecturer_name' => 'Dr. T. Bandara',
        'duty_type' => 'Practicals',
        'instructors' => ['NNE', 'ADM', 'WIJ', 'TSH', 'MAS'],
        'conflicts' => [],
    ],
    [
        'code' => 'IS 1212',
        'name' => 'Probability and Statistics',
        'year' => '1st Year',
        'program' => 'IS',
        'lecturer' => 'NPK',
        'lecturer_name' => 'Dr. N. P. Karunaratne',
        'duty_type' => 'Tutorials',
        'instructors' => ['GLS', 'AMJ'],
        'conflicts' => [],
        'notes' => ['GLS' => 'Assignment Lead'],
    ],
    [
        'code' => 'IS 1213',
        'name' => 'Organizational Behavior',
        'year' => '1st Year',
        'program' => 'IS',
        'lecturer' => 'PDL',
        'lecturer_name' => 'Dr. P. De Livera',
        'duty_type' => 'Assignment Marking',
        'instructors' => ['NJN', 'PRL', 'AMJ'],
        'conflicts' => [],
    ],
    [
        'code' => 'IS 1214',
        'name' => 'Data Structures and Algorithms',
        'year' => '1st Year',
        'program' => 'IS',
        'lecturer' => 'NAS',
        'lecturer_name' => 'Dr. N. A. Silva',
        'duty_type' => 'Practicals',
        'instructors' => ['DUH', 'AMJ', 'WYC', 'JRA', 'PRL', 'HPM'],
        'conflicts' => ['AMJ' => 'Double-booked with IS1212 tutorial slot'],
    ],
    [
        'code' => 'SCS 2310',
        'name' => 'Digital Signal Processing',
        'year' => '2nd Year',
        'program' => 'CS',
        'lecturer' => 'ASA',
        'lecturer_name' => 'Dr. A. S. Alahakoon',
        'duty_type' => 'Assignment Marking',
        'instructors' => ['BMC', 'GLS', 'WYC', 'HPM'],
        'conflicts' => [],
    ],
    [
        'code' => 'SCS 2311',
        'name' => 'Cryptography and Information Security',
        'year' => '2nd Year',
        'program' => 'CS',
        'lecturer' => 'TNK',
        'lecturer_name' => 'Dr. T. N. Kulathunga',
        'duty_type' => 'Practicals',
        'instructors' => ['NJN', 'CIT', 'DUH', 'GLS', 'GND'],
        'conflicts' => [],
    ],
    [
        'code' => 'SCS 2312',
        'name' => 'Computational Models and Programming Languages',
        'year' => '2nd Year',
        'program' => 'CS',
        'lecturer' => 'MIE',
        'lecturer_name' => 'Dr. M. I. Ekanayake',
        'duty_type' => 'Tutorials',
        'instructors' => ['HPM', 'GND', 'WDI', 'KST', 'PDW'],
        'conflicts' => [],
    ],
    [
        'code' => 'SCS 2313',
        'name' => 'Computer System Architecture',
        'year' => '2nd Year',
        'program' => 'CS',
        'lecturer' => 'KGG',
        'lecturer_name' => 'Prof. K. G. Gamage',
        'duty_type' => 'Assignment Marking',
        'instructors' => ['TSR', 'NNE'],
        'conflicts' => [],
    ],
    [
        'code' => 'SCS 2314',
        'name' => 'Middleware Architecture',
        'year' => '2nd Year',
        'program' => 'CS',
        'lecturer' => 'CRC',
        'lecturer_name' => 'Dr. C. R. Caldera',
        'duty_type' => 'Assignment Marking',
        'instructors' => ['NNE', 'JRA', 'BMC', 'CIT'],
        'conflicts' => [],
    ],
    [
        'code' => 'SCS 2315',
        'name' => 'Electronics and Physical Computing',
        'year' => '2nd Year',
        'program' => 'CS',
        'lecturer' => 'HBE',
        'lecturer_name' => 'Dr. H. B. Elvitigala',
        'duty_type' => 'Tutorials',
        'instructors' => ['HPM', 'JSB', 'BMC', 'NJN', 'RUR', 'SSW'],
        'conflicts' => ['NJN' => 'Marked on leave (1/19/2026)'],
    ],
    [
        'code' => 'IS 2208',
        'name' => 'Information Systems Management and Strategy',
        'year' => '2nd Year',
        'program' => 'IS',
        'lecturer' => 'GSR',
        'lecturer_name' => 'Prof. G. Ranasinghe',
        'duty_type' => 'Assignment Marking',
        'instructors' => ['UPE', 'WIJ', 'NNE'],
        'conflicts' => [],
    ],
    [
        'code' => 'IS 2209',
        'name' => 'Data Management and Governance',
        'year' => '2nd Year',
        'program' => 'IS',
        'lecturer' => 'YSR',
        'lecturer_name' => 'Dr. Y. S. Rajapaksha',
        'duty_type' => 'Tutorials',
        'instructors' => ['CIT', 'WIJ', 'AYS', 'JRA', 'GLS', 'VSM'],
        'conflicts' => ['WIJ' => 'Overallocated (> 18 hrs/wk)'],
    ],
    [
        'code' => 'IS 2210',
        'name' => 'Applied Data Science',
        'year' => '2nd Year',
        'program' => 'IS',
        'lecturer' => 'KTK',
        'lecturer_name' => 'Dr. K. T. Kodikara',
        'duty_type' => 'Tutorials',
        'instructors' => ['WYC', 'DUH', 'TSR', 'WIJ'],
        'conflicts' => [],
    ],
    [
        'code' => 'IS 2211',
        'name' => 'UI/UX Design',
        'year' => '2nd Year',
        'program' => 'IS',
        'lecturer' => 'DGS',
        'lecturer_name' => 'Dr. D. Samarasinghe',
        'duty_type' => 'Practicals',
        'instructors' => ['TSR', 'PRL', 'GND'],
        'conflicts' => [],
    ],
    [
        'code' => 'IS 2212',
        'name' => 'Cloud Infrastructure and Applications',
        'year' => '2nd Year',
        'program' => 'IS',
        'lecturer' => 'KMT',
        'lecturer_name' => 'Dr. K. M. Thilina',
        'duty_type' => 'Assignment Marking',
        'instructors' => ['DUH', 'WDI', 'WIJ'],
        'conflicts' => [],
    ],
];

// Allocation stats summary (Workload Allocation tab)
$allocationSummary = [
    ['code' => 'TSR', 'name' => 'T. S. Rathnayake', 'courses_count' => 4, 'total_hours' => 14, 'status' => 'normal'],
    ['code' => 'BMC', 'name' => 'B. M. Cooray', 'courses_count' => 4, 'total_hours' => 15, 'status' => 'normal'],
    ['code' => 'PRL', 'name' => 'P. R. Liyanage', 'courses_count' => 4, 'total_hours' => 13, 'status' => 'normal'],
    ['code' => 'NNE', 'name' => 'N. N. Ekanayake', 'courses_count' => 5, 'total_hours' => 16, 'status' => 'normal'],
    ['code' => 'WDI', 'name' => 'W. D. Illangasinghe', 'courses_count' => 4, 'total_hours' => 12, 'status' => 'normal'],
    ['code' => 'NJN', 'name' => 'N. J. Nanayakkara', 'courses_count' => 4, 'total_hours' => 13, 'status' => 'warning'],
    ['code' => 'DUH', 'name' => 'D. U. Hettiarachchi', 'courses_count' => 5, 'total_hours' => 17, 'status' => 'normal'],
    ['code' => 'TSH', 'name' => 'T. Sharnitha', 'courses_count' => 3, 'total_hours' => 11, 'status' => 'conflict'],
    ['code' => 'WIJ', 'name' => 'W. I. Jayawardena', 'courses_count' => 5, 'total_hours' => 19, 'status' => 'overload'],
    ['code' => 'AMJ', 'name' => 'A. M. Jayasuriya', 'courses_count' => 4, 'total_hours' => 12, 'status' => 'conflict'],
    ['code' => 'GLS', 'name' => 'G. L. Samaranayake', 'courses_count' => 4, 'total_hours' => 13, 'status' => 'normal'],
];

if ($mode === 'lecturer' && $currentStaffCode) {
    $allCourses = array_filter($allCourses, function ($c) use ($currentStaffCode) {
        return strpos($c['lecturer'], $currentStaffCode) !== false;
    });
}
?>

<div class="wm-wrapper" id="workloadMatrixRoot">
    <?php if ($showSummaryCards): ?>
        <!-- Modernized Clean KPI Cards -->
        <div class="wm-kpi-grid">
            <div class="wm-kpi-card">
                <div class="wm-kpi-icon icon-blue"><i class="fa-solid fa-graduation-cap"></i></div>
                <div class="wm-kpi-data">
                    <p class="wm-kpi-num"><?= count($allCourses) ?></p>
                    <p class="wm-kpi-label">Active Course Modules</p>
                </div>
            </div>
            <div class="wm-kpi-card">
                <div class="wm-kpi-icon icon-purple"><i class="fa-solid fa-user-group"></i></div>
                <div class="wm-kpi-data">
                    <p class="wm-kpi-num">18</p>
                    <p class="wm-kpi-label">Junior Staff Deployed</p>
                </div>
            </div>
            <div class="wm-kpi-card">
                <div class="wm-kpi-icon icon-yellow"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <div class="wm-kpi-data">
                    <p class="wm-kpi-num">14.2h</p>
                    <p class="wm-kpi-label">Target Weekly Workload</p>
                </div>
            </div>
            <div class="wm-kpi-card">
                <div class="wm-kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="wm-kpi-data">
                    <p class="wm-kpi-num">3</p>
                    <p class="wm-kpi-label">Flagged Issues / Conflicts</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Upper Sorting & Filter Controls (Handles all filtering cleanly without dividing rows) -->
    <div class="dir-card wm-toolbar-card">
        <div class="wm-toolbar-inner">
            <div class="search-box wm-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="wmSearchInput" placeholder="Search module code, title, lecturer, instructor..." autocomplete="off">
            </div>

            <!-- Degree Filter Segment -->
            <div class="wm-filter-group">
                <span class="wm-filter-label">Degree:</span>
                <div class="seg" id="wmProgramSeg" role="group" aria-label="Filter by degree program">
                    <button type="button" class="seg-btn active" data-prog="all">All Degrees</button>
                    <button type="button" class="seg-btn" data-prog="CS">CS</button>
                    <button type="button" class="seg-btn" data-prog="IS">IS</button>
                </div>
            </div>

            <!-- Year Filter Segment -->
            <div class="wm-filter-group">
                <span class="wm-filter-label">Year:</span>
                <div class="seg" id="wmYearSeg" role="group" aria-label="Filter by academic year">
                    <button type="button" class="seg-btn active" data-year="all">All Years</button>
                    <button type="button" class="seg-btn" data-year="1st Year">1st Year</button>
                    <button type="button" class="seg-btn" data-year="2nd Year">2nd Year</button>
                </div>
            </div>

            <div class="wm-toolbar-actions">
                <button type="button" class="btn-secondary" id="toggleAllocationSummaryBtn">
                    <i class="fa-solid fa-chart-pie"></i> Workload Balance
                </button>
            </div>
        </div>
    </div>

    <!-- Allocation Summary Drawer (Toggled from Workload Balance button) -->
    <div class="dir-card wm-summary-card" id="allocationSummaryCard" style="display: none;">
        <div class="wm-summary-head">
            <div>
                <h4><i class="fa-solid fa-scale-balanced text-primary"></i> Junior Staff Workload Balance & Equity</h4>
                <p class="page-head-sub">Monitoring accumulated weekly hours to prevent over-allocation and burnout</p>
            </div>
            <button type="button" class="icon-btn-close" id="closeSummaryCard" title="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="wm-summary-pills">
            <?php foreach ($allocationSummary as $staff): ?>
                <?php
                $pillClass = 'wm-stat-pill-normal';
                if ($staff['status'] === 'overload') $pillClass = 'wm-stat-pill-overload';
                elseif ($staff['status'] === 'conflict') $pillClass = 'wm-stat-pill-conflict';
                elseif ($staff['status'] === 'warning') $pillClass = 'wm-stat-pill-warning';
                ?>
                <div class="wm-summary-item <?= $pillClass ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="wm-sum-code"><?= htmlspecialchars($staff['code']) ?></span>
                        <span class="wm-sum-hours"><?= $staff['total_hours'] ?> hrs/wk</span>
                    </div>
                    <span class="wm-sum-name"><?= htmlspecialchars($staff['name']) ?></span>
                    <span class="wm-sum-courses"><?= $staff['courses_count'] ?> course modules assigned</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Unified, Modern Course Workload Matrix Table (No dividing headers; clean responsive rows) -->
    <div class="dir-card wm-matrix-card">
        <div class="dir-scroll">
            <table class="dir-table wm-table" id="workloadMatrixTable">
                <thead>
                    <tr>
                        <th style="width: 140px;">Course Code</th>
                        <th style="min-width: 200px;">Course Title</th>
                        <th style="width: 110px;">Programme</th>
                        <th style="width: 140px;">Lecturer in-charge</th>
                        <th style="width: 130px;">Engagement</th>
                        <th style="min-width: 320px;">Assigned Supportive Members (Instructors 1–7)</th>
                        <th style="width: 100px; text-align: right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allCourses as $c): ?>
                        <tr class="wm-data-row" 
                            data-year="<?= htmlspecialchars($c['year']) ?>" 
                            data-prog="<?= htmlspecialchars($c['program']) ?>"
                            data-search="<?= htmlspecialchars(strtolower($c['code'] . ' ' . $c['name'] . ' ' . $c['lecturer'] . ' ' . $c['program'] . ' ' . $c['year'] . ' ' . implode(' ', $c['instructors']))) ?>">
                            <td>
                                <span class="wm-course-code"><?= htmlspecialchars($c['code']) ?></span>
                            </td>
                            <td>
                                <div class="wm-course-meta">
                                    <strong class="wm-course-title"><?= htmlspecialchars($c['name']) ?></strong>
                                </div>
                            </td>
                            <td>
                                <div class="wm-scope-badge">
                                    <span class="wm-prog-pill wm-prog-<?= strtolower($c['program']) ?>"><?= htmlspecialchars($c['program']) ?></span>
                                    <span class="wm-year-pill"><?= htmlspecialchars($c['year']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="wm-lecturer-chip" title="<?= htmlspecialchars($c['lecturer_name']) ?>">
                                    <span class="wm-lec-avatar"><?= htmlspecialchars(substr($c['lecturer'], 0, 2)) ?></span>
                                    <span class="wm-lec-name"><?= htmlspecialchars($c['lecturer']) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php
                                $typeClass = 'duty-tag-practical';
                                if (strpos($c['duty_type'], 'Tutorial') !== false) $typeClass = 'duty-tag-tutorial';
                                elseif (strpos($c['duty_type'], 'Marking') !== false) $typeClass = 'duty-tag-marking';
                                elseif (strpos($c['duty_type'], 'Coordination') !== false) $typeClass = 'duty-tag-coordination';
                                ?>
                                <span class="wm-duty-type <?= $typeClass ?>">
                                    <?= htmlspecialchars($c['duty_type']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="wm-instructors-list">
                                    <?php foreach ($c['instructors'] as $idx => $inst): ?>
                                        <?php
                                        $hasConflict = isset($c['conflicts'][$inst]);
                                        $hasNote = isset($c['notes'][$inst]);
                                        $pillStyle = 'wm-inst-chip';
                                        if ($hasConflict) $pillStyle .= ' wm-inst-conflict';
                                        elseif ($hasNote) $pillStyle .= ' wm-inst-note';
                                        ?>
                                        <span class="<?= $pillStyle ?>" title="<?= $hasConflict ? htmlspecialchars($c['conflicts'][$inst]) : ($hasNote ? htmlspecialchars($c['notes'][$inst]) : 'Instructor ' . ($idx + 1)) ?>">
                                            <span class="inst-code"><?= htmlspecialchars($inst) ?></span>
                                            <?php if ($hasConflict): ?>
                                                <i class="fa-solid fa-triangle-exclamation conflict-icon"></i>
                                            <?php elseif ($hasNote): ?>
                                                <i class="fa-solid fa-circle-info note-icon"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <?php if (!empty($c['conflicts'])): ?>
                                    <span class="pill pill-danger" title="<?= htmlspecialchars(implode(', ', $c['conflicts'])) ?>">Conflict</span>
                                <?php else: ?>
                                    <span class="pill pill-active">Allocated</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="dir-empty" id="wmEmptyMsg" style="display: none;">No courses match the selected criteria.</p>
        </div>
    </div>
</div>
