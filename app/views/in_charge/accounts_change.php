<?php
// Step 1 of the handover flow — confirm which seat is being reassigned.
// $position / $holder come from AccountsController::change().
$positionLabels = [
    'coordinator' => 'Coordinator',
    'in_charge' => 'In-Charge',
];
?>

<div class="accounts-view">
    <div class="dir-card handover-card">
        <div class="handover-current">
            <span class="pill pill-muted"><?= htmlspecialchars($positionLabels[$position]) ?></span>
            <div>
                <p class="handover-name"><?= htmlspecialchars($holder['name']) ?></p>
                <p class="page-head-sub"><?= htmlspecialchars($holder['email']) ?></p>
            </div>
        </div>

        <div class="modal-foot handover-actions">
            <a class="btn-cancel" href="/settings/handover">Cancel</a>
            <a class="btn-block" href="/settings/handover/select/<?= urlencode($position) ?>/<?= urlencode($holder['code']) ?>">
                Continue
            </a>
        </div>
    </div>
</div>
