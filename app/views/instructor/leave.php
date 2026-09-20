<?php

// Instructor Leave View
$title = "Leave Management";

$annualBalance = 15;
$daysUsed = 6;
$upcomingDays = 0;
$totalRequests = 2;

$upcomingLeaves = []; // Empty for demo
$historyLeaves = [
    ['id' => 1, 'type' => 'Casual Leave', 'dates' => '2025-06-10 – 2025-06-12', 'reason' => 'Family event', 'cover' => 'Dr. N. Perera', 'status' => 'Approved'],
    ['id' => 2, 'type' => 'Sick Leave', 'dates' => '2025-04-05', 'reason' => 'Fever', 'cover' => '—', 'status' => 'Approved'],
];
?>

<div class="lv-container">
    <div class="lv-header">
        <div class="lv-title-area">
            <h2 class="lv-title">Leave</h2>
            <p class="lv-sub">Manage your leave requests</p>
        </div>
        <button type="button" class="btn-primary-sm lv-new-btn" id="requestLeaveBtn">
            <i class="fa-solid fa-plus"></i> Request Leave
        </button>
    </div>

    <div class="lv-body">
        <!-- Stat Cards -->
        <div class="lv-stats-grid">
            <div class="lv-stat-card">
                <div class="lv-icon-box" style="background: #e8edf5; color: #1a3a6b;">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>
                <p class="lv-stat-label">ANNUAL BALANCE</p>
                <p class="lv-stat-value"><?= $annualBalance ?> <span class="lv-stat-unit">days</span></p>
            </div>
            <div class="lv-stat-card">
                <div class="lv-icon-box" style="background: #d0fae5; color: #0f766e;">
                    <i class="fa-solid fa-calendar-minus"></i>
                </div>
                <p class="lv-stat-label">DAYS USED</p>
                <p class="lv-stat-value"><?= $daysUsed ?> <span class="lv-stat-unit">days</span></p>
            </div>
            <div class="lv-stat-card">
                <div class="lv-icon-box" style="background: #fef3c7; color: #b45309;">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <p class="lv-stat-label">UPCOMING DAYS</p>
                <p class="lv-stat-value"><?= $upcomingDays ?> <span class="lv-stat-unit">days</span></p>
            </div>
            <div class="lv-stat-card">
                <div class="lv-icon-box" style="background: #f4f6f9; color: #6b7c96;">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <p class="lv-stat-label">TOTAL REQUESTS</p>
                <p class="lv-stat-value"><?= $totalRequests ?></p>
            </div>
        </div>

        <!-- Upcoming Leave -->
        <div class="lv-table-card">
            <div class="lv-table-header">
                <p>Upcoming Leave</p>
            </div>
            <div class="lv-table-wrapper">
                <?php if (empty($upcomingLeaves)): ?>
                    <div class="lv-empty-state">No upcoming leave scheduled.</div>
                <?php else: ?>
                    <table class="lv-table">
                        <thead>
                            <tr>
                                <th>TYPE</th>
                                <th>DATES</th>
                                <th>REASON</th>
                                <th>COVERING STAFF</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Rows go here -->
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Leave History -->
        <div class="lv-table-card">
            <div class="lv-table-header">
                <p>Leave History</p>
            </div>
            <div class="lv-table-wrapper">
                <table class="lv-table">
                    <thead>
                        <tr>
                            <th>TYPE</th>
                            <th>DATES</th>
                            <th>REASON</th>
                            <th>COVERING STAFF</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($historyLeaves as $l): ?>
                            <tr>
                                <td class="lv-td-type"><?= htmlspecialchars($l['type']) ?></td>
                                <td><?= htmlspecialchars($l['dates']) ?></td>
                                <td><?= htmlspecialchars($l['reason']) ?></td>
                                <td><?= htmlspecialchars($l['cover']) ?></td>
                                <td>
                                    <?php if ($l['status'] === 'Approved'): ?>
                                        <span class="lv-status status-approved">Approved</span>
                                    <?php else: ?>
                                        <span class="lv-status"><?= htmlspecialchars($l['status']) ?></span>
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
