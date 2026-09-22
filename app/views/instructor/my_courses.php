<?php

// instructor/my_courses.php — Courses page for Instructors, Lecturers, Coordinators, and In-Charge.
// Matches the exact design of timetable_officer/courses.php (media_1790062326672.png).
// - Junior Staff (Instructors, Coordinators): Shows their assigned courses with full metadata, NO evaluate actions.
// - Senior Staff (Lecturers, In-Charge): Shows their assigned courses with Session Type filter and inline slide-in evaluation flow.
// - Evaluation Flow: Master-detail slide transition with back button (inspired by messages view in media_1790065071135.png & media_1790065084972.png).

$totalCourses = count($assignedCourses ?? []);
?>

<div class="courses-flow-wrapper" id="coursesFlowWrapper" data-is-lecturer="<?= !empty($isLecturer) ? '1' : '0' ?>">

    <!-- PANE 1: COURSES CATALOG / LIST (Master View) -->
    <div class="courses-flow-pane active" id="coursesListPane">
        <div class="page-head">
            <div>
                <h2><?= htmlspecialchars($pageTitle ?? 'My Assigned Courses') ?></h2>
                <p class="page-head-sub"><span id="courseCountDisplay"><?= $totalCourses ?></span> of <?= $totalCourses ?> courses</p>
            </div>
        </div>

        <!-- Filter & Search Controls (Matching media_1790062326672.png) -->
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

            <?php if (!empty($isLecturer)): ?>
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
            <?php endif; ?>
        </div>

        <!-- Main Course Table (Matching media_1790062326672.png) -->
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
                            <?php if (!empty($isLecturer)): ?>
                                <th style="width: 130px; text-align: right;">Actions</th>
                            <?php endif; ?>
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
                                
                                <td class="cell-code"><?= htmlspecialchars($c['code']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($c['name']) ?></strong>
                                    <?php if (!empty($isLecturer) && !empty($c['sessions'])): ?>
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
                                            <span class="tag tag-lecturer" title="<?= htmlspecialchars($c['lecturer_names'][$lCode] ?? $lCode) ?>">
                                                <?= htmlspecialchars($lCode) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (!empty($c['lecturer_names'])): ?>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 3px; font-weight: 500;">
                                            <?= htmlspecialchars(implode(', ', array_values($c['lecturer_names']))) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="tag-row">
                                        <?php foreach ($c['instructors'] as $iCode): ?>
                                            <span class="tag tag-instructor"><?= htmlspecialchars($iCode) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <?php if (!empty($isLecturer)): ?>
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
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="dir-empty" id="coursesEmptyMsg" style="display: none;">No courses match your filter criteria.</p>
            </div>
        </div>
    </div>

    <?php if (!empty($isLecturer)): ?>
        <!-- PANE 2: COURSE INSTRUCTORS EVALUATION COMPONENT (Reusable for Lecturers and In-Charge) -->
        <?php require \app\core\Application::$ROOT_DIR . '/views/components/evaluation_panel.php'; ?>
    <?php endif; ?>

</div>

<script src="/js/instructor/courses.js"></script>
