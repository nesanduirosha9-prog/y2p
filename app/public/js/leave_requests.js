// leave_requests.js — the Leave Requests page (Coordinator / In-Charge).
//
// Read-only. Renders everyone's leave from the #leaveData payload
// (LeaveRequestsController::index() -> LeaveRequestModel::all()) into two
// tabs, each with its own copy of the same filters (search, rank, type, date
// range):
//   upcoming  last day is today or later — soonest first
//   history   fully passed — most recent first
// Cells come from js/leave_cells.js, shared with the member's own Leave page.
(function () {
    const dataEl = document.getElementById('leaveData');
    const page = document.getElementById('lrPage');
    if (!dataEl || !page || !window.leaveCells) return;

    const cells = window.leaveCells;
    const { esc } = cells;
    const payload = JSON.parse(dataEl.textContent);
    const TODAY = payload.today;
    const records = payload.records || [];

    const TABS = {
        upcoming: {
            rows: records.filter(r => r.end_date >= TODAY)
                .sort((a, b) => a.start_date.localeCompare(b.start_date)),
            empty: 'No one has upcoming leave.',
        },
        history: {
            rows: records.filter(r => r.end_date < TODAY)
                .sort((a, b) => b.start_date.localeCompare(a.start_date)),
            empty: 'No past leave recorded yet.',
        },
    };
    const BLANK = { q: '', rank: 'all', type: 'all', from: '', to: '' };

    function matches(r, f) {
        if (f.rank !== 'all' && r.requester_rank !== f.rank) return false;
        if (f.type !== 'all' && r.leave_type !== f.type) return false;
        // Overlap with the picked range, not containment.
        if (f.from && r.end_date < f.from) return false;
        if (f.to && r.start_date > f.to) return false;
        if (f.q && !`${r.requester_name} ${r.requester_code}`.toLowerCase().includes(f.q)) return false;
        return true;
    }

    function row(r) {
        return `<tr>
            <td>${cells.badge(r.requester_code, r.requester_rank, r.requester_name)}</td>
            <td><span class="leave-name">${esc(r.requester_name)}</span></td>
            <td>${cells.type(r)}</td>
            <td>${cells.dates(r, TODAY)}</td>
            <td>${cells.covers(r)}</td>
            <td>${cells.reason(r)}</td>
        </tr>`;
    }

    function render(key) {
        const tab = TABS[key];
        const f = tab.filter;
        const list = tab.rows.filter(r => matches(r, f));
        const filtered = Object.keys(BLANK).some(k => f[k] !== BLANK[k]);

        page.querySelector(`[data-rows="${key}"]`).innerHTML = list.length
            ? list.map(row).join('')
            : `<tr><td colspan="6" class="leave-empty">${esc(filtered && tab.rows.length ? 'No leave matches these filters.' : tab.empty)}</td></tr>`;
        page.querySelector(`[data-summary="${key}"]`).textContent = filtered
            ? `Showing ${list.length} of ${tab.rows.length}`
            : `${tab.rows.length} leave record${tab.rows.length === 1 ? '' : 's'}`;
        page.querySelector(`[data-count="${key}"]`).textContent = tab.rows.length;
        page.querySelector(`[data-filters="${key}"] [data-clear]`).hidden = !filtered;
    }

    // ---- Filters: one set per tab ----------------------------------------

    Object.keys(TABS).forEach(key => {
        const tab = TABS[key];
        tab.filter = { ...BLANK };
        const bar = page.querySelector(`[data-filters="${key}"]`);

        bar.addEventListener('input', e => {
            const name = e.target.dataset.filter;
            if (!name) return;
            tab.filter[name] = name === 'q' ? e.target.value.trim().toLowerCase() : e.target.value;
            render(key);
        });
        bar.querySelector('[data-clear]').addEventListener('click', () => {
            tab.filter = { ...BLANK };
            bar.querySelectorAll('[data-filter]').forEach(input => {
                input.value = BLANK[input.dataset.filter];
            });
            render(key);
        });

        render(key);
    });

    // ---- Tabs (?tab= kept in the URL so a reload opens the same one) -----

    const tabBar = document.getElementById('lrTabs');
    tabBar.addEventListener('click', e => {
        const b = e.target.closest('[data-tab]');
        if (!b) return;
        const key = b.dataset.tab;
        tabBar.querySelectorAll('[data-tab]').forEach(x => {
            x.classList.toggle('active', x === b);
            x.setAttribute('aria-selected', x === b ? 'true' : 'false');
        });
        page.querySelectorAll('[data-panel]').forEach(p => { p.hidden = p.dataset.panel !== key; });

        const url = new URL(location.href);
        url.searchParams.set('tab', key);
        history.replaceState(history.state, '', url);
    });
})();
