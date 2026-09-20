<?php

// Instructor Settings View
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
                <button type="button" class="btn-primary-sm">Save Changes</button>
            </div>
        </div>

        <!-- Passkeys Section -->
        <div class="set-card">
            <div class="set-card-header flex-between">
                <p>Passkeys</p>
                <button type="button" class="set-text-btn"><i class="fa-solid fa-plus"></i> Add Passkey</button>
            </div>
            <div class="set-card-body">
                <div class="set-passkeys-list">
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
    </div>
</div>
