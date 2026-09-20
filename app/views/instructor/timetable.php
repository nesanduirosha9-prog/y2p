<?php

// Instructor Timetable View — matches the Figma "My Timetable" / "Student Timetable"
// wireframes (file SWhtq4yyK9R8QerlDiK40v, page "Instructor"). Session actions
// (Request Support Staff / Request Time Change / My Requests) are DOM-only demo
// flows — nothing persists server-side.
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

function weekDateFor(string $dayKey): string
{
    static $offset = ['mon' => 0, 'tue' => 1, 'wed' => 2, 'thu' => 3, 'fri' => 4];
    $monday = new DateTime('monday this week');
    $monday->modify('+' . $offset[$dayKey] . ' days');
    return $monday->format('D, j M Y');
}

$occupied = [];
foreach ($sessions as $s) {
    for ($i = 0; $i < $s['duration']; $i++) {
        $occupied[$s['day']][$s['start'] + $i] = $i === 0 ? $s : 'busy';
    }
}

// One hardcoded demo "assignment" block (matches the Figma AssignmentDetails /
// ReqTimeChange screens) so the Request Time Change flow has something to open.
// Only added if it doesn't collide with real seeded sessions.
$demoAssignment = [
    'day' => 'thu', 'start' => 14, 'duration' => 1,
    'code' => 'CS3401', 'title' => 'Mid-Sem Lab Assessment',
    'location' => 'Lab A-201', 'type' => 'assignment',
];
if ($dept === 'cs' && empty($occupied[$demoAssignment['day']][$demoAssignment['start']])) {
    $occupied[$demoAssignment['day']][$demoAssignment['start']] = $demoAssignment;
    $sessions[] = $demoAssignment;
}

$batchLabel = 'Y' . $year . ' ' . strtoupper($dept);
$myCourseCodes = array_values(array_unique(array_column($sessions, 'code')));
?>

<div class="tt-view" data-dept="<?= htmlspecialchars($dept) ?>" data-sem="<?= $sem ?>" data-year="<?= $year ?>">

    <div class="tt-toolbar">
        <div class="tt-filters">
            <div class="segmented" id="ttModeToggle">
                <button type="button" class="segmented-btn active" data-mode="my">My Timetable</button>
                <button type="button" class="segmented-btn" data-mode="student">Student Timetable</button>
            </div>
            <div class="v-divider"></div>
            <div class="tt-inline-filters" id="myTimetableFilters">
                <div class="segmented segmented-dark">
                    <a class="segmented-btn <?= $sem === 1 ? 'active' : '' ?>" href="<?= ttUrl($dept, 1, $year) ?>">Sem 1</a>
                    <a class="segmented-btn <?= $sem === 2 ? 'active' : '' ?>" href="<?= ttUrl($dept, 2, $year) ?>">Sem 2</a>
                </div>
            </div>
            <div class="tt-inline-filters" id="studentTimetableFilters" hidden>
                <div class="segmented segmented-dark" id="stDeptToggle">
                    <button type="button" class="segmented-btn active" data-dept="cs">CS</button>
                    <button type="button" class="segmented-btn" data-dept="is">IS</button>
                </div>
                <div class="segmented" id="stYearToggle">
                    <button type="button" class="segmented-btn active" data-year="1">Y1</button>
                    <button type="button" class="segmented-btn" data-year="2">Y2</button>
                    <button type="button" class="segmented-btn" data-year="3">Y3</button>
                    <button type="button" class="segmented-btn" data-year="4">Y4</button>
                </div>
            </div>
        </div>

        <div class="tt-actions" id="ttActionsMy">
            <button type="button" class="btn-outline-sm" id="selectSlotsBtn">
                <i class="fa-solid fa-list-check"></i> Select Slots
            </button>
            <button type="button" class="btn-dark" id="myRequestsBtn">
                <i class="fa-solid fa-clipboard-list"></i> My Requests
                <span class="req-badge" id="myRequestsBadge" hidden></span>
            </button>
        </div>
    </div>

    <div class="tt-body" id="ttBody">
        <div id="myTimetableSection" class="tt-section">
          <div class="tt-grid-row">
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
                                     data-duration="<?= $cell['duration'] ?>"
                                     data-batch="<?= htmlspecialchars($batchLabel) ?>"
                                     data-date="<?= htmlspecialchars(weekDateFor($dayKey)) ?>">
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
                <span class="legend-item"><i class="legend-dot dot-assignment"></i>Assignment</span>
                <span class="legend-hint" id="legendHint">Click any session for details · use Select Slots to request support staff</span>
            </div>
        </div>

        <div id="studentTimetableSection" class="tt-section" hidden>
            <div class="st-free-bar" id="stFreeBar"></div>
            <div class="tt-grid-card">
                <div class="tt-grid" id="stGrid"></div>
            </div>
            <div class="tt-legend">
                <span class="legend-item"><i class="legend-dot dot-mine"></i>Your courses</span>
                <span class="legend-item"><i class="legend-dot dot-lab"></i>Lab</span>
                <span class="legend-item"><i class="legend-dot dot-practical"></i>Practical</span>
                <span class="legend-item"><i class="legend-dot dot-lecture"></i>Lecture</span>
                <span class="legend-item"><i class="legend-dot dot-assignment"></i>Assignment</span>
                <span class="legend-hint" id="stCaption">CS Y1 schedule · ★ = your courses · empty cells = students are free</span>
            </div>
        </div>

        <aside class="tt-side-panel" id="ttSidePanel" hidden>
            <div class="tsp-header">
                <div>
                    <h2 id="tspTitle">&mdash;</h2>
                    <p id="tspSubtitle"></p>
                </div>
                <button type="button" class="modal-close" id="tspClose"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="tsp-body" id="tspBody"></div>
            <div class="tsp-footer" id="tspFooter"></div>
        </aside>
    </div>

    <div class="tt-confirm-bar" id="selectConfirmBar" hidden>
        <div class="confirm-summary">
            <span class="confirm-dot"></span>
            <span id="selectConfirmText">0 slots selected</span>
        </div>
        <div class="confirm-buttons">
            <button type="button" class="btn-ghost" id="cancelSelectBtn">Cancel</button>
            <button type="button" class="btn-primary-sm" id="requestStaffFromSelectBtn" disabled>Request Support Staff</button>
        </div>
    </div>
</div>

<script>
    window.__ttMyCourseCodes = <?= json_encode($myCourseCodes) ?>;
</script>
<script src="/js/instructor/timetable.js"></script>
