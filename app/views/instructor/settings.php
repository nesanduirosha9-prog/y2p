<?php

// instructor/settings.php — "Settings" page (SettingsController).
// NOTE (gap — directly affects the self-signup flow): this whole page uses
// hardcoded dummy data, NOT the logged-in staff member's real row
// ($_SESSION['staff_code']/StaffModel::findByCode()). A self-registered
// account currently has nowhere to actually see or edit its own
// name/phone/bio — "Save Changes" in settings.js only shows a toast, it
// never calls the backend either. Both sides need wiring up.
$title = "Settings";

// Dummy data
$profile = [
    'name' => 'Ms. Thilini Fernando',
    'bio' => 'Instructor in the Department of Computer Science. Specialising in lab-based practical education for undergraduate students.',
    'office' => 'Room 204, Science Block B',
    'ext' => '4512',
    'email' => 'tmf@ucsc.cmb.ac.lk',
    'designation' => 'Instructor'
];

$passkeys = [
    ['id' => 1, 'name' => 'MacBook Pro Touch ID', 'added' => '3 Jan 2025']
];

$notificationPrefs = [
    ['key' => 'workload', 'label' => 'Workload Assignments', 'sub' => 'Notify when new assignments are sent for acceptance', 'on' => true],
    ['key' => 'leave', 'label' => 'Leave Approvals', 'sub' => 'Notify when your leave requests are approved or rejected', 'on' => true],
    ['key' => 'messages', 'label' => 'New Messages', 'sub' => 'Notify on direct messages and group mentions', 'on' => true],
    ['key' => 'timetable', 'label' => 'Timetable Updates', 'sub' => 'Notify when your timetable is changed or finalized', 'on' => true],
    ['key' => 'staff_requests', 'label' => 'Staff Request Updates', 'sub' => 'Notify on status changes to your support requests', 'on' => false],
    ['key' => 'digest', 'label' => 'Weekly Summary Digest', 'sub' => 'Receive a weekly summary of your workload and schedule', 'on' => false],
];
?>

<div class="set-container">
    <div class="set-header">
        <div class="set-title-area">
            <h2 class="set-title">Settings</h2>
            <p class="set-sub">Manage your profile and preferences</p>
        </div>
    </div>

    <div class="set-body">
        <!-- Profile Section -->
        <div class="set-card">
            <div class="set-card-header">
                <p>Profile</p>
            </div>
            <div class="set-card-body set-profile-layout">
                <!-- Avatar -->
                <div class="set-avatar-col">
                    <div class="set-avatar-box">TF</div>
                    <button type="button" class="set-upload-btn"><i class="fa-solid fa-upload"></i> Upload</button>
                </div>

                <!-- Form Fields -->
                <div class="set-form-grid">
                    <div class="form-group full-width">
                        <label>FULL NAME</label>
                        <input type="text" class="set-input" value="<?= htmlspecialchars($profile['name']) ?>">
                    </div>
                    <div class="form-group full-width">
                        <label>BIO</label>
                        <textarea class="set-input set-textarea" rows="3"><?= htmlspecialchars($profile['bio']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>OFFICE LOCATION</label>
                        <input type="text" class="set-input" value="<?= htmlspecialchars($profile['office']) ?>">
                    </div>
                    <div class="form-group">
                        <label>PHONE EXTENSION</label>
                        <input type="text" class="set-input" value="<?= htmlspecialchars($profile['ext']) ?>">
                    </div>
                    <div class="form-group">
                        <label>EMAIL</label>
                        <div class="set-readonly"><?= htmlspecialchars($profile['email']) ?></div>
                    </div>
                    <div class="form-group">
                        <label>DESIGNATION</label>
                        <div class="set-readonly"><?= htmlspecialchars($profile['designation']) ?></div>
                    </div>
                </div>
            </div>
            <div class="set-card-footer">
                <button type="button" class="btn-primary-sm" id="setSaveChanges">Save Changes</button>
            </div>
        </div>

        <!-- Passkeys Section -->
        <div class="set-card">
            <div class="set-card-header flex-between">
                <p>Passkeys</p>
                <button type="button" class="set-text-btn" id="setAddPasskey"><i class="fa-solid fa-plus"></i> Add Passkey</button>
            </div>
            <div class="set-card-body">
                <div class="set-passkeys-list" id="setPasskeysList">
                    <?php foreach($passkeys as $pk): ?>
                        <div class="set-passkey-item">
                            <div class="set-passkey-info">
                                <div class="set-pk-icon"><i class="fa-solid fa-fingerprint"></i></div>
                                <div>
                                    <p class="set-pk-name"><?= htmlspecialchars($pk['name']) ?></p>
                                    <p class="set-pk-date">Added <?= htmlspecialchars($pk['added']) ?></p>
                                </div>
                            </div>
                            <button type="button" class="set-remove-btn">Remove</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Notification Preferences Section -->
        <div class="set-card">
            <div class="set-card-header">
                <p>Notification Preferences</p>
            </div>
            <div class="set-card-body">
                <div class="set-notif-list">
                    <?php foreach ($notificationPrefs as $pref): ?>
                        <div class="set-notif-item">
                            <div>
                                <p class="set-notif-label"><?= htmlspecialchars($pref['label']) ?></p>
                                <p class="set-notif-sub"><?= htmlspecialchars($pref['sub']) ?></p>
                            </div>
                            <label class="set-toggle">
                                <input type="checkbox" data-pref="<?= htmlspecialchars($pref['key']) ?>" <?= $pref['on'] ? 'checked' : '' ?>>
                                <span class="set-toggle-track"></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/js/instructor/settings.js"></script>
