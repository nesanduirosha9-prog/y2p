<?php

use app\core\ViewHelpers;

// Staff Details: read-only directory of teaching staff, split into two tabs
// — Lecturer Details and Junior Staff Details — matching the EER's Senior
// Lecturer / Junior Staff distinction. $lecturers, $juniorStaff, $courseMeta
// are read from the database by LecturersController (an empty database
// renders empty tables). Search and the program/year filters run
// client-side (lecturers.js); the year/program filters match a staff member
// if ANY course they teach matches.

/** Renders one staff directory table (used for both tabs below). */
function renderStaffTable(string $idPrefix, array $staff, array $courseMeta, string $emptyLabel): void
{
    $total = count($staff);
    ?>
    <div class="dir-controls">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="<?= $idPrefix ?>Search" placeholder="Search name, code, courses&hellip;" autocomplete="off">
        </div>
        <div class="seg" id="<?= $idPrefix ?>ProgramFilter" role="group" aria-label="Filter by program">
            <button type="button" class="seg-btn active" data-value="">All Programs</button>
            <button type="button" class="seg-btn" data-value="CS">CS</button>
            <button type="button" class="seg-btn" data-value="IS">IS</button>
        </div>
        <div class="seg seg-dark" id="<?= $idPrefix ?>YearFilter" role="group" aria-label="Filter by year">
            <button type="button" class="seg-btn active" data-value="">All Years</button>
            <?php foreach ([1, 2, 3, 4] as $y): ?>
                <button type="button" class="seg-btn" data-value="<?= $y ?>">Year <?= $y ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="dir-card">
        <div class="dir-scroll">
            <table class="dir-table" id="<?= $idPrefix ?>Table">
                <thead>
                    <tr>
                        <th>Staff Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Assigned Courses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $code => $s): ?>
                        <?php
                        $years = [];
                        $programs = [];
                        foreach ($s['courses'] as $cc) {
                            if (isset($courseMeta[$cc])) {
                                $years[] = $courseMeta[$cc]['year'];
                                $programs[] = $courseMeta[$cc]['program'];
                            }
                        }
                        $years = array_values(array_unique($years));
                        $programs = array_values(array_unique($programs));
                        $dept = $s['dept'] ?? '';
                        $email = $s['email'] ?? '';
                        $search = strtolower($code . ' ' . $s['name'] . ' ' . $dept . ' ' . $email . ' ' . implode(' ', $s['courses']));
                        ?>
                        <tr data-programs="<?= htmlspecialchars(implode(',', $programs)) ?>"
                            data-years="<?= htmlspecialchars(implode(',', $years)) ?>"
                            data-search="<?= htmlspecialchars($search) ?>">
                            <td><?= ViewHelpers::codeBadge($code, $idPrefix === 'lecturer' ? 'lecturer' : 'staff', $s['name']) ?></td>
                            <td>
                                <div class="lec-identity">
                                    <span class="lec-avatar"><?= htmlspecialchars(ViewHelpers::staffInitials($s['name'])) ?></span>
                                    <span>
                                        <span class="lec-name"><?= htmlspecialchars($s['name']) ?></span>
                                        <span class="lec-dept"><?= htmlspecialchars($dept) ?></span>
                                    </span>
                                </div>
                            </td>
                            <td class="lec-email"><?= htmlspecialchars($email) ?></td>
                            <td>
                                <div class="tag-row">
                                    <?php foreach ($s['courses'] as $cc): ?>
                                        <span class="code-badge code-badge--course"><?= htmlspecialchars($cc) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="dir-empty" id="<?= $idPrefix ?>Empty" <?= $total ? 'hidden' : '' ?>>
                <?= $total ? 'No staff match your search.' : $emptyLabel ?>
            </p>
        </div>
    </div>
    <?php
}
?>

<div class="lecturers-view">

    <div class="seg" id="staffTabGroup" role="tablist">
        <button type="button" class="seg-btn active" data-tab="lecturer-details">Lecturer Details</button>
        <button type="button" class="seg-btn" data-tab="junior-staff-details">Junior Staff Details</button>
    </div>

    <div class="staff-tab-panel" data-panel="lecturer-details">
        <?php renderStaffTable('lecturer', $lecturers, $courseMeta, 'No lecturers yet.'); ?>
    </div>

    <div class="staff-tab-panel" data-panel="junior-staff-details" hidden>
        <?php renderStaffTable('junior', $juniorStaff, $courseMeta, 'No junior staff yet.'); ?>
    </div>
</div>

<script src="/js/lecturers.js"></script>
