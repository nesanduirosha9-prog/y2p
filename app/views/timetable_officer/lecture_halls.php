<?php

// Lecture Halls: directory of teaching spaces. $rooms comes straight from
// RoomModel (an empty database renders an empty table). Search runs
// client-side; Add / Edit / Delete all persist through LectureHallsController
// (POST /lecture-halls, PUT and DELETE /lecture-halls/{code}), called from
// lecture_halls.js. The same side drawer serves Add and Edit.

$typeLabels = [
    'lab' => 'Laboratory',
    'lecture_hall' => 'Lecture Hall',
    'tutorial_room' => 'Tutorial Room',
    'other' => 'Other',
];

$total = count($rooms);
?>

<div class="halls-view">

    <div class="dir-controls">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="hallSearch" placeholder="Search halls&hellip;" autocomplete="off">
        </div>
        <button type="button" class="btn-primary" id="addHallBtn" style="margin-left: auto;">
            <i class="fa-solid fa-plus"></i> Add Hall
        </button>
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
                                <div class="tag-row">
                                    <button type="button" class="link-action" data-edit-hall>Edit</button>
                                    <button type="button" class="icon-action danger" data-delete-hall title="Delete hall"><i class="fa-regular fa-trash-can"></i></button>
                                </div>
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

<!-- Add / Edit Lecture Hall Side Drawer — lecture_halls.js switches the title,
     subtitle, button text and whether the code field is editable. -->
<div class="side-drawer-overlay" id="hallModal" hidden>
    <div class="side-drawer" role="dialog" aria-modal="true" aria-labelledby="hallModalTitle">
        <div class="side-drawer-header">
            <div>
                <h3 class="side-drawer-title" id="hallModalTitle">Edit Lecture Hall</h3>
                <p class="side-drawer-subtitle" id="hallModalSubtitle">Update capacity and venue type configuration</p>
            </div>
            <button type="button" class="side-drawer-close" data-close aria-label="Close drawer"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form class="side-drawer-body" id="hallForm">
            <input type="hidden" id="hallEditCode" value="">

            <div class="form-row">
                <label for="hallFieldName">Venue Code / Name</label>
                <input type="text" id="hallFieldName" maxlength="20" placeholder="LT-501" disabled>
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
