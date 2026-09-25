<?php

// Course Management: searchable/filterable course table + Add / Edit Course
// drawer. $courses / $lecturers / $instructors are read from the database by
// CoursesController (an empty database renders an empty table). Search and
// the program/year filters run client-side in courses.js; Add / Edit / Delete
// persist through POST /courses, PUT and DELETE /courses/{code}.

/** JSON for a <select>-backed multi-select: [{code,label}, ...] */
$lecturerOptions = [];
foreach ($lecturers as $code => $l) {
    $lecturerOptions[] = ['code' => $code, 'label' => $code . ' — ' . $l['name'], 'dept' => $l['dept']];
}
$instructorOptions = [];
foreach ($instructors as $code => $name) {
    $instructorOptions[] = ['code' => $code, 'label' => $code . ' — ' . $name];
}

$total = count($courses);
?>

<div class="courses-view"
     data-lecturers='<?= htmlspecialchars(json_encode($lecturerOptions), ENT_QUOTES) ?>'
     data-instructors='<?= htmlspecialchars(json_encode($instructorOptions), ENT_QUOTES) ?>'>

    <div class="page-head">
        <p class="page-head-sub"><span id="courseCount"><?= $total ?></span> of <?= $total ?> courses</p>
        <button type="button" class="btn-primary" id="addCourseBtn">
            <i class="fa-solid fa-plus"></i> Add Course
        </button>
    </div>

    <div class="dir-controls">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="courseSearch" placeholder="Search courses, staff&hellip;" autocomplete="off">
        </div>
        <div class="seg" id="programFilter" role="group" aria-label="Filter by program">
            <button type="button" class="seg-btn active" data-value="">All Programs</button>
            <button type="button" class="seg-btn" data-value="CS">CS</button>
            <button type="button" class="seg-btn" data-value="IS">IS</button>
        </div>
        <div class="seg seg-dark" id="yearFilter" role="group" aria-label="Filter by year">
            <button type="button" class="seg-btn active" data-value="">All Years</button>
            <?php foreach ([1, 2, 3, 4] as $y): ?>
                <button type="button" class="seg-btn" data-value="<?= $y ?>">Year <?= $y ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="dir-card">
        <div class="dir-scroll">
            <table class="dir-table" id="coursesTable">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Credits</th>
                        <th>Year</th>
                        <th>Program</th>
                        <th>Lecturers</th>
                        <th>Instructors</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $c): ?>
                        <tr data-code="<?= htmlspecialchars($c['code']) ?>"
                            data-name="<?= htmlspecialchars($c['name']) ?>"
                            data-credits="<?= (int)$c['credits'] ?>"
                            data-year="<?= (int)$c['year'] ?>"
                            data-semester="<?= (int)$c['semester'] ?>"
                            data-program="<?= htmlspecialchars($c['program']) ?>"
                            data-lecturers="<?= htmlspecialchars(implode(',', $c['lecturers'])) ?>"
                            data-instructors="<?= htmlspecialchars(implode(',', $c['instructors'])) ?>"
                            data-search="<?= htmlspecialchars(strtolower($c['code'] . ' ' . $c['name'] . ' ' . implode(' ', $c['lecturers']) . ' ' . implode(' ', $c['instructors']))) ?>">
                            <td><?= \app\core\ViewHelpers::codeBadge($c['code'], 'course', $c['name']) ?></td>
                            <td><?= htmlspecialchars($c['name']) ?></td>
                            <td><?= (int)$c['credits'] ?></td>
                            <td><span class="pill pill-year-<?= (int)$c['year'] ?>">Year <?= (int)$c['year'] ?></span></td>
                            <td><span class="pill pill-muted"><?= htmlspecialchars($c['program']) ?></span></td>
                            <td>
                                <div class="tag-row">
                                    <?php foreach ($c['lecturers'] as $code): ?>
                                        <span class="code-badge code-badge--lecturer"><?= htmlspecialchars($code) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <div class="tag-row">
                                    <?php foreach ($c['instructors'] as $code): ?>
                                        <span class="code-badge code-badge--staff"><?= htmlspecialchars($code) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <div class="tag-row">
                                    <button type="button" class="icon-action" data-act="edit" title="Edit course"><i class="fa-solid fa-pen"></i></button>
                                    <button type="button" class="icon-action danger" data-act="delete" title="Delete course"><i class="fa-regular fa-trash-can"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="dir-empty" id="coursesEmpty" <?= $total ? 'hidden' : '' ?>>
                <?= $total ? 'No courses match your search.' : 'No courses yet.' ?>
            </p>
        </div>
    </div>
</div>

<!-- Add / Edit Course Side Drawer (Slide-out panel matching media_1790082539024.png) -->
<div class="side-drawer-overlay" id="courseModal" hidden>
    <div class="side-drawer" role="dialog" aria-modal="true" aria-labelledby="courseModalTitle">
        <div class="side-drawer-header">
            <div>
                <h3 class="side-drawer-title" id="courseModalTitle">Add New Course</h3>
                <p class="side-drawer-subtitle" id="courseModalSubtitle">Enter course details and staff assignments</p>
            </div>
            <button type="button" class="side-drawer-close" data-close aria-label="Close drawer"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form class="side-drawer-body" id="courseForm">
            <input type="hidden" id="editingCode" value="">

            <div class="field-grid">
                <div class="form-row">
                    <label for="fieldCode">Course Code</label>
                    <input type="text" id="fieldCode" placeholder="CS1101" maxlength="20" required>
                </div>
                <div class="form-row">
                    <label for="fieldCredits">Credits</label>
                    <input type="number" id="fieldCredits" min="1" max="12" value="3" required>
                </div>
            </div>

            <div class="form-row">
                <label for="fieldName">Course Name</label>
                <input type="text" id="fieldName" placeholder="Introduction to Programming" required>
            </div>

            <div class="field-grid">
                <div class="form-row">
                    <label for="fieldYear">Academic Year</label>
                    <select id="fieldYear" required>
                        <option value="">Select&hellip;</option>
                        <?php foreach ([1, 2, 3, 4] as $y): ?>
                            <option value="<?= $y ?>">Year <?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <label for="fieldProgram">Program</label>
                    <select id="fieldProgram" required>
                        <option value="">Select&hellip;</option>
                        <option value="CS">CS</option>
                        <option value="IS">IS</option>
                    </select>
                </div>
            </div>

            <!-- courses.semester is NOT NULL with no default, so the form must supply it. -->
            <div class="form-row">
                <label for="fieldSemester">Semester</label>
                <select id="fieldSemester" required>
                    <option value="">Select&hellip;</option>
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                </select>
            </div>

            <p class="section-label">Staff Assignment</p>

            <div class="form-row">
                <label>Lecturers</label>
                <div class="multi-select" data-name="lecturers">
                    <button type="button" class="multi-trigger">
                        <span class="multi-placeholder">Select lecturers&hellip;</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="multi-menu" hidden></div>
                </div>
            </div>

            <div class="form-row">
                <label>Instructors</label>
                <div class="multi-select" data-name="instructors">
                    <button type="button" class="multi-trigger">
                        <span class="multi-placeholder">Select instructors&hellip;</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="multi-menu" hidden></div>
                </div>
            </div>
        </form>

        <div class="side-drawer-footer">
            <button type="button" class="btn-drawer-cancel" data-close>Cancel</button>
            <button type="submit" form="courseForm" class="btn-drawer-submit" id="courseSubmitBtn">Add Course</button>
        </div>
    </div>
</div>

<script src="/js/courses.js"></script>
