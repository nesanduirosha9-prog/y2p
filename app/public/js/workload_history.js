// workload_history.js — the History tab of the Workload page.
//
// Every allocation over time, one row per staff member per event:
//   duties   auto | manual | cover | replacement | removed
//   courses  allocated (semester start) | added | removed
//
// Three sources, merged:
//   - DATA.records  everything before this week (WorkloadPrototypeData::
//                   allocationHistory())
//   - this week's duties — seeded from DATA.duties, then replaced wholesale by
//     every `sched:changed` from js/scheduler.js, so the board and History
//     always agree
//   - course edits made this session — appended from `wm:changed` events sent
//     by js/workload_matrix.js
//
// Loaded BEFORE those two scripts, so it is listening when they start.
// The period picker is the shared js/period_nav.js, the same one Evaluations
// uses, so "Week 5" means the same week on both pages; History adds its "All"
// option so everything can be sorted at once. Column headings sort; Group by
// adds headed sections on top of that.
(function () {
    const dataEl = document.getElementById('histData');
    if (!dataEl || !document.getElementById('histBody')) return;

    const DATA = JSON.parse(dataEl.textContent);

    const staffByCode = {};
    DATA.staff.forEach(s => { staffByCode[s.code] = s; });

    const HOW_LABEL = {
        auto: 'Auto', manual: 'Manual', cover: 'Cover', replacement: 'Replacement',
        removed: 'Removed', allocated: 'Allocated', added: 'Added',
    };

    // Order the How column sorts in: what happened, from routine to exceptional.
    const HOW_ORDER = ['allocated', 'auto', 'added', 'manual', 'cover', 'replacement', 'removed'];

    const DEFAULTS = { search: '', kind: 'all', group: 'none', sort: 'date', dir: -1 };
    const state = Object.assign({}, DEFAULTS);
    let period = null;

    // 'Week 5 · Semester 1' for each teaching week's Monday.
    const weekInfo = {};
    PeriodNav.teachingWeeks(DATA.calendar).forEach(w => { weekInfo[w.start] = w; });
    const weekLabel = start => weekInfo[start] ? 'Week ' + weekInfo[start].number + ' · ' + weekInfo[start].semName + ' ' + weekInfo[start].year : start;

    // This week's duties as they ship; scheduler.js takes over on the
    // Coordinator's page. The In-Charge has no board, so this seed is it.
    let liveDuty = DATA.duties.flatMap(d => d.assigned.map(code => ({
        kind: 'duty', date: d.date, week: DATA.week.from, staff: code,
        course: d.course, course_name: d.course_name || '',
        lecturer: d.requester, lecturer_name: d.requester_name || d.requester,
        title: d.duty, slots: d.slots, how: 'auto', note: '',
    })));
    const liveCourse = [];

    const el = id => document.getElementById(id);
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, m => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]
    ));

    // ---------------------------------------------------------------- helpers
    // Spelled out rather than toLocaleDateString(): en-GB now gives "Sept",
    // while the rest of the app (and period_nav.js) says "Sep".
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    function localDate(iso) { return new Date(iso + 'T00:00:00'); }
    function shortDate(iso) { const d = localDate(iso); return d.getDate() + ' ' + MONTHS[d.getMonth()]; }
    function weekday(iso) { return DAYS[localDate(iso).getDay()].slice(0, 3); }
    function longDay(iso) { const d = localDate(iso); return DAYS[d.getDay()] + ' ' + shortDate(iso) + ' ' + d.getFullYear(); }

    /** ['10-11', '11-12'] → '10–12'. Slot labels are hour ranges. */
    function hoursOf(slots) {
        if (!slots || !slots.length) return '';
        return slots[0].split('-')[0] + '–' + slots[slots.length - 1].split('-')[1];
    }

    function staffName(code) { return staffByCode[code] ? staffByCode[code].name : code; }

    function allRecords() {
        return DATA.records.concat(liveDuty, liveCourse);
    }

    /** Records in the chosen period that pass the kind and search filters. */
    function visible() {
        const weeks = new Set((period ? period.weeks : []).map(w => w.start));
        const q = state.search.trim().toLowerCase();
        return allRecords().filter(r => {
            if (!weeks.has(r.week)) return false;
            if (state.kind !== 'all' && r.kind !== state.kind) return false;
            if (q) {
                const hay = [r.staff, staffName(r.staff), r.course, r.course_name, r.lecturer,
                    r.lecturer_name, r.title, r.note, HOW_LABEL[r.how]].join(' ').toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });
    }

    // ---------------------------------------------------------------- grouping
    const GROUPS = {
        none: {
            key: () => '',
            label: () => '',
            order: () => 0,
        },
        week: {
            key: r => r.week,
            label: r => esc(weekLabel(r.week)) + ' <span class="hist-group-sub">from ' + esc(shortDate(r.week)) + '</span>',
            order: (a, b) => b.localeCompare(a),          // newest week first
        },
        date: {
            key: r => r.date,
            label: r => esc(longDay(r.date)),
            order: (a, b) => b.localeCompare(a),          // newest day first
        },
        course: {
            key: r => r.course,
            label: r => codeBadge(r.course, 'course') + ' ' + esc(r.course_name),
            order: (a, b) => a.localeCompare(b),
        },
        lecturer: {
            key: r => r.lecturer,
            label: r => codeBadge(r.lecturer, 'lecturer') + ' ' + esc(r.lecturer_name),
            order: (a, b) => a.localeCompare(b),
        },
        staff: {
            key: r => r.staff,
            label: r => codeBadge(r.staff, 'staff') + ' ' + esc(staffName(r.staff)),
            order: (a, b) => a.localeCompare(b),
        },
    };

    /** Newest first; then course, then staff, so a duty's team sits together. */
    function byRecency(a, b) {
        return b.date.localeCompare(a.date) || a.course.localeCompare(b.course) ||
            (a.how === 'removed') - (b.how === 'removed') || a.staff.localeCompare(b.staff);
    }

    /** The column the table is sorted by, in state.dir; recency breaks ties. */
    const SORT_KEY = {
        date: r => r.date,
        staff: r => r.staff,                      // by code, as the badges show
        course: r => r.course,
        lecturer: r => r.lecturer,
        how: r => String(HOW_ORDER.indexOf(r.how)).padStart(2, '0'),
    };
    function bySort(a, b) {
        const k = SORT_KEY[state.sort];
        return state.dir * k(a).localeCompare(k(b)) || byRecency(a, b);
    }

    function paintSortHeads() {
        document.querySelectorAll('#histHead .hist-sort').forEach(btn => {
            const on = btn.dataset.sort === state.sort;
            btn.classList.toggle('is-asc', on && state.dir === 1);
            btn.classList.toggle('is-desc', on && state.dir === -1);
            btn.closest('th').setAttribute('aria-sort', on ? (state.dir === 1 ? 'ascending' : 'descending') : 'none');
        });
    }

    // --------------------------------------------------------------- rendering
    /** A code badge that narrows the table to that code when clicked. */
    function pick(code, kind, title) {
        return `<button type="button" class="hist-pick" data-q="${esc(code)}" title="Show only ${esc(code)}">${codeBadge(code, kind, { title })}</button>`;
    }

    function row(r) {
        const what = r.kind === 'duty'
            ? `<span class="hist-what">${esc(r.title)}</span><span class="hist-sub">${esc(hoursOf(r.slots))}</span>`
            : `<span class="hist-what">${esc(r.title)}</span><span class="hist-sub">Course team</span>`;
        const wk = weekInfo[r.week];
        return `
            <tr class="${r.how === 'removed' ? 'hist-row-removed' : ''}">
                <td>
                    <strong>${esc(weekday(r.date))}</strong> <span class="hist-date">${esc(shortDate(r.date))}</span>
                    <span class="hist-sub">${wk ? 'Week ' + wk.number : ''}</span>
                </td>
                <td>
                    <div class="hist-staff">
                        ${pick(r.staff, 'staff', staffName(r.staff))}
                        <span class="hist-staff-name">${esc(staffName(r.staff))}</span>
                    </div>
                </td>
                <td>${pick(r.course, 'course', r.course_name)}</td>
                <td>${pick(r.lecturer, 'lecturer', r.lecturer_name)}</td>
                <td>${what}</td>
                <td>
                    <span class="hist-how hist-how-${esc(r.how)}"${r.note ? ` title="${esc(r.note)}"` : ''}>${esc(HOW_LABEL[r.how] || r.how)}</span>
                </td>
            </tr>`;
    }

    function render() {
        const list = visible();

        const g = GROUPS[state.group];
        const groups = {};
        list.forEach(r => { (groups[g.key(r)] = groups[g.key(r)] || []).push(r); });

        paintSortHeads();
        el('histBody').innerHTML = Object.keys(groups).sort(g.order).map(key => {
            const rows = groups[key].sort(bySort);
            if (state.group === 'none') return rows.map(row).join('');
            return `
                <tr class="hist-group-row">
                    <td colspan="6">
                        <span class="hist-group-label">${g.label(rows[0])}</span>
                        <span class="hist-group-count">${rows.length}</span>
                    </td>
                </tr>
                ${rows.map(row).join('')}`;
        }).join('');

        el('histEmpty').hidden = list.length > 0;
        const total = allRecords().filter(r => period && period.weeks.some(w => w.start === r.week)).length;
        const filtered = state.search || state.kind !== 'all';
        el('histCount').textContent = period
            ? (filtered ? `Showing ${list.length} of ${total} records` : `${total} records`) + ' · ' + period.label
            : '';
        el('histClear').hidden = !filtered && state.group === DEFAULTS.group &&
            state.sort === DEFAULTS.sort && state.dir === DEFAULTS.dir;
    }

    // ------------------------------------------------------------------ wiring
    document.addEventListener('sched:changed', e => { liveDuty = e.detail.rows; render(); });
    document.addEventListener('wm:changed', e => { liveCourse.push(e.detail); render(); });

    el('histSearch').addEventListener('input', e => { state.search = e.target.value; render(); });
    el('histGroup').addEventListener('change', e => { state.group = e.target.value; render(); });

    el('histKindSeg').addEventListener('click', e => {
        const btn = e.target.closest('[data-kind]');
        if (!btn) return;
        state.kind = btn.dataset.kind;
        el('histKindSeg').querySelectorAll('.seg-btn').forEach(b => b.classList.toggle('active', b === btn));
        render();
    });

    el('histBody').addEventListener('click', e => {
        const b = e.target.closest('[data-q]');
        if (!b) return;
        state.search = b.dataset.q;
        el('histSearch').value = b.dataset.q;
        render();
    });

    // Sort: a new column starts A→Z (newest first for dates); the same
    // column again reverses it.
    el('histHead').addEventListener('click', e => {
        const btn = e.target.closest('.hist-sort');
        if (!btn) return;
        if (state.sort === btn.dataset.sort) state.dir = -state.dir;
        else { state.sort = btn.dataset.sort; state.dir = state.sort === 'date' ? -1 : 1; }
        render();
    });

    el('histClear').addEventListener('click', () => {
        Object.assign(state, DEFAULTS);
        el('histSearch').value = '';
        el('histGroup').value = DEFAULTS.group;
        el('histKindSeg').querySelectorAll('.seg-btn').forEach(b => b.classList.toggle('active', b.dataset.kind === 'all'));
        render();
    });

    PeriodNav.create(el('histPeriodNav'), {
        calendar: DATA.calendar,
        units: ['week', 'month', 'semester', 'year', 'all'],
        onChange(p) { period = p; render(); },
    });
})();
