<?php

// Lecturer Details: read-only directory of teaching staff. $lecturers /
// $courseMeta are read from the database by LecturersController (an empty
// database renders an empty table). Search and the program/year filters run
// client-side (lecturers.js); the year/program filters match a lecturer if ANY
// course they teach matches.

function lecturerInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(fn($p) => strtoupper(substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters);
}

$total = count($lecturers);
?>

<div class="lecturers-view">

    <div class="page-head">
        <div>
            <h2>Lecturer Details</h2>
            <p class="page-head-sub"><span id="lecturerCount"><?= $total ?></span> of <?= $total ?> lecturers</p>
        </div>
    </div>

    <div class="dir-controls">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="lecturerSearch" placeholder="Search name, code, courses&hellip;" autocomplete="off">
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
            <table class="dir-table" id="lecturersTable">
                <thead>
                    <tr>
                        <th>Lecturer Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Assigned Courses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lecturers as $code => $l): ?>
                        <?php
                        $years = [];
                        $programs = [];
                        foreach ($l['courses'] as $cc) {
                            if (isset($courseMeta[$cc])) {
                                $years[] = $courseMeta[$cc]['year'];
                                $programs[] = $courseMeta[$cc]['program'];
                            }
                        }
                        $years = array_values(array_unique($years));
                        $programs = array_values(array_unique($programs));
                        $search = strtolower($code . ' ' . $l['name'] . ' ' . $l['dept'] . ' ' . $l['email'] . ' ' . implode(' ', $l['courses']));
                        ?>
                        <tr data-programs="<?= htmlspecialchars(implode(',', $programs)) ?>"
                            data-years="<?= htmlspecialchars(implode(',', $years)) ?>"
                            data-search="<?= htmlspecialchars($search) ?>">
                            <td><span class="pill pill-muted"><?= htmlspecialchars($code) ?></span></td>
                            <td>
                                <div class="lec-identity">
                                    <span class="lec-avatar"><?= htmlspecialchars(lecturerInitials($l['name'])) ?></span>
                                    <span>
                                        <span class="lec-name"><?= htmlspecialchars($l['name']) ?></span>
                                        <span class="lec-dept"><?= htmlspecialchars($l['dept']) ?></span>
                                    </span>
                                </div>
                            </td>
                            <td class="lec-email"><?= htmlspecialchars($l['email']) ?></td>
                            <td>
                                <div class="tag-row">
                                    <?php foreach ($l['courses'] as $cc): ?>
                                        <span class="tag tag-course"><?= htmlspecialchars($cc) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="dir-empty" id="lecturersEmpty" <?= $total ? 'hidden' : '' ?>>
                <?= $total ? 'No lecturers match your search.' : 'No lecturers yet.' ?>
            </p>
        </div>
    </div>
</div>

<script src="/js/lecturers.js"></script>
