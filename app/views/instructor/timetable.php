<?php

use app\core\ViewHelpers;

$days = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday'];
$hours = [8, 9, 10, 11, 12, 13, 14, 15, 16];

function weekDateObj(string $dayKey): DateTime
{
    static $offset = ['mon' => 0, 'tue' => 1, 'wed' => 2, 'thu' => 3, 'fri' => 4];
    $monday = new DateTime('monday this week');
    $monday->modify('+' . $offset[$dayKey] . ' days');
    return $monday;
}

$occupied = [];
foreach ($sessions as $s) {
    // Map array keys to the new structure
    $day = $s['day_of_week'];
    $start = $s['start_hour'];
    $duration = $s['duration_hours'];

    for ($i = 0; $i < $duration; $i++) {
        $occupied[$day][$start + $i] = $i === 0 ? $s : 'busy';
    }
}

// Group flat $requests rows into arrays of slots per request_id
// e.g. requestGroups[0] = [ {mon 14:00}, {wed 8:00} ] for request_id 1
$requestGroups = [];
foreach ($requests as $rs) {
    $requestGroups[$rs['request_id']][] = [
        'course_code'      => $rs['course_code'],
        'request_id'      => $rs['request_id'],
        'status'           => $rs['status'],
        'description'      => $rs['description'],
        'day_of_week'      => $rs['day_of_week'],
        'start_hour'       => $rs['start_hour'],
        'duration_hours'   => $rs['duration_hours'],
        'weeks'            => $rs['weeks']
    ];
}
$requestGroups = array_values($requestGroups); // reindex to a plain numeric array
?>

<div class="tt-view">
    <div class="tt-grid-card">
        <div class="tt-grid" id="timetableGrid" style="position: relative;">
            <svg id="connectionLines" style="position: absolute; top:0; left:0; width:100%; height:100%; pointer-events: none; z-index: 50; overflow: visible;opacity:0.5"></svg>
            <!-- Top-Left intersection: Calendar icon injected here -->
            <!-- <div class="tt-grid-corner">
                <div class="calendar-picker-wrap" title="Select week">
                    <i class="fa-solid fa-calendar-days"></i>
                    <input type="date" id="weekPicker" aria-label="Select week">
                </div>
            </div> -->

    <div class="tt-grid-corner">
        <div class="calendar-picker-wrap" title="Select week" id="calendarPickerWrap">
            <i class="fa-solid fa-calendar-days"></i>
            <input type="date" id="weekPicker" aria-label="Select week" style="display:none;">
        </div>
     <?php require_once \app\core\Application::$ROOT_DIR . '/views/components/mini_calendar.php'; ?>
    </div>

            <?php foreach ($days as $dayKey => $label): ?>
                <div class="tt-grid-day-head" data-day-key="<?= $dayKey ?>">
                    <span class="tt-day-abbr"><?= strtoupper(substr($label, 0, 3)) ?></span>
                    <span class="tt-day-num"><?= weekDateObj($dayKey)->format('j') ?></span>
                </div>
            <?php endforeach; ?>

            <?php foreach ($hours as $rowIndex => $h): ?>
                <div class="tt-grid-time <?= $h === 12 ? 'tt-time-lunch' : '' ?>" style="grid-column: 1; grid-row: <?= $rowIndex + 2 ?>;">
                    <span><?= ViewHelpers::hourLabel($h) ?></span>
                </div>
                <?php foreach ($days as $dayKey => $dayLabel):
                    $cell = $occupied[$dayKey][$h] ?? null;
                    $isLunch = $h === 12;
                ?>
                    <?php if ($cell === 'busy'): ?>
                        <!-- Skip rendering for trailing hours of a multi-hour session -->
                    <?php elseif (is_array($cell)): ?>
                        <div class="tt-block type-<?= htmlspecialchars($cell['session_type']) ?>"
                             style="grid-column: <?= array_search($dayKey, array_keys($days)) + 2 ?>; grid-row: <?= $rowIndex + 2 ?> / span <?= $cell['duration_hours'] ?>; cursor: pointer;"
                             data-course-code="<?= htmlspecialchars($cell['course_code']) ?>"
                             data-day-of-week="<?= htmlspecialchars($dayKey) ?>"
                             data-start-hour="<?= htmlspecialchars($h) ?>">
                            <p class="tt-block-code"><?= htmlspecialchars($cell['course_code']) ?></p>
                            <p class="tt-block-title"><?= htmlspecialchars($cell['course_name']) ?></p>
                            <?php if (!empty($cell['location'])): ?>
                                <p class="tt-block-loc"><?= htmlspecialchars($cell['location']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="tt-cell <?= $isLunch ? 'tt-cell-lunch' : '' ?>"
                             style="grid-column: <?= array_search($dayKey, array_keys($days)) + 2 ?>; grid-row: <?= $rowIndex + 2 ?>;">
                           
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>

                    <!------------------------------>

            <?php
                $lunchRowIndex = array_search(12, $hours) + 2;
                $dayColumnSpan = count($days);
            ?>
            <div id="staffFabContainer"
                 style="grid-column: 2 / span <?= $dayColumnSpan ?>; grid-row: <?= $lunchRowIndex ?>;">
                <button type="button" id="staffFab" title="Assign Staff" aria-label="Assign Staff">
                    <i class="fa-solid fa-user-tie"></i>
                </button>

                <div id="staffSearchBar" class="collapsed">
                    <button type="button" id="staffSearchCancel" aria-label="Cancel">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <input type="text" id="staffSearchInput" placeholder="Click a session to assign staff...">
                    <button type="button" id="staffSearchSend" aria-label="Confirm">
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- <div id="staffFabContainer"> -->
    <!-- Collapsed state: round icon button -->
    <!-- <button type="button" id="staffFab" title="Assign Staff" aria-label="Assign Staff">
        <i class="fa-solid fa-user-tie"></i>
    </button> -->

    <!-- Expanded state: search-bar style -->
    <!-- <div id="staffSearchBar" class="collapsed">
        <button type="button" id="staffSearchCancel" aria-label="Cancel">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <input type="text" id="staffSearchInput" placeholder="Click a session to assign staff...">
        <button type="button" id="staffSearchSend" aria-label="Confirm">
            <i class="fa-solid fa-arrow-right"></i>
        </button>
    </div>
</div> -->

<?php require_once \app\core\Application::$ROOT_DIR . '/views/components/staff_assign_panel.php'; ?>
</div>

<script id="requestedSlotsData" type="application/json">
<?= json_encode(
        $requestGroups,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>
</script>
<?php require_once \app\core\Application::$ROOT_DIR . '/views/components/slot_request_panel.php'; ?>
<?php require_once \app\core\Application::$ROOT_DIR . '/views/components/request_detail_panel.php'; ?>
<?php require_once \app\core\Application::$ROOT_DIR . '/views/components/staff_slot_info_modal.php'; ?>

<script src="/js/instructor/timetable.js"></script>
<script src="/js/instructor/mini-calendar.js"></script>