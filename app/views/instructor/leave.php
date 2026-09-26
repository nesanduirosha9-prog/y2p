<?php

// instructor/leave.php — "Leave" page (LeaveController).
// The member's own leave and the Request / Edit Leave panel. Leave is
// approved on paper, so this only records it: no status, upcoming until its
// last day passes, then history.
//
// Laid out like the Coordinator's Leave Requests page: the Staff Details tab
// bar (.staff-tabs) over two panels, each with its own filters (type, date
// range). Request Leave sits in the Upcoming panel only. Every row renders
// client-side by /js/instructor/leave.js (cells from /js/leave_cells.js) from
// $leaveData, which LeaveController::index() builds from LeaveRequestModel:
//   today    server date, splits Upcoming from History
//   rank     the member's rank — covers share it, and so their badge style
//   records  the member's leave, each with its per-day covers
//   covers   who may cover (same rank, active, not the member)
// $tab is the tab to open on (?tab=upcoming|history).
$panels = [
    'upcoming' => 'Upcoming leave',
    'history' => 'History',
];
?>

<div class="lr-page" id="lvPage">

    <div class="staff-tabs" id="lvTabs" role="tablist">
        <?php foreach ($panels as $key => $label): ?>
            <button type="button" class="staff-tab <?= $tab === $key ? 'active' : '' ?>" data-tab="<?= $key ?>" role="tab">
                <span><?= $label ?></span>
                <span class="staff-tab-badge" data-count="<?= $key ?>"></span>
            </button>
        <?php endforeach; ?>
    </div>

    <?php foreach ($panels as $key => $label): ?>
        <section class="lr-panel" data-panel="<?= $key ?>" <?= $tab === $key ? '' : 'hidden' ?>>
            <div class="lr-toolbar" data-filters="<?= $key ?>">
                <label class="lr-filter">
                    <span>Type</span>
                    <select class="lr-select" data-filter="type">
                        <option value="all">All types</option>
                        <option value="sick">Sick leave</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label class="lr-filter">
                    <span>From</span>
                    <input type="date" class="lr-select" data-filter="from">
                </label>
                <label class="lr-filter">
                    <span>To</span>
                    <input type="date" class="lr-select" data-filter="to">
                </label>
                <button type="button" class="lr-clear" data-clear hidden>Clear filters</button>
                <?php if ($key === 'upcoming'): ?>
                    <button type="button" class="btn-primary-sm lr-toolbar-end" id="requestLeaveBtn">Request Leave</button>
                <?php endif; ?>
            </div>

            <div class="dir-card">
                <p class="lr-count" data-summary="<?= $key ?>"></p>
                <div class="dir-scroll">
                    <table class="leave-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Dates</th>
                                <th>Cover staff</th>
                                <th>Reason</th>
                                <?php if ($key === 'upcoming'): ?><th></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody data-rows="<?= $key ?>"></tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php endforeach; ?>

        <!-- Request Leave panel — .floating-panel (components.css) opens it over the page -->
        <aside class="lv-side-panel floating-panel" id="lvRequestPanel" hidden>
            <div class="lv-panel-header">
                <div class="lv-panel-header-left">
                    <button type="button" class="lv-btn-back" id="lvBackBtn" aria-label="Back to leave overview" title="Back">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <div>
                        <h2 id="lvPanelTitle">Request Leave</h2>
                        <p id="lvPanelSub">Select dates and a cover for each day</p>
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
                                <option value="sick">Sick Leave</option>
                                <option value="other">Other</option>
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
                        <i class="fa-solid fa-paper-plane"></i> <span id="lvSubmitLabel">Submit</span>
                    </button>
                </div>
            </div>
        </aside>
</div>

<script type="application/json" id="leaveData"><?= json_encode($leaveData) ?></script>
<script src="/js/leave_cells.js"></script>
<script src="/js/instructor/leave.js"></script>

