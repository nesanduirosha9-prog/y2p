<?php
// components/settings_form.php — shared "Account Settings" form, required by
// views/settings.php, the one Settings view every role renders.
//
// Expects in scope:
//   $profile     — array, a row from StaffModel::findByCode() (real DB data)
//   $formAction  — string, where the form POSTs to (always '/settings')
//   $roleLabel   — string, optional badge text
//   $isInCharge  — bool, optional flag for Department In-Charge role
//   $roleHolders — array, optional list of role holders for In-Charge handover tab

use app\core\StaffEmail;
use app\core\ViewHelpers;

$isInCharge = $isInCharge ?? (($_SESSION['position'] ?? '') === 'in_charge');

// The 3-letter badge code, the same fallback the header chip uses, so the two
// avatars on screen never disagree. (This used to derive two letters from the
// name, which gave "TF" here and a hardcoded "IN" in the header.)
$initials = ViewHelpers::currentAvatarCode();
?>
<div class="sys-container">

    <?php if ($isInCharge): ?>
        <!-- Tab Bar: Profile Settings vs Account Handover (In-Charge Only) -->
        <div class="settings-tabs" id="settingsTabs" role="tablist">
            <button type="button" class="settings-tab active" data-tab="profile" role="tab" aria-selected="true" id="tab-profile">
                <i class="fa-solid fa-user-gear"></i>
                <span>Profile Settings</span>
            </button>
            <button type="button" class="settings-tab" data-tab="handover" role="tab" aria-selected="false" id="tab-handover">
                <i class="fa-solid fa-people-arrows"></i>
                <span>Account Handover</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Panel 1: Profile Settings -->
    <div class="settings-panel" id="settings-panel-profile" role="tabpanel" aria-labelledby="tab-profile">
        <div class="sys-card">
            <div class="sys-card-header">
                <h3><i class="fa-solid fa-user"></i> Profile &amp; Account Details</h3>
            </div>

            <div class="sys-card-body">
                <!-- Avatar: kept in this browser only (no column on `staff` yet).
                     js/settings.js writes it through window.StaffSyncAvatar, which
                     also repaints the header chip. -->
                <div class="sys-avatar-row">
                    <div class="sys-avatar-box">
                        <img src="" id="avatarImage" alt="Avatar" style="display: none;">
                        <span id="avatarInitials" data-avatar-code="<?= htmlspecialchars($initials) ?>"><?= htmlspecialchars($initials) ?></span>
                    </div>
                    <div class="sys-avatar-actions">
                        <div class="sys-avatar-btns">
                            <label for="avatarFileInput" class="sys-btn sys-btn-secondary sys-btn-sm">
                                <i class="fa-solid fa-upload"></i> Upload Photo
                            </label>
                            <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                            <button type="button" class="sys-btn sys-btn-danger-text" id="removeAvatarBtn" style="display: none;">
                                Remove Photo
                            </button>
                        </div>
                        <span class="sys-avatar-hint">Saved in this browser only &mdash; up to 2&nbsp;MB.</span>
                    </div>
                </div>

                <form id="sysProfileForm" data-action="<?= htmlspecialchars($formAction) ?>" onsubmit="return false;">
                    <div class="sys-form-grid">
                        <div class="sys-form-group full-width">
                            <label class="sys-label" for="fullName">Full Name</label>
                            <input type="text" class="sys-input" id="fullName" name="name" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" required>
                        </div>

                        <div class="sys-form-group">
                            <label class="sys-label" for="phoneMobile">Telephone Number</label>
                            <input type="tel" class="sys-input" id="phoneMobile" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="+94 7X XXX XXXX">
                        </div>

                        <div class="sys-form-group">
                            <label class="sys-label" for="officeDetail">Office Location</label>
                            <input type="text" class="sys-input" id="officeDetail" name="office" value="<?= htmlspecialchars($profile['office'] ?? '') ?>">
                        </div>

                        <div class="sys-form-group">
                            <label class="sys-label" for="phoneExtension">Office Extension</label>
                            <input type="text" class="sys-input" id="phoneExtension" name="extension" value="<?= htmlspecialchars($profile['extension'] ?? '') ?>" placeholder="e.g. 4512">
                        </div>

                        <div class="sys-form-group">
                            <label class="sys-label" for="emailAddr">Email Address</label>
                            <input type="email" class="sys-input" id="emailAddr" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" disabled title="Email address cannot be changed directly">
                        </div>

                        <div class="sys-form-group full-width">
                            <label class="sys-label" for="bioShort">Brief Biography</label>
                            <textarea class="sys-textarea" id="bioShort" name="bio" rows="3" placeholder="Tell students and colleagues a bit about your academic interests..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <div class="sys-card-footer">
                <button type="button" class="sys-btn sys-btn-primary" id="saveSettingsBtn">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </div>

        <div style="height: 20px;"></div>

        <!-- Theme: decorative only -->
        <div class="sys-card">
            <div class="sys-card-header">
                <h3><i class="fa-solid fa-palette"></i> Preferred Theme</h3>
            </div>
            <div class="sys-card-body">
                <div class="sys-theme-options">
                    <div class="sys-theme-pill active" data-theme-val="light">
                        <span><i class="fa-solid fa-sun" style="color: #f59e0b;"></i> Light Mode</span>
                        <i class="fa-solid fa-check check-icon"></i>
                    </div>
                    <div class="sys-theme-pill" data-theme-val="dark">
                        <span><i class="fa-solid fa-moon" style="color: #6366f1;"></i> Dark Mode</span>
                        <i class="fa-solid fa-check check-icon"></i>
                    </div>
                    <div class="sys-theme-pill" data-theme-val="system">
                        <span><i class="fa-solid fa-desktop" style="color: #2563eb;"></i> System Default</span>
                        <i class="fa-solid fa-check check-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div style="height: 20px;"></div>

        <!-- Notification preferences: decorative only -->
        <div class="sys-card">
            <div class="sys-card-header">
                <h3><i class="fa-solid fa-bell"></i> Notification Preferences</h3>
            </div>
            <div class="sys-card-body">
                <div class="sys-notif-list">
                    <div class="sys-notif-item">
                        <div>
                            <p class="sys-notif-label">Email Notifications</p>
                            <p class="sys-notif-sub">Receive email alerts for key updates, leave approvals, and assignments.</p>
                        </div>
                        <label class="sys-switch">
                            <input type="checkbox" checked>
                            <span class="sys-slider"></span>
                        </label>
                    </div>
                    <div class="sys-notif-item">
                        <div>
                            <p class="sys-notif-label">In-App Dashboard Alerts</p>
                            <p class="sys-notif-sub">Show live notifications bell alerts inside your StaffSync dashboard.</p>
                        </div>
                        <label class="sys-switch">
                            <input type="checkbox" checked>
                            <span class="sys-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isInCharge): ?>
        <!-- Panel 2: Account Handover (In-Charge Only)
             Coordinator is a multi-seat role: the In-Charge decides how many
             there are (Add coordinator / Revoke). The In-Charge and Timetable
             Officer seats are single, so those rows only offer Change. -->
        <?php
        $coordinatorCount = count(array_filter($roleHolders ?? [], fn($h) => ($h['position'] ?? '') === 'coordinator'));
        ?>
        <?php
        // The Timetable Officer is its own account, not a seat handed between
        // staff: its Change keeps the account (and its history) and only
        // moves the login to the new officer's email.
        $positionLabels = ['coordinator' => 'Coordinator', 'in_charge' => 'In-Charge', 'timetable_officer' => 'Timetable Officer'];
        ?>
        <div class="settings-panel" id="settings-panel-handover" role="tabpanel" aria-labelledby="tab-handover" hidden>
          <div class="ho-layout" id="hoLayout">
           <div class="ho-main">
            <div class="dir-card">
                <div class="handover-head">
                    <div>
                        <h3>Key roles</h3>
                        <p class="page-head-sub"><?= $coordinatorCount ?> Coordinator<?= $coordinatorCount === 1 ? '' : 's' ?> &middot; 1 In-Charge &middot; 1 Timetable Officer</p>
                    </div>
                    <button type="button" class="btn-primary handover-add-btn"
                            data-handover="coordinator" data-label="Coordinator">
                        Add coordinator
                    </button>
                </div>
                <div class="dir-scroll">
                    <table class="dir-table">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Name</th>
                                <th>Email Address</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="handoverBody">
                            <?php foreach (($roleHolders ?? []) as $h): ?>
                                <?php
                                $posKey = $h['role'] === 'timetable_officer' ? 'timetable_officer' : $h['position'];
                                $isCoordinator = $posKey === 'coordinator';
                                $isLastCoordinator = $isCoordinator && $coordinatorCount <= 1;
                                ?>
                                <tr>
                                    <td><span class="pill pill-muted"><?= htmlspecialchars(ViewHelpers::roleHolderLabel($h)) ?></span></td>
                                    <td>
                                        <div class="lec-identity">
                                            <?= ViewHelpers::codeBadge($h['code'], $h['academic_rank'] === 'senior' ? 'lecturer' : 'staff') ?>
                                            <span class="lec-name"><?= htmlspecialchars($h['name']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($h['email']) ?></td>
                                    <td>
                                        <div class="handover-row-actions">
                                            <?php if (isset($positionLabels[$posKey])): ?>
                                                <button type="button" class="btn-secondary"
                                                        data-handover="<?= htmlspecialchars($posKey) ?>"
                                                        data-label="<?= htmlspecialchars($positionLabels[$posKey]) ?>"
                                                        data-code="<?= htmlspecialchars($h['code']) ?>"
                                                        data-name="<?= htmlspecialchars($h['name']) ?>"
                                                        data-email="<?= htmlspecialchars($h['email']) ?>"
                                                        data-kind="<?= $h['academic_rank'] === 'senior' ? 'lecturer' : 'staff' ?>">
                                                    Change
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($isCoordinator): ?>
                                                <button type="button" class="btn-revoke"
                                                        data-revoke="<?= htmlspecialchars($h['code']) ?>"
                                                        data-name="<?= htmlspecialchars($h['name']) ?>"
                                                        <?= $isLastCoordinator ? 'disabled title="The department needs at least one Coordinator"' : 'title="Take the Coordinator role away. They stay on staff as Junior Staff."' ?>>
                                                    Revoke
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($roleHolders)): ?>
                                <tr><td colspan="4" class="dir-empty">No role holders found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="accounts-note">
                Giving someone a role needs a verification code from them. Revoking a Coordinator takes effect straight away.
            </p>
           </div>

            <!-- Change / Add panel, docked on the right like Request Leave.
                 js/settings.js (initHandoverPanel) fills it and walks through
                 the two steps: pick the new holder, then enter their code. -->
            <aside class="ho-side-panel" id="hoPanel" hidden aria-labelledby="hoPanelTitle">
                <div class="ho-panel-header">
                    <h2 id="hoPanelTitle">Change role</h2>
                    <button type="button" class="ho-panel-close" id="hoPanelClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <!-- Step 1: who takes the seat -->
                <div class="ho-panel-body" id="hoStepPick">
                    <div class="ho-field" id="hoCurrentRow">
                        <p class="ho-label">Current holder</p>
                        <div class="ho-person" id="hoCurrent"></div>
                    </div>

                    <!-- Timetable Officer only: the account stays, the login moves -->
                    <div class="ho-field" id="hoEmailRow" hidden>
                        <label class="ho-label" for="hoNewEmail">New officer's email</label>
                        <input type="email" id="hoNewEmail" placeholder="name@<?= htmlspecialchars(StaffEmail::domain()) ?>" autocomplete="off">
                        <p class="ho-hint">The account, its timetable and its history stay. The new officer sets a password with <b>Forgot password</b> and fills in their profile from Settings. The current officer is signed out.</p>
                    </div>

                    <div class="ho-field" id="hoPickRow">
                        <label class="ho-label" for="hoSearch">New holder</label>
                        <div class="search-box handover-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="hoSearch" placeholder="Search by name or email…" autocomplete="off">
                        </div>
                        <div class="candidate-list" id="hoCandidates"></div>
                        <p class="dir-empty" id="hoCandidatesEmpty" hidden>Nobody matches.</p>
                    </div>

                    <p class="form-error" id="hoPickError" hidden></p>
                </div>

                <!-- Step 2: the code sent to the new holder -->
                <div class="ho-panel-body" id="hoStepVerify" hidden>
                    <div class="ho-field">
                        <p class="ho-label">Code sent to</p>
                        <div class="ho-person" id="hoTarget"></div>
                    </div>
                    <div class="ho-field">
                        <label class="ho-label" for="hoOtp">6-digit code</label>
                        <input type="text" id="hoOtp" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="000000">
                    </div>
                    <p class="form-error" id="hoOtpError" hidden></p>
                </div>

                <div class="ho-panel-footer">
                    <button type="button" class="btn-secondary" id="hoBack">Cancel</button>
                    <button type="button" class="btn-primary" id="hoNext" disabled>Send code</button>
                </div>
            </aside>
          </div>
        </div>
    <?php endif; ?>

</div>
