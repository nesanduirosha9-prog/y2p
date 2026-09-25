<?php
// Step 2 of the handover flow — search & pick the replacement lecturer.
// $position / $holder / $candidates come from AccountsController::selectView(),
// or from add() — where $holder is null because nobody is being replaced, only
// one more Coordinator added.
$positionLabels = [
    'coordinator' => 'Coordinator',
    'in_charge' => 'In-Charge',
];
$isAdd = $holder === null;
$candidateNoun = $position === 'coordinator' ? 'junior staff member' : 'lecturer';

// The top bar's title depends on add vs change, so it is set here rather
// than in the controller; the layout reads it after this view.
$pageTitle = ($isAdd ? 'Add ' : 'Change ') . $positionLabels[$position];
?>

<div class="accounts-view" data-position="<?= htmlspecialchars($position) ?>" data-from-code="<?= $isAdd ? '' : htmlspecialchars($holder['code']) ?>">
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

        <div class="modal-foot handover-actions">
            <a class="btn-cancel" href="<?= $isAdd ? '/settings#handover' : '/settings/handover/change/' . urlencode($position) . '/' . urlencode($holder['code']) ?>">Back</a>
            <button type="button" class="btn-block" id="btnConfirmCandidate" disabled>Confirm</button>
        </div>
    </div>
</div>

<script src="/js/in_charge/accounts.js"></script>
