<?php

// Instructor Timetable View
// Reuses the styling from timetable_officer but adapted for instructor role.
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
    return '/instructor/timetable?dept=' . urlencode($dept) . '&sem=' . $sem . '&year=' . $year;
}

$occupied = [];
foreach ($sessions as $s) {
    for ($i = 0; $i < $s['duration']; $i++) {
        $occupied[$s['day']][$s['start'] + $i] = $i === 0 ? $s : 'busy';
    }
}
?>

<div class="tt-view" data-dept="<?= htmlspecialchars($dept) ?>" data-sem="<?= $sem ?>" data-year="<?= $year ?>">

    <div class="tt-toolbar">
        <div class="tt-filters">
            <div class="segmented">
                <a class="segmented-btn active" href="#">My Timetable</a>
                <a class="segmented-btn" href="#">Student Timetable</a>
            </div>
            <div class="v-divider"></div>
            <div class="segmented segmented-dark">
                <a class="segmented-btn <?= $sem === 1 ? 'active' : '' ?>" href="<?= ttUrl($dept, 1, $year) ?>">Sem 1</a>
                <a class="segmented-btn <?= $sem === 2 ? 'active' : '' ?>" href="<?= ttUrl($dept, 2, $year) ?>">Sem 2</a>
            </div>
        </div>

        <div class="tt-actions">
            <button type="button" class="btn-dark" id="addAssignmentBtn">
                <i class="fa-solid fa-plus"></i> Add Assignment
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

<script src="/js/instructor/timetable.js"></script>
