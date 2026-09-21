<?php

// Coordinator "Staff" screen — pulled from Figma node 34:5043 (canvas
// "Coordinator"), frame "Document" > "Dashboard" > "StaffManagement".
// $pending and $activeStaff are read from the database by StaffController
// (StaffModel::pendingRegistrations() / activeStaff()).

$roleLabels = [
    'timetable_officer' => 'Timetable Officer',
];

function staffRoleLabel(array $s): string
{
    if ($s['role'] === 'timetable_officer') {
        return 'Timetable Officer';
    }
    $rank = $s['academic_rank'] === 'senior' ? 'Senior Lecturer' : 'Junior Staff Member';
    if ($s['position'] === 'coordinator') {
        return $rank . ' · Coordinator';
    }
    if ($s['position'] === 'in_charge') {
        return $rank . ' · In-Charge';
    }
    return $rank;
}

function staffInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(fn($p) => strtoupper(substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters);
}
?>

<div class="staff-view">

    <div class="page-head">
        <div>
            <h2>Staff Management</h2>
            <p class="page-head-sub"><?= count($activeStaff) ?> active members &middot; <?= count($pending) ?> pending requests</p>
        </div>
    </div>

    <!-- Pending Registration Requests -->
    <div class="dir-card staff-section">
        <div class="staff-section-head">
            <div>
                <h3>Pending Registration Requests</h3>
                <p class="page-head-sub"><?= count($pending) ?> requests awaiting role assignment</p>
            </div>
            <?php if (count($pending)): ?>
                <span class="pill pill-pending"><?= count($pending) ?> Pending</span>
            <?php endif; ?>
        </div>

        <div class="dir-scroll">
            <table class="dir-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Assign Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="pendingTableBody">
                    <?php foreach ($pending as $p): ?>
                        <tr data-code="<?= htmlspecialchars($p['code']) ?>">
                            <td>
                                <div class="lec-identity">
                                    <span class="lec-avatar"><?= htmlspecialchars(staffInitials($p['name'])) ?></span>
                                    <span class="lec-name"><?= htmlspecialchars($p['name']) ?></span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($p['email']) ?></td>
                            <td><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
                            <td>
                                <select class="assign-role-select">
                                    <option value="">Select role&hellip;</option>
                                    <option value="junior">Junior Staff Member</option>
                                    <option value="senior">Senior Lecturer</option>
                                    <option value="timetable_officer">Timetable Officer</option>
                                </select>
                            </td>
                            <td>
                                <div class="staff-row-actions">
                                    <button type="button" class="btn-primary-sm btn-confirm">
                                        <i class="fa-solid fa-check"></i> Confirm
                                    </button>
                                    <button type="button" class="icon-action danger btn-reject" title="Reject">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="dir-empty" id="pendingEmpty" <?= count($pending) ? 'hidden' : '' ?>>No pending registrations.</p>
        </div>
    </div>

    <!-- Active Staff Members -->
    <div class="staff-section-spacer"></div>

    <div class="dir-controls">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="activeStaffSearch" placeholder="Search name, email, phone&hellip;" autocomplete="off">
        </div>
        <div class="seg" id="activeStaffFilter" role="group" aria-label="Filter by role">
            <button type="button" class="seg-btn active" data-value="">All Roles</button>
            <button type="button" class="seg-btn" data-value="junior">Junior Staff Member</button>
            <button type="button" class="seg-btn" data-value="senior">Lecturer</button>
            <button type="button" class="seg-btn" data-value="timetable_officer">Timetable Officer</button>
        </div>
    </div>

    <div class="dir-card">
        <div class="staff-section-head">
            <div>
                <h3>Active Staff Members</h3>
                <p class="page-head-sub"><?= count($activeStaff) ?> of <?= count($activeStaff) ?> members shown</p>
            </div>
        </div>
        <div class="dir-scroll">
            <table class="dir-table" id="activeStaffTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Phone Number</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activeStaff as $s): ?>
                        <?php
                        $rankKey = $s['role'] === 'timetable_officer' ? 'timetable_officer' : $s['academic_rank'];
                        $search = strtolower($s['name'] . ' ' . $s['email'] . ' ' . ($s['phone'] ?? ''));
                        ?>
                        <tr data-role="<?= htmlspecialchars($rankKey) ?>" data-search="<?= htmlspecialchars($search) ?>">
                            <td>
                                <div class="lec-identity">
                                    <span class="lec-avatar"><?= htmlspecialchars(staffInitials($s['name'])) ?></span>
                                    <span class="lec-name"><?= htmlspecialchars($s['name']) ?></span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($s['email']) ?></td>
                            <td><span class="pill pill-muted"><?= htmlspecialchars(staffRoleLabel($s)) ?></span></td>
                            <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                            <td><span class="pill pill-active"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $s['availability_status']))) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="dir-empty" id="activeStaffEmpty" <?= count($activeStaff) ? 'hidden' : '' ?>>No active staff yet.</p>
        </div>
    </div>
</div>

<script src="/js/coordinator/staff.js"></script>
