<?php

// audit_log.php — the activity log, at /audit (department-wide) and /audit/me
// (your own record). AuditController renders both, with $scope telling them
// apart; see that controller for why it is one view and not two.
//
// A SHELL: js/audit.js renders the feed, the counts and the detail panel from
// the payload at the bottom. The view's job is the furniture — the filter bar
// and the empty states — so there is one place to change how filtering looks.
//
// WHO THIS IS FOR. Several of the lecturers reading this screen are not
// interested in software, and an audit log is the easiest screen in any system
// to make unusable. So, deliberately:
//   - the feed is grouped under plain date headings ("Today", "Yesterday",
//     "Monday, 14 September 2026"), not a dense table with a horizontal
//     scrollbar;
//   - each row is one English sentence — "Dr Sarah Chen approved a leave
//     request" — and not a key like `leave.approved`;
//   - the period control is a single labelled dropdown, not a date-picker
//     puzzle, with the two date boxes appearing only if you ask for them;
//   - the active filters are written out in words above the list with one
//     button that clears everything;
//   - nothing is hover-only, and every control has a visible label.
//
// $scope      'system' | 'own'
// $heading    / $subheading  page copy
// $ownLogUrl  link to the reader's own record, on the system screen only
// $auditData  the payload (AuditPrototypeData::feed)

$isSystem = ($scope ?? 'own') === 'system';
?>

<div class="aud-page" id="auditPage" data-scope="<?= htmlspecialchars($scope) ?>">

    <div class="page-head">
        <div>
            <?php if (!$isSystem): ?>
                <!-- Reached from the profile menu, so no sidebar item leads back.
                     audit.js turns this into history.back() when the reader
                     came from another StaffSync page. -->
                <a href="/timetable" class="aud-back" id="audBack">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            <?php endif; ?>
            <h2><?= htmlspecialchars($heading) ?></h2>
            <p class="page-head-sub"><?= htmlspecialchars($subheading) ?></p>
        </div>
        <div class="page-head-actions">
            <?php if ($isSystem && !empty($ownLogUrl)): ?>
                <a href="<?= htmlspecialchars($ownLogUrl) ?>" class="aud-btn-outline">
                    <i class="fa-solid fa-user"></i> My own record
                </a>
            <?php endif; ?>
            <button type="button" class="aud-btn-outline" id="audExportBtn"
                    title="Downloads exactly the entries you are looking at, as a spreadsheet file">
                <i class="fa-solid fa-file-arrow-down"></i> Download these entries
            </button>
        </div>
    </div>

    <!-- The one thing every reader of an audit log needs told, in the one place
         they will certainly look. Not a tooltip, not a footnote. -->
    <div class="aud-seal-strip">
        <i class="fa-solid fa-lock"></i>
        <p>
            <strong>This is a permanent record.</strong>
            Entries are written by the system as work happens. Nobody can add, change or delete
            one — not you, not the Coordinator, not the Department In-Charge.
        </p>
    </div>

    <!-- Four counts, all derived from the entries actually on the page. The
         last one is a filter: clicking it shows only the failed and refused
         attempts, which is the reason most people open a log at all. -->
    <div class="aud-kpi-grid">
        <div class="aud-kpi">
            <div class="aud-kpi-icon icon-blue"><i class="fa-solid fa-list-ul"></i></div>
            <div>
                <p class="aud-kpi-num" id="audKpiTotal">—</p>
                <p class="aud-kpi-label">Entries you can see</p>
            </div>
        </div>
        <div class="aud-kpi">
            <div class="aud-kpi-icon icon-green"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div>
                <p class="aud-kpi-num" id="audKpiToday">—</p>
                <p class="aud-kpi-label">Recorded today</p>
            </div>
        </div>
        <div class="aud-kpi">
            <div class="aud-kpi-icon icon-purple">
                <i class="fa-solid <?= $isSystem ? 'fa-users' : 'fa-calendar-week' ?>"></i>
            </div>
            <div>
                <p class="aud-kpi-num" id="audKpiPeople">—</p>
                <p class="aud-kpi-label"><?= $isSystem ? 'People active today' : 'Recorded this week' ?></p>
            </div>
        </div>
        <button type="button" class="aud-kpi aud-kpi-btn" id="audKpiProblemCard"
                title="Show only sign-in failures and refused actions">
            <div class="aud-kpi-icon icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
                <p class="aud-kpi-num" id="audKpiProblems">—</p>
                <p class="aud-kpi-label">Failed or refused</p>
                <!-- Spelled out rather than left to a hover state or a cursor
                     change, and it says what it will do next, not what it is. -->
                <p class="aud-kpi-hint" id="audKpiProblemHint">Click to show only these</p>
            </div>
        </button>
    </div>

    <!-- ---------------------------------------------------------------- Filters -->
    <div class="dir-card aud-filters">

        <div class="aud-filter-row">
            <div class="search-box aud-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" id="audSearch" autocomplete="off"
                       placeholder="Search a name, a course, a reference…">
            </div>

            <div class="aud-field">
                <label class="aud-label" for="audPeriod">Time period</label>
                <select class="aud-select" id="audPeriod">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="week" selected>Last 7 days</option>
                    <option value="month">Last 30 days</option>
                    <optgroup label="Semesters">
                        <?php foreach ($auditData['semesters'] as $term): ?>
                            <option value="sem:<?= htmlspecialchars($term['key']) ?>">
                                <?= htmlspecialchars($term['label']) ?><?= $term['current'] ? ' (current)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Years">
                        <option value="year:2026">Year 2026</option>
                        <option value="year:2025">Year 2025</option>
                    </optgroup>
                    <option value="all">Everything on record</option>
                    <option value="custom">Between two dates…</option>
                </select>
            </div>

            <?php if ($isSystem): ?>
                <div class="aud-field">
                    <label class="aud-label" for="audPerson">Person</label>
                    <select class="aud-select" id="audPerson">
                        <option value="">Everyone</option>
                        <?php foreach ($auditData['actors'] as $code => $actor): ?>
                            <option value="<?= htmlspecialchars($code) ?>">
                                <?= htmlspecialchars($actor['name']) ?> (<?= htmlspecialchars($code) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <!-- Only shown when "Between two dates" is chosen, so the common case
             stays a single dropdown. -->
        <div class="aud-filter-row aud-dates" id="audDateRow" hidden>
            <div class="aud-field">
                <label class="aud-label" for="audFrom">From</label>
                <input type="date" class="aud-select" id="audFrom">
            </div>
            <div class="aud-field">
                <label class="aud-label" for="audTo">To</label>
                <input type="date" class="aud-select" id="audTo">
            </div>
        </div>

        <!-- Kind of activity. Buttons rather than a multi-select: the whole list
             is visible at once, each one carries its own count, and they are
             large enough to hit on a phone or with a shaky mouse. -->
        <div class="aud-chip-block">
            <p class="aud-label">Kind of activity</p>
            <div class="aud-chips" id="audChips"></div>
        </div>

        <!-- What is filtered, in words, with one way out. -->
        <div class="aud-summary">
            <p class="aud-summary-text" id="audSummary"></p>
            <button type="button" class="aud-btn-ghost" id="audClear" hidden>
                <i class="fa-solid fa-xmark"></i> Clear all filters
            </button>
        </div>
    </div>

    <!-- What this reader is not shown, said out loud rather than left to guess. -->
    <p class="aud-restriction">
        <i class="fa-solid fa-circle-info"></i>
        <?= htmlspecialchars($auditData['restriction']) ?>
    </p>

    <!-- ------------------------------------------------------------------- Feed -->
    <div class="aud-feed" id="audFeed"></div>

    <div class="aud-empty" id="audEmpty" hidden>
        <i class="fa-regular fa-folder-open"></i>
        <p class="aud-empty-title">Nothing recorded for those filters</p>
        <p class="aud-empty-sub">Try a longer time period, or clear the filters to see everything again.</p>
        <button type="button" class="aud-btn-outline" id="audEmptyClear">Clear all filters</button>
    </div>

    <div class="aud-more" id="audMoreWrap" hidden>
        <button type="button" class="aud-btn-outline aud-more-btn" id="audMoreBtn">Show 50 more</button>
        <p class="aud-more-note" id="audMoreNote"></p>
    </div>

    <!-- --------------------------------------------------------------- Detail -->
    <!-- One entry in full, including where it came from and its integrity
         value. Read-only by design: there is no form here and no save button,
         because there is nothing about a recorded entry that may be changed. -->
    <div class="side-drawer-overlay" id="audDrawerOverlay" hidden>
        <aside class="side-drawer aud-drawer" role="dialog" aria-labelledby="audDrawerTitle" aria-modal="true">
            <div class="side-drawer-header">
                <div>
                    <p class="aud-drawer-tag" id="audDrawerTag">Entry</p>
                    <h3 class="side-drawer-title" id="audDrawerTitle">—</h3>
                    <p class="side-drawer-subtitle" id="audDrawerWhen">—</p>
                </div>
                <button type="button" class="side-drawer-close" id="audDrawerClose" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="side-drawer-body" id="audDrawerBody"></div>
            <div class="side-drawer-footer aud-drawer-foot">
                <p class="aud-drawer-locked">
                    <i class="fa-solid fa-lock"></i> Recorded by the system. This entry cannot be changed.
                </p>
            </div>
        </aside>
    </div>
</div>

<?php
// The whole visible log in one payload, which is honest about being a
// prototype: the real screen asks the server for one page of rows at a time and
// pushes the filters into SQL. The filter code in js/audit.js is written so
// that swap is a change to where `entries` comes from, not to how filtering
// behaves — see the header of app/public/js/audit.js.
?>
<?php
// JSON_HEX_TAG matters here specifically: a log entry's target and detail are
// written from whatever a member typed (a leave reason, a course title), and a
// stray "</script>" inside the payload would end this element early and drop the
// rest of the page. JSON.parse reads the < escapes back unchanged.
?>
<script type="application/json" id="audData"><?= json_encode($auditData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="/js/audit.js"></script>
