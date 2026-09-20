<?php

// Instructor Requests View
$title = "Requests";

// Dummy data for the UI
$supportTypes = ["Lab Assistant", "Technical Support", "Equipment Setup", "IT Support", "Administrative Support"];
$requests = [
    ['id' => 1, 'course' => 'CS3401 – Fundamentals of Computing Lab', 'type' => 'Lab Assistant', 'preferred' => '', 'notes' => 'Need someone to assist with equipment.', 'date' => '9 Jul 2025', 'status' => 'Pending'],
    ['id' => 2, 'course' => 'IT2301 – Web Technologies Practical', 'type' => 'Technical Support', 'preferred' => 'Mr. A. Karunaratne', 'notes' => 'Server setup help', 'date' => '8 Jul 2025', 'status' => 'Approved'],
];
?>

<div class="req-container">
    <div class="req-header">
        <div class="req-title-area">
            <h2 class="req-title">Supportive Staff Requests</h2>
            <p class="req-sub">Request additional support for your sessions</p>
        </div>
        <button type="button" class="btn-primary-sm req-new-btn" id="newRequestBtn">
            <i class="fa-solid fa-plus"></i> New Request
        </button>
    </div>

    <div class="req-body">
        <!-- New Request Form (hidden by default) -->
        <div class="req-form-card" id="newRequestForm" style="display: none;">
            <div class="req-form-header">
                <p>New Support Request</p>
                <button type="button" class="req-close-btn" id="closeRequestForm"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="req-form-body">
                <div class="form-group full-width">
                    <label>COURSE / MODULE</label>
                    <input type="text" class="req-input" placeholder="e.g. CS3401 – Fundamentals of Computing Lab">
                </div>
                <div class="form-group">
                    <label>TYPE OF SUPPORT</label>
                    <div class="req-select-wrapper">
                        <select class="req-select">
                            <option value="">Select type</option>
                            <?php foreach($supportTypes as $type): ?>
                                <option><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down chevron"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>PREFERRED STAFF <span class="font-normal">(optional)</span></label>
                    <input type="text" class="req-input" placeholder="Staff name if known">
                </div>
                <div class="form-group full-width">
                    <label>NOTES</label>
                    <textarea class="req-input req-textarea" rows="3" placeholder="Describe what support is needed..."></textarea>
                </div>
                <div class="req-form-actions full-width">
                    <button type="button" class="btn-outline req-cancel-btn" id="cancelRequestBtn">Cancel</button>
                    <button type="button" class="btn-primary-sm">Submit Request</button>
                </div>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="req-table-card">
            <div class="req-table-header">
                <p>My Requests</p>
            </div>
            <div class="req-table-wrapper">
                <table class="req-table">
                    <thead>
                        <tr>
                            <th>COURSE / MODULE</th>
                            <th>SUPPORT TYPE</th>
                            <th>PREFERRED STAFF</th>
                            <th>NOTES</th>
                            <th>SUBMITTED</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($requests as $r): ?>
                            <tr>
                                <td class="req-td-course"><?= htmlspecialchars($r['course']) ?></td>
                                <td><?= htmlspecialchars($r['type']) ?></td>
                                <td><?= htmlspecialchars($r['preferred'] ?: '—') ?></td>
                                <td class="req-td-notes"><?= htmlspecialchars($r['notes']) ?></td>
                                <td><?= htmlspecialchars($r['date']) ?></td>
                                <td>
                                    <?php if ($r['status'] === 'Approved'): ?>
                                        <span class="req-status status-approved">Approved</span>
                                    <?php elseif ($r['status'] === 'Pending'): ?>
                                        <span class="req-status status-pending">Pending</span>
                                    <?php else: ?>
                                        <span class="req-status status-rejected">Rejected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="/js/instructor/requests.js"></script>
