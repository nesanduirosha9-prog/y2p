<?php

use app\core\ViewHelpers;

// In-Charge "Accounts" / Role Assignment — Figma node 34:5044, frames
// "Accounts" / "Account -> Change". $holders comes from
// StaffModel::roleHolders(): the current Timetable Officer plus every
// active Coordinator/In-Charge.

function roleHolderPositionKey(array $h): string
{
    return $h['role'] === 'timetable_officer' ? 'timetable_officer' : $h['position'];
}
?>

<div class="accounts-view">
    <div class="page-head">
        <p class="page-head-sub">Manage key academic role holders for the department.</p>
    </div>

    <div class="dir-card">
        <div class="dir-scroll">
            <table class="dir-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Lecturer Name</th>
                        <th>Email Address</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($holders as $h): ?>
                        <tr>
                            <td><span class="pill pill-muted"><?= htmlspecialchars(ViewHelpers::roleHolderLabel($h)) ?></span></td>
                            <td><?= htmlspecialchars($h['name']) ?></td>
                            <td><?= htmlspecialchars($h['email']) ?></td>
                            <td>
                                <a class="btn-secondary" href="/in-charge/accounts/change/<?= urlencode(roleHolderPositionKey($h)) ?>/<?= urlencode($h['code']) ?>">
                                    Change
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!count($holders)): ?>
                        <tr><td colspan="4" class="dir-empty">No role holders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="accounts-note">
        <i class="fa-solid fa-shield-halved"></i>
        Role changes require OTP verification to ensure only you can reassign these seats.
    </p>
</div>
