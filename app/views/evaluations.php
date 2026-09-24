<?php

// evaluations.php — lecturer evaluations of junior staff, at /evaluations.
//
// One screen for the Coordinator and the Department In-Charge. It used to be
// two sets of copy ("Evaluations Dashboard" / "Appraisal Center") over one
// dataset and one workflow, which read as two different tools; they now get the
// same page, and only the sidebar word differs.
//
// A SHELL: js/evaluations.js renders the list and the detail drawer from the
// payload below, which EvaluationsController::index() builds. The fixture that
// used to live in components/evaluations_review.php is gone.
//
// Status is binary — Evaluated or Not evaluated — because the question this
// page answers is "did the lecturer do this week's evaluation?". Lecturers
// sometimes forget a week, and that gap is what the Coordinator needs to see.

?>

<div class="<?= htmlspecialchars($viewClass) ?> eval-page" id="evalPage">

    <div class="page-head">
        <div>
            <h2><?= htmlspecialchars($heading) ?></h2>
            <p class="page-head-sub"><?= htmlspecialchars($subheading) ?></p>
        </div>
        <div class="page-head-actions">
            <button type="button" class="btn-outline" id="evalExportBtn">
                <i class="fa-solid fa-file-arrow-down"></i> Export summary
            </button>
        </div>
    </div>

    <!-- Which stretch of time the page is showing: this week by default,
         with arrows back through earlier weeks, months, semesters, years. -->
    <div class="dir-card eval-period-card">
        <div id="evPeriodNav"></div>
    </div>

    <!-- KPIs, all derived from the payload for the chosen period -->
    <div class="wm-kpi-grid">
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-blue"><i class="fa-solid fa-clipboard-list"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num" id="evKpiDue">—</p>
                <p class="wm-kpi-label">Evaluations due</p>
            </div>
        </div>
        <div class="wm-kpi-card wm-kpi-clickable" id="evKpiDoneCard" role="button" tabindex="0"
             title="Show only the evaluated ones">
            <div class="wm-kpi-icon icon-green"><i class="fa-solid fa-circle-check"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num" id="evKpiDone">—</p>
                <p class="wm-kpi-label">Evaluated</p>
            </div>
        </div>
        <div class="wm-kpi-card wm-kpi-clickable" id="evKpiMissingCard" role="button" tabindex="0"
             title="Show only the ones not evaluated">
            <div class="wm-kpi-icon icon-red"><i class="fa-solid fa-clock"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num" id="evKpiMissing">—</p>
                <p class="wm-kpi-label">Not evaluated</p>
            </div>
        </div>
        <div class="wm-kpi-card">
            <div class="wm-kpi-icon icon-purple"><i class="fa-solid fa-star"></i></div>
            <div class="wm-kpi-data">
                <p class="wm-kpi-num" id="evKpiAvg">—</p>
                <p class="wm-kpi-label">Average score (out of 5)</p>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="wm-toolbar">
        <div class="wm-toolbar-inner">
            <div class="search-box wm-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="evSearch" placeholder="Search junior staff, course or lecturer…" autocomplete="off">
            </div>

            <div class="seg" id="evStatusSeg" role="group" aria-label="Status">
                <button type="button" class="seg-btn active" data-status="all">All</button>
                <button type="button" class="seg-btn" data-status="evaluated">Evaluated</button>
                <button type="button" class="seg-btn" data-status="missing">Not evaluated</button>
            </div>

            <div class="wm-filter-group">
                <label class="wm-filter-label" for="evSort">Sort</label>
                <select class="wm-select" id="evSort">
                    <option value="week">Newest week first</option>
                    <option value="score-desc">Highest score</option>
                    <option value="score-asc">Lowest score</option>
                    <option value="name">Junior staff name</option>
                    <option value="lecturer">Lecturer in charge</option>
                </select>
            </div>

            <button type="button" class="btn-ghost wm-clear" id="evClear" hidden>
                <i class="fa-solid fa-xmark"></i> Clear
            </button>
        </div>
    </div>

    <!-- List: one row per junior staff member, per course, per teaching week -->
    <div class="dir-card">
        <div class="wm-table-head-note" id="evCount"></div>
        <div class="dir-scroll">
            <table class="dir-table" id="evTable">
                <thead>
                    <tr>
                        <th style="min-width:200px;">Junior staff</th>
                        <th style="min-width:200px;">Course module</th>
                        <th style="min-width:220px;">Lecturer in charge</th>
                        <th style="width:130px;">Week</th>
                        <th style="width:100px;">Overall</th>
                        <th style="width:140px;text-align:right;">Status</th>
                    </tr>
                </thead>
                <tbody id="evBody"></tbody>
            </table>
        </div>
        <p class="dir-empty" id="evEmpty" hidden>No evaluations match those filters.</p>
    </div>

    <!-- Detail drawer -->
    <div class="eval-drawer-backdrop" id="evDrawerBackdrop" hidden></div>
    <aside class="eval-drawer" id="evDrawer" hidden aria-labelledby="evDrawerName">
        <div class="eval-drawer-header">
            <div>
                <span class="eval-drawer-tag" id="evDrawerTag">Evaluation</span>
                <h3 class="eval-drawer-title" id="evDrawerName">—</h3>
                <p class="eval-drawer-sub" id="evDrawerCourse">—</p>
            </div>
            <button type="button" class="eval-drawer-close" id="evDrawerClose" aria-label="Close panel">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="eval-drawer-body" id="evDrawerBody"></div>
    </aside>
</div>

<script type="application/json" id="evData"><?= json_encode($evalData) ?></script>
<script src="/js/period_nav.js"></script>
<script src="/js/evaluations.js"></script>
