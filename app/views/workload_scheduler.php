<?php

// workload_scheduler.php — the Duty Scheduler (/workload/scheduler), Coordinator only.
//
// The other half of the workload job. The Workload Matrix allocates staff to
// courses for the semester; this screen covers one dated duty at a time —
// "who is free this Thursday 1–3pm, and who is owed work?".
//
// It reproduces the spreadsheet's Request -> Main -> allocate -> invite flow
// (CurrentViews/Duty_Allocator_V8_25_II + script.js) as three steps you can see
// at once, instead of five tabs and a macro menu:
//
//   1. Requests   the queue lecturers file into      (Request sheet)
//   2. This week  duties needing cover               (Main sheet)
//   3. Invites    what gets sent once filled         (previewEmail / sendDutyInvitation)
//
// A SHELL only: js/scheduler.js renders every card from the payload at the
// bottom, and runs the real allocation rules against it. See that file for the
// algorithm — it is a faithful port of assignLowestWorkloadForWeek(), so the
// backend version is a translation rather than a fresh design.
?>

<div class="scheduler-container sched-page" id="schedPage">

    <div class="page-head">
        <div>
            <h2>Duty Scheduler</h2>
            <p class="page-head-sub">
                Triage what lecturers ask for, fill each duty with the least-loaded staff who are actually free, then send the invites.
            </p>
        </div>
        <div class="page-head-actions">
            <a href="/workload/distribution" class="btn-outline">
                <i class="fa-solid fa-table-cells"></i> Workload Matrix
            </a>
            <button type="button" class="btn-primary" id="autoAllocateBtn">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-allocate week
            </button>
        </div>
    </div>

    <!-- KPI strip, all derived -->
    <div class="wm-kpi-grid sched-kpi-grid">
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-purple"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num" id="kpiWeek">—</p>
                <p class="wm-kpi-label" id="kpiWeekRange">Active week</p>
            </div>
        </div>
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-yellow"><i class="fa-solid fa-inbox"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num" id="kpiRequests">—</p>
                <p class="wm-kpi-label">Requests waiting</p>
            </div>
        </div>
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-blue"><i class="fa-solid fa-user-check"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num" id="kpiFilled">—</p>
                <p class="wm-kpi-label">Duty slots filled</p>
            </div>
        </div>
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num" id="kpiConflicts">—</p>
                <p class="wm-kpi-label">Clashes to resolve</p>
            </div>
        </div>
    </div>

    <!-- Step tabs -->
    <div class="sched-nav-tabs" role="tablist">
        <button type="button" class="sched-nav-tab active" data-view="week" role="tab" aria-selected="true">
            <i class="fa-solid fa-calendar-week"></i>
            <span>This week</span>
            <span class="sched-tab-badge" id="tabWeekBadge">—</span>
        </button>
        <button type="button" class="sched-nav-tab" data-view="requests" role="tab" aria-selected="false">
            <i class="fa-solid fa-inbox"></i>
            <span>Requests</span>
            <span class="sched-tab-badge alert-badge" id="tabRequestsBadge">—</span>
        </button>
        <button type="button" class="sched-nav-tab" data-view="availability" role="tab" aria-selected="false">
            <i class="fa-solid fa-table-list"></i>
            <span>Who's free</span>
        </button>
    </div>

    <!-- 1. This week -->
    <section class="sched-view-panel" id="panelWeek">
        <div class="dir-card sched-card">
            <div class="sched-panel-header">
                <div>
                    <h3 id="weekHeading">Duties this week</h3>
                    <p class="page-head-sub">Each card is one dated duty. Auto-allocate fills the gaps; you can still swap anyone by hand.</p>
                </div>
                <div class="sched-legend">
                    <span class="legend-item"><span class="legend-dot dot-green"></span> Filled</span>
                    <span class="legend-item"><span class="legend-dot dot-yellow"></span> Needs staff</span>
                    <span class="legend-item"><span class="legend-dot dot-red"></span> Clash</span>
                </div>
            </div>
            <div class="duty-cards-grid" id="dutyGrid"></div>
            <p class="dir-empty" id="dutyEmpty" hidden>Nothing scheduled this week yet. Approve a request to get started.</p>
        </div>
    </section>

    <!-- 2. Requests queue -->
    <section class="sched-view-panel" id="panelRequests" hidden>
        <div class="dir-card sched-card">
            <div class="sched-panel-header">
                <div>
                    <h3>Requests from lecturers</h3>
                    <p class="page-head-sub">Tick the ones to run this week. Approving moves them onto the board and allocates them in one step.</p>
                </div>
                <div class="sched-batch-actions">
                    <button type="button" class="btn-batch-start" id="batchApproveBtn" disabled>
                        <i class="fa-solid fa-play"></i> Approve &amp; allocate (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>
            <div class="dir-scroll">
                <table class="dir-table" id="requestsTable">
                    <thead>
                        <tr>
                            <th style="width:42px;"><input type="checkbox" id="selectAllRequests" aria-label="Select all requests"></th>
                            <th style="width:150px;">Requested by</th>
                            <th style="min-width:210px;">Duty</th>
                            <th style="width:130px;">Date</th>
                            <th style="min-width:170px;">Time</th>
                            <th style="width:90px;text-align:center;">Staff</th>
                            <th style="width:160px;">Can we fill it?</th>
                        </tr>
                    </thead>
                    <tbody id="requestsBody"></tbody>
                </table>
            </div>
            <p class="dir-empty" id="requestsEmpty" hidden>The queue is clear.</p>
        </div>
    </section>

    <!-- 3. Availability grid -->
    <section class="sched-view-panel" id="panelAvailability" hidden>
        <div class="dir-card sched-card">
            <div class="sched-panel-header">
                <div>
                    <h3>Who's free</h3>
                    <p class="page-head-sub">
                        The weekly availability every allocation is checked against — the five Monday–Friday sheets, in one grid.
                        Staff maintain their own row; on leave beats free.
                    </p>
                </div>
                <div class="sched-avail-controls">
                    <div class="seg" id="availDaySeg" role="group" aria-label="Weekday"></div>
                </div>
            </div>
            <div class="dir-scroll">
                <table class="dir-table avail-table" id="availTable">
                    <thead id="availHead"></thead>
                    <tbody id="availBody"></tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Allocation explanation: why these people, and who was ruled out -->
    <div class="modal-overlay" id="allocModal" hidden>
        <div class="modal sched-modal">
            <div class="modal-head">
                <h3 id="allocModalTitle">Allocation result</h3>
                <button type="button" class="modal-close" id="allocModalClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body" id="allocModalBody"></div>
            <div class="modal-foot">
                <button type="button" class="btn-outline" id="allocModalDismiss">Close</button>
            </div>
        </div>
    </div>

    <!-- Manual swap picker -->
    <div class="modal-overlay" id="swapModal" hidden>
        <div class="modal sched-modal">
            <div class="modal-head">
                <h3 id="swapModalTitle">Add staff</h3>
                <button type="button" class="modal-close" id="swapModalClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body" id="swapModalBody"></div>
            <div class="modal-foot">
                <button type="button" class="btn-outline" id="swapModalDismiss">Done</button>
            </div>
        </div>
    </div>

    <!-- Invite preview — the spreadsheet's previewEmail(), shown honestly -->
    <div class="modal-overlay" id="inviteModal" hidden>
        <div class="modal sched-modal sched-modal-wide">
            <div class="modal-head">
                <h3>Invite preview</h3>
                <button type="button" class="modal-close" id="inviteModalClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body" id="inviteModalBody"></div>
            <div class="modal-foot sched-invite-foot">
                <p class="sched-invite-note">
                    <i class="fa-solid fa-circle-info"></i>
                    Preview only — sending mail and creating the calendar event are backend work, not wired up yet.
                </p>
                <button type="button" class="btn-outline" id="inviteModalDismiss">Close</button>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="schedData"><?= json_encode($schedulerData) ?></script>
<script src="/js/scheduler.js"></script>
