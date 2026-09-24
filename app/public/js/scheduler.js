// scheduler.js — the Duty Scheduler screen (/workload/scheduler).
//
// Renders from the #schedData payload that WorkloadController::scheduler()
// emits, and runs the allocation rules against it in the browser.
//
// ─────────────────────────────────────────────────────────────────────────────
// THE ALLOCATOR
//
// allocate() below is a faithful port of assignLowestWorkloadForWeek() in
// CurrentViews/script.js, the Apps Script the department runs today. Same rules,
// same order:
//
//   1. derive the weekday from the duty's date; weekends are not schedulable
//   2. keep only staff free in EVERY requested slot (the Mon–Fri sheets)
//   3. drop anyone whose approved leave covers that date  (getLeaveMap)
//   4. drop anyone inactive or temporarily paused         (NameLists cols G/H)
//   5. drop anyone already booked on that date at an overlapping slot
//      (this is checkDuplicateAssignments(), applied BEFORE the fact rather
//       than as an alert afterwards — the one place this improves on the sheet)
//   6. sort by fewest duties so far, then fewest course hours as a tiebreak
//   7. take the first `headcount`
//
// Writing it here first is deliberate: the rules are pinned down and visible
// before any table exists, so the server-side port is a translation rather than
// a fresh design. Where it runs is the only thing that should change.
//
// PROTOTYPE BOUNDARY: every result lives in memory. Nothing is saved, no mail
// is sent, no calendar event is created — the invite modal says so out loud.
// ─────────────────────────────────────────────────────────────────────────────
(function () {
    const dataEl = document.getElementById('schedData');
    const page = document.getElementById('schedPage');
    if (!dataEl || !page) return;

    const DATA = JSON.parse(dataEl.textContent);
    const SLOTS = DATA.slots;
    const SLOT_HOURS = DATA.slotHours;
    const WEEKDAYS = DATA.weekdays;                 // {mon:'Monday', …}
    const DAY_KEYS = Object.keys(WEEKDAYS);

    const staffByCode = {};
    DATA.staff.forEach(s => { staffByCode[s.code] = s; });

    const baseLoad = {};
    DATA.load.forEach(l => { baseLoad[l.code] = l; });

    // Working state. duties/requests are mutated as you approve and allocate.
    let duties = DATA.duties.map(d => Object.assign({}, d, { assigned: [...d.assigned] }));
    let requests = DATA.requests.map(r => Object.assign({}, r));
    let view = 'week';
    let availDay = 'mon';
    const selected = new Set();

    const el = id => document.getElementById(id);
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, m => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]
    ));

    // ------------------------------------------------------------ date helpers
    /** 'mon'…'fri' for an ISO date, or null for a weekend. */
    function weekdayKey(iso) {
        const d = new Date(iso + 'T00:00:00');
        const k = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'][d.getDay()];
        return DAY_KEYS.includes(k) ? k : null;
    }

    function prettyDate(iso) {
        const d = new Date(iso + 'T00:00:00');
        return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
    }

    function dayName(iso) {
        const k = weekdayKey(iso);
        return k ? WEEKDAYS[k] : '—';
    }

    /** '8-9' → '8:00 AM'. Used for the human time range on each card. */
    function slotLabel(slot) {
        const h = SLOT_HOURS[slot];
        if (h == null) return slot;
        const ampm = h >= 12 ? 'PM' : 'AM';
        const hr = h % 12 === 0 ? 12 : h % 12;
        return hr + ':00 ' + ampm;
    }

    function slotRange(slots) {
        if (!slots.length) return '—';
        const hours = slots.map(s => SLOT_HOURS[s]).sort((a, b) => a - b);
        const endH = hours[hours.length - 1] + 1;
        const endAmPm = endH >= 12 ? 'PM' : 'AM';
        const endHr = endH % 12 === 0 ? 12 : endH % 12;
        return slotLabel(slots.slice().sort((a, b) => SLOT_HOURS[a] - SLOT_HOURS[b])[0]) +
            ' – ' + endHr + ':00 ' + endAmPm;
    }

    /** Is this person's approved leave covering that date? (getLeaveMap) */
    function onLeave(code, iso) {
        return DATA.leave.some(l => l.code === code && iso >= l.from && iso <= l.to);
    }

    function leaveReason(code, iso) {
        const l = DATA.leave.find(x => x.code === code && iso >= x.from && iso <= x.to);
        return l ? l.reason + ' leave' : '';
    }

    /** Free in every one of these slots on that weekday? */
    function freeForAll(code, dayKey, slots) {
        const row = DATA.availability[code];
        if (!row || !row[dayKey]) return false;
        return slots.every(s => row[dayKey].includes(s));
    }

    /** Already booked on this date at a slot that overlaps? */
    function clashesOn(code, iso, slots, exceptDutyId) {
        return duties.some(d =>
            d.id !== exceptDutyId &&
            d.date === iso &&
            d.assigned.includes(code) &&
            d.slots.some(s => slots.includes(s))
        );
    }

    /** Duty count so far — the sort key the spreadsheet uses. */
    function dutyCount(code) {
        return duties.reduce((n, d) => n + (d.assigned.includes(code) ? 1 : 0), 0);
    }

    // ----------------------------------------------------------- the allocator
    /**
     * Returns { picked, rejected } for one duty without mutating anything, so
     * the UI can explain the decision before it is applied.
     */
    function evaluate(duty) {
        const dayKey = weekdayKey(duty.date);
        const rejected = [];

        if (!dayKey) {
            return { picked: [], rejected: [], fatal: 'That date falls on a weekend — duties are Monday to Friday.' };
        }

        const eligible = [];
        DATA.staff.forEach(s => {
            const code = s.code;
            if (duty.assigned.includes(code)) return;            // already on it

            if (!s.active) { rejected.push({ code, why: 'Inactive' }); return; }
            if (s.paused) { rejected.push({ code, why: 'Temporarily paused' }); return; }
            if (onLeave(code, duty.date)) { rejected.push({ code, why: leaveReason(code, duty.date) }); return; }
            if (!freeForAll(code, dayKey, duty.slots)) { rejected.push({ code, why: 'Not free for every slot' }); return; }
            if (clashesOn(code, duty.date, duty.slots, duty.id)) { rejected.push({ code, why: 'Already booked that day' }); return; }

            eligible.push({ code, duties: dutyCount(code), hours: (baseLoad[code] || {}).hours || 0 });
        });

        // Fewest duties first; course hours break the tie. This is the fairness
        // rule — the lightest-loaded person is the default pick, not a judgement.
        eligible.sort((a, b) => a.duties - b.duties || a.hours - b.hours || a.code.localeCompare(b.code));

        const need = Math.max(0, duty.headcount - duty.assigned.length);
        return { picked: eligible.slice(0, need).map(e => e.code), rejected, eligible };
    }

    /** Applies evaluate() to a duty. The single place the future POST goes. */
    function allocate(duty) {
        const result = evaluate(duty);
        duty.assigned = duty.assigned.concat(result.picked);
        return result;
    }

    // ------------------------------------------------------------- derivations
    /**
     * Every problem with a duty's current roster, not just double-booking.
     * The spreadsheet only ever checked for duplicates (checkDuplicateAssignments),
     * so somebody rostered onto a slot they are busy in — or a day they are on
     * leave for — sailed through. Anything hand-assigned, or inherited from an
     * older allocation, is re-checked here against the same rules the allocator
     * applies, so the board cannot show a green card over a broken roster.
     */
    function dutyProblems(d) {
        const dayKey = weekdayKey(d.date);
        const out = {};
        d.assigned.forEach(code => {
            const s = staffByCode[code];
            if (clashesOn(code, d.date, d.slots, d.id)) out[code] = 'double-booked';
            else if (onLeave(code, d.date)) out[code] = leaveReason(code, d.date);
            else if (dayKey && !freeForAll(code, dayKey, d.slots)) out[code] = 'not free for every slot';
            else if (s && !s.active) out[code] = 'inactive';
            else if (s && s.paused) out[code] = 'paused';
        });
        return out;
    }

    function dutyState(d) {
        const problems = dutyProblems(d);
        const clashes = Object.keys(problems);
        if (clashes.length) return { key: 'clash', clashes, problems };
        if (d.assigned.length >= d.headcount) return { key: 'filled', clashes: [], problems: {} };
        return { key: 'short', clashes: [], problems: {} };
    }

    // --------------------------------------------------------------- rendering
    function renderKpis() {
        const filled = duties.reduce((n, d) => n + Math.min(d.assigned.length, d.headcount), 0);
        const needed = duties.reduce((n, d) => n + d.headcount, 0);
        const clashes = duties.reduce((n, d) => n + (dutyState(d).key === 'clash' ? 1 : 0), 0);

        el('kpiWeek').textContent = 'Week ' + DATA.week.number;
        el('kpiWeekRange').textContent = DATA.week.label;
        el('kpiRequests').textContent = requests.length;
        el('kpiFilled').textContent = filled + ' / ' + needed;
        el('kpiConflicts').textContent = clashes;
        el('tabWeekBadge').textContent = duties.length + ' duties';
        el('tabRequestsBadge').textContent = requests.length + ' pending';
        el('tabRequestsBadge').hidden = requests.length === 0;
        el('weekHeading').textContent = 'Duties this week (' + DATA.week.label + ')';
    }

    function renderWeek() {
        const grid = el('dutyGrid');
        const ordered = [...duties].sort((a, b) => a.date.localeCompare(b.date) ||
            (SLOT_HOURS[a.slots[0]] || 0) - (SLOT_HOURS[b.slots[0]] || 0));

        grid.innerHTML = ordered.map(d => {
            const st = dutyState(d);
            // Reuses the existing .duty-session-card palette in scheduler.css
            // rather than introducing a parallel set of card classes.
            const cardClass = st.key === 'clash' ? 'duty-session-card is-conflict'
                : st.key === 'filled' ? 'duty-session-card is-fulfilled'
                : 'duty-session-card is-pending';

            const chips = d.assigned.map(code => {
                const why = st.problems ? st.problems[code] : null;
                const bad = !!why;
                const s = staffByCode[code];
                return codeBadge(code, 'staff', {
                    title: (s ? s.name : code) + (bad ? ' — ' + why : ''),
                    classes: [bad ? 'is-flagged' : ''],
                    inner: (bad ? '<i class="fa-solid fa-triangle-exclamation"></i>' : '')
                        + `<button type="button" class="wm-chip-x" data-drop="${esc(d.id)}|${esc(code)}"
                                title="Remove ${esc(code)}" aria-label="Remove ${esc(code)}">&times;</button>`,
                });
            }).join('');

            return `
                <article class="${cardClass}" data-duty="${esc(d.id)}">
                    <header class="session-card-top">
                        <div class="session-day-badge">
                            <span class="session-day">${esc(dayName(d.date))}</span>
                            <span class="session-date">${esc(prettyDate(d.date))}</span>
                        </div>
                        <span class="session-time-pill">
                            <i class="fa-regular fa-clock"></i> ${esc(slotRange(d.slots))}
                        </span>
                    </header>

                    <div class="session-card-middle">
                        ${codeBadge(d.course, 'course', { title: d.course_name })}
                        <h4 class="session-title">${esc(d.duty)}</h4>
                        <p class="session-course-sub">${esc(d.course_name || '')} · requested by ${esc(d.requester_name || d.requester)}</p>
                    </div>

                    ${st.key === 'clash' ? `
                        <div class="session-conflict-alert">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span>${Object.entries(st.problems).map(([c, w]) => esc(c) + ' — ' + esc(w)).join('; ')}</span>
                        </div>` : ''}

                    <footer class="session-card-bottom">
                        <div class="session-assignees-header">
                            <span class="assignees-title">Assigned staff</span>
                            ${d.assigned.length >= d.headcount
                                ? `<span class="status-chip chip-success"><i class="fa-solid fa-check"></i> ${d.assigned.length} of ${d.headcount}</span>`
                                : `<span class="status-chip chip-warning"><i class="fa-solid fa-user-clock"></i> ${d.assigned.length} of ${d.headcount}</span>`}
                        </div>
                        <div class="session-staff-pills">
                            ${chips || '<p class="unassigned-notice">Nobody allocated yet.</p>'}
                            <button type="button" class="wm-add-chip" data-swap="${esc(d.id)}" title="Add someone by hand"><i class="fa-solid fa-plus"></i></button>
                        </div>
                        <div class="session-card-actions">
                            <button type="button" class="btn-secondary-sm" data-fill="${esc(d.id)}"
                                    ${d.assigned.length >= d.headcount ? 'disabled' : ''}>
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Fill
                            </button>
                            <button type="button" class="btn-ghost" data-invite="${esc(d.id)}"
                                    ${d.assigned.length === 0 ? 'disabled' : ''}>
                                <i class="fa-regular fa-envelope"></i> Invite
                            </button>
                        </div>
                    </footer>
                </article>`;
        }).join('');

        el('dutyEmpty').hidden = duties.length > 0;
    }

    function renderRequests() {
        el('requestsBody').innerHTML = requests.map(r => {
            // Show up front whether this request is even fillable, so the
            // Coordinator is not approving something that cannot be staffed.
            const probe = evaluate({ id: '_probe', date: r.date, slots: r.slots, headcount: r.headcount, assigned: [] });
            const canFill = probe.fatal ? 0 : Math.min(probe.picked.length, r.headcount);
            const verdict = probe.fatal
                ? `<span class="pill pill-danger" title="${esc(probe.fatal)}">Weekend</span>`
                : canFill >= r.headcount
                    ? `<span class="pill pill-active">${canFill} available</span>`
                    : `<span class="pill pill-warn" title="Only ${canFill} of ${r.headcount} staff are free">Only ${canFill} of ${r.headcount}</span>`;

            return `
                <tr data-request="${esc(r.id)}">
                    <td><input type="checkbox" class="request-cb" data-id="${esc(r.id)}" ${selected.has(r.id) ? 'checked' : ''}></td>
                    <td>
                        ${codeBadge(r.requester, 'lecturer', { title: r.requester_name })}
                    </td>
                    <td>
                        ${codeBadge(r.course, 'course', { title: r.course_name })}
                        <p class="page-head-sub" style="margin:2px 0 0;">${esc(r.duty)}</p>
                        ${r.note ? `<p class="sched-req-note">${esc(r.note)}</p>` : ''}
                    </td>
                    <td>
                        <strong>${esc(dayName(r.date))}</strong>
                        <p class="page-head-sub" style="margin:0;">${esc(prettyDate(r.date))}</p>
                    </td>
                    <td>
                        <div class="duty-item-slots">
                            ${r.slots.map(s => `<span class="slot-tag">${esc(s)}</span>`).join('')}
                        </div>
                    </td>
                    <td style="text-align:center;"><strong>${r.headcount}</strong></td>
                    <td>${verdict}</td>
                </tr>`;
        }).join('');

        el('requestsEmpty').hidden = requests.length > 0;
        el('selectedCount').textContent = selected.size;
        el('batchApproveBtn').disabled = selected.size === 0;
        const all = el('selectAllRequests');
        all.checked = requests.length > 0 && selected.size === requests.length;
    }

    function renderAvailability() {
        el('availDaySeg').innerHTML = DAY_KEYS.map(k =>
            `<button type="button" class="seg-btn ${k === availDay ? 'active' : ''}" data-day="${k}">${esc(WEEKDAYS[k].slice(0, 3))}</button>`
        ).join('');

        el('availHead').innerHTML = `
            <tr>
                <th style="width:180px;">Staff</th>
                ${SLOTS.map(s => `<th class="avail-slot-head">${esc(s)}</th>`).join('')}
                <th style="width:90px;text-align:right;">Free</th>
            </tr>`;

        // Anyone on leave on the selected weekday's date this week is shown as
        // unavailable outright, because leave beats a free slot.
        const dayIndex = DAY_KEYS.indexOf(availDay);
        const weekStart = new Date(DATA.week.from + 'T00:00:00');
        const dayDate = new Date(weekStart);
        dayDate.setDate(weekStart.getDate() + dayIndex);
        const iso = dayDate.toISOString().slice(0, 10);

        el('availBody').innerHTML = DATA.staff.map(s => {
            const free = (DATA.availability[s.code] || {})[availDay] || [];
            const leave = onLeave(s.code, iso);
            const blocked = !s.active || s.paused || leave;
            const reason = !s.active ? 'Inactive' : s.paused ? 'Paused' : leave ? leaveReason(s.code, iso) : '';

            return `
                <tr class="${blocked ? 'avail-blocked' : ''}">
                    <td>
                        <div class="lec-identity">
                            ${codeBadge(s.code, 'staff')}
                            <div>
                                <span class="lec-name">${esc(s.name)}</span>
                                ${reason ? `<span class="wm-sum-courses">${esc(reason)}</span>` : ''}
                            </div>
                        </div>
                    </td>
                    ${SLOTS.map(slot => {
                        const isFree = !blocked && free.includes(slot);
                        const booked = duties.some(d => d.date === iso && d.assigned.includes(s.code) && d.slots.includes(slot));
                        const cls = booked ? 'avail-cell is-booked' : isFree ? 'avail-cell is-free' : 'avail-cell is-busy';
                        const t = booked ? 'On duty' : isFree ? 'Free' : blocked ? reason : 'Unavailable';
                        return `<td class="${cls}" title="${esc(t)}"></td>`;
                    }).join('')}
                    <td style="text-align:right;"><strong>${blocked ? 0 : free.length}</strong></td>
                </tr>`;
        }).join('');
    }

    function render() {
        renderKpis();
        if (view === 'week') renderWeek();
        else if (view === 'requests') renderRequests();
        else renderAvailability();
    }

    // ------------------------------------------------------------------ modals
    function openModal(id) { el(id).hidden = false; }
    function closeModal(id) { el(id).hidden = true; }

    function showAllocationResult(title, results) {
        el('allocModalTitle').textContent = title;

        el('allocModalBody').innerHTML = results.map(({ duty, result }) => {
            const need = duty.headcount;
            const got = duty.assigned.length;
            const shortfall = Math.max(0, need - got);

            // Group the rejections so the explanation is readable rather than a
            // list of 18 names.
            const byReason = {};
            (result.rejected || []).forEach(r => {
                (byReason[r.why] = byReason[r.why] || []).push(r.code);
            });

            return `
                <div class="alloc-result ${shortfall ? 'is-short' : 'is-ok'}">
                    <div class="alloc-result-head">
                        ${codeBadge(duty.course, 'course', { title: duty.course_name })}
                        <strong>${esc(duty.duty)}</strong>
                        <span class="alloc-result-when">${esc(dayName(duty.date))} ${esc(slotRange(duty.slots))}</span>
                    </div>
                    ${result.fatal
                        ? `<p class="alloc-fatal"><i class="fa-solid fa-circle-exclamation"></i> ${esc(result.fatal)}</p>`
                        : `
                        <p class="alloc-line">
                            ${result.picked.length
                                ? `Picked <strong>${esc(result.picked.join(', '))}</strong> — the least-loaded staff free for every slot.`
                                : 'Nobody could be added.'}
                            ${shortfall ? `<span class="alloc-short">Still ${shortfall} short of ${need}.</span>` : ''}
                        </p>
                        ${Object.keys(byReason).length ? `
                            <details class="alloc-why">
                                <summary>Why the others were ruled out</summary>
                                <ul>
                                    ${Object.entries(byReason).map(([why, codes]) =>
                                        `<li><span class="alloc-why-reason">${esc(why)}</span> ${esc(codes.join(', '))}</li>`
                                    ).join('')}
                                </ul>
                            </details>` : ''}`}
                </div>`;
        }).join('');

        openModal('allocModal');
    }

    function showSwap(dutyId) {
        const duty = duties.find(d => d.id === dutyId);
        if (!duty) return;
        const dayKey = weekdayKey(duty.date);
        el('swapModalTitle').textContent = 'Add staff to ' + duty.course;

        const rows = DATA.staff.map(s => {
            const code = s.code;
            const already = duty.assigned.includes(code);
            const reasons = [];
            if (!s.active) reasons.push('Inactive');
            if (s.paused) reasons.push('Paused');
            if (onLeave(code, duty.date)) reasons.push(leaveReason(code, duty.date));
            if (dayKey && !freeForAll(code, dayKey, duty.slots)) reasons.push('Not free for every slot');
            if (clashesOn(code, duty.date, duty.slots, duty.id)) reasons.push('Already booked that day');

            return { code, name: s.name, already, reasons, duties: dutyCount(code) };
        }).sort((a, b) => a.reasons.length - b.reasons.length || a.duties - b.duties || a.code.localeCompare(b.code));

        el('swapModalBody').innerHTML = `
            <p class="swap-intro">Anyone can be added, but the reasons the allocator skipped them are spelled out —
               overriding is your call, not a hidden one.</p>
            ${rows.map(r => `
                <div class="swap-row ${r.already ? 'is-assigned' : ''} ${r.reasons.length ? 'is-warned' : ''}">
                    ${codeBadge(r.code, 'staff')}
                    <div class="swap-id">
                        <span class="swap-name">${esc(r.name)}</span>
                        <span class="swap-meta">${r.duties} dut${r.duties === 1 ? 'y' : 'ies'} this week
                            ${r.reasons.length ? '· <b>' + esc(r.reasons.join(' · ')) + '</b>' : '· free'}</span>
                    </div>
                    ${r.already
                        ? `<button type="button" class="btn-ghost" data-swap-remove="${esc(dutyId)}|${esc(r.code)}">Remove</button>`
                        : `<button type="button" class="btn-secondary-sm" data-swap-add="${esc(dutyId)}|${esc(r.code)}">Add</button>`}
                </div>`).join('')}`;

        openModal('swapModal');
    }

    function showInvite(dutyId) {
        const duty = duties.find(d => d.id === dutyId);
        if (!duty) return;

        el('inviteModalBody').innerHTML = `
            <div class="invite-meta">
                <div><span class="invite-label">To</span><span>${esc(duty.requester_name || duty.requester)} and ${duty.assigned.length} assigned staff</span></div>
                <div><span class="invite-label">Subject</span><span>[Duty] ${esc(duty.course)} — ${esc(dayName(duty.date))} ${esc(prettyDate(duty.date))}</span></div>
            </div>
            <div class="invite-preview">
                <p>Dear <strong>${esc(duty.requester_name || duty.requester)}</strong>,</p>
                <p>Please find below the supportive members assigned to your session:</p>
                <table class="invite-table">
                    <thead><tr><th>Code</th><th>Name</th><th>Email</th></tr></thead>
                    <tbody>
                        ${duty.assigned.map(code => {
                            const s = staffByCode[code] || { name: code };
                            return `<tr>
                                <td>${esc(code)}</td>
                                <td>${esc(s.name)}</td>
                                <td>${esc(code.toLowerCase())}@ucsc.cmb.ac.lk</td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
                <p class="invite-detail"><strong>Course:</strong> ${esc(duty.course)} — ${esc(duty.course_name || '')}</p>
                <p class="invite-detail"><strong>Date:</strong> ${esc(dayName(duty.date))}, ${esc(prettyDate(duty.date))}</p>
                <p class="invite-detail"><strong>Time:</strong> ${esc(slotRange(duty.slots))}</p>
                <p class="invite-sign">Kindly meet the lecturer in advance to clarify your duties.</p>
            </div>`;

        openModal('inviteModal');
    }

    // ----------------------------------------------------------------- wiring
    document.querySelector('.sched-nav-tabs').addEventListener('click', e => {
        const btn = e.target.closest('[data-view]');
        if (!btn) return;
        view = btn.dataset.view;
        document.querySelectorAll('.sched-nav-tab').forEach(t => {
            const on = t === btn;
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        el('panelWeek').hidden = view !== 'week';
        el('panelRequests').hidden = view !== 'requests';
        el('panelAvailability').hidden = view !== 'availability';
        render();
    });

    el('autoAllocateBtn').addEventListener('click', () => {
        const open = duties.filter(d => d.assigned.length < d.headcount);
        if (!open.length) {
            showAllocationResult('Nothing to allocate', []);
            el('allocModalBody').innerHTML = '<p class="dir-empty">Every duty this week is already fully staffed.</p>';
            return;
        }
        // Sequential on purpose: each duty's result feeds the next one's duty
        // counts, which is what keeps the spread even across the week.
        const results = open.map(d => ({ duty: d, result: allocate(d) }));
        render();
        showAllocationResult('Allocated ' + open.length + ' dut' + (open.length === 1 ? 'y' : 'ies'), results);
    });

    el('dutyGrid').addEventListener('click', e => {
        const fill = e.target.closest('[data-fill]');
        if (fill) {
            const d = duties.find(x => x.id === fill.dataset.fill);
            const result = allocate(d);
            render();
            showAllocationResult('Filled ' + d.course, [{ duty: d, result }]);
            return;
        }
        const drop = e.target.closest('[data-drop]');
        if (drop) {
            const [id, code] = drop.dataset.drop.split('|');
            const d = duties.find(x => x.id === id);
            d.assigned = d.assigned.filter(c => c !== code);
            render();
            return;
        }
        const swap = e.target.closest('[data-swap]');
        if (swap) { showSwap(swap.dataset.swap); return; }
        const invite = e.target.closest('[data-invite]');
        if (invite) showInvite(invite.dataset.invite);
    });

    el('swapModalBody').addEventListener('click', e => {
        const add = e.target.closest('[data-swap-add]');
        if (add) {
            const [id, code] = add.dataset.swapAdd.split('|');
            const d = duties.find(x => x.id === id);
            if (d && !d.assigned.includes(code)) d.assigned.push(code);
            render();
            showSwap(id);
            return;
        }
        const rm = e.target.closest('[data-swap-remove]');
        if (rm) {
            const [id, code] = rm.dataset.swapRemove.split('|');
            const d = duties.find(x => x.id === id);
            if (d) d.assigned = d.assigned.filter(c => c !== code);
            render();
            showSwap(id);
        }
    });

    el('requestsBody').addEventListener('change', e => {
        const cb = e.target.closest('.request-cb');
        if (!cb) return;
        if (cb.checked) selected.add(cb.dataset.id); else selected.delete(cb.dataset.id);
        renderRequests();
    });

    el('selectAllRequests').addEventListener('change', e => {
        selected.clear();
        if (e.target.checked) requests.forEach(r => selected.add(r.id));
        renderRequests();
    });

    // moveTrueRowsToMain() + the allocator, as one action: approving a request
    // is only useful if it lands staffed.
    el('batchApproveBtn').addEventListener('click', () => {
        const taken = requests.filter(r => selected.has(r.id));
        if (!taken.length) return;

        const results = taken.map(r => {
            const duty = {
                id: 'duty-' + r.id,
                requester: r.requester,
                requester_name: r.requester_name,
                course: r.course,
                course_name: '',
                duty: r.duty,
                date: r.date,
                slots: r.slots,
                headcount: r.headcount,
                assigned: [],
            };
            duties.push(duty);
            return { duty, result: allocate(duty) };
        });

        requests = requests.filter(r => !selected.has(r.id));
        selected.clear();

        view = 'week';
        document.querySelectorAll('.sched-nav-tab').forEach(t => {
            const on = t.dataset.view === 'week';
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        el('panelWeek').hidden = false;
        el('panelRequests').hidden = true;
        el('panelAvailability').hidden = true;

        render();
        showAllocationResult('Scheduled ' + taken.length + ' request' + (taken.length === 1 ? '' : 's'), results);
    });

    el('availDaySeg').addEventListener('click', e => {
        const btn = e.target.closest('[data-day]');
        if (!btn) return;
        availDay = btn.dataset.day;
        renderAvailability();
    });

    [['allocModal', 'allocModalClose', 'allocModalDismiss'],
     ['swapModal', 'swapModalClose', 'swapModalDismiss'],
     ['inviteModal', 'inviteModalClose', 'inviteModalDismiss']].forEach(([modal, close, dismiss]) => {
        el(close).addEventListener('click', () => closeModal(modal));
        el(dismiss).addEventListener('click', () => closeModal(modal));
        el(modal).addEventListener('click', e => { if (e.target === el(modal)) closeModal(modal); });
    });

    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        ['allocModal', 'swapModal', 'inviteModal'].forEach(m => { if (!el(m).hidden) closeModal(m); });
    });

    render();
})();
