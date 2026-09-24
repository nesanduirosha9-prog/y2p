// workload_matrix.js — the Workload Matrix screen (/workload/distribution).
//
// Renders everything from the #wmData payload that
// WorkloadController::distribution() emits. No markup in here depends on the
// fixture being fake: when the models land, the same JSON arrives from the
// database and this file does not change.
//
// What it does beyond the spreadsheet it replaces:
//   - two readings of one dataset: by course (the sheet's shape) and by staff
//     (who is doing what), which the sheet cannot show at all;
//   - a staff-load drawer that recomputes the moment you assign or unassign,
//     so the consequence of an allocation is visible while you make it;
//   - assignment from a list sorted lightest-load-first, so the fair choice is
//     the default choice rather than something you work out by eye;
//   - "needs attention" derived from the data (under-staffed, overloaded,
//     inactive staff still allocated) instead of hand-coloured red cells.
//
// PROTOTYPE BOUNDARY: edits live in memory. Every mutation funnels through
// assign() / unassign(), which is where the future POST goes.
(function () {
    const root = document.getElementById('wmPage');
    const dataEl = document.getElementById('wmData');
    if (!root || !dataEl) return;

    const DATA = JSON.parse(dataEl.textContent);
    const canEdit = DATA.canEdit === true;

    // Course rows are mutated in place by assign/unassign.
    const courses = DATA.courses.map((c, i) => Object.assign({ _id: 'c' + i }, c));
    const staffByCode = {};
    DATA.staff.forEach(s => { staffByCode[s.code] = s; });

    const ENGAGEMENTS = DATA.engagements;

    // Load thresholds, in weekly hours. Derived from the median so the bands
    // stay meaningful whatever the department's size.
    const BAND = { light: 0.6, heavy: 1.25, overload: 1.5 };

    const BAND_LABEL = { over: 'Overloaded', heavy: 'Heavy', ok: 'Balanced', under: 'Under-used' };

    const state = { view: 'course', search: '', year: 'all', program: 'all', engagement: 'all', staff: null, issuesOnly: false };

    // ---------------------------------------------------------------- helpers
    const el = id => document.getElementById(id);
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, m => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]
    ));
    const engagementLabel = k => ENGAGEMENTS[k] || k;
    // Maps the engagement key to the existing .duty-tag-* palette in
    // workload_matrix.css rather than deriving it from the key, because
    // assignment_marking and coordination_* do not split cleanly.
    const ENGAGEMENT_CLASS = {
        practical: 'duty-tag-practical',
        tutorial: 'duty-tag-tutorial',
        assignment_marking: 'duty-tag-marking',
        coordination_lecture: 'duty-tag-coordination',
        coordination_project: 'duty-tag-coordination',
    };
    const engagementClass = k => ENGAGEMENT_CLASS[k] || 'duty-tag-practical';
    const yearLabel = y => ({ 1: '1st', 2: '2nd', 3: '3rd', 4: '4th' }[y] || y) + ' year';

    /** Recompute every staff member's load from the current allocations. */
    function computeLoad() {
        const load = {};
        DATA.staff.forEach(s => {
            load[s.code] = { code: s.code, name: s.name, active: s.active, paused: s.paused, courses: 0, hours: 0 };
        });
        courses.forEach(c => {
            c.instructors.forEach(code => {
                if (load[code]) {
                    load[code].courses++;
                    load[code].hours += Number(c.hours) || 0;
                }
            });
        });
        return load;
    }

    function median(nums) {
        if (!nums.length) return 0;
        const s = [...nums].sort((a, b) => a - b);
        const m = Math.floor(s.length / 2);
        return s.length % 2 ? s[m] : (s[m - 1] + s[m]) / 2;
    }

    /** Which band a person's hours fall into, relative to the department median. */
    function bandFor(hours, med) {
        if (med === 0) return 'ok';
        if (hours >= med * BAND.overload) return 'over';
        if (hours >= med * BAND.heavy) return 'heavy';
        if (hours <= med * BAND.light) return 'under';
        return 'ok';
    }

    /** Median weekly hours across active staff — the reference the bands are cut from. */
    function medianLoad(load) {
        return median(Object.values(load).filter(l => l.active).map(l => l.hours));
    }

    /**
     * Why this person may not take this course, or '' if they may. The one
     * rule behind both the drawer's disabled buttons and assign()'s guard, so
     * the two cannot disagree. A timetable-clash check belongs here too once
     * sessions are modelled. Advisory only — the server must re-check.
     */
    function blockReason(l, c, med) {
        if (!l.active) return 'Inactive';
        if (l.paused) return 'Temporarily paused';
        if (med && l.hours + (Number(c.hours) || 0) >= med * BAND.overload) return 'Would be overloaded';
        return '';
    }

    /** Problems with one course row, as human sentences. */
    function issuesFor(c) {
        const out = [];
        if (c.instructors.length < c.target) {
            out.push('Needs ' + (c.target - c.instructors.length) + ' more (' + c.instructors.length + ' of ' + c.target + ')');
        }
        c.instructors.forEach(code => {
            const s = staffByCode[code];
            if (!s) { out.push(code + ' is not on the roster'); return; }
            if (!s.active) out.push(s.name + ' is inactive');
            else if (s.paused) out.push(s.name + ' is temporarily paused');
        });
        const seen = new Set();
        c.instructors.forEach(code => {
            if (seen.has(code)) out.push(code + ' is listed twice');
            seen.add(code);
        });
        return out;
    }

    // ------------------------------------------------------------- filtering
    function visibleCourses() {
        const q = state.search.trim().toLowerCase();
        return courses.filter(c => {
            if (state.year !== 'all' && String(c.year) !== state.year) return false;
            if (state.program !== 'all' && c.program !== state.program) return false;
            if (state.engagement !== 'all' && c.engagement !== state.engagement) return false;
            if (state.staff && !c.instructors.includes(state.staff)) return false;
            if (state.issuesOnly && issuesFor(c).length === 0) return false;
            if (q) {
                const hay = (c.code + ' ' + c.name + ' ' + c.lecturer + ' ' + c.lecturer_name + ' ' +
                    c.program + ' ' + engagementLabel(c.engagement) + ' ' + c.instructors.join(' ')).toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });
    }

    // ------------------------------------------------------------- rendering
    function renderKpis(load, med) {
        const values = Object.values(load);
        const deployed = values.filter(l => l.courses > 0).length;
        const issueCount = courses.reduce((n, c) => n + (issuesFor(c).length ? 1 : 0), 0);

        el('kpiCourses').textContent = courses.length;
        el('kpiStaff').textContent = deployed + ' / ' + values.length;
        el('kpiAvgHours').textContent = med.toFixed(1);
        el('kpiIssues').textContent = issueCount;
        el('kpiIssuesCard').classList.toggle('is-active', state.issuesOnly);
        el('kpiIssuesCard').classList.toggle('has-issues', issueCount > 0);
    }

    /**
     * One staff member as a drawer row — shared by the assign drawer and the
     * load drawer. `opts.tag` is 'button' when the whole row is the action.
     */
    function staffRow(l, med, opts) {
        const band = bandFor(l.hours, med);
        const tag = opts.tag || 'div';
        const type = tag === 'button' ? ' type="button"' : '';
        return `
            <${tag}${type} class="wm-cand ${opts.classes || ''}" ${opts.attrs || ''}>
                ${codeBadge(l.code, 'staff')}
                <span class="wm-cand-id">
                    <span class="wm-cand-name">${esc(l.name)}</span>
                    <span class="wm-cand-meta">${l.courses} course${l.courses === 1 ? '' : 's'} · ${l.hours}h/week${opts.reason ? ' · <b>' + esc(opts.reason) + '</b>' : ''}</span>
                </span>
                <span class="wm-cand-band band-${band}" title="${esc(BAND_LABEL[band])}"></span>
                ${opts.action || ''}
            </${tag}>`;
    }

    /** The toolbar trigger: "Staff load", or the member the table is filtered to. */
    function renderStaffFilter() {
        const s = state.staff ? staffByCode[state.staff] : null;
        el('wmStaffBtnLabel').innerHTML = state.staff
            ? codeBadge(state.staff, 'staff') + (s ? ' ' + esc(s.name) : '')
            : 'Staff load';
        el('wmStaffBtn').classList.toggle('is-filtered', !!state.staff);
        el('wmStaffClear').hidden = !state.staff;
    }

    let loadBand = 'all';

    function renderLoadDrawer() {
        const load = computeLoad();
        const med = medianLoad(load);
        const q = el('wmLoadSearch').value.trim().toLowerCase();
        const all = Object.values(load);

        el('wmLoadSub').textContent = all.length + ' staff · median ' + med.toFixed(1) + 'h/week';

        const rows = all
            .filter(l => loadBand === 'all' || bandFor(l.hours, med) === loadBand)
            .filter(l => !q || (l.code + ' ' + l.name).toLowerCase().includes(q))
            .sort((a, b) => b.hours - a.hours || a.code.localeCompare(b.code));

        el('wmLoadBody').innerHTML = rows.map(l => {
            const selected = state.staff === l.code;
            return staffRow(l, med, {
                tag: 'button',
                classes: 'wm-cand-pick' + (selected ? ' is-selected' : '') + (!l.active ? ' is-blocked' : ''),
                attrs: `data-pick-staff="${esc(l.code)}" aria-pressed="${selected}"`,
                reason: !l.active ? 'Inactive' : l.paused ? 'Paused' : '',
                action: selected ? '<i class="fa-solid fa-check wm-cand-tick"></i>' : '',
            });
        }).join('') || '<p class="dir-empty">No staff match.</p>';
    }

    function renderCourseView(list, load, med) {
        el('wmThead').innerHTML = `
            <tr>
                <th style="width:120px;">Course</th>
                <th style="min-width:190px;">Title</th>
                <th style="width:120px;">Scope</th>
                <th style="width:150px;">Lecturer in-charge</th>
                <th style="width:150px;">Engagement</th>
                <th style="min-width:300px;">Assigned staff</th>
                <th style="width:120px;text-align:right;">Status</th>
            </tr>`;

        el('wmTbody').innerHTML = list.map(c => {
            const issues = issuesFor(c);
            const short = c.instructors.length < c.target;
            const chips = c.instructors.map(code => {
                const s = staffByCode[code];
                const l = load[code];
                const note = c.notes && c.notes[code] ? c.notes[code] : '';
                // One colour for every staff code. Notes and problems live in
                // the tooltip, and a problem also turns the row's status to
                // "Check" (issuesFor), so the chip itself stays plain.
                const why = !s ? 'not on the roster' : !s.active ? 'inactive' : s.paused ? 'temporarily paused' : '';
                return codeBadge(code, 'staff', {
                    title: (s ? s.name : code) + (l ? ' — ' + l.hours + 'h/week' : '') + (note ? ' · ' + note : '') + (why ? ' · ' + why : ''),
                    inner: (canEdit ? `<button type="button" class="wm-chip-x" data-remove="${esc(c._id)}|${esc(code)}" title="Remove ${esc(code)}" aria-label="Remove ${esc(code)}">&times;</button>` : ''),
                });
            }).join('');

            return `
                <tr class="wm-data-row ${issues.length ? 'has-issue' : ''}" data-course="${esc(c._id)}">
                    <td>${codeBadge(c.code, 'course', { title: c.name })}</td>
                    <td><strong class="wm-course-title">${esc(c.name)}</strong></td>
                    <td>
                        <div class="wm-scope-badge">
                            <span class="wm-prog-pill wm-prog-${esc(String(c.program).toLowerCase())}">${esc(c.program)}</span>
                            <span class="wm-year-pill">${esc(yearLabel(c.year))}</span>
                        </div>
                    </td>
                    <td>${codeBadge(c.lecturer, 'lecturer', { title: c.lecturer_name })}</td>
                    <td>
                        <span class="wm-duty-type ${engagementClass(c.engagement)}">${esc(engagementLabel(c.engagement))}</span>
                        <span class="wm-hours-note">${esc(c.hours)}h/wk</span>
                    </td>
                    <td>
                        <div class="wm-instructors-list">
                            ${chips || '<span class="wm-none">None assigned</span>'}
                            ${canEdit ? `<button type="button" class="wm-add-chip" data-add="${esc(c._id)}" title="Assign staff to ${esc(c.code)}"><i class="fa-solid fa-plus"></i></button>` : ''}
                        </div>
                    </td>
                    <td style="text-align:right;">
                        ${issues.length
                            ? `<span class="pill pill-danger" title="${esc(issues.join(' · '))}">${short ? esc(c.instructors.length + '/' + c.target) : 'Check'}</span>`
                            : `<span class="pill pill-active">${esc(c.instructors.length + '/' + c.target)}</span>`}
                    </td>
                </tr>`;
        }).join('');
    }

    function renderStaffView(list, load, med) {
        // Invert the visible courses into one row per staff member. This is the
        // reading the spreadsheet cannot produce — it only has the course axis.
        const byStaff = {};
        Object.values(load).forEach(l => { byStaff[l.code] = { load: l, rows: [] }; });
        list.forEach(c => {
            c.instructors.forEach(code => {
                if (byStaff[code]) byStaff[code].rows.push(c);
            });
        });

        const q = state.search.trim().toLowerCase();
        const rows = Object.values(byStaff)
            .filter(x => x.rows.length > 0 || (!q && !state.issuesOnly && state.staff === null))
            .filter(x => !state.staff || x.load.code === state.staff)
            .sort((a, b) => b.load.hours - a.load.hours);

        el('wmThead').innerHTML = `
            <tr>
                <th style="width:190px;">Staff member</th>
                <th style="width:120px;">Load</th>
                <th style="width:110px;">Status</th>
                <th style="min-width:380px;">Allocated to</th>
            </tr>`;

        el('wmTbody').innerHTML = rows.map(x => {
            const l = x.load;
            const band = bandFor(l.hours, med);
            const status = !l.active ? '<span class="pill pill-danger">Inactive</span>'
                : l.paused ? '<span class="pill pill-warn">Paused</span>'
                : band === 'over' ? '<span class="pill pill-danger">Overloaded</span>'
                : band === 'heavy' ? '<span class="pill pill-warn">Heavy</span>'
                : band === 'under' ? '<span class="pill pill-muted">Under-used</span>'
                : '<span class="pill pill-active">Balanced</span>';

            return `
                <tr class="wm-data-row">
                    <td>
                        <div class="wm-staff-identity">
                            ${codeBadge(l.code, 'staff')}
                            <span class="wm-staff-name">${esc(l.name)}</span>
                        </div>
                    </td>
                    <td>
                        <div class="wm-staff-load band-${band}">
                            <strong>${l.hours}h</strong>
                            <span>${l.courses} course${l.courses === 1 ? '' : 's'}</span>
                        </div>
                    </td>
                    <td>${status}</td>
                    <td>
                        <div class="wm-instructors-list">
                            ${x.rows.map(c => codeBadge(c.code, 'course', {
                                title: c.name + ' — ' + engagementLabel(c.engagement) + ', ' + c.hours + 'h/week',
                                inner: canEdit ? `<button type="button" class="wm-chip-x" data-remove="${esc(c._id)}|${esc(l.code)}" title="Remove from ${esc(c.code)}" aria-label="Remove from ${esc(c.code)}">&times;</button>` : '',
                            })).join('') || '<span class="wm-none">No allocations</span>'}
                        </div>
                    </td>
                </tr>`;
        }).join('');

        return rows.length;
    }

    function render() {
        const load = computeLoad();
        const med = medianLoad(load);
        const list = visibleCourses();

        renderKpis(load, med);
        renderStaffFilter();
        if (openDrawerId === 'wmLoadDrawer') renderLoadDrawer();

        let count;
        if (state.view === 'staff') {
            count = renderStaffView(list, load, med);
        } else {
            renderCourseView(list, load, med);
            count = list.length;
        }

        el('wmEmpty').hidden = count > 0;
        const filtered = state.search || state.year !== 'all' || state.program !== 'all' ||
            state.engagement !== 'all' || state.staff || state.issuesOnly;
        el('wmClearFilters').hidden = !filtered;
        el('wmResultCount').textContent = filtered
            ? `Showing ${count} of ${courses.length} allocations`
            : `${courses.length} allocations`;

        if (state.staff) {
            el('wmResultCount').textContent += ' · filtered to ' + state.staff;
        }
    }

    // ------------------------------------------------------------- mutations
    // The two functions the backend will replace with a POST. Everything that
    // changes an allocation goes through here, so there is exactly one place to
    // add the request and the failure handling.
    function assign(courseId, code) {
        const c = courses.find(x => x._id === courseId);
        if (!c || c.instructors.includes(code)) return;
        const load = computeLoad();
        if (!load[code]) return;
        const why = blockReason(load[code], c, medianLoad(load));
        if (why) {
            toast(code + ' can’t be assigned to ' + c.code + ': ' + why.toLowerCase());
            return;
        }
        c.instructors.push(code);
        announce(c, code, 'added');
        render();
        toast(code + ' assigned to ' + c.code);
    }

    function unassign(courseId, code) {
        const c = courses.find(x => x._id === courseId);
        if (!c || !c.instructors.includes(code)) return;
        c.instructors = c.instructors.filter(x => x !== code);
        announce(c, code, 'removed');
        render();
        toast(code + ' removed from ' + c.code);
    }

    /** Tells the History tab (js/workload_history.js) about a course change. */
    function announce(c, code, how) {
        document.dispatchEvent(new CustomEvent('wm:changed', { detail: {
            kind: 'course',
            date: DATA.today,
            week: DATA.today,
            staff: code,
            course: c.code,
            course_name: c.name,
            lecturer: c.lecturer,
            lecturer_name: c.lecturer_name,
            title: engagementLabel(c.engagement),
            slots: [],
            how,
            note: 'this session',
        } }));
    }

    function toast(msg) {
        if (window.ttToast) { window.ttToast(msg); return; }
        let t = document.getElementById('wmToast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'wmToast';
            t.className = 'wm-toast';
            document.body.appendChild(t);
        }
        t.textContent = msg;
        t.classList.add('show');
        clearTimeout(t._timer);
        t._timer = setTimeout(() => t.classList.remove('show'), 2200);
    }

    // --------------------------------------------------------------- drawers
    // Two drawers (assign, load) share one backdrop and one open/close path.
    // Opening pushes a history entry so a phone's back gesture closes the
    // drawer instead of leaving the page; on phones the drawer is full-width
    // and covers the backdrop, so without this the only way out is the ×.
    let drawerCourse = null;
    let openDrawerId = null;

    function showDrawer(id) {
        const wasOpen = openDrawerId !== null;
        ['wmDrawer', 'wmLoadDrawer'].forEach(d => { el(d).hidden = d !== id; });
        el('wmDrawerBackdrop').hidden = false;
        openDrawerId = id;
        if (!wasOpen) history.pushState({ wmDrawer: true }, '');
    }

    /** fromPop: the back gesture already removed our history entry. */
    function closeDrawers(fromPop) {
        if (openDrawerId === null) return;
        openDrawerId = null;
        drawerCourse = null;
        el('wmDrawer').hidden = true;
        el('wmLoadDrawer').hidden = true;
        el('wmDrawerBackdrop').hidden = true;
        if (fromPop !== true && history.state && history.state.wmDrawer) history.back();
    }

    function openDrawer(courseId) {
        const c = courses.find(x => x._id === courseId);
        if (!c) return;
        drawerCourse = c;
        el('wmDrawerTitle').textContent = c.code + ' — ' + c.name;
        el('wmDrawerSearch').value = '';
        renderDrawer();
        showDrawer('wmDrawer');
        el('wmDrawerSearch').focus();
    }

    function openLoadDrawer() {
        el('wmLoadSearch').value = '';
        renderLoadDrawer();
        showDrawer('wmLoadDrawer');
        el('wmLoadSearch').focus();
    }

    function renderDrawer() {
        if (!drawerCourse) return;
        const c = drawerCourse;
        const load = computeLoad();
        const med = medianLoad(load);
        const q = el('wmDrawerSearch').value.trim().toLowerCase();

        el('wmDrawerSub').textContent = engagementLabel(c.engagement) + ' · ' +
            c.instructors.length + ' of ' + c.target + ' assigned · ' + c.hours + 'h per person';

        // Lightest first — the fair pick is the top of the list, which is the
        // same rule the Duty Scheduler's allocator uses.
        const candidates = Object.values(load)
            .filter(l => !q || (l.code + ' ' + l.name).toLowerCase().includes(q))
            .sort((a, b) => a.hours - b.hours || a.code.localeCompare(b.code));

        el('wmDrawerBody').innerHTML = candidates.map(l => {
            const already = c.instructors.includes(l.code);
            // Someone already on the course can always be removed, even if
            // they would now be blocked from joining it.
            const reason = already ? '' : blockReason(l, c, med);
            return staffRow(l, med, {
                classes: (already ? 'is-assigned' : '') + (reason ? ' is-blocked' : ''),
                reason: reason,
                action: already
                    ? `<button type="button" class="wm-cand-btn is-remove" data-drawer-remove="${esc(l.code)}">Remove</button>`
                    : `<button type="button" class="wm-cand-btn" data-drawer-add="${esc(l.code)}" ${reason ? 'disabled title="' + esc(reason) + '"' : ''}>Assign</button>`,
            });
        }).join('') || '<p class="dir-empty">No staff match that search.</p>';
    }

    // ------------------------------------------------------------- rebalance
    // Pairs the most-loaded person with the least-loaded who could take one of
    // their courses. Advice only — the Coordinator applies it or ignores it.
    function renderBalance() {
        const load = computeLoad();
        const active = Object.values(load).filter(l => l.active && !l.paused);
        const med = median(active.map(l => l.hours));
        const heavy = active.filter(l => bandFor(l.hours, med) === 'over' || bandFor(l.hours, med) === 'heavy')
            .sort((a, b) => b.hours - a.hours);
        const light = active.filter(l => bandFor(l.hours, med) === 'under')
            .sort((a, b) => a.hours - b.hours);

        const moves = [];
        heavy.forEach(h => {
            const theirs = courses.filter(c => c.instructors.includes(h.code));
            theirs.forEach(c => {
                if (moves.length >= 6) return;
                const taker = light.find(l => !c.instructors.includes(l.code));
                if (taker) {
                    moves.push({ course: c, from: h, to: taker });
                    taker.hours += c.hours;
                    h.hours -= c.hours;
                }
            });
        });

        el('wmBalanceBody').innerHTML = moves.length ? `
            <p class="wm-balance-intro">${moves.length} move${moves.length === 1 ? '' : 's'} would flatten the load.
               Nothing is applied until you press Apply.</p>
            ${moves.map((m, i) => `
                <div class="wm-balance-row">
                    <div class="wm-balance-course">
                        ${codeBadge(m.course.code, 'course', { title: m.course.name })}
                        <span class="wm-balance-eng">${esc(engagementLabel(m.course.engagement))}</span>
                    </div>
                    <div class="wm-balance-move">
                        ${codeBadge(m.from.code, 'staff', { title: m.from.name })}
                        <i class="fa-solid fa-arrow-right"></i>
                        ${codeBadge(m.to.code, 'staff', { title: m.to.name })}
                    </div>
                    <button type="button" class="btn-secondary-sm" data-apply-move="${i}">Apply</button>
                </div>`).join('')}
        ` : '<p class="dir-empty">The load is already even — no moves worth making.</p>';

        el('wmBalanceBody').dataset.moves = JSON.stringify(
            moves.map(m => ({ course: m.course._id, from: m.from.code, to: m.to.code }))
        );
    }

    // ----------------------------------------------------------------- wiring
    el('wmViewSeg').addEventListener('click', e => {
        const btn = e.target.closest('[data-view]');
        if (!btn) return;
        state.view = btn.dataset.view;
        el('wmViewSeg').querySelectorAll('.seg-btn').forEach(b => b.classList.toggle('active', b === btn));
        render();
    });

    el('wmSearch').addEventListener('input', e => { state.search = e.target.value; render(); });
    el('wmYear').addEventListener('change', e => { state.year = e.target.value; render(); });
    el('wmProgram').addEventListener('change', e => { state.program = e.target.value; render(); });
    el('wmEngagement').addEventListener('change', e => { state.engagement = e.target.value; render(); });

    el('wmClearFilters').addEventListener('click', () => {
        Object.assign(state, { search: '', year: 'all', program: 'all', engagement: 'all', staff: null, issuesOnly: false });
        el('wmSearch').value = '';
        el('wmYear').value = 'all';
        el('wmProgram').value = 'all';
        el('wmEngagement').value = 'all';
        render();
    });

    el('kpiIssuesCard').addEventListener('click', () => { state.issuesOnly = !state.issuesOnly; render(); });
    el('kpiIssuesCard').addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); state.issuesOnly = !state.issuesOnly; render(); }
    });

    // Staff load drawer
    el('wmStaffBtn').addEventListener('click', openLoadDrawer);
    el('wmStaffClear').addEventListener('click', () => { state.staff = null; render(); });
    el('wmLoadSearch').addEventListener('input', renderLoadDrawer);
    el('wmLoadBand').addEventListener('click', e => {
        const btn = e.target.closest('[data-band]');
        if (!btn) return;
        loadBand = btn.dataset.band;
        el('wmLoadBand').querySelectorAll('.wm-band-btn').forEach(b => b.classList.toggle('is-active', b === btn));
        renderLoadDrawer();
    });
    el('wmLoadBody').addEventListener('click', e => {
        const row = e.target.closest('[data-pick-staff]');
        if (!row) return;
        // Picking the current member again clears the filter.
        state.staff = state.staff === row.dataset.pickStaff ? null : row.dataset.pickStaff;
        closeDrawers();
        render();
    });

    el('wmTbody').addEventListener('click', e => {
        const rm = e.target.closest('[data-remove]');
        if (rm) {
            const [courseId, code] = rm.dataset.remove.split('|');
            unassign(courseId, code);
            return;
        }
        const add = e.target.closest('[data-add]');
        if (add) openDrawer(add.dataset.add);
    });

    el('wmDrawerBody').addEventListener('click', e => {
        const addBtn = e.target.closest('[data-drawer-add]');
        if (addBtn && drawerCourse) { assign(drawerCourse._id, addBtn.dataset.drawerAdd); renderDrawer(); return; }
        const rmBtn = e.target.closest('[data-drawer-remove]');
        if (rmBtn && drawerCourse) { unassign(drawerCourse._id, rmBtn.dataset.drawerRemove); renderDrawer(); }
    });

    el('wmDrawerSearch').addEventListener('input', renderDrawer);
    // Wrapped, not passed directly: the click event would arrive as `fromPop`.
    root.querySelectorAll('[data-drawer-close]').forEach(b => b.addEventListener('click', () => closeDrawers()));
    el('wmDrawerBackdrop').addEventListener('click', () => closeDrawers());
    window.addEventListener('popstate', () => closeDrawers(true));

    const balanceBtn = el('wmBalanceBtn');
    if (balanceBtn) {
        balanceBtn.addEventListener('click', () => { renderBalance(); el('wmBalanceModal').hidden = false; });
        el('wmBalanceClose').addEventListener('click', () => { el('wmBalanceModal').hidden = true; });
        el('wmBalanceDismiss').addEventListener('click', () => { el('wmBalanceModal').hidden = true; });
        el('wmBalanceModal').addEventListener('click', e => {
            if (e.target === el('wmBalanceModal')) el('wmBalanceModal').hidden = true;
        });
        el('wmBalanceBody').addEventListener('click', e => {
            const btn = e.target.closest('[data-apply-move]');
            if (!btn) return;
            const moves = JSON.parse(el('wmBalanceBody').dataset.moves || '[]');
            const m = moves[Number(btn.dataset.applyMove)];
            if (!m) return;
            unassign(m.course, m.from);
            assign(m.course, m.to);
            btn.outerHTML = '<span class="pill pill-active">Applied</span>';
        });
    }

    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (openDrawerId !== null) closeDrawers();
        else if (balanceBtn && !el('wmBalanceModal').hidden) el('wmBalanceModal').hidden = true;
    });

    render();
})();
