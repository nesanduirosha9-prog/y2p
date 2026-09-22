<?php

// Lecture Halls: directory of teaching spaces. $rooms comes straight from
// RoomModel (an empty database renders an empty table). Search runs
// client-side; editing a hall's capacity/type persists via PUT
// /lecture-halls/{code} (LectureHallsController::update), handled in
// lecture_halls.js.

$typeLabels = [
    'lab' => 'Laboratory',
    'lecture_hall' => 'Lecture Hall',
    'tutorial_room' => 'Tutorial Room',
    'other' => 'Other',
];

$total = count($rooms);
?>

<div class="halls-view">

    <div class="page-head">
        <p class="page-head-sub">Manage teaching spaces by name, capacity and type.</p>
    </div>

    <div class="dir-controls">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="hallSearch" placeholder="Search halls&hellip;" autocomplete="off">
        </div>
    </div>

    <div class="dir-card">
        <div class="dir-scroll">
            <table class="dir-table" id="hallsTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Capacity</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $r): ?>
                        <?php
                        $typeLabel = $typeLabels[$r['type']] ?? ucfirst($r['type']);
                        $search = strtolower($r['code'] . ' ' . $typeLabel);
                        ?>
                        <tr data-search="<?= htmlspecialchars($search) ?>"
                            data-code="<?= htmlspecialchars($r['code']) ?>"
                            data-type="<?= htmlspecialchars($r['type']) ?>"
                            data-capacity="<?= (int) $r['capacity'] ?>">
                            <td><span class="cell-code"><?= htmlspecialchars($r['code']) ?></span></td>
                            <td class="hall-capacity"><?= (int) $r['capacity'] ?></td>
                            <td><span class="pill pill-muted hall-type"><?= htmlspecialchars($typeLabel) ?></span></td>
                            <td>
                                <button type="button" class="link-action" data-edit-hall>Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="dir-empty" id="hallsEmpty" <?= $total ? 'hidden' : '' ?>>
                <?= $total ? 'No halls match your search.' : 'No lecture halls yet.' ?>
            </p>
        </div>
    </div>
</div>

<!-- Edit Lecture Hall Side Drawer (Slide-out panel matching media_1790082539024.png) -->
<div class="side-drawer-overlay" id="hallModal" hidden>
    <div class="side-drawer" role="dialog" aria-modal="true" aria-labelledby="hallModalTitle">
        <div class="side-drawer-header">
            <div>
                <h3 class="side-drawer-title" id="hallModalTitle">Edit Lecture Hall</h3>
                <p class="side-drawer-subtitle">Update capacity and venue type configuration</p>
            </div>
            <button type="button" class="side-drawer-close" data-close aria-label="Close drawer"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form class="side-drawer-body" id="hallForm">
            <input type="hidden" id="hallEditCode" value="">

            <div class="form-row">
                <label for="hallFieldName">Venue Code / Name</label>
                <input type="text" id="hallFieldName" disabled>
            </div>

            <div class="field-grid">
                <div class="form-row">
                    <label for="hallFieldCapacity">Capacity (Seats)</label>
                    <input type="number" id="hallFieldCapacity" min="1" required>
                </div>
                <div class="form-row">
                    <label for="hallFieldType">Venue Type</label>
                    <select id="hallFieldType" required>
                        <?php foreach ($typeLabels as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <p class="form-error" id="hallFormError" hidden></p>
        </form>

        <div class="side-drawer-footer">
            <button type="button" class="btn-drawer-cancel" data-close>Cancel</button>
            <button type="submit" form="hallForm" class="btn-drawer-submit" id="hallSubmitBtn">Save Changes</button>
        </div>
    </div>
</div>

<script src="/js/lecture_halls.js"></script>
