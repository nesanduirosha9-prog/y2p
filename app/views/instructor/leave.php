<?php

// instructor/leave.php — "Leave" page (LeaveController).
// NOTE (gap): none of this reads/writes the `leave_requests` table
// (migration 009) — see LeaveController.php. $leaveRecords below is a fixed
// demo data set (dates generated relative to "today" so upcoming/history
// always split sensibly regardless of when the page is opened), embedded as
// JSON and rendered entirely client-side by /js/instructor/leave.js — the
// same JSON-payload + JS-render approach views/messages.php uses for
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

$instructorRoster = [
    ['code' => 'TMF', 'name' => 'Ms. Thilini Fernando', 'department' => 'Computer Science'],
    ['code' => 'MKA', 'name' => 'Mr. Kwame Addo', 'department' => 'Computer Science'],
    ['code' => 'MEM', 'name' => 'Ms. Efua Mensah', 'department' => 'Computer Science'],
    ['code' => 'MAB', 'name' => 'Mr. Ato Baidoo', 'department' => 'Computer Science'],
    ['code' => 'MYD', 'name' => 'Ms. Yaa Darko', 'department' => 'Computer Science'],
    ['code' => 'MKO', 'name' => 'Mr. Kojo Amoah', 'department' => 'Computer Science'],
    ['code' => 'MNA', 'name' => 'Ms. Nana Ama', 'department' => 'Computer Science'],
    ['code' => 'MAT', 'name' => 'Mr. Atta Tetteh', 'department' => 'Computer Science'],
    ['code' => 'MEQ', 'name' => 'Ms. Esi Quaye', 'department' => 'Computer Science'],
    ['code' => 'MAD', 'name' => 'Mr. Adom Boateng', 'department' => 'Computer Science'],
    ['code' => 'MYB', 'name' => 'Ms. Yaw Bediako', 'department' => 'Information Systems'],
];

$leaveRecords = [
    [
        'id' => 1,
        'type' => 'Study Leave',
        'dates' => [lvOffsetDate($today, 12), lvOffsetDate($today, 13), lvOffsetDate($today, 14)],
        'reason' => 'Curriculum research',
        'cover_staff' => [
            ['code' => 'MKO', 'name' => 'Mr. Kojo Amoah', 'date' => lvOffsetDate($today, 12)],
            ['code' => 'MKO', 'name' => 'Mr. Kojo Amoah', 'date' => lvOffsetDate($today, 13)],
            ['code' => 'MNA', 'name' => 'Ms. Nana Ama', 'date' => lvOffsetDate($today, 14)],
        ],
        'cancelled' => false,
    ],
    [
        'id' => 2,
        'type' => 'Sick Leave',
        'dates' => [lvOffsetDate($today, 3)],
        'reason' => 'Medical appointment',
        'cover_staff' => [
            ['code' => 'MEM', 'name' => 'Ms. Efua Mensah', 'date' => lvOffsetDate($today, 3)],
        ],
        'cancelled' => false,
        'timeFrom' => '09:00',
        'timeTo' => '12:00',
    ],
    [
        'id' => 3,
        'type' => 'Other',
        'dates' => [lvOffsetDate($today, -35), lvOffsetDate($today, -34), lvOffsetDate($today, -33)],
        'reason' => 'Family event',
        'cover_staff' => [
            ['code' => 'MNA', 'name' => 'Ms. Nana Ama', 'date' => lvOffsetDate($today, -35)],
            ['code' => 'MAB', 'name' => 'Mr. Ato Baidoo', 'date' => lvOffsetDate($today, -34)],
            ['code' => 'MAB', 'name' => 'Mr. Ato Baidoo', 'date' => lvOffsetDate($today, -33)],
        ],
        'cancelled' => false,
    ],
    [
        'id' => 4,
        'type' => 'Sick Leave',
        'dates' => [lvOffsetDate($today, -70)],
        'reason' => 'Fever recovery',
        'cover_staff' => [
            ['code' => 'MYD', 'name' => 'Ms. Yaa Darko', 'date' => lvOffsetDate($today, -70)],
        ],
        'cancelled' => false,
    ],
    [
        'id' => 5,
        'type' => 'Study Leave',
        'dates' => [lvOffsetDate($today, -20), lvOffsetDate($today, -19)],
        'reason' => 'ICCS 2025 Workshop',
        'cover_staff' => [
            ['code' => 'MAB', 'name' => 'Mr. Ato Baidoo', 'date' => lvOffsetDate($today, -20)],
            ['code' => 'TMF', 'name' => 'Ms. Thilini Fernando', 'date' => lvOffsetDate($today, -19)],
        ],
        'cancelled' => true,
    ],
];
?>

<div class="lv-container">
    <div class="lv-header">
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
                <div class="lv-form-row-2">
                    <div class="form-group">
                        <label class="lv-field-label"><i class="fa-solid fa-layer-group"></i> Leave Type</label>
                        <div class="req-select-wrapper">
                            <select class="req-select" id="lvType">
                                <option value="">Select type&hellip;</option>
                                <option>Sick Leave</option>
                                <option>Study Leave</option>
                                <option>Other</option>
                            </select>
                            <i class="fa-solid fa-chevron-down chevron"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="lv-field-label"><i class="fa-regular fa-pen-to-square"></i> Reason <span class="lv-optional" style="font-weight: 400; color: #94a3b8; font-size: 11px;">(Optional)</span></label>
                        <input type="text" class="req-input" id="lvReason" placeholder="Reason (optional)..." autocomplete="off">
                    </div>
                </div>

                <div class="form-group full-width">
                    <div class="lv-section-header">
                        <label class="lv-field-label"><i class="fa-regular fa-calendar-days"></i> Select Dates</label>
                        <span class="lv-selected-count-badge" id="lvDateCountBadge" style="display: none;">0 Days</span>
                    </div>
                    <div class="lv-calendar-card">
                        <?php require \app\core\Application::$ROOT_DIR . '/views/components/calendar.php'; ?>
                        <div class="lv-time-range-bar">
                            <div class="lv-time-bar-top">
                                <label class="lv-time-bar-label"><i class="fa-regular fa-clock"></i> Leave Hours</label>
                                <div class="lv-seg" id="lvDurationSeg" role="group" aria-label="Leave hours">
                                    <button type="button" class="lv-seg-btn active" data-duration="full">Full Day</button>
                                    <button type="button" class="lv-seg-btn" data-duration="partial">Specific Time</button>
                                </div>
                            </div>
                            <div class="lv-time-inputs-row" id="lvTimeInputsRow" hidden>
                                <div class="lv-time-group">
                                    <div class="lv-time-box">
                                        <span class="lv-time-tag">From</span>
                                        <input type="time" class="lv-time-clean-input" id="lvTimeFrom" value="08:00">
                                    </div>
                                    <span class="lv-time-divider"><i class="fa-solid fa-arrow-right"></i></span>
                                    <div class="lv-time-box">
                                        <span class="lv-time-tag">To</span>
                                        <input type="time" class="lv-time-clean-input" id="lvTimeTo" value="12:00">
                                    </div>
                                </div>
                                <div class="lv-time-summary-chip" id="lvTimePreviewText">
                                    <i class="fa-regular fa-clock"></i> 8:00 AM &ndash; 12:00 PM <strong>(4 hrs)</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="lv-selected-dates" id="lvSelectedDates"></div>
                </div>

                <!-- Per-Day Cover Staff Assignment (Instructors Only, Mandatory) -->
                <div class="form-group full-width">
                    <div class="lv-section-header">
                        <label class="lv-field-label" style="margin: 0;">
                            <i class="fa-solid fa-user-shield"></i> Cover Staff by Date <span class="lv-required" style="color: #ef4444; font-size: 11px; font-weight: 700;">* Required</span>
                        </label>
                        <button type="button" class="lv-apply-all-btn" id="lvApplyAllBtn" style="display: none;">
                            <i class="fa-solid fa-clone"></i> Apply to all days
                        </button>
                    </div>
                    <p class="lv-field-hint" style="font-size: 11.5px; color: #64748b; margin-top: 3px; margin-bottom: 8px;">
                        Assign fellow instructors to cover each date of your leave. Every leave day must have an assigned cover instructor before submitting.
                    </p>
                    <div id="lvPerDayCoverContainer">
                        <div class="lv-no-dates-cover-hint" id="lvNoDatesCoverHint">
                            <i class="fa-regular fa-calendar-check"></i> Select dates from the calendar above to assign cover instructors.
                        </div>
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

<script type="application/json" id="leaveData"><?= json_encode([
    'today' => $today->format('Y-m-d'),
    'records' => $leaveRecords,
    'instructors' => $instructorRoster,
    'currentUser' => $_SESSION['staff_code'] ?? 'MKA',
]) ?></script>
<script src="/js/instructor/leave.js"></script>

