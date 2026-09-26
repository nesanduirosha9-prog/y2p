<?php

// workload_distribution.php — the Workload page, for the Coordinator and the
// Department In-Charge.
//
// Two jobs on one page, as tabs:
//   Courses     the semester allocation — who supports which course, for which
//               engagement type. Both positions; only the Coordinator edits.
//   This week   dated duties needing cover            (spreadsheet Main sheet)
//   Requests    the queue lecturers file into         (Request sheet)
//   Who's free  the availability every duty is checked against (Mon–Fri sheets)
//   History     every allocation over time: duties and course changes
// This week / Requests / Who's free used to be a separate Duty Scheduler page
// and are Coordinator only. History is read-only, so the In-Charge has it too. Matrix = org chart for the term; duties = shift rota for
// the week.
//
// This view is a SHELL. Every row, pill and number is rendered by
// js/workload_matrix.js, js/scheduler.js and js/workload_history.js from the
// JSON payloads at the bottom, which WorkloadController::distribution() builds. js/workload_hub.js
// switches the tabs.
//
// $viewClass / $canEdit come from
// WorkloadController::COPY. $tabs lists the tabs this position may see; $tab is
// the one to open on (?tab=).

use app\core\WorkloadPrototypeData;

// Server-side first paint of the active tab, so nothing flashes before
// workload_hub.js runs.
$panel = fn(string ...$t) => in_array($tab, $t, true) ? '' : 'hidden';
?>

<div class="wm-hub" id="wmHub" data-tab="<?= htmlspecialchars($tab) ?>">

    <?php if (count($tabs) > 1): ?>
    <nav class="sched-nav-tabs hub-tabs" id="hubTabs" role="tablist">
        <button type="button" class="sched-nav-tab" data-tab="courses" role="tab">
            <i class="fa-solid fa-table-cells"></i>
            <span>Courses</span>
        </button>
        <?php if ($schedulerData): ?>
        <button type="button" class="sched-nav-tab" data-tab="week" role="tab">
            <i class="fa-solid fa-calendar-week"></i>
            <span>This week</span>
        </button>
        <button type="button" class="sched-nav-tab" data-tab="requests" role="tab">
            <i class="fa-solid fa-inbox"></i>
            <span>Requests</span>
        </button>
        <button type="button" class="sched-nav-tab" data-tab="free" role="tab">
            <i class="fa-solid fa-table-list"></i>
            <span>Who's free</span>
        </button>
        <?php endif; ?>
        <button type="button" class="sched-nav-tab" data-tab="history" role="tab">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>History</span>
        </button>
    </nav>
    <?php endif; ?>

    <section data-hub-panel="courses" <?= $panel('courses') ?>>
        <div class="<?= htmlspecialchars($viewClass) ?> wm-page" id="wmPage" data-can-edit="<?= $canEdit ? '1' : '0' ?>">

            <!-- Toolbar: view switch, filters, search -->
            <div class="wm-toolbar">
                <div class="wm-toolbar-inner">
                    <div class="seg wm-view-seg" id="wmViewSeg" role="group" aria-label="View mode">
                        <button type="button" class="seg-btn active" data-view="course">
                            <i class="fa-solid fa-book-open"></i> By course
                        </button>
                        <button type="button" class="seg-btn" data-view="staff">
                            <i class="fa-solid fa-user-group"></i> By staff
                        </button>
                    </div>

                    <div class="search-box wm-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="wmSearch" placeholder="Search course, lecturer or staff code…" autocomplete="off">
                    </div>

                    <div class="wm-filter-group">
                        <label class="wm-filter-label" for="wmYear">Year</label>
                        <select class="wm-select" id="wmYear">
                            <option value="all">All years</option>
                            <option value="1">1st year</option>
                            <option value="2">2nd year</option>
                            <option value="3">3rd year</option>
                            <option value="4">4th year</option>
                        </select>
                    </div>

                    <div class="wm-filter-group">
                        <label class="wm-filter-label" for="wmProgram">Programme</label>
                        <select class="wm-select" id="wmProgram">
                            <option value="all">All</option>
                            <option value="CS">CS</option>
                            <option value="IS">IS</option>
                        </select>
                    </div>

                    <div class="wm-filter-group">
                        <label class="wm-filter-label" for="wmEngagement">Engagement</label>
                        <select class="wm-select" id="wmEngagement">
                            <option value="all">All types</option>
                            <?php foreach (WorkloadPrototypeData::ENGAGEMENTS as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Staff load: opens the load drawer. It replaces the old always-on
                         rail of bars, which stopped scaling once every junior member
                         was on it. Shows the picked member when the table is filtered. -->
                    <div class="wm-staff-filter">
                        <button type="button" class="wm-staff-trigger" id="wmStaffBtn" aria-haspopup="dialog">
                            <span id="wmStaffBtnLabel">Staff load</span>
                            <i class="fa-solid fa-chevron-down wm-staff-caret"></i>
                        </button>
                        <button type="button" class="wm-staff-clear" id="wmStaffClear" hidden aria-label="Show all staff" title="Show all staff">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <button type="button" class="btn-ghost wm-clear" id="wmClearFilters" hidden>
                        <i class="fa-solid fa-xmark"></i> Clear
                    </button>

                    <?php if ($canEdit): ?>
                        <button type="button" class="btn-primary wm-balance-btn" id="wmBalanceBtn" title="Suggest moves that even out the load">
                            Suggest Rebalance
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- The matrix itself -->
            <div class="dir-card wm-matrix-card">
                <div class="wm-table-head-note" id="wmResultCount"></div>
                <div class="dir-scroll">
                    <table class="dir-table wm-table" id="wmTable">
                        <thead id="wmThead"></thead>
                        <tbody id="wmTbody"></tbody>
                    </table>
                </div>
                <p class="dir-empty" id="wmEmpty" hidden>Nothing matches those filters.</p>
            </div>

            <!-- One backdrop for both drawers; only one is ever open -->
            <div class="wm-drawer-backdrop" id="wmDrawerBackdrop" hidden></div>

            <!-- Assign drawer: opens from a row's "+" when the Coordinator adds staff -->
            <aside class="wm-drawer" id="wmDrawer" hidden aria-labelledby="wmDrawerTitle">
                <div class="wm-drawer-head">
                    <div>
                        <span class="wm-drawer-tag" id="wmDrawerTag">Assign staff</span>
                        <h3 class="wm-drawer-title" id="wmDrawerTitle">—</h3>
                        <p class="wm-drawer-sub" id="wmDrawerSub">—</p>
                    </div>
                    <button type="button" class="wm-drawer-close" data-drawer-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="wm-drawer-search">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="wmDrawerSearch" placeholder="Filter staff…" autocomplete="off">
                    </div>
                    <p class="wm-drawer-hint">Sorted by current load — lightest first, so the fair pick is the top one.</p>
                </div>
                <div class="wm-drawer-body" id="wmDrawerBody"></div>
            </aside>

            <!-- Load drawer: everyone's weekly hours; picking a member filters the table -->
            <aside class="wm-drawer" id="wmLoadDrawer" hidden aria-labelledby="wmLoadTitle">
                <div class="wm-drawer-head">
                    <div>
                        <span class="wm-drawer-tag">Staff load</span>
                        <h3 class="wm-drawer-title" id="wmLoadTitle">Weekly hours from course allocations</h3>
                        <p class="wm-drawer-sub" id="wmLoadSub">—</p>
                    </div>
                    <button type="button" class="wm-drawer-close" data-drawer-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="wm-drawer-search">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="wmLoadSearch" placeholder="Filter staff…" autocomplete="off">
                    </div>
                    <div class="wm-band-filter" id="wmLoadBand" role="group" aria-label="Load band">
                        <button type="button" class="wm-band-btn is-active" data-band="all">All</button>
                        <button type="button" class="wm-band-btn" data-band="over"><span class="wm-band-dot band-over"></span>Overloaded</button>
                        <button type="button" class="wm-band-btn" data-band="heavy"><span class="wm-band-dot band-heavy"></span>Heavy</button>
                        <button type="button" class="wm-band-btn" data-band="ok"><span class="wm-band-dot band-ok"></span>Balanced</button>
                        <button type="button" class="wm-band-btn" data-band="under"><span class="wm-band-dot band-under"></span>Under-used</button>
                    </div>
                    <p class="wm-drawer-hint">Heaviest first. Pick someone to filter the table to their rows.</p>
                </div>
                <div class="wm-drawer-body" id="wmLoadBody"></div>
            </aside>

            <!-- Rebalance suggestions -->
            <div class="modal-overlay" id="wmBalanceModal" hidden>
                <div class="modal wm-balance-modal">
                    <div class="modal-head">
                        <h3>Suggested rebalance</h3>
                        <button type="button" class="modal-close" id="wmBalanceClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body" id="wmBalanceBody"></div>
                    <div class="modal-foot">
                        <button type="button" class="btn-outline" id="wmBalanceDismiss">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if ($schedulerData): ?>
    <div class="sched-page" id="schedPage">

        <!-- This week: one row per duty, grouped by day -->
        <section class="sched-view-panel" id="panelWeek" data-hub-panel="week" <?= $panel('week') ?>>
            <div class="dir-card sched-card">
                <div class="sched-panel-header">
                    <div>
                        <h3 id="weekHeading">Duties this week</h3>
                        <p class="page-head-sub">Auto-allocate swaps in leave covers and fills every gap with the least-loaded staff who are free. Use + to change anyone by hand.</p>
                    </div>
                    <button type="button" class="btn-primary" id="autoAllocateBtn">
                        Auto-allocate week
                    </button>
                </div>
                <div class="dir-scroll">
                    <table class="dir-table duty-table">
                        <thead>
                            <tr>
                                <th style="width:110px;">Date</th>
                                <th style="width:150px;">Time</th>
                                <th style="width:110px;">Course</th>
                                <th style="min-width:200px;">Duty</th>
                                <th style="width:110px;">Requested by</th>
                                <th style="min-width:260px;">Assigned staff</th>
                                <th style="width:80px;">Staff</th>
                                <th style="width:140px;text-align:right;"></th>
                            </tr>
                        </thead>
                        <tbody id="dutyGrid"></tbody>
                    </table>
                </div>
                <p class="dir-empty" id="dutyEmpty" hidden>Nothing scheduled this week yet. Approve a request to get started.</p>
            </div>
        </section>

        <!-- Requests queue -->
        <section class="sched-view-panel" id="panelRequests" data-hub-panel="requests" <?= $panel('requests') ?>>
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
                                <th style="min-width:240px;">Duty</th>
                                <th style="width:130px;">Date</th>
                                <th style="width:190px;">Time</th>
                                <th style="width:80px;text-align:center;">Staff</th>
                                <th style="width:160px;">Can we fill it?</th>
                            </tr>
                        </thead>
                        <tbody id="requestsBody"></tbody>
                    </table>
                </div>
                <p class="dir-empty" id="requestsEmpty" hidden>The queue is clear.</p>
            </div>
        </section>

        <!-- Who's free -->
        <section class="sched-view-panel" id="panelAvailability" data-hub-panel="free" <?= $panel('free') ?>>
            <div class="dir-card sched-card">
                <div class="sched-panel-header">
                    <div>
                        <h3>Who's free</h3>
                        <p class="page-head-sub">
                            The weekly availability every allocation is checked against. Pick one person to see their whole week.
                        </p>
                    </div>
                    <div class="sched-avail-controls">
                        <div class="sched-combo" id="availStaffCombo">
                            <button type="button" class="sched-combo-trigger" id="availStaffBtn" aria-haspopup="listbox" aria-expanded="false">
                                <i class="fa-solid fa-user"></i>
                                <span id="availStaffLabel">All staff</span>
                                <i class="fa-solid fa-chevron-down sched-combo-caret"></i>
                            </button>
                            <div class="sched-combo-pop" id="availStaffPop" hidden>
                                <div class="search-box">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" id="availStaffSearch" placeholder="Search name or code…" autocomplete="off">
                                </div>
                                <div class="sched-combo-list" id="availStaffList" role="listbox"></div>
                            </div>
                        </div>
                        <div class="seg" id="availDaySeg" role="group" aria-label="Weekday"></div>
                    </div>
                </div>
                <div class="sched-legend">
                    <span class="legend-item"><span class="avail-swatch is-free"></span> Free</span>
                    <span class="legend-item"><span class="avail-swatch is-busy"></span> Busy — unavailable, on leave or on duty (hover to see which)</span>
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

        <!-- Manual add/remove picker -->
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
    <?php endif; ?>

    <!-- History: every allocation, before this week and during it -->
    <section class="hist-page" data-hub-panel="history" <?= $panel('history') ?>>

        <!-- One toolbar: when (period picker), then what (search, kind, grouping) -->
        <div class="wm-toolbar hist-toolbar">
            <div id="histPeriodNav"></div>
            <div class="wm-toolbar-inner">
                <div class="search-box wm-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="histSearch" placeholder="Search staff, course or lecturer…" autocomplete="off">
                </div>

                <div class="seg" id="histKindSeg" role="group" aria-label="Kind">
                    <button type="button" class="seg-btn active" data-kind="all">All</button>
                    <button type="button" class="seg-btn" data-kind="duty">Duties</button>
                    <button type="button" class="seg-btn" data-kind="course">Course changes</button>
                </div>

                <div class="wm-filter-group">
                    <label class="wm-filter-label" for="histGroup">Group by</label>
                    <select class="wm-select" id="histGroup">
                        <option value="none">No grouping</option>
                        <option value="week">Week</option>
                        <option value="date">Day</option>
                        <option value="course">Course</option>
                        <option value="lecturer">Lecturer</option>
                        <option value="staff">Staff member</option>
                    </select>
                </div>

                <button type="button" class="btn-ghost wm-clear" id="histClear" hidden>
                    <i class="fa-solid fa-xmark"></i> Clear
                </button>
            </div>
        </div>

        <div class="dir-card">
            <div class="wm-table-head-note" id="histCount"></div>
            <div class="dir-scroll">
                <table class="dir-table hist-table" id="histTable">
                    <!-- Click a heading to sort by it; click again to reverse. -->
                    <thead id="histHead">
                        <tr>
                            <!-- Shares of the width, not pixels, so the columns spread across the card. -->
                            <th style="width:14%;"><button type="button" class="hist-sort" data-sort="date">Date</button></th>
                            <th style="width:22%;"><button type="button" class="hist-sort" data-sort="staff">Staff</button></th>
                            <th style="width:15%;"><button type="button" class="hist-sort" data-sort="course">Course</button></th>
                            <th style="width:14%;"><button type="button" class="hist-sort" data-sort="lecturer">Lecturer</button></th>
                            <th style="width:22%;">What</th>
                            <th style="width:13%;"><button type="button" class="hist-sort" data-sort="how">How</button></th>
                        </tr>
                    </thead>
                    <tbody id="histBody"></tbody>
                </table>
            </div>
            <p class="dir-empty" id="histEmpty" hidden>Nothing allocated in this period.</p>
        </div>
    </section>
</div>

<script src="/js/period_nav.js"></script>
<script type="application/json" id="histData"><?= json_encode($historyData) ?></script>
<!-- Before the matrix and scheduler scripts: they announce their state as they
     start, and History has to be listening. -->
<script src="/js/workload_history.js"></script>
<script type="application/json" id="wmData"><?= json_encode($matrixData) ?></script>
<script src="/js/workload_matrix.js"></script>
<?php if ($schedulerData): ?>
    <script type="application/json" id="schedData"><?= json_encode($schedulerData) ?></script>
    <script src="/js/scheduler.js"></script>
<?php endif; ?>
<!-- Last: its first show() fires hub:tab, which scheduler.js must already hear. -->
<script src="/js/workload_hub.js"></script>
