// audit.js — the Activity Log screen (/audit and /audit/me).
//
// Renders from the #audData payload that AuditController builds. The rows have
// already been filtered to what this reader is allowed to see (see
// AuditPrototypeData::feed) — everything here is convenience on top of an
// already-authorised set, never the thing that keeps rows apart.
//
// THERE IS NO MUTATION IN THIS FILE, and that is the point. Every other screen
// in the prototype has one function that stands in for a future POST; this one
// has none, because an audit entry is written by the system and then never
// touched. If a future version of this file grows a save, an edit or a delete,
// the log has stopped being an audit log.
//
// WHEN THE BACKEND LANDS: filtering moves into SQL and the browser is handed one
// page of rows at a time. The state object below is deliberately the same shape
// as that query — period, person, categories, search, problems-only, limit — so
// the swap is `ENTRIES = await fetch(...)` plus deleting the three filter
// functions, with no change to how the screen behaves or looks.
//
// Written for readers who are not interested in computers: plain sentences, day
// headings, one visible label per control, and the active filters spelled out in
// words with a single button that clears them.
(function () {
    const page = document.getElementById('auditPage');
    const dataEl = document.getElementById('audData');
    if (!page || !dataEl) return;

    // "Back" on /audit/me: return to whichever StaffSync page opened it. The
    // href (/timetable) is the fallback for a bookmark or a fresh tab.
    const backLink = document.getElementById('audBack');
    if (backLink) {
        backLink.addEventListener('click', e => {
            let sameSite = false;
            try { sameSite = new URL(document.referrer).origin === location.origin; } catch (_) { /* no referrer */ }
            if (sameSite && history.length > 1) {
                e.preventDefault();
                history.back();
            }
        });
    }

    const DATA = JSON.parse(dataEl.textContent);
    const ENTRIES = DATA.entries;              // newest first, already authorised
    const ACTIONS = DATA.actions;              // 'leave.approved' -> {label, category}
    const CATEGORIES = DATA.categories;        // 'leave' -> {label, icon}
    const ACTORS = DATA.actors;                // 'DSC' -> {name, label, ...}
    const SEMESTERS = DATA.semesters;
    const IS_SYSTEM = DATA.scope === 'system';
    const VIEWER = DATA.viewer;
    const PAGE_SIZE = 50;

    const state = {
        search: '',
        period: 'week',
        from: '',
        to: '',
        cats: new Set(),       // empty means every kind
        person: '',
        problems: false,
        limit: PAGE_SIZE,
    };

    const el = id => document.getElementById(id);
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, m => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]
    ));

    // ---------------------------------------------------------------- dates
    // Timestamps are 'YYYY-MM-DDTHH:mm:ss' in the server's clock, so a date
    // range is a string comparison on the first ten characters.
    const dayOf = ts => ts.slice(0, 10);

    // EVERY DATE HERE IS THE SERVER'S, never this browser's. The entries are
    // stamped by the server, so grouping or filtering them against the reader's
    // own clock would put rows under the wrong day whenever the two disagree —
    // which is all the time, since PHP here runs on UTC and the machine does
    // not. It also means a reader whose laptop clock is wrong still sees the
    // record correctly, which for an audit trail is the whole point.
    const TODAY = DATA.today;
    const SERVER_NOW = new Date(DATA.now);

    function ymd(d) {
        const p = n => String(n).padStart(2, '0');
        return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
    }

    /** n days from the server's today (negative for the past). */
    function shiftDays(n) {
        const [y, m, d] = TODAY.split('-').map(Number);
        const out = new Date(y, m - 1, d);
        out.setDate(out.getDate() + n);
        return ymd(out);
    }

    /** The chosen period as an inclusive {from, to, label}; nulls mean unbounded. */
    function periodRange() {
        const p = state.period;
        if (p === 'today') return { from: TODAY, to: TODAY, label: 'today' };
        if (p === 'yesterday') {
            const y = shiftDays(-1);
            return { from: y, to: y, label: 'yesterday' };
        }
        if (p === 'week') return { from: shiftDays(-6), to: TODAY, label: 'the last 7 days' };
        if (p === 'month') return { from: shiftDays(-29), to: TODAY, label: 'the last 30 days' };
        if (p.startsWith('sem:')) {
            const term = SEMESTERS.find(t => t.key === p.slice(4));
            return term
                ? { from: term.start, to: term.end, label: term.label }
                : { from: null, to: null, label: 'everything on record' };
        }
        if (p.startsWith('year:')) {
            const y = p.slice(5);
            return { from: y + '-01-01', to: y + '-12-31', label: 'year ' + y };
        }
        if (p === 'custom') {
            const from = state.from || null;
            const to = state.to || null;
            let label = 'a chosen date range';
            if (from && to) label = longDate(from) + ' to ' + longDate(to);
            else if (from) label = 'from ' + longDate(from);
            else if (to) label = 'up to ' + longDate(to);
            return { from: from, to: to, label: label };
        }
        return { from: null, to: null, label: 'everything on record' };
    }

    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    function longDate(day) {
        const [y, m, d] = day.split('-').map(Number);
        return d + ' ' + MONTHS[m - 1] + ' ' + y;
    }

    function dayHeading(day) {
        if (day === TODAY) return 'Today';
        if (day === shiftDays(-1)) return 'Yesterday';
        const [y, m, d] = day.split('-').map(Number);
        return DAYS[new Date(y, m - 1, d).getDay()] + ', ' + d + ' ' + MONTHS[m - 1] + ' ' + y;
    }

    function clockTime(ts) {
        return ts.slice(11, 16);
    }

    /** "3 hours ago" / "yesterday" / "on 4 June 2026" — how people actually say it. */
    function relative(ts) {
        // Both sides parsed the same way, in the server's frame, so the answer is
        // right whatever zone the browser is in.
        const then = new Date(ts.replace(' ', 'T'));
        const mins = Math.round((SERVER_NOW.getTime() - then.getTime()) / 60000);
        if (mins < 1) return 'just now';
        if (mins < 60) return mins + (mins === 1 ? ' minute ago' : ' minutes ago');
        const hrs = Math.round(mins / 60);
        if (hrs < 24) return hrs + (hrs === 1 ? ' hour ago' : ' hours ago');
        const days = Math.round(hrs / 24);
        if (days === 1) return 'yesterday';
        if (days < 30) return days + ' days ago';
        return 'on ' + longDate(dayOf(ts));
    }

    // ------------------------------------------------------------- filtering
    const isProblem = e => e.result !== 'success';

    function matchesSearch(e) {
        const q = state.search.trim().toLowerCase();
        if (!q) return true;
        const actor = ACTORS[e.actor] || { name: e.actor };
        const action = ACTIONS[e.action] || { label: e.action };
        const hay = [e.id, e.actor, actor.name, action.label, e.target, e.detail, e.ip, e.device]
            .join(' ').toLowerCase();
        return hay.indexOf(q) !== -1;
    }

    /**
     * Everything except the category filter. The chip counts are taken from
     * this, so a chip's number is what you would get by clicking it — a count
     * taken after its own filter would always read as the current total.
     */
    function filteredExceptCategory() {
        const range = periodRange();
        return ENTRIES.filter(e => {
            const day = dayOf(e.ts);
            if (range.from && day < range.from) return false;
            if (range.to && day > range.to) return false;
            if (state.person && e.actor !== state.person) return false;
            if (state.problems && !isProblem(e)) return false;
            return matchesSearch(e);
        });
    }

    function filtered() {
        if (state.cats.size === 0) return filteredExceptCategory();
        return filteredExceptCategory().filter(e => {
            const cat = (ACTIONS[e.action] || {}).category;
            return state.cats.has(cat);
        });
    }

    // ------------------------------------------------------------- rendering
    function renderChips() {
        const base = filteredExceptCategory();
        const counts = {};
        base.forEach(e => {
            const cat = (ACTIONS[e.action] || {}).category;
            counts[cat] = (counts[cat] || 0) + 1;
        });

        const all = `
            <button type="button" class="aud-chip ${state.cats.size === 0 ? 'is-active' : ''}" data-cat="">
                All activity
                <span class="aud-chip-count">${base.length}</span>
            </button>`;

        const rest = Object.keys(CATEGORIES).map(key => {
            const c = CATEGORIES[key];
            const n = counts[key] || 0;
            return `
                <button type="button" class="aud-chip ${state.cats.has(key) ? 'is-active' : ''} ${n === 0 ? 'is-empty' : ''}"
                        data-cat="${esc(key)}" aria-pressed="${state.cats.has(key)}">
                    ${esc(c.label)}
                    <span class="aud-chip-count">${n}</span>
                </button>`;
        }).join('');

        el('audChips').innerHTML = all + rest;
    }

    function renderSummary(list) {
        const range = periodRange();
        const bits = [];

        bits.push(`<strong>${list.length.toLocaleString()}</strong> ${list.length === 1 ? 'entry' : 'entries'} from ${esc(range.label)}`);

        if (state.cats.size > 0) {
            const names = Array.from(state.cats).map(k => (CATEGORIES[k] || {}).label || k);
            bits.push(names.join(', '));
        }
        if (state.person) {
            const a = ACTORS[state.person];
            bits.push('by ' + esc(a ? a.name : state.person));
        }
        if (state.problems) bits.push('failed or refused only');
        if (state.search.trim()) bits.push('matching “' + esc(state.search.trim()) + '”');

        let text = bits.join(' · ');
        if (list.length > 0) {
            text += ` · newest ${esc(relative(list[0].ts))}`;
        }
        el('audSummary').innerHTML = text;

        const dirty = state.cats.size > 0 || state.person || state.problems ||
            state.search.trim() !== '' || state.period !== 'week';
        el('audClear').hidden = !dirty;
    }

    /** One entry as a single readable sentence plus what it was done to. */
    /** Failed / Refused as a small label; nothing for a normal entry. */
    function outcomeFlag(e) {
        if (e.result === 'failed') return '<span class="aud-flag aud-flag-failed">Failed</span>';
        if (e.result === 'refused') return '<span class="aud-flag aud-flag-refused">Refused</span>';
        return '';
    }

    // Phones: one entry as a readable sentence, stacked.
    function entryRow(e) {
        const actor = ACTORS[e.actor] || { name: e.actor, label: '' };
        const action = ACTIONS[e.action] || { label: e.action, category: 'system' };

        // Labels are stored capitalised ("Approved a leave request") so they can
        // stand alone in the drawer and in the exported file; lower-casing the
        // first letter is what turns one into a sentence about a person.
        const verb = action.label.charAt(0).toLowerCase() + action.label.slice(1);
        const who = IS_SYSTEM ? actor.name : 'You';

        const flags = [];
        if (outcomeFlag(e)) flags.push(outcomeFlag(e));
        if (IS_SYSTEM && e.actor === VIEWER.code) flags.push('<span class="aud-flag aud-flag-you">You</span>');

        return `
            <button type="button" class="aud-entry ${isProblem(e) ? 'is-problem' : ''}" data-id="${esc(e.id)}">
                <span class="aud-entry-time">${esc(clockTime(e.ts))}</span>
                <span class="aud-entry-main">
                    <span class="aud-entry-line"><strong>${esc(who)}</strong> ${esc(verb)}</span>
                    ${e.target ? `<span class="aud-entry-target">${esc(e.target)}</span>` : ''}
                    ${e.detail ? `<span class="aud-entry-detail">${esc(e.detail)}</span>` : ''}
                    <span class="aud-entry-meta">
                        ${IS_SYSTEM ? `<span class="aud-code">${esc(e.actor)}</span>` : ''}
                        <span class="aud-ref">${esc(e.id)}</span>
                        ${flags.join('')}
                    </span>
                </span>
            </button>`;
    }

    // Desktop: the same entry spread across columns.
    function tableHead() {
        return `
            <tr>
                <th style="width:80px;">Time</th>
                <th style="width:110px;">Entry</th>
                ${IS_SYSTEM ? '<th style="min-width:190px;">Person</th>' : ''}
                <th style="min-width:200px;">Action</th>
                <th style="min-width:200px;">Applied to</th>
                <th style="min-width:220px;">Detail</th>
                <th style="width:100px;">Outcome</th>
                <th style="width:130px;">Network address</th>
            </tr>`;
    }

    function tableRow(e) {
        const actor = ACTORS[e.actor] || { name: e.actor, label: '' };
        const action = ACTIONS[e.action] || { label: e.action, category: 'system' };
        const you = IS_SYSTEM && e.actor === VIEWER.code ? ' <span class="aud-flag aud-flag-you">You</span>' : '';

        return `
            <tr class="aud-row" data-id="${esc(e.id)}" tabindex="0">
                <td class="aud-mono">${esc(e.ts.slice(11, 19))}</td>
                <td class="aud-mono aud-muted">${esc(e.id)}</td>
                ${IS_SYSTEM ? `
                <td>
                    <div class="aud-person">
                        ${codeBadge(e.actor, 'staff', { title: actor.name })}
                        <span>${esc(actor.name)}${you}</span>
                    </div>
                </td>` : ''}
                <td>${esc(action.label)}</td>
                <td>${e.target ? esc(e.target) : '<span class="aud-muted">—</span>'}</td>
                <td class="aud-muted">${e.detail ? esc(e.detail) : '—'}</td>
                <td>${outcomeFlag(e) || 'Completed'}</td>
                <td class="aud-mono aud-muted">${esc(e.ip || '—')}</td>
            </tr>`;
    }

    function renderFeed() {
        const list = filtered();
        const shown = list.slice(0, state.limit);

        el('audEmpty').hidden = list.length !== 0;
        el('audFeed').hidden = list.length === 0;

        // Group under one heading per day. The list arrives newest first, so a
        // single pass keeps both the days and the entries in order.
        const groups = [];
        let current = null;
        shown.forEach(e => {
            const day = dayOf(e.ts);
            if (!current || current.day !== day) {
                current = { day: day, items: [] };
                groups.push(current);
            }
            current.items.push(e);
        });

        el('audFeed').innerHTML = groups.map(g => `
            <section class="aud-day">
                <div class="aud-day-head">
                    <h3 class="aud-day-title">${esc(dayHeading(g.day))}</h3>
                    <span class="aud-day-count">${g.items.length} ${g.items.length === 1 ? 'entry' : 'entries'}</span>
                </div>
                <div class="dir-card aud-day-table">
                    <div class="dir-scroll">
                        <table class="dir-table aud-table">
                            <thead>${tableHead()}</thead>
                            <tbody>${g.items.map(tableRow).join('')}</tbody>
                        </table>
                    </div>
                </div>
                <div class="aud-day-list">${g.items.map(entryRow).join('')}</div>
            </section>`).join('');

        const more = list.length - shown.length;
        el('audMoreWrap').hidden = more <= 0;
        if (more > 0) {
            el('audMoreBtn').textContent = 'Show ' + Math.min(PAGE_SIZE, more) + ' more';
            el('audMoreNote').textContent = 'Showing ' + shown.length.toLocaleString() +
                ' of ' + list.length.toLocaleString() + ' entries';
        }

        renderSummary(list);
        return list;
    }

    function render() {
        renderChips();
        renderFeed();
    }

    // ---------------------------------------------------------------- drawer
    function openEntry(id) {
        const e = ENTRIES.find(x => x.id === id);
        if (!e) return;

        const actor = ACTORS[e.actor] || { name: e.actor, label: '' };
        const action = ACTIONS[e.action] || { label: e.action, category: 'system' };
        const cat = CATEGORIES[action.category] || { label: action.category };
        const resultText = {
            success: 'Completed',
            failed: 'Failed — the action did not happen',
            refused: 'Refused — this account is not permitted to do it',
        }[e.result] || e.result;

        el('audDrawerTag').textContent = e.id;
        el('audDrawerTitle').textContent = action.label;
        el('audDrawerWhen').textContent = dayHeading(dayOf(e.ts)) + ' at ' + clockTime(e.ts) +
            ' · ' + relative(e.ts);

        el('audDrawerBody').innerHTML = `
            <div class="aud-drawer-lead">
                <div class="aud-drawer-lead-text">
                    <p>${esc(IS_SYSTEM ? actor.name : 'You')}</p>
                    <p>${esc(actor.label || '')}${IS_SYSTEM ? ' · ' + esc(e.actor) : ''}</p>
                </div>
            </div>

            <div>
                <p class="aud-section-label">What happened</p>
                <dl class="aud-kv">
                    <dt>Action</dt><dd>${esc(action.label)}</dd>
                    <dt>Kind</dt><dd>${esc(cat.label)}</dd>
                    ${e.target ? `<dt>Applied to</dt><dd>${esc(e.target)}</dd>` : ''}
                    ${e.detail ? `<dt>Detail</dt><dd>${esc(e.detail)}</dd>` : ''}
                    <dt>Outcome</dt><dd>${esc(resultText)}</dd>
                </dl>
            </div>

            <div>
                <p class="aud-section-label">When and from where</p>
                <dl class="aud-kv">
                    <dt>Date</dt><dd>${esc(longDate(dayOf(e.ts)))}</dd>
                    <dt>Time</dt><dd>${esc(e.ts.slice(11, 19))}</dd>
                    <dt>Network address</dt><dd class="mono">${esc(e.ip)}</dd>
                    <dt>Device</dt><dd>${esc(e.device)}</dd>
                </dl>
            </div>

            <div>
                <p class="aud-section-label">Record</p>
                <div class="aud-seal-box">
                    <p class="aud-seal-value">${esc(e.seal)}</p>
                    <p class="aud-seal-note">
                        A check value worked out from this entry when it was written. If a single
                        character of the entry ever changed, the value would no longer match — which
                        is how the system can prove the record has not been tampered with.
                    </p>
                </div>
            </div>`;

        el('audDrawerOverlay').hidden = false;
        el('audDrawerClose').focus();
    }

    function closeDrawer() {
        el('audDrawerOverlay').hidden = true;
    }

    // ---------------------------------------------------------------- export
    // Exports exactly what is on screen, filters and all — an audit extract is
    // only useful if you can say what it covers. This is real, not a stub: the
    // file is built in the browser from rows the server already sent.
    function exportCsv() {
        const list = filtered();
        const range = periodRange();
        const head = ['Entry', 'Date', 'Time', 'Person', 'Code', 'Role',
            'Action', 'Applied to', 'Detail', 'Outcome', 'Network address', 'Device', 'Check value'];

        const cell = v => '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"';
        const rows = list.map(e => {
            const actor = ACTORS[e.actor] || { name: e.actor, label: '' };
            const action = ACTIONS[e.action] || { label: e.action };
            return [e.id, dayOf(e.ts), e.ts.slice(11, 19), actor.name, e.actor, actor.label,
                action.label, e.target, e.detail, e.result, e.ip, e.device, e.seal].map(cell).join(',');
        });

        const csv = head.map(cell).join(',') + '\r\n' + rows.join('\r\n') + '\r\n';
        // The BOM is what makes Excel open a UTF-8 CSV without mangling names.
        const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'staffsync-activity-' + (IS_SYSTEM ? 'department' : VIEWER.code.toLowerCase()) +
            '-' + TODAY + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);

        // Requesting an extract of the log is itself an auditable action; the
        // backend records it at this point. Nothing is faked into the list here.
        el('audMoreNote').textContent = 'Downloaded ' + list.length.toLocaleString() +
            ' entries covering ' + range.label + '.';
    }

    // ---------------------------------------------------------------- events
    function reset() {
        state.search = '';
        state.period = 'week';
        state.from = '';
        state.to = '';
        state.cats.clear();
        state.person = '';
        state.problems = false;
        state.limit = PAGE_SIZE;
        el('audSearch').value = '';
        el('audPeriod').value = 'week';
        el('audDateRow').hidden = true;
        el('audFrom').value = '';
        el('audTo').value = '';
        if (el('audPerson')) el('audPerson').value = '';
        render();
    }

    el('audSearch').addEventListener('input', function () {
        state.search = this.value;
        state.limit = PAGE_SIZE;
        render();
    });

    el('audPeriod').addEventListener('change', function () {
        state.period = this.value;
        state.limit = PAGE_SIZE;
        el('audDateRow').hidden = this.value !== 'custom';
        // Opening the date boxes with nothing in them would empty the list for
        // no obvious reason, so they start on the last month.
        if (this.value === 'custom' && !state.from && !state.to) {
            state.from = shiftDays(-29);
            state.to = TODAY;
            el('audFrom').value = state.from;
            el('audTo').value = state.to;
        }
        render();
    });

    el('audFrom').addEventListener('change', function () {
        state.from = this.value;
        state.limit = PAGE_SIZE;
        render();
    });

    el('audTo').addEventListener('change', function () {
        state.to = this.value;
        state.limit = PAGE_SIZE;
        render();
    });

    if (el('audPerson')) {
        el('audPerson').addEventListener('change', function () {
            state.person = this.value;
            state.limit = PAGE_SIZE;
            render();
        });
    }

    // Chips toggle, and more than one can be on at a time — "leave and duties"
    // is a question people actually ask. "All activity" clears the selection.
    el('audChips').addEventListener('click', function (event) {
        const chip = event.target.closest('.aud-chip');
        if (!chip) return;
        const cat = chip.dataset.cat;
        if (cat === '') {
            state.cats.clear();
        } else if (state.cats.has(cat)) {
            state.cats.delete(cat);
        } else {
            state.cats.add(cat);
        }
        state.limit = PAGE_SIZE;
        render();
    });

    el('audFeed').addEventListener('click', function (event) {
        const row = event.target.closest('[data-id]');
        if (row) openEntry(row.dataset.id);
    });
    el('audFeed').addEventListener('keydown', function (event) {
        const row = event.target.closest('.aud-row');
        if (row && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            openEntry(row.dataset.id);
        }
    });

    el('audMoreBtn').addEventListener('click', function () {
        state.limit += PAGE_SIZE;
        renderFeed();
    });

    el('audClear').addEventListener('click', reset);
    el('audEmptyClear').addEventListener('click', reset);
    el('audExportBtn').addEventListener('click', exportCsv);

    el('audDrawerClose').addEventListener('click', closeDrawer);
    el('audDrawerOverlay').addEventListener('click', function (event) {
        if (event.target === this) closeDrawer();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !el('audDrawerOverlay').hidden) closeDrawer();
    });

    render();
})();
