<?php

// workload_distribution.php — the Workload Matrix, for the Coordinator and the
// Department In-Charge.
//
// The semester allocation: who supports which course, for which engagement
// type. Its sibling screen, the Duty Scheduler, handles the other half of the
// job — who covers one dated duty this week. Matrix = org chart for the term;
// scheduler = shift rota for the week.
//
// This view is a SHELL. Every row, pill and number is rendered by
// js/workload_matrix.js from the JSON payload at the bottom, which
// WorkloadController::distribution() builds. The ~300 lines of literal course
// arrays that used to live in components/workload_matrix.php are gone; when the
// models land, only the controller changes.
//
// $viewClass / $heading / $subheading / $showSchedulerLink / $canEdit come from
// WorkloadController::COPY. The two wordings differ on purpose: the Coordinator
// is allocating, the In-Charge is overseeing — which is also why only the
// Coordinator gets the assign/unassign controls.

use app\core\WorkloadPrototypeData;
?>

<div class="<?= htmlspecialchars($viewClass) ?> wm-page" id="wmPage" data-can-edit="<?= $canEdit ? '1' : '0' ?>">

    <div class="page-head">
        <div>
            <h2><?= htmlspecialchars($heading) ?></h2>
            <p class="page-head-sub"><?= htmlspecialchars($subheading) ?></p>
        </div>
        <div class="page-head-actions">
            <?php if (!empty($showSchedulerLink)): ?>
                <a href="/workload/scheduler" class="btn-outline">
                    <i class="fa-solid fa-calendar-check"></i> Duty Scheduler
                </a>
            <?php endif; ?>
            <?php if ($canEdit): ?>
                <button type="button" class="btn-primary" id="wmBalanceBtn" title="Suggest moves that even out the load">
                    <i class="fa-solid fa-scale-balanced"></i> Suggest Rebalance
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI strip — all four derived from the payload, never typed -->
    <div class="wm-kpi-grid">
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-blue"><i class="fa-solid fa-graduation-cap"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num" id="kpiCourses">—</p>
                <p class="wm-kpi-label">Course allocations</p>
            </div>
        </div>
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-purple"><i class="fa-solid fa-user-group"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num" id="kpiStaff">—</p>
                <p class="wm-kpi-label">Staff deployed</p>
            </div>
        </div>
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-yellow"><i class="fa-solid fa-scale-balanced"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num" id="kpiAvgHours">—</p>
                <p class="wm-kpi-label">Median load (hrs/week)</p>
            </div>
        </div>
        <div class="wm-kpi-card wm-kpi-clickable" id="kpiIssuesCard" role="button" tabindex="0"
             title="Show only the rows that need attention">
            <div class="wm-kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num" id="kpiIssues">—</p>
                <p class="wm-kpi-label">Needs attention</p>
            </div>
        </div>
    </div>

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
                    <i class="fa-solid fa-scale-balanced"></i>
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

<script type="application/json" id="wmData"><?= json_encode($matrixData) ?></script>
<script src="/js/workload_matrix.js"></script>
