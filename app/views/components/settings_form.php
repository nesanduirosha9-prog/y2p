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

use app\core\ViewHelpers;

$isInCharge = $isInCharge ?? (($_SESSION['position'] ?? '') === 'in_charge');

$initials = '??';
if (!empty($profile['name'])) {
    $clean = preg_replace('/^(Dr\.|Mr\.|Mrs\.|Ms\.|Miss|Prof\.|Rev\.|Eng\.)\s*/i', '', $profile['name']);
    $parts = preg_split('/\s+/', trim($clean));
    $initials = strtoupper(($parts[0][0] ?? '') . ($parts[1][0] ?? $parts[0][1] ?? ''));
}
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
                <!-- Avatar: preview-only, not persisted -->
                <div class="sys-avatar-row">
                    <div class="sys-avatar-box">
                        <img src="" id="avatarImage" alt="Avatar" style="display: none;">
                        <span id="avatarInitials"><?= htmlspecialchars($initials) ?></span>
                    </div>
                    <div class="sys-avatar-actions">
                        <div class="sys-avatar-btns">
                            <label for="avatarFileInput" class="sys-btn sys-btn-secondary sys-btn-sm">
                                <i class="fa-solid fa-upload"></i> Upload Photo
                            </label>
                            <input type="file" id="avatarFileInput" accept="image/*" style="display: none;">
                            <button type="button" class="sys-btn sys-btn-danger-text" id="removeAvatarBtn" style="display: none;">
                                Remove Photo
                            </button>
                        </div>
                        <span class="sys-avatar-hint">Preview only &mdash; photos aren't saved yet.</span>
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
        <!-- Panel 2: Account Handover (In-Charge Only) -->
        <div class="settings-panel" id="settings-panel-handover" role="tabpanel" aria-labelledby="tab-handover" hidden>
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
                            <?php foreach (($roleHolders ?? []) as $h): ?>
                                <?php
                                $posKey = $h['role'] === 'timetable_officer' ? 'timetable_officer' : $h['position'];
                                ?>
                                <tr>
                                    <td><span class="pill pill-muted"><?= htmlspecialchars(ViewHelpers::roleHolderLabel($h)) ?></span></td>
                                    <td><?= htmlspecialchars($h['name']) ?></td>
                                    <td><?= htmlspecialchars($h['email']) ?></td>
                                    <td>
                                        <a class="btn-secondary" href="/settings/handover/change/<?= urlencode($posKey) ?>/<?= urlencode($h['code']) ?>">
                                            Change
                                        </a>
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

            <p class="accounts-note" style="margin-top: 14px; font-size: 12.5px; color: #6b7c96; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-shield-halved" style="color: #1a3a6b;"></i>
                Role changes require OTP verification to ensure only you can reassign these seats.
            </p>
        </div>
    <?php endif; ?>

</div>
