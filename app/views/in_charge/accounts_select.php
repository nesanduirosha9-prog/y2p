<?php
// Step 2 of the handover flow — search & pick the replacement lecturer.
// $position / $holder / $candidates come from AccountsController::selectView(),
// or from add() — where $holder is null because nobody is being replaced, only
// one more Coordinator added.
$positionLabels = [
    'coordinator' => 'Coordinator',
    'in_charge' => 'In-Charge',
    'timetable_officer' => 'Timetable Officer',
];
$isAdd = $holder === null;
$candidateNoun = $position === 'coordinator' ? 'junior staff member' : 'lecturer';
?>

<div class="accounts-view" data-position="<?= htmlspecialchars($position) ?>" data-from-code="<?= $isAdd ? '' : htmlspecialchars($holder['code']) ?>">
    <div class="page-head">
        <div>
            <h2><?= $isAdd ? 'Add' : 'Change' ?> <?= htmlspecialchars($positionLabels[$position]) ?></h2>
            <p class="page-head-sub">
                <?= $isAdd
                    ? 'Choose the ' . $candidateNoun . ' who will join the current Coordinators. Only Junior Staff without a role are listed.'
                    : 'Search ' . $candidateNoun . ' by name.' ?>
            </p>
        </div>
    </div>

    <div class="dir-card handover-card">
        <div class="search-box handover-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="candidateSearch" placeholder="Search <?= htmlspecialchars($candidateNoun) ?> by name&hellip;" autocomplete="off">
        </div>

        <div class="candidate-list" id="candidateList">
            <?php foreach ($candidates as $c): ?>
                <label class="candidate-row" data-search="<?= htmlspecialchars(strtolower($c['name'] . ' ' . $c['email'])) ?>">
                    <input type="radio" name="candidate" value="<?= htmlspecialchars($c['code']) ?>">
                    <?= \app\core\ViewHelpers::codeBadge($c['code'], ($c['academic_rank'] ?? '') === 'senior' ? 'lecturer' : 'staff') ?>
                    <span class="candidate-id">
                        <span class="candidate-name"><?= htmlspecialchars($c['name']) ?></span>
                        <span class="candidate-email"><?= htmlspecialchars($c['email']) ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
            <p class="dir-empty" id="candidateEmpty" <?= count($candidates) ? 'hidden' : '' ?>>No eligible <?= htmlspecialchars($candidateNoun) ?>s found.</p>
        </div>

        <?php if ($position === 'timetable_officer'): ?>
            <div class="form-row handover-rank-row">
                <label for="fromNewRank"><?= htmlspecialchars($holder['name']) ?>'s new rank after stepping down</label>
                <select id="fromNewRank">
                    <option value="">Select rank&hellip;</option>
                    <option value="junior">Junior Staff Member</option>
                    <option value="senior">Lecturer</option>
                </select>
            </div>
        <?php endif; ?>

        <div class="modal-foot handover-actions">
            <a class="btn-cancel" href="<?= $isAdd ? '/settings#handover' : '/settings/handover/change/' . urlencode($position) . '/' . urlencode($holder['code']) ?>">Back</a>
            <button type="button" class="btn-block" id="btnConfirmCandidate" disabled>Confirm</button>
        </div>
    </div>
</div>

<script src="/js/in_charge/accounts.js"></script>
