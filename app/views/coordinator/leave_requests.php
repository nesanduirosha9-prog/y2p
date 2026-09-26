<?php

// coordinator/leave_requests.php — "Leave Requests" page
// (coordinator/LeaveRequestsController), for the Coordinator and the In-Charge.
//
// Two tabs over the same $leaveData records, with the same filters:
//   Upcoming  leave whose last day is today or later, soonest first
//   History   leave that has fully passed, most recent first
// The tab bar is the Staff Details one (.staff-tabs in coordinator/staff.css).
// Every row is rendered by js/leave_requests.js; $tab is the tab to open on.
$panels = [
    'upcoming' => 'Upcoming leave',
    'history' => 'History',
];
?>

<div class="lr-page" id="lrPage">

    <div class="staff-tabs" id="lrTabs" role="tablist">
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
                <input type="search" class="lr-search" data-filter="q" placeholder="Search by name or staff code" autocomplete="off" aria-label="Search staff">
                <label class="lr-filter">
                    <span>Staff</span>
                    <select class="lr-select" data-filter="rank">
                        <option value="all">Everyone</option>
                        <option value="senior">Lecturers</option>
                        <option value="junior">Instructors</option>
                    </select>
                </label>
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
            </div>

            <div class="dir-card">
                <p class="lr-count" data-summary="<?= $key ?>"></p>
                <div class="dir-scroll">
                    <table class="leave-table">
                        <thead>
                            <tr>
                                <th style="width: 90px;">Staff</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Dates</th>
                                <th>Cover staff</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody data-rows="<?= $key ?>"></tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<script type="application/json" id="leaveData"><?= json_encode($leaveData) ?></script>
<script src="/js/leave_cells.js"></script>
<script src="/js/leave_requests.js"></script>
