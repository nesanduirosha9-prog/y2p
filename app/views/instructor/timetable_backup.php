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
    for ($i = 0; $i < $s['duration']; $i++) {
        $occupied[$s['day']][$s['start'] + $i] = $i === 0 ? $s : 'busy';
    }
}

// Assignment block placeholder
$demoAssignment = [
    'day' => 'thu', 'start' => 14, 'duration' => 1,
    'code' => 'CS3401', 'title' => 'Mid-Sem Lab Assessment',
    'location' => 'Lab A-201', 'type' => 'assignment',
];
if ($dept === 'cs' && empty($occupied[$demoAssignment['day']][$demoAssignment['start']])) {
    $occupied[$demoAssignment['day']][$demoAssignment['start']] = $demoAssignment;
    $sessions[] = $demoAssignment;
}
?>

<div class="tt-view">
    <div class="tt-grid-card">
        <div class="tt-grid">
            <!-- Top-Left intersection: Calendar icon injected here -->
            <div class="tt-grid-corner">
                <div class="calendar-picker-wrap" title="Select week">
                    <i class="fa-solid fa-calendar-days"></i>
                    <input type="date" id="weekPicker" aria-label="Select week">
                </div>
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
                    <?php elseif (is_array($cell)): ?>
                        <div class="tt-block type-<?= $cell['type'] ?>"
                             style="grid-column: <?= array_search($dayKey, array_keys($days)) + 2 ?>; grid-row: <?= $rowIndex + 2 ?> / span <?= $cell['duration'] ?>;">
                            <p class="tt-block-code"><?= htmlspecialchars($cell['code']) ?></p>
                            <p class="tt-block-title"><?= htmlspecialchars($cell['title']) ?></p>
                            <p class="tt-block-loc"><?= htmlspecialchars($cell['location']) ?></p>
                        </div>
                    <?php else: ?>
                        <div class="tt-cell <?= $isLunch ? 'tt-cell-lunch' : '' ?>"
                             style="grid-column: <?= array_search($dayKey, array_keys($days)) + 2 ?>; grid-row: <?= $rowIndex + 2 ?>;">
                            <?= $isLunch && $dayKey === 'wed' ? '<span class="lunch-label">Lunch</span>' : '' ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="/js/instructor/timetable.js"></script>