<?php
// Step 4 — success state. $holders comes from StaffModel::roleHolders()
// (already reflects the change that was just made).
function roleHolderLabel(array $h): string
{
    if ($h['role'] === 'timetable_officer') {
        return 'Timetable Officer';
    }
    return $h['position'] === 'in_charge' ? 'In-Charge' : 'Coordinator';
}
?>

<div class="accounts-view">
    <div class="handover-success">
        <i class="fa-solid fa-circle-check"></i>
        <h2>Role updated</h2>
        <p class="page-head-sub">The role assignment has been updated successfully.</p>
    </div>

    <div class="dir-card">
        <div class="dir-scroll">
            <table class="dir-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Lecturer Name</th>
                        <th>Email Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($holders as $h): ?>
                        <tr>
                            <td><span class="pill pill-muted"><?= htmlspecialchars(roleHolderLabel($h)) ?></span></td>
                            <td><?= htmlspecialchars($h['name']) ?></td>
                            <td><?= htmlspecialchars($h['email']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="handover-actions-center">
        <a class="btn-primary" href="/in-charge/accounts">Back to Role Assignment</a>
    </div>
</div>
