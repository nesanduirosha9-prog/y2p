<?php

use app\core\ViewHelpers;

// components/staff_directory.php — Shared Staff Management Directory component.
// Reusable across Coordinator and In-Charge views.
// Expects: $pending, $activeStaff, and optional $basePath.

// One canonical path for both positions. The previous fallback branched on
// position and produced an in-charge-prefixed path that was never a
// registered route — a link straight to a 404.
$basePath = $basePath ?? '/staff';
$currentStaffCode = $_SESSION['staff_code'] ?? '';
$currentUserPosition = $_SESSION['position'] ?? '';
?>

<div class="staff-view" data-base-path="<?= htmlspecialchars($basePath) ?>">

    <!-- Tab Bar: Active Staff Members & Pending Registration Requests -->
    <div class="staff-tabs" id="staffTabs" role="tablist">
        <button type="button" class="staff-tab active" data-tab="active" role="tab" aria-selected="true" id="tab-active">
            <i class="fa-solid fa-users"></i>
            <span>Active Staff Members</span>
            <span class="staff-tab-badge" id="activeStaffBadge"><?= count($activeStaff) ?></span>
        </button>
        <button type="button" class="staff-tab" data-tab="pending" role="tab" aria-selected="false" id="tab-pending">
            <i class="fa-solid fa-user-clock"></i>
            <span>Pending Requests</span>
            <span class="staff-tab-badge pill-pending" id="pendingBadge" <?= count($pending) === 0 ? 'style="display: none;"' : '' ?>><?= count($pending) ?></span>
        </button>
    </div>

    <!-- Tab Panel 1: Active Staff Members -->
    <div class="staff-panel" id="staff-panel-active" role="tabpanel" aria-labelledby="tab-active">
        <div class="dir-controls">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="activeStaffSearch" placeholder="Search name, code, department, courses&hellip;" autocomplete="off">
            </div>
            <div class="seg" id="activeStaffFilter" role="group" aria-label="Filter by role">
                <button type="button" class="seg-btn active" data-value="">All Roles</button>
                <button type="button" class="seg-btn" data-value="junior">Junior Staff Member</button>
                <button type="button" class="seg-btn" data-value="senior">Lecturer</button>
                <button type="button" class="seg-btn" data-value="timetable_officer">Timetable Officer</button>
            </div>
            <button type="button" class="btn-primary staff-add-btn" id="addStaffBtn">
                <i class="fa-solid fa-user-plus"></i> Add staff
            </button>
        </div>

        <div class="dir-card">
            <div class="staff-section-head">
                <div>
                    <h3>Active Staff Members</h3>
                    <p class="page-head-sub" id="activeStaffShownCount"><?= count($activeStaff) ?> of <?= count($activeStaff) ?> members shown</p>
                </div>
            </div>
            <div class="dir-scroll">
                <table class="dir-table" id="activeStaffTable">
                    <thead>
                        <tr>
                            <th>Staff Code</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Assigned Courses</th>
                            <th>Phone Number</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeStaff as $s): ?>
                            <?php
                            $rankKey = $s['role'] === 'timetable_officer' ? 'timetable_officer' : $s['academic_rank'];
                            $courses = $s['courses'] ?? [];
                            $coursesStr = implode(' ', $courses);
                            $dept = $s['department'] ?? ($s['role'] === 'timetable_officer' ? 'Administration' : 'Academic Staff');
                            $search = strtolower($s['code'] . ' ' . $s['name'] . ' ' . $dept . ' ' . $s['email'] . ' ' . ($s['phone'] ?? '') . ' ' . $coursesStr);
                            
                            $isSelf = ($s['code'] === $currentStaffCode);
                            $isTargetInCharge = (($s['position'] ?? '') === 'in_charge');
                            $isTargetCoordinator = (($s['position'] ?? '') === 'coordinator');

                            // Permissions:
                            // 1. Both coordinator & in-charge cannot delete or deactivate their own account.
                            // 2. In-Charge cannot be deleted or deactivated by anyone.
                            // 3. Coordinator can ONLY be deleted or deactivated by In-Charge.
                            // 4. Other staff can be managed by either Coordinator or In-Charge.
                            $canManage = false;
                            if (!$isSelf && !$isTargetInCharge) {
                                if ($isTargetCoordinator) {
                                    $canManage = ($currentUserPosition === 'in_charge');
                                } else {
                                    $canManage = true;
                                }
                            }
                            // Inactive = deactivated after leaving the university (migration 023).
                            // Pending rows never reach this table: activeStaff() needs a role.
                            $isInactive = (($s['status'] ?? 'active') === 'inactive');
                            ?>
                            <tr data-code="<?= htmlspecialchars($s['code']) ?>"
                                data-role="<?= htmlspecialchars($rankKey) ?>"
                                data-search="<?= htmlspecialchars($search) ?>"
                                data-status="<?= $isInactive ? 'inactive' : 'active' ?>"
                                <?= $isInactive ? 'class="is-inactive"' : '' ?>>
                                <td><?= ViewHelpers::codeBadge($s['code'], $rankKey === 'senior' ? 'lecturer' : 'staff', $s['name']) ?></td>
                                <td>
                                    <div class="lec-identity">
                                        <span class="lec-avatar"><?= htmlspecialchars(ViewHelpers::staffInitials($s['name'])) ?></span>
                                        <span>
                                            <span class="lec-name"><?= htmlspecialchars($s['name']) ?></span>
                                            <span class="lec-dept"><?= htmlspecialchars($dept) ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="lec-email"><?= htmlspecialchars($s['email']) ?></td>
                                <td><span class="pill pill-muted"><?= htmlspecialchars(ViewHelpers::staffRoleLabel($s)) ?></span></td>
                                <td>
                                    <?php if (!empty($courses)): ?>
                                        <div class="tag-row">
                                            <?php foreach ($courses as $cc): ?>
                                                <span class="code-badge code-badge--course"><?= htmlspecialchars($cc) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($s['phone'] ?: '—') ?></td>
                                <td>
                                    <span class="pill <?= $isInactive ? 'pill-muted' : 'pill-active' ?> status-indicator-pill">
                                        <?= $isInactive ? 'Inactive' : 'Active' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isSelf): ?>
                                        <span class="pill pill-subtle"><i class="fa-solid fa-user"></i> You</span>
                                    <?php elseif ($canManage): ?>
                                        <div class="staff-row-actions">
                                            <button type="button"
                                                    class="btn-action-status <?= $isInactive ? 'btn-activate' : 'btn-deactivate' ?>"
                                                    data-code="<?= htmlspecialchars($s['code']) ?>"
                                                    data-name="<?= htmlspecialchars($s['name']) ?>"
                                                    data-courses="<?= htmlspecialchars($coursesStr) ?>"
                                                    title="<?= $isInactive ? 'Reactivate account' : 'Deactivate account' ?>">
                                                <i class="fa-solid <?= $isInactive ? 'fa-user-check' : 'fa-user-slash' ?>"></i>
                                                <span><?= $isInactive ? 'Reactivate' : 'Deactivate' ?></span>
                                            </button>
                                            <button type="button" 
                                                    class="icon-action danger btn-delete-staff" 
                                                    data-code="<?= htmlspecialchars($s['code']) ?>"
                                                    data-name="<?= htmlspecialchars($s['name']) ?>"
                                                    title="Delete account">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted" title="Protected account">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="dir-empty" id="activeStaffEmpty" <?= count($activeStaff) ? 'hidden' : '' ?>>No active staff yet.</p>
            </div>
        </div>
    </div>

    <!-- Tab Panel 2: Pending Registration Requests -->
    <div class="staff-panel" id="staff-panel-pending" role="tabpanel" aria-labelledby="tab-pending" hidden>
        <div class="dir-card staff-section">
            <div class="staff-section-head">
                <div>
                    <h3>Pending Registration Requests</h3>
                    <p class="page-head-sub" id="pendingRequestsSub"><?= count($pending) ?> requests awaiting role assignment</p>
                </div>
                <span class="pill pill-pending" id="pendingSectionPill" <?= count($pending) === 0 ? 'style="display: none;"' : '' ?>><?= count($pending) ?> Pending</span>
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
                                        <span class="lec-avatar"><?= htmlspecialchars(ViewHelpers::staffInitials($p['name'])) ?></span>
                                        <span class="lec-name"><?= htmlspecialchars($p['name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($p['email']) ?></td>
                                <td><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
                                <td>
                                    <select class="assign-role-select">
                                        <option value="">Select role&hellip;</option>
                                        <option value="junior">Junior Staff Member</option>
                                        <option value="senior">Lecturer</option>
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
    </div>

    <!-- Add staff: email + role. The account gets a random temporary password
         emailed to the member (DEMO_AUTH: the shared demo password, nothing
         emailed or shown); the member changes it and fills in their profile
         from Settings.
         Opens from the right, like every other panel in the system. -->
    <div class="side-panel-backdrop" id="addStaffBackdrop" hidden></div>
    <aside class="side-panel" id="addStaffPanel" hidden role="dialog" aria-modal="true" aria-labelledby="addStaffTitle">
        <div class="side-panel-head">
            <div>
                <span class="side-panel-tag">Staff Details</span>
                <h3 class="side-panel-title" id="addStaffTitle">Add staff</h3>
            </div>
            <button type="button" class="side-panel-close" data-add-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form class="add-staff-pane" id="addStaffForm" novalidate>
            <div class="side-panel-body">
                <div class="form-row">
                    <label for="addStaffEmail">University email</label>
                    <input type="email" id="addStaffEmail" name="email" placeholder="name@ucsc.cmb.ac.lk" autocomplete="off" required>
                    <p class="form-error" id="addStaffEmailError" hidden></p>
                </div>

                <fieldset class="form-row add-staff-roles">
                    <legend>Role</legend>
                    <label class="role-option">
                        <input type="radio" name="role" value="lecturer">
                        <span class="role-option-card">
                            <i class="fa-solid fa-chalkboard-user"></i>
                            <span>
                                <strong>Lecturer</strong>
                                <small>Teaches courses, evaluates junior staff</small>
                            </span>
                        </span>
                    </label>
                    <label class="role-option">
                        <input type="radio" name="role" value="junior">
                        <span class="role-option-card">
                            <i class="fa-solid fa-user-graduate"></i>
                            <span>
                                <strong>Junior Staff</strong>
                                <small>Supports courses: practicals, tutorials, marking</small>
                            </span>
                        </span>
                    </label>
                    <p class="form-error" id="addStaffRoleError" hidden></p>
                </fieldset>

                <p class="form-error" id="addStaffError" hidden></p>
            </div>

            <div class="side-panel-foot">
                <button type="button" class="btn-cancel" data-add-close>Cancel</button>
                <button type="submit" class="btn-block" id="addStaffSubmit">Create account</button>
            </div>
        </form>

        <!-- Shown in place of the form once the account exists -->
        <div class="add-staff-pane add-staff-done" id="addStaffDone" hidden>
            <div class="side-panel-body">
                <div class="add-staff-done-icon"><i class="fa-solid fa-circle-check"></i></div>
                <p class="add-staff-done-title" id="addStaffDoneTitle"></p>
                <p class="add-staff-done-sub" id="addStaffDoneSub" hidden></p>

                <!-- Sign-in details: only when a real (non-demo) email failed to send -->
                <dl class="add-staff-creds" id="addStaffCreds" hidden>
                    <div class="add-staff-cred">
                        <dt>Email</dt>
                        <dd id="addStaffCredEmail"></dd>
                    </div>
                    <div class="add-staff-cred">
                        <dt>Temporary password</dt>
                        <dd>
                            <code id="addStaffCredPassword"></code>
                            <button type="button" class="add-staff-copy" id="addStaffCopy" aria-label="Copy password" title="Copy password">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </dd>
                    </div>
                </dl>
            </div>
            <div class="side-panel-foot">
                <button type="button" class="btn-cancel" id="addStaffAnother">Add another</button>
                <button type="button" class="btn-block" id="addStaffFinish">Done</button>
            </div>
        </div>
    </aside>
</div>
