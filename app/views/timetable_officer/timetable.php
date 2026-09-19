<?php

// Timetable view: department/semester/year filters + weekly grid + the
// "Schedule Course" selection -> modal -> add-to-grid flow.
// $courses / $sessions come from CourseModel / TimetableSessionModel via
// TimetableController. The "Add to Timetable" step is still client-side only
// (see timetable.js) — persisting it needs a POST route + TimetableSessionModel::create().

$days = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday'];
$hours = [8, 9, 10, 11, 12, 13, 14, 15, 16];

function hourLabel(int $h): string
{
    $suffix = $h < 12 ? 'AM' : 'PM';
    $display = $h % 12 === 0 ? 12 : $h % 12;
    return "{$display} {$suffix}";
}

function ttUrl(string $dept, int $sem, int $year): string
{
    return '/timetable?dept=' . urlencode($dept) . '&sem=' . $sem . '&year=' . $year;
}

// Map each occupied (day, hour) cell to the session that starts there, or 'busy' for
// a continuation cell so the grid knows what NOT to render as clickable/free.
$occupied = [];
foreach ($sessions as $s) {
    for ($i = 0; $i < $s['duration']; $i++) {
        $occupied[$s['day']][$s['start'] + $i] = $i === 0 ? $s : 'busy';
    }
}

$locations = array_values(array_unique(array_filter(array_map(fn($s) => $s['location'], $sessions))));
$lecturers = array_values(array_unique(array_map(fn($c) => $c['lecturer'], $courses)));
?>

<div class="tt-view" data-dept="<?= htmlspecialchars($dept) ?>" data-sem="<?= $sem ?>" data-year="<?= $year ?>">

    <div class="tt-toolbar">
        <div class="tt-filters">
            <div class="segmented">
                <a class="segmented-btn <?= $dept === 'cs' ? 'active' : '' ?>" href="<?= ttUrl('cs', $sem, $year) ?>">Computer Science</a>
                <a class="segmented-btn <?= $dept === 'is' ? 'active' : '' ?>" href="<?= ttUrl('is', $sem, $year) ?>">Information Systems</a>
            </div>
            <div class="v-divider"></div>
            <div class="segmented segmented-dark">
                <a class="segmented-btn <?= $sem === 1 ? 'active' : '' ?>" href="<?= ttUrl($dept, 1, $year) ?>">Sem 1</a>
                <a class="segmented-btn <?= $sem === 2 ? 'active' : '' ?>" href="<?= ttUrl($dept, 2, $year) ?>">Sem 2</a>
            </div>
        </div>

        <div class="tt-actions">
            <select class="tt-select" id="lecturerFilter">
                <option value="">All Lecturers</option>
                <?php foreach ($lecturers as $l): ?>
                    <option value="<?= htmlspecialchars($l) ?>"><?= htmlspecialchars($l) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="tt-select" id="roomFilter">
                <option value="">All Rooms</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="v-divider"></div>
            <button type="button" class="btn-dark" id="scheduleToggleBtn">
                <i class="fa-solid fa-plus"></i> Schedule Course
            </button>
        </div>
    </div>

    <div class="tt-body">
        <div class="tt-year-tabs">
            <?php foreach ([1, 2, 3, 4] as $y): ?>
                <a class="year-tab <?= $year === $y ? 'active' : '' ?>" href="<?= ttUrl($dept, $sem, $y) ?>">Y<?= $y ?></a>
            <?php endforeach; ?>
        </div>

        <div class="tt-grid-card">
            <div class="tt-grid">
                <div class="tt-grid-corner"></div>
                <?php foreach ($days as $label): ?>
                    <div class="tt-grid-day-head"><?= $label ?></div>
                <?php endforeach; ?>

                <?php foreach ($hours as $rowIndex => $h): ?>
                    <div class="tt-grid-time"><?= hourLabel($h) ?></div>
                    <?php foreach ($days as $dayKey => $dayLabel):
                        $cell = $occupied[$dayKey][$h] ?? null;
                        $isLunch = $h === 12;
                    ?>
                        <?php if ($cell === 'busy'): ?>
                            <?php // continuation row of a multi-hour session above — render nothing here ?>
                        <?php elseif (is_array($cell)): ?>
                            <div class="tt-block type-<?= $cell['type'] ?>"
                                 style="grid-column: <?= array_search($dayKey, array_keys($days)) + 2 ?>; grid-row: <?= $rowIndex + 2 ?> / span <?= $cell['duration'] ?>;"
                                 data-code="<?= htmlspecialchars($cell['code']) ?>"
                                 data-title="<?= htmlspecialchars($cell['title']) ?>"
                                 data-location="<?= htmlspecialchars($cell['location']) ?>"
                                 data-type="<?= htmlspecialchars($cell['type']) ?>"
                                 data-day="<?= htmlspecialchars($dayLabel) ?>"
                                 data-start="<?= hourLabel($h) ?>"
                                 data-duration="<?= $cell['duration'] ?>">
                                <p class="tt-block-code"><?= htmlspecialchars($cell['code']) ?></p>
                                <p class="tt-block-title"><?= htmlspecialchars($cell['title']) ?></p>
                                <p class="tt-block-loc"><?= htmlspecialchars($cell['location']) ?></p>
                            </div>
                        <?php else: ?>
                            <div class="tt-cell <?= $isLunch ? 'tt-cell-lunch' : '' ?>"
                                 style="grid-column: <?= array_search($dayKey, array_keys($days)) + 2 ?>; grid-row: <?= $rowIndex + 2 ?>;"
                                 data-day="<?= htmlspecialchars($dayLabel) ?>"
                                 data-day-key="<?= $dayKey ?>"
                                 data-hour="<?= $h ?>"
                                 data-hour-label="<?= hourLabel($h) ?>"
                                 <?= $isLunch ? 'data-lunch="1"' : '' ?>>
                                <?= $isLunch && $dayKey === 'wed' ? '<span class="lunch-label">Lunch Break</span>' : '' ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="tt-legend">
        <span class="legend-item"><i class="legend-dot dot-lecture"></i>Lecture</span>
        <span class="legend-item"><i class="legend-dot dot-tutorial"></i>Tutorial</span>
        <span class="legend-item"><i class="legend-dot dot-lab"></i>Lab</span>
        <span class="legend-item"><i class="legend-dot dot-practical"></i>Practical</span>
        <span class="legend-hint" id="legendHint">Click any session block for details</span>
    </div>

    <div class="tt-confirm-bar" id="confirmBar" hidden>
        <div class="confirm-summary">
            <span class="confirm-dot"></span>
            <span id="slotsCountText">0 slots selected</span>
            <span id="slotsDayText" class="confirm-day"></span>
        </div>
        <div class="confirm-buttons">
            <button type="button" class="btn-ghost" id="clearSelectionBtn">Clear</button>
            <button type="button" class="btn-primary-sm" id="confirmSelectionBtn" disabled>Confirm Selection &rarr;</button>
        </div>
    </div>
</div>

<!-- Schedule Course Session modal -->
<div class="tt-modal-overlay" id="scheduleModal" hidden>
    <div class="tt-modal">
        <div class="tt-modal-header">
            <div>
                <h2>Schedule Course Session</h2>
                <p id="modalSubtitle"><?= strtoupper(htmlspecialchars($dept)) ?> &middot; Year <?= $year ?> &middot; Sem <?= $sem ?></p>
            </div>
            <button type="button" class="modal-close" id="closeScheduleModal"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="tt-modal-body">
            <div class="selected-slots-card">
                <div class="selected-slots-icon"><i class="fa-regular fa-clock"></i></div>
                <div>
                    <p class="field-label">Selected Time Slots</p>
                    <p class="selected-slots-range" id="selectedSlotsRange">&mdash;</p>
                    <p class="selected-slots-total" id="selectedSlotsTotal"></p>
                </div>
            </div>

            <div class="form-field">
                <label for="courseModule">Course Module</label>
                <div class="select-wrap">
                    <i class="fa-solid fa-book"></i>
                    <select id="courseModule">
                        <option value="">Select a course&hellip;</option>
                        <?php foreach ($courses as $code => $c): ?>
                            <option value="<?= htmlspecialchars($code) ?>" data-lecturer="<?= htmlspecialchars($c['lecturer']) ?>">
                                <?= htmlspecialchars($code) ?> &mdash; <?= htmlspecialchars($c['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </div>
                <p class="field-hint" id="lecturerHint">&nbsp;</p>
            </div>

            <div class="form-field">
                <label>Session Type</label>
                <div class="type-toggle" id="sessionTypeToggle">
                    <button type="button" class="type-btn active" data-type="lecture">Lecture</button>
                    <button type="button" class="type-btn" data-type="tutorial">Tutorial</button>
                    <button type="button" class="type-btn" data-type="lab">Lab</button>
                    <button type="button" class="type-btn" data-type="practical">Practical</button>
                </div>
            </div>

            <div class="form-field">
                <label for="venueInput">Venue</label>
                <div class="select-wrap">
                    <i class="fa-solid fa-location-dot"></i>
                    <input type="text" id="venueInput" placeholder="e.g. Lab A-201, LT-301&hellip;">
                </div>
            </div>
        </div>

        <div class="tt-modal-footer">
            <button type="button" class="btn-outline" id="modalBackBtn">Back</button>
            <button type="button" class="btn-primary-sm" id="addToTimetableBtn">Add to Timetable</button>
        </div>
    </div>
</div>

<!-- Read-only session details modal -->
<div class="tt-modal-overlay" id="detailsModal" hidden>
    <div class="tt-modal tt-modal-sm">
        <div class="tt-modal-header">
            <div>
                <h2 id="detailsCode">&mdash;</h2>
                <p id="detailsTitle"></p>
            </div>
            <button type="button" class="modal-close" id="closeDetailsModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="tt-modal-body">
            <p class="detail-row"><i class="fa-solid fa-location-dot"></i> <span id="detailsLocation"></span></p>
            <p class="detail-row"><i class="fa-regular fa-clock"></i> <span id="detailsWhen"></span></p>
            <p class="detail-row"><i class="fa-solid fa-tag"></i> <span id="detailsType"></span></p>
        </div>
        <div class="tt-modal-footer">
            <button type="button" class="btn-primary-sm" id="closeDetailsBtn2">Close</button>
        </div>
    </div>
</div>

<script src="/js/timetable.js"></script>
