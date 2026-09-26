<?php

use app\core\ViewHelpers;

// Timetable view: department/semester/year filters + weekly grid + the
// side panel for slot details (edit/save/delete), publish button, and
// past academic years archive dropdown.

$days = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday'];
$hours = [8, 9, 10, 11, 12, 13, 14, 15, 16];

function ttUrl(string $dept, int $sem, int $year): string
{
    return '/timetable?dept=' . urlencode($dept) . '&sem=' . $sem . '&year=' . $year;
}

// Every session starting in each (day, hour) slot — more than one when sessions
// overlap, and those share the slot side by side in lanes — plus which hours
// are covered, so the grid knows what NOT to render as a clickable free cell.
$layout = ViewHelpers::timetableLanes($sessions);

// Filter options come from the database, not from what happens to be on the
// grid: every hall/lab in `rooms` (so one added under Lecture Halls shows up
// at once), and every lecturer assigned to this grid's courses, one entry per
// person even when a course has several.
$allRooms = $rooms ?? [];
$locations = array_column($allRooms, 'code');
$lecturers = array_values(array_unique(array_merge([], ...array_map(fn($c) => $c['lecturers'], array_values($courses)))));
sort($lecturers);
?>

<div class="tt-view" data-dept="<?= htmlspecialchars($dept) ?>" data-sem="<?= $sem ?>" data-year="<?= $year ?>">

    <!-- Top header with Academic Year dropdown & Publish button -->
    <div class="tt-header-bar">
        <div class="tt-header-left">
            <div class="academic-year-wrap">
                <label for="academicYearSelect"><i class="fa-regular fa-calendar-days"></i> Academic Year:</label>
                <div class="select-wrap-sm">
                    <select id="academicYearSelect" class="tt-select-sm">
                        <option value="2024/2025" selected>2024/2025 (Current)</option>
                        <option value="2023/2024">2023/2024 (Archive)</option>
                        <option value="2022/2023">2022/2023 (Archive)</option>
                    </select>
                </div>
            </div>
            <div class="pub-status-wrap">
                <span class="pub-badge published" id="pubBadge">
                    <i class="fa-solid fa-circle-check"></i> Published
                </span>
            </div>
        </div>

        <div class="tt-header-right">
            <button type="button" class="btn-publish" id="publishTimetableBtn">
                <i class="fa-solid fa-cloud-arrow-up"></i> Publish Timetable
            </button>
        </div>
    </div>

    <!-- Archive Banner for Past Years 
    <div class="tt-archive-notice" id="archiveNotice" hidden>
        <div class="archive-notice-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div class="archive-notice-content">
            <p class="archive-notice-title">Viewing Past Academic Year: <span id="archiveYearLabel">2023/2024</span> (Read-Only Archive)</p>
            <p class="archive-notice-sub">Historical timetables are locked for auditing. Editing, scheduling, and publishing are disabled.</p>
        </div>
        <button type="button" class="btn-archive-exit" id="returnToCurrentYearBtn">
            <i class="fa-solid fa-arrow-rotate-left"></i> Return to Current Year
        </button>
    </div>
    -->
    <!-- Toolbar with Dept/Sem filters, lecturer/room filters, and Schedule button -->
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
            <select class="tt-select" id="lecturerFilter" style="width: 180px; flex-shrink: 0; min-width: 180px;"
                    data-searchable data-search-placeholder="Search lecturers…">
                <option value="">All Lecturers</option>
                <?php foreach ($lecturers as $l): ?>
                    <option value="<?= htmlspecialchars($l) ?>"><?= htmlspecialchars($l) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="tt-select" id="roomFilter" style="width: 160px; flex-shrink: 0; min-width: 160px;"
                    data-searchable data-search-placeholder="Search rooms…">
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

    <!-- Body: Year tabs + Grid + Slide-out Side Panel -->
    <div class="tt-body">
        <div class="tt-year-tabs">
            <?php foreach ([1, 2, 3, 4] as $y): ?>
                <a class="year-tab <?= $year === $y ? 'active' : '' ?>" href="<?= ttUrl($dept, $sem, $y) ?>">Y<?= $y ?></a>
            <?php endforeach; ?>
        </div>

        <div class="tt-grid-row">
            <div class="tt-grid-card">
                <div class="tt-day-nav" id="ttDayNav">
                    <button type="button" class="tt-day-nav-btn" id="dayPrevBtn" aria-label="Previous day" title="Previous day">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <div class="tt-day-nav-info">
                        <span class="tt-day-nav-title" id="dayNavTitle">Monday</span>
                        <span class="tt-day-nav-date" id="dayNavDate">Mon</span>
                    </div>
                    <button type="button" class="tt-day-nav-btn" id="dayNextBtn" aria-label="Next day" title="Next day">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
                <div class="tt-grid" id="ttGrid">
                    <div class="tt-grid-corner"></div>
                    <?php foreach ($days as $dayKey => $label): ?>
                        <div class="tt-grid-day-head" data-day-key="<?= $dayKey ?>"><?= $label ?></div>
                    <?php endforeach; ?>

                    <?php foreach ($hours as $rowIndex => $h): ?>
                        <div class="tt-grid-time" style="grid-column: 1; grid-row: <?= $rowIndex + 2 ?>;"><?= ViewHelpers::hourLabel($h) ?></div>
                        <?php foreach ($days as $dayKey => $dayLabel):
                            $starting = $layout['starts'][$dayKey][$h] ?? [];
                            $isLunch = $h === 12;
                        ?>
                            <?php if ($starting): ?>
                                <?php foreach ($starting as $cell): $lane = ViewHelpers::laneAttrs($cell); ?>
                                <div class="tt-block type-<?= $cell['type'] ?><?= $lane['class'] ?>"
                                     style="grid-column: <?= array_search($dayKey, array_keys($days)) + 2 ?>; grid-row: <?= $rowIndex + 2 ?> / span <?= $cell['duration'] ?>;<?= $lane['style'] ?>"
                                     data-code="<?= htmlspecialchars($cell['code']) ?>"
                                     data-title="<?= htmlspecialchars($cell['title']) ?>"
                                     data-location="<?= htmlspecialchars($cell['location']) ?>"
                                     data-type="<?= htmlspecialchars($cell['type']) ?>"
                                     data-day="<?= htmlspecialchars($dayLabel) ?>"
                                     data-day-key="<?= $dayKey ?>"
                                     data-start="<?= ViewHelpers::hourLabel($h) ?>"
                                     data-start-hour="<?= $h ?>"
                                     data-duration="<?= $cell['duration'] ?>"
                                     data-lecturer="<?= htmlspecialchars($courses[$cell['code']]['lecturer'] ?? 'TBA') ?>">
                                    <p class="tt-block-code"><?= htmlspecialchars($cell['code']) ?></p>
                                    <p class="tt-block-title"><?= htmlspecialchars($cell['title']) ?></p>
                                    <p class="tt-block-loc"><?= htmlspecialchars($cell['location']) ?></p>
                                </div>
                                <?php endforeach; ?>
                            <?php elseif (isset($layout['covered'][$dayKey][$h])): ?>
                                <?php // continuation row of a multi-hour session above ?>
                            <?php else: ?>
                                <div class="tt-cell <?= $isLunch ? 'tt-cell-lunch' : '' ?>"
                                     style="grid-column: <?= array_search($dayKey, array_keys($days)) + 2 ?>; grid-row: <?= $rowIndex + 2 ?>;"
                                     data-day="<?= htmlspecialchars($dayLabel) ?>"
                                     data-day-key="<?= $dayKey ?>"
                                     data-hour="<?= $h ?>"
                                     data-hour-label="<?= ViewHelpers::hourLabel($h) ?>"
                                     <?= $isLunch ? 'data-lunch="1"' : '' ?>>
                                    <?= $isLunch && $dayKey === 'wed' ? '<span class="lunch-label">Lunch Break</span>' : '' ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Slide-out Side Panel for Session Details (View / Edit / Delete / Schedule).
                 .floating-panel (components.css) opens it over the page like the Add Course drawer.
                 Both ways of adding a session — clicking a free slot, and Schedule Course →
                 select slots → Confirm — open the same Schedule Session form here. -->
            <aside class="tt-side-panel floating-panel" id="ttSidePanel" hidden>
                <div class="tsp-header">
                    <div class="tsp-header-left">
                        <button type="button" class="tsp-btn-back" id="tspBack" aria-label="Back"><i class="fa-solid fa-arrow-left"></i></button>
                        <div>
                            <h2 id="tspTitle">&mdash;</h2>
                            <p id="tspSubtitle"></p>
                        </div>
                    </div>
                    <button type="button" class="modal-close" id="tspClose"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="tsp-body" id="tspBody"></div>
                <div class="tsp-footer" id="tspFooter"></div>
            </aside>
        </div>
    </div>

    <div class="tt-legend">
        <span class="legend-item"><i class="legend-dot dot-lecture"></i>Lecture</span>
        <span class="legend-item"><i class="legend-dot dot-tutorial"></i>Tutorial</span>
        <span class="legend-item"><i class="legend-dot dot-lab"></i>Lab</span>
        <span class="legend-item"><i class="legend-dot dot-practical"></i>Practical</span>
        <span class="legend-hint" id="legendHint">Click any slot or session block for details &amp; actions</span>
    </div>

    <!-- Multi-slot selection confirm bar (used during drag/selection mode) -->
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

<!-- Publish Confirmation Modal -->
<div class="tt-modal-overlay" id="publishModal" hidden>
    <div class="tt-modal tt-modal-sm">
        <div class="tt-modal-header">
            <div>
                <h2>Publish Timetable</h2>
                <p id="publishModalSubtitle"><?= strtoupper(htmlspecialchars($dept)) ?> &middot; Year <?= $year ?> &middot; Sem <?= $sem ?></p>
            </div>
            <button type="button" class="modal-close" id="closePublishModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="tt-modal-body">
            <div class="publish-info-card">
                <div class="publish-info-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                <div>
                    <p class="publish-info-title">Update Student Timetable</p>
                    <p class="publish-info-desc">
                        Publishing will make the current finalized schedule live across the entire system. Students and academic staff will immediately see the updated timetable.
                    </p>
                </div>
            </div>
            <p class="publish-warning-text">
                <i class="fa-solid fa-circle-check" style="color: #059669;"></i> Ready to sync all scheduled sessions to the Student Timetable.
            </p>
        </div>
        <div class="tt-modal-footer">
            <button type="button" class="btn-outline" id="cancelPublishBtn">Cancel</button>
            <button type="button" class="btn-publish-confirm" id="confirmPublishBtn">
                <i class="fa-solid fa-cloud-arrow-up"></i> Publish Now
            </button>
        </div>
    </div>
</div>

<!-- Toast Feedback Notification -->
<div class="tt-toast" id="ttToast" hidden>
    <i class="fa-solid fa-circle-check" id="toastIcon"></i>
    <span id="toastMsg">Timetable published successfully!</span>
</div>

<script>
    window.__ttCourses = <?= json_encode($courses) ?>;
    window.__ttRooms = <?= json_encode($allRooms) ?>;
    window.__ttDept = <?= json_encode($dept) ?>;
    window.__ttSem = <?= json_encode($sem) ?>;
    window.__ttYear = <?= json_encode($year) ?>;
    window.__ttInitialSessions = <?= json_encode($sessions) ?>;
</script>
<script src="/js/searchable_select.js"></script>
<script src="/js/timetable.js"></script>
