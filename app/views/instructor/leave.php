<?php

// instructor/leave.php — "Leave" page (LeaveController).
// NOTE (gap): none of this reads/writes the `leave_requests` table
// (migration 009) — see LeaveController.php. $leaveRecords below is a fixed
// demo data set (dates generated relative to "today" so upcoming/history
// always split sensibly regardless of when the page is opened), embedded as
// JSON and rendered entirely client-side by /js/instructor/leave.js — the
// same JSON-payload + JS-render approach instructor/messages.php uses for
// $conversationsData, so stat totals, the Upcoming Leaves list, and the
// History table all stay derived from one source instead of three.
$title = "Leave Management";

$today = new DateTime('today');

function lvOffsetDate(DateTime $base, int $days): string
{
    $d = clone $base;
    $d->modify(($days >= 0 ? '+' : '') . $days . ' days');
    return $d->format('Y-m-d');
}

$coverStaffOptions = ['Dr. N. Perera', 'Prof. A. Silva', 'Mr. K. Bandara', 'Dr. S. Rajapaksa', 'Ms. L. Wickramasinghe'];

$leaveRecords = [
    ['id' => 1, 'type' => 'Annual Leave', 'dates' => [lvOffsetDate($today, 12), lvOffsetDate($today, 13), lvOffsetDate($today, 14)], 'reason' => 'Family holiday', 'cover' => 'Dr. N. Perera', 'cancelled' => false],
    ['id' => 2, 'type' => 'Medical Leave', 'dates' => [lvOffsetDate($today, 3)], 'reason' => 'Medical appointment', 'cover' => '', 'cancelled' => false, 'timeFrom' => '09:00', 'timeTo' => '12:00'],
    ['id' => 3, 'type' => 'Casual Leave', 'dates' => [lvOffsetDate($today, -35), lvOffsetDate($today, -34), lvOffsetDate($today, -33)], 'reason' => 'Family event', 'cover' => 'Dr. N. Perera', 'cancelled' => false],
    ['id' => 4, 'type' => 'Sick Leave', 'dates' => [lvOffsetDate($today, -70)], 'reason' => 'Fever', 'cover' => '', 'cancelled' => false],
    ['id' => 5, 'type' => 'Conference Leave', 'dates' => [lvOffsetDate($today, -20), lvOffsetDate($today, -19)], 'reason' => 'ICCS 2025 Conference', 'cover' => 'Prof. A. Silva', 'cancelled' => true],
];
?>

<div class="lv-container">
    <div class="lv-header">
        <p class="lv-sub">Track your leave balance and manage upcoming requests</p>
        <button type="button" class="btn-primary-sm lv-new-btn" id="requestLeaveBtn">
            <i class="fa-solid fa-plus"></i> Request Leave
        </button>
    </div>

    <div class="lv-body">
        <div class="lv-main-col">
            <!-- Stat Cards -->
            <div class="lv-stats-grid">
                <div class="lv-stat-card">
                    <div class="lv-icon-box" style="background: #e8edf5; color: #1a3a6b;">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <p class="lv-stat-label">ANNUAL BALANCE</p>
                    <p class="lv-stat-value"><span id="lvStatBalance">21</span> <span class="lv-stat-unit">days</span></p>
                </div>
                <div class="lv-stat-card">
                    <div class="lv-icon-box" style="background: #d0fae5; color: #0f766e;">
                        <i class="fa-solid fa-calendar-minus"></i>
                    </div>
                    <p class="lv-stat-label">DAYS USED</p>
                    <p class="lv-stat-value"><span id="lvStatUsed">0</span> <span class="lv-stat-unit">days</span></p>
                </div>
                <div class="lv-stat-card">
                    <div class="lv-icon-box" style="background: #fef3c7; color: #b45309;">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                    <p class="lv-stat-label">UPCOMING DAYS</p>
                    <p class="lv-stat-value"><span id="lvStatUpcoming">0</span> <span class="lv-stat-unit">days</span></p>
                </div>
                <div class="lv-stat-card">
                    <div class="lv-icon-box" style="background: #f4f6f9; color: #6b7c96;">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <p class="lv-stat-label">TOTAL REQUESTS</p>
                    <p class="lv-stat-value"><span id="lvStatTotal">0</span></p>
                </div>
            </div>

            <!-- Upcoming Leaves -->
            <div class="lv-table-card" id="lvUpcomingCard">
                <div class="lv-table-header lv-header-amber">
                    <i class="fa-regular fa-clock"></i>
                    <p>Upcoming Leaves</p>
                    <span class="lv-head-note">You can cancel before the leave date</span>
                </div>
                <div id="lvUpcomingList"></div>
            </div>

            <!-- Leave History -->
            <div class="lv-table-card">
                <div class="lv-table-header lv-header-wrap">
                    <p>Leave History</p>
                    <div class="lv-filter-bar">
                        <span class="lv-filter-label">Filter by date:</span>
                        <div class="lv-filter-group">
                            <span>From</span>
                            <input type="date" class="lv-date-input" id="lvFilterFrom">
                        </div>
                        <span class="lv-filter-sep">&mdash;</span>
                        <div class="lv-filter-group">
                            <span>To</span>
                            <input type="date" class="lv-date-input" id="lvFilterTo">
                        </div>
                        <button type="button" class="lv-btn-clear-filter" id="lvClearFilter" hidden>
                            <i class="fa-solid fa-xmark"></i> Clear
                        </button>
                    </div>
                </div>
                <div class="lv-table-wrapper">
                    <table class="lv-table">
                        <thead>
                            <tr>
                                <th>LEAVE TYPE</th>
                                <th>DATES / TIME</th>
                                <th>DAYS</th>
                                <th>REASON</th>
                                <th>COVER STAFF</th>
                                <th>NOTE</th>
                            </tr>
                        </thead>
                        <tbody id="lvHistoryBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Request Leave panel -->
        <aside class="lv-side-panel" id="lvRequestPanel" hidden>
            <div class="lv-panel-header">
                <div class="lv-panel-header-left">
                    <button type="button" class="lv-btn-back" id="lvBackBtn" aria-label="Back to leave overview" title="Back">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <div>
                        <h2>Request Leave</h2>
                        <p>Select dates &amp; submit for approval</p>
                    </div>
                </div>
                <button type="button" class="modal-close" id="closeLeaveModal" title="Close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="lv-panel-body">
                <div class="form-group full-width">
                    <label class="lv-field-label"><i class="fa-solid fa-layer-group"></i> Leave Type</label>
                    <div class="req-select-wrapper">
                        <select class="req-select" id="lvType">
                            <option value="">Select leave type&hellip;</option>
                            <option>Annual Leave</option>
                            <option>Casual Leave</option>
                            <option>Sick Leave</option>
                            <option>Conference Leave</option>
                            <option>Study Leave</option>
                            <option>Maternity Leave</option>
                            <option>No-Pay Leave</option>
                        </select>
                        <i class="fa-solid fa-chevron-down chevron"></i>
                    </div>
                </div>

                <div class="form-group full-width">
                    <div class="lv-section-header">
                        <label class="lv-field-label"><i class="fa-regular fa-calendar-days"></i> Select Dates</label>
                        <span class="lv-selected-count-badge" id="lvDateCountBadge" style="display: none;">0 Days</span>
                    </div>
                    <div class="lv-calendar-card">
                        <?php require \app\core\Application::$ROOT_DIR . '/views/components/calendar.php'; ?>
                        <div class="lv-manual-date-row">
                            <i class="fa-regular fa-keyboard lv-manual-icon"></i>
                            <input type="text" class="req-input lv-manual-input" id="lvManualDate" placeholder="Type date (YYYY-MM-DD)">
                            <button type="button" class="btn-outline-sm" id="lvAddManualDate">Add</button>
                        </div>
                    </div>
                    <div class="lv-selected-dates" id="lvSelectedDates"></div>
                </div>

                <div class="lv-partial-card">
                    <div class="lv-partial-head">
                        <div class="lv-partial-head-left">
                            <div class="lv-partial-icon"><i class="fa-regular fa-clock"></i></div>
                            <div>
                                <p class="lv-partial-title">Partial Day Leave</p>
                                <p class="lv-partial-desc">Specify specific hours instead of full day</p>
                            </div>
                        </div>
                        <div class="lv-toggle" id="lvPartialToggle" role="button" tabindex="0">
                            <div class="lv-toggle-track" id="lvPartialTrack">
                                <div class="lv-toggle-thumb"></div>
                            </div>
                        </div>
                    </div>
                    <div class="lv-partial-body" id="lvPartialBody" hidden>
                        <div class="lv-time-grid">
                            <div class="form-group" style="margin:0">
                                <label class="lv-time-label">FROM TIME</label>
                                <div class="lv-time-input-wrap">
                                    <input type="time" class="req-input lv-time-input" id="lvTimeFrom" value="08:00">
                                </div>
                            </div>
                            <div class="form-group" style="margin:0">
                                <label class="lv-time-label">TO TIME</label>
                                <div class="lv-time-input-wrap">
                                    <input type="time" class="req-input lv-time-input" id="lvTimeTo" value="10:00">
                                </div>
                            </div>
                            <div class="lv-time-preview" id="lvTimePreview">
                                <i class="fa-regular fa-clock"></i>
                                <span id="lvTimePreviewText"></span>
                            </div>
                        </div>
                    </div>
                    <div class="lv-fullday-note" id="lvFulldayNote">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Full day leave &mdash; absent for the entire day</span>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label class="lv-field-label"><i class="fa-regular fa-pen-to-square"></i> Reason</label>
                    <textarea class="req-input req-textarea" id="lvReason" rows="3" placeholder="Briefly describe the reason for your leave request..."></textarea>
                </div>

                <div class="form-group full-width">
                    <label class="lv-field-label"><i class="fa-solid fa-user-shield"></i> Cover Staff <span class="lv-optional">(optional)</span></label>
                    <div class="req-select-wrapper">
                        <select class="req-select" id="lvCover">
                            <option value="">Select cover staff member&hellip;</option>
                            <?php foreach ($coverStaffOptions as $name): ?>
                                <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down chevron"></i>
                    </div>
                </div>
            </div>
            <div class="lv-panel-footer">
                <div class="btn-row">
                    <button type="button" class="btn-outline" id="cancelLeaveModal">Cancel</button>
                    <button type="button" class="btn-primary-sm lv-submit-btn" id="submitLeaveRequest" disabled>
                        <i class="fa-solid fa-paper-plane"></i> Submit Request
                    </button>
                </div>
            </div>
        </aside>
    </div>
</div>

<script type="application/json" id="leaveData"><?= json_encode(['today' => $today->format('Y-m-d'), 'records' => $leaveRecords]) ?></script>
<script src="/js/instructor/leave.js"></script>
