<?php
// components/settings_form.php — shared "Account Settings" form, required by
// both instructor/settings.php and timetable_officer/settings.php (same
// pattern as components/notifications.php: one partial, included via
// require so it shares the parent view's scope).
//
// Expects in scope:
//   $profile     — array, a row from StaffModel::findByCode() (real DB data)
//   $formAction  — string, where the form POSTs to ('/settings' or '/instructor/settings')
//   $roleLabel   — string, badge text shown in the header (e.g. "Timetable Officer")
//
// Only `name`, `phone`, `office`, `extension`, `bio` are ever submitted —
// those are the only staff columns a signed-in member may edit about
// themselves (see StaffModel::updateProfile()). Email/code stay locked.
// Avatar upload, theme pills, and notification toggles below are decorative
// only for now — there's no `avatar`/theme/notification-prefs column on
// `staff` to save them to yet, so "Save Changes" never touches them.
$initials = '??';
if (!empty($profile['name'])) {
    $clean = preg_replace('/^(Dr\.|Mr\.|Mrs\.|Ms\.|Miss|Prof\.|Rev\.|Eng\.)\s*/i', '', $profile['name']);
    $parts = preg_split('/\s+/', trim($clean));
    $initials = strtoupper(($parts[0][0] ?? '') . ($parts[1][0] ?? $parts[0][1] ?? ''));
}
?>
<div class="sys-container">
    <div class="sys-header">
        <p class="sys-sub">Manage your personal profile, office contact details, and account preferences.</p>
        <div class="sys-role-badge">
            <i class="fa-solid fa-user-check"></i> <?= htmlspecialchars($roleLabel ?? 'Staff Member') ?>
        </div>
    </div>

    <div class="sys-card">
        <div class="sys-card-header">
            <h3><i class="fa-solid fa-user"></i> Profile & Account Details</h3>
        </div>

        <div class="sys-card-body">
            <!-- Avatar: preview-only, not persisted (no avatar column on `staff`) -->
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
                    <span class="sys-avatar-hint">Preview only — photos aren't saved yet.</span>
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
                        <label class="sys-label">Email Address</label>
                        <div class="sys-readonly">
                            <span><?= htmlspecialchars($profile['email'] ?? '') ?></span>
                            <span class="sys-lock-tag"><i class="fa-solid fa-lock"></i> Locked</span>
                        </div>
                    </div>

                    <div class="sys-form-group full-width">
                        <label class="sys-label" for="academicBio">Bio</label>
                        <textarea class="sys-textarea" id="academicBio" name="bio" rows="3"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                    </div>
                </div>
            </form>
        </div>

        <div class="sys-card-footer">
            <button type="button" class="sys-btn sys-btn-primary" id="saveSettingsBtn">
                <i class="fa-solid fa-check"></i> Save Changes
            </button>
        </div>
    </div>

    <div class="sys-card">
        <div class="sys-card-header">
            <h3><i class="fa-solid fa-shield-halved"></i> Password & Security</h3>
        </div>
        <div class="sys-card-body">
            <div class="sys-password-row">
                <div class="sys-password-info">
                    <p>Account Password</p>
                    <span>To reset or update your account password, use the official StaffSync recovery process.</span>
                </div>
                <a href="/forgot-password" class="sys-btn sys-btn-secondary" target="_blank">
                    <i class="fa-solid fa-key"></i> Change Password
                </a>
            </div>
        </div>
    </div>

    <!-- Theme: decorative only — not persisted, and doesn't yet re-theme the
         dashboard shell. Matches the header's theme toggle button, which is
         the same "not wired up yet" state (see layouts/dashboard.php). -->
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

    <!-- Notification preferences: decorative only — no per-category prefs
         column exists on `staff` yet, so these toggles aren't persisted. -->
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
