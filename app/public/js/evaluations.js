// evaluations.js — the Staff Evaluations screen (/evaluations), shared by the
// Coordinator and the Department In-Charge.
//
// Renders from the #evData payload that EvaluationsController::index() builds:
//   calendar     teaching weeks (shared with the lecturers' history — see
//                js/period_nav.js)
//   assignments  who is due an evaluation each week, and by which lecturer
//   evaluations  what was actually submitted, one per staff/course/week
//
// Every due (assignment, week) pair in the chosen period becomes a row. It is
// Evaluated if a submission exists for that week and Not evaluated if not — a
// lecturer who forgot a week shows up here, which a list of submissions alone
// could never show.
//
// An evaluation is what the lecturer's form collects and nothing more: a 1–5
// star rating (whole numbers only) and an optional comment.
(function () {
    const page = document.getElementById('evalPage');
    const dataEl = document.getElementById('evData');
    if (!page || !dataEl) return;

    const DATA = JSON.parse(dataEl.textContent);
    const ASSIGNMENTS = DATA.assignments;

    // Submissions by "staff|course|week".
    const SUBMITTED = new Map();
    DATA.evaluations.forEach(e => {
        SUBMITTED.set(e.staff_code + '|' + e.course_code + '|' + e.week, e);
    });

    const state = { search: '', status: 'all', sort: 'week' };
    let period = null;
    let rows = [];            // every due row in the period, before search/status filters
    let openKey = null;

    const el = id => document.getElementById(id);
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, m => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]
    ));

    const toRating = n => Math.min(5, Math.max(1, Math.round(n)));

    function scoreBand(n) {
        if (n >= 5) return 'high';
        if (n >= 4) return 'mid';
        return 'low';
    }

    const SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const shortDate = iso => { const [, m, d] = iso.split('-').map(Number); return d + ' ' + SHORT[m - 1]; };

    /** Label for a lecturer or junior staff member: the standard code badge, then the name. */
    const person = (code, name, kind) => `
        <div class="lec-identity">
            ${codeBadge(code, kind, { title: name })}
            <span class="lec-name">${esc(name)}</span>
        </div>`;

    // ------------------------------------------------------------- building
    /** One row per assignment per teaching week of the period. */
    function buildRows() {
        rows = [];
        (period ? period.weeks : []).forEach(w => {
            ASSIGNMENTS.forEach(a => {
                const key = a.staff_code + '|' + a.course_code + '|' + w.start;
                const sub = SUBMITTED.get(key) || null;
                rows.push({
                    key, week: w, a, sub,
                    score: sub ? toRating(Number(sub.rating)) : null,
                });
            });
        });
    }

    function visible() {
        const q = state.search.trim().toLowerCase();
        const list = rows.filter(r => {
            if (state.status === 'evaluated' && !r.sub) return false;
            if (state.status === 'missing' && r.sub) return false;
            if (q) {
                const hay = (r.a.staff_name + ' ' + r.a.staff_code + ' ' + r.a.course_code + ' ' +
                    r.a.course_name + ' ' + r.a.lecturer + ' ' + r.a.lecturer_name).toLowerCase();
                if (!hay.includes(q)) return false;
            }
            return true;
        });

        // Not-evaluated rows have no score; they sort after scored ones either way.
        const byScore = dir => (x, y) => (x.score === null) - (y.score === null) || dir * ((x.score || 0) - (y.score || 0));
        const byWeek = (x, y) => y.week.start.localeCompare(x.week.start);
        if (state.sort === 'score-desc') list.sort((x, y) => byScore(-1)(x, y) || byWeek(x, y));
        else if (state.sort === 'score-asc') list.sort((x, y) => byScore(1)(x, y) || byWeek(x, y));
        else if (state.sort === 'name') list.sort((x, y) => x.a.staff_name.localeCompare(y.a.staff_name) || byWeek(x, y));
        else if (state.sort === 'lecturer') list.sort((x, y) => x.a.lecturer_name.localeCompare(y.a.lecturer_name) || byWeek(x, y));
        else list.sort((x, y) => byWeek(x, y) || x.a.staff_name.localeCompare(y.a.staff_name));
        return list;
    }

    // ------------------------------------------------------------- rendering
    function renderKpis() {
        const done = rows.filter(r => r.sub);
        el('evKpiDue').textContent = rows.length;
        el('evKpiDone').textContent = done.length;
        el('evKpiMissing').textContent = rows.length - done.length;
        el('evKpiAvg').textContent = done.length
            ? toRating(done.reduce((s, r) => s + r.score, 0) / done.length)
            : '—';

        el('evKpiDoneCard').classList.toggle('is-active', state.status === 'evaluated');
        el('evKpiMissingCard').classList.toggle('is-active', state.status === 'missing');
        el('evKpiMissingCard').classList.toggle('has-issues', rows.length - done.length > 0);
    }

    function renderList() {
        const list = visible();

        el('evBody').innerHTML = list.map(r => `
            <tr class="eval-row ${r.sub ? '' : 'is-missing'}" data-key="${esc(r.key)}">
                <td>${person(r.a.staff_code, r.a.staff_name, 'staff')}</td>
                <td>
                    ${codeBadge(r.a.course_code, 'course', { title: r.a.course_name })}
                    <p class="page-head-sub" style="margin:2px 0 0;">${esc(r.a.course_name)}</p>
                </td>
                <td>${person(r.a.lecturer, r.a.lecturer_name, 'lecturer')}</td>
                <td class="eval-week-cell">
                    <strong>Week ${r.week.number}</strong>
                    <span>${esc(shortDate(r.week.start))}</span>
                </td>
                <td>
                    ${r.sub
                        ? `<div class="eval-score-pill score-${scoreBand(r.score)}"><i class="fa-solid fa-star"></i> <strong>${r.score}</strong> / 5</div>`
                        : '<span class="text-muted">—</span>'}
                </td>
                <td style="text-align:right;">
                    ${r.sub
                        ? '<span class="pill pill-active"><i class="fa-solid fa-check"></i> Evaluated</span>'
                        : '<span class="pill pill-warn"><i class="fa-regular fa-clock"></i> Not evaluated</span>'}
                </td>
            </tr>`).join('');

        el('evEmpty').hidden = list.length > 0;
        el('evEmpty').textContent = rows.length ? 'No evaluations match those filters.' : 'No teaching weeks in this period.';
        const filtered = state.search || state.status !== 'all';
        el('evClear').hidden = !filtered;
        el('evCount').textContent = filtered
            ? `Showing ${list.length} of ${rows.length} evaluations due in ${period.label}`
            : `${rows.length} evaluations due in ${period.label}`;
    }

    function render() {
        renderKpis();
        renderList();
    }

    // ---------------------------------------------------------------- drawer
    function openDrawer(key) {
        const r = rows.find(x => x.key === key);
        if (!r) return;
        openKey = key;

        el('evDrawerTag').textContent = (r.sub ? 'Evaluated' : 'Not evaluated') + ' · Week ' + r.week.number + ', ' + r.week.semName;
        el('evDrawerName').textContent = r.a.staff_name;
        el('evDrawerCourse').textContent = r.a.course_code + ' · ' + r.a.course_name + ' · lecturer in charge ' + r.a.lecturer_name;

        if (!r.sub) {
            el('evDrawerBody').innerHTML = `
                <div class="eval-missing-note">
                    <i class="fa-regular fa-clock"></i>
                    <div>
                        <strong>No evaluation for this week</strong>
                        <p>${esc(r.a.lecturer_name)} has not evaluated ${esc(r.a.staff_name)} on ${esc(r.a.course_code)}
                           for the week of ${esc(shortDate(r.week.start))}.</p>
                    </div>
                </div>`;
        } else {
            const e = r.sub;
            el('evDrawerBody').innerHTML = `
                <div class="eval-drawer-score band-${scoreBand(r.score)}">
                    <span class="eval-drawer-stars" aria-label="${r.score} out of 5">
                        ${[1, 2, 3, 4, 5].map(n => `<i class="fa-solid fa-star ${n <= r.score ? 'is-on' : ''}"></i>`).join('')}
                    </span>
                    <span class="eval-drawer-score-num">${r.score}</span>
                    <span class="eval-drawer-score-of">out of 5</span>
                    <span class="eval-drawer-score-date">Submitted ${esc(e.date)}</span>
                </div>

                <div class="eval-drawer-section">
                    <h4>Lecturer's comment</h4>
                    ${e.comment
                        ? `<p class="eval-drawer-text eval-drawer-quote">${esc(e.comment)}</p>`
                        : '<p class="eval-drawer-text"><em>No comment was added — a rating only.</em></p>'}
                </div>`;
        }

        el('evDrawer').hidden = false;
        el('evDrawerBackdrop').hidden = false;
    }

    function closeDrawer() {
        openKey = null;
        el('evDrawer').hidden = true;
        el('evDrawerBackdrop').hidden = true;
    }

    // ---------------------------------------------------------------- wiring
    PeriodNav.create(el('evPeriodNav'), {
        calendar: DATA.calendar,
        units: ['week', 'month', 'semester', 'year'],
        onChange(p) {
            period = p;
            buildRows();
            if (openKey) closeDrawer();
            render();
        },
    });

    el('evSearch').addEventListener('input', e => { state.search = e.target.value; renderList(); });
    el('evSort').addEventListener('change', e => { state.sort = e.target.value; renderList(); });

    function setStatus(status) {
        state.status = status;
        el('evStatusSeg').querySelectorAll('.seg-btn').forEach(b => b.classList.toggle('active', b.dataset.status === status));
        render();
    }

    el('evStatusSeg').addEventListener('click', e => {
        const btn = e.target.closest('[data-status]');
        if (btn) setStatus(btn.dataset.status);
    });

    // The two count cards double as filters; a second click clears.
    ['evKpiDoneCard|evaluated', 'evKpiMissingCard|missing'].forEach(pair => {
        const [id, status] = pair.split('|');
        const toggle = () => setStatus(state.status === status ? 'all' : status);
        el(id).addEventListener('click', toggle);
        el(id).addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
        });
    });

    el('evClear').addEventListener('click', () => {
        state.search = '';
        el('evSearch').value = '';
        setStatus('all');
    });

    el('evBody').addEventListener('click', e => {
        const row = e.target.closest('.eval-row');
        if (row) openDrawer(row.dataset.key);
    });

    el('evDrawerClose').addEventListener('click', closeDrawer);
    el('evDrawerBackdrop').addEventListener('click', closeDrawer);
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !el('evDrawer').hidden) closeDrawer();
    });

    // Export is a report the backend will render; say so rather than pretending.
    el('evalExportBtn').addEventListener('click', () => {
        const msg = 'Export needs the backend — not wired up yet.';
        if (window.ttToast) window.ttToast(msg);
        else alert(msg);
    });
})();
