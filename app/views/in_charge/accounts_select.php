<?php
// Step 2 of the handover flow — search & pick the replacement lecturer.
// $position / $holder / $candidates come from AccountsController::selectView().
$positionLabels = [
    'coordinator' => 'Coordinator',
    'in_charge' => 'In-Charge',
    'timetable_officer' => 'Timetable Officer',
];

function candidateInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(fn($p) => strtoupper(substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters);
}
?>

<div class="accounts-view" data-position="<?= htmlspecialchars($position) ?>" data-from-code="<?= htmlspecialchars($holder['code']) ?>">
    <div class="page-head">
        <div>
            <h2>Change <?= htmlspecialchars($positionLabels[$position]) ?></h2>
            <p class="page-head-sub">Search lecturer by name.</p>
        </div>
    </div>

    <div class="dir-card handover-card">
        <div class="search-box handover-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="candidateSearch" placeholder="Search lecturer by name&hellip;" autocomplete="off">
        </div>

        <div class="candidate-list" id="candidateList">
            <?php foreach ($candidates as $c): ?>
                <label class="candidate-row" data-search="<?= htmlspecialchars(strtolower($c['name'] . ' ' . $c['email'])) ?>">
                    <input type="radio" name="candidate" value="<?= htmlspecialchars($c['code']) ?>">
                    <span class="lec-avatar"><?= htmlspecialchars(candidateInitials($c['name'])) ?></span>
                    <span>
                        <span class="lec-name"><?= htmlspecialchars($c['name']) ?></span>
                        <span class="page-head-sub"><?= htmlspecialchars($c['email']) ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
            <p class="dir-empty" id="candidateEmpty" <?= count($candidates) ? 'hidden' : '' ?>>No eligible lecturers found.</p>
        </div>

        <?php if ($position === 'timetable_officer'): ?>
            <div class="form-row handover-rank-row">
                <label for="fromNewRank"><?= htmlspecialchars($holder['name']) ?>'s new rank after stepping down</label>
                <select id="fromNewRank">
                    <option value="">Select rank&hellip;</option>
                    <option value="junior">Junior Staff Member</option>
                    <option value="senior">Senior Lecturer</option>
                </select>
            </div>
        <?php endif; ?>

        <div class="modal-foot handover-actions">
            <a class="btn-cancel" href="/settings/handover/change/<?= urlencode($position) ?>/<?= urlencode($holder['code']) ?>">Back</a>
            <button type="button" class="btn-block" id="btnConfirmCandidate" disabled>Confirm</button>
        </div>
    </div>
</div>

<script src="/js/in_charge/accounts.js"></script>
