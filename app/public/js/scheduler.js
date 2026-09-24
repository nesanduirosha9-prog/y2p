// scheduler.js — the duty tabs of the Workload page (/workload/distribution:
// This week, Requests, Who's free).
//
// Renders from the #schedData payload that WorkloadController::distribution()
// emits, and runs the allocation rules against it in the browser. Which tab is
// showing is decided by workload_hub.js, which announces it with `hub:tab`.
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
// On top of the sheet: when someone already on a duty goes on leave, the cover
// they named on their leave application (instructor/leave.php) is swapped in,
// provided the cover passes the same checks.
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
    // `via` records how each person got onto a duty ({ how, note }), and
    // `removed` everyone taken off one — the History tab reads both.
    let duties = DATA.duties.map(d => Object.assign({}, d, {
        assigned: [...d.assigned],
        via: Object.fromEntries(d.assigned.map(c => [c, { how: 'auto', note: '' }])),
    }));
    const removed = [];                             // { duty, code, note }
    let requests = DATA.requests.map(r => Object.assign({}, r));
    let view = 'week';
    let availDay = 'mon';
    let availStaff = null;                          // Who's free: one person's week, or everyone
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
        // Not toLocaleDateString('en-GB'): that now gives "Sept", not "Sep".
        const d = new Date(iso + 'T00:00:00');
        return d.getDate() + ' ' + ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][d.getMonth()];
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

    /**
     * Every reason `code` can't take this duty, in the allocator's order. The
     * one rule set behind the allocator, the manual picker and the cover
     * check, so they cannot disagree.
     */
    function reasonsAgainst(code, duty) {
        const s = staffByCode[code];
        const dayKey = weekdayKey(duty.date);
        const out = [];
        if (!s || !s.active) out.push('Inactive');
        else if (s.paused) out.push('Temporarily paused');
        if (onLeave(code, duty.date)) out.push(leaveReason(code, duty.date));
        if (dayKey && !freeForAll(code, dayKey, duty.slots)) out.push('Not free for every slot');
        if (clashesOn(code, duty.date, duty.slots, duty.id)) out.push('Already booked that day');
        return out;
    }

    /** The cover named on this person's leave for that date, or null. */
    function coverFor(code, iso) {
        const l = DATA.leave.find(x => x.code === code && iso >= x.from && iso <= x.to);
        return l && l.cover ? (l.cover[iso] || null) : null;
    }

    /** The cover, but only if they can actually take this duty. */
    function usableCover(code, duty) {
        const cover = coverFor(code, duty.date);
        if (!cover || duty.assigned.includes(cover)) return null;
        return reasonsAgainst(cover, duty).length ? null : cover;
    }

    /** Replaces `code` with their cover on this duty. Returns the cover, or null. */
    function swapInCover(duty, code) {
        const cover = usableCover(code, duty);
        if (!cover) return null;
        const reason = leaveReason(code, duty.date);
        duty.assigned = duty.assigned.map(c => (c === code ? cover : c));
        duty.via[cover] = { how: 'cover', note: 'covering ' + code + (reason ? ' (' + reason + ')' : '') };
        takeOff(duty, code, (reason || 'On leave') + ' — covered by ' + cover);
        return cover;
    }

    /** Removes someone from a duty and keeps the record for History. */
    function takeOff(duty, code, note) {
        duty.assigned = duty.assigned.filter(c => c !== code);
        delete duty.via[code];
        removed.push({ duty, code, note });
    }

    /**
     * This week's allocations as History records — everyone on a duty now,
     * plus everyone taken off one. Sent with every render, so the History tab
     * always matches the board.
     */
    function historyRows() {
        const base = d => ({
            kind: 'duty',
            date: d.date,
            week: DATA.week.from,
            course: d.course,
            course_name: d.course_name || '',
            lecturer: d.requester,
            lecturer_name: d.requester_name || d.requester,
            title: d.duty,
            slots: d.slots,
        });
        const rows = [];
        duties.forEach(d => d.assigned.forEach(code => {
            const v = d.via[code] || { how: 'auto', note: '' };
            rows.push(Object.assign(base(d), { staff: code, how: v.how, note: v.note }));
        }));
        removed.forEach(r => rows.push(Object.assign(base(r.duty), { staff: r.code, how: 'removed', note: r.note })));
        return rows;
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

            const why = reasonsAgainst(code, duty);
            if (why.length) { rejected.push({ code, why: why[0] }); return; }

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
        result.picked.forEach(c => { duty.via[c] = { how: 'auto', note: '' }; });
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

    /**
     * `clashes` are the rostered people who can't actually do it; `covers`
     * maps each of them to a usable leave cover, where there is one.
     */
    function dutyState(d) {
        const problems = dutyProblems(d);
        const clashes = Object.keys(problems);
        if (clashes.length) {
            const covers = {};
            clashes.forEach(code => {
                const cover = usableCover(code, d);
                if (cover) covers[code] = cover;
            });
            return { key: 'clash', clashes, problems, covers };
        }
        if (d.assigned.length >= d.headcount) return { key: 'filled', clashes: [], problems: {}, covers: {} };
        return { key: 'short', clashes: [], problems: {}, covers: {} };
    }

    // --------------------------------------------------------------- rendering
    function renderKpis() {
        let filled = 0, needed = 0, clashes = 0;
        duties.forEach(d => {
            const st = dutyState(d);
            filled += Math.min(d.assigned.length - st.clashes.length, d.headcount);
            needed += d.headcount;
            if (st.key === 'clash') clashes++;
        });

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

    /** This week: one row per duty, grouped under a heading per day. */
    function renderWeek() {
        const byDay = {};
        [...duties]
            .sort((a, b) => a.date.localeCompare(b.date) ||
                (SLOT_HOURS[a.slots[0]] || 0) - (SLOT_HOURS[b.slots[0]] || 0))
            .forEach(d => (byDay[d.date] = byDay[d.date] || []).push(d));

        el('dutyGrid').innerHTML = Object.keys(byDay).map(iso => `
            <section class="duty-day">
                <h4 class="duty-day-head">${esc(dayName(iso))}<span>${esc(prettyDate(iso))}</span></h4>
                ${byDay[iso].map(dutyRow).join('')}
            </section>`).join('');

        el('dutyEmpty').hidden = duties.length > 0;
    }

    /**
     * One duty. The count shows only people who can actually do it, so a
     * roster with someone on leave reads 2/3, not 3/3. Each problem gets
     * exactly one fix: their leave cover if that cover is free, otherwise
     * Replace (drop them and let the allocator pick).
     */
    function dutyRow(d) {
        const st = dutyState(d);
        const ok = d.assigned.length - st.clashes.length;

        const chips = d.assigned.map(code => {
            const why = st.problems[code];
            const s = staffByCode[code];
            // Plain chip; the problem is spelled out in the issue line below.
            return codeBadge(code, 'staff', { title: (s ? s.name : code) + (why ? ' — ' + why : '') });
        }).join('');

        const issues = st.clashes.map(code => {
            const cover = st.covers[code];
            return `
                <div class="duty-row-issue">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>${esc(code)} — ${esc(st.problems[code])}${cover ? ' · cover: ' + esc(cover) : ''}</span>
                    ${cover
                        ? `<button type="button" class="btn-secondary-sm" data-cover="${esc(d.id)}|${esc(code)}">
                               <i class="fa-solid fa-user-shield"></i> Use ${esc(cover)}
                           </button>`
                        : `<button type="button" class="btn-ghost" data-replace="${esc(d.id)}|${esc(code)}">Replace</button>`}
                </div>`;
        }).join('');

        return `
            <article class="duty-row is-${st.key}" data-duty="${esc(d.id)}">
                <span class="duty-row-time">${esc(slotRange(d.slots))}</span>
                <div class="duty-row-main">
                    ${codeBadge(d.course, 'course', { title: d.course_name })}
                    <span class="duty-row-title" title="${esc(d.course_name || d.course)} · requested by ${esc(d.requester_name || d.requester)}">${esc(d.duty)}</span>
                </div>
                <div class="duty-row-staff">
                    ${chips || '<span class="wm-none">Nobody yet</span>'}
                    <button type="button" class="wm-add-chip" data-swap="${esc(d.id)}" title="Add or remove staff by hand" aria-label="Add or remove staff"><i class="fa-solid fa-plus"></i></button>
                    <span class="duty-row-count" title="${ok} of ${d.headcount} staff can do it">${ok}/${d.headcount}</span>
                </div>
                <div class="duty-row-actions">
                    ${ok < d.headcount && !st.clashes.length
                        ? `<button type="button" class="btn-secondary-sm" data-fill="${esc(d.id)}"><i class="fa-solid fa-wand-magic-sparkles"></i> Fill</button>`
                        : ''}
                    <button type="button" class="btn-ghost" data-invite="${esc(d.id)}" title="Preview invite" aria-label="Preview invite"
                            ${d.assigned.length ? '' : 'disabled'}>
                        <i class="fa-regular fa-envelope"></i>
                    </button>
                </div>
                ${issues}
            </article>`;
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

    /**
     * ISO date of that weekday in the active week, built from local parts.
     * toISOString() would convert local midnight to UTC, which in Sri Lanka
     * (UTC+5:30) lands on the previous day.
     */
    function isoForDay(dayKey) {
        const d = new Date(DATA.week.from + 'T00:00:00');
        d.setDate(d.getDate() + DAY_KEYS.indexOf(dayKey));
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0');
    }

    /**
     * One person × one weekday: the slot cells, how many slots are genuinely
     * free (not on duty, not blocked), and why the whole day is blocked.
     * Leave, inactive and paused beat a free slot.
     */
    function availRow(s, dayKey) {
        const iso = isoForDay(dayKey);
        const free = (DATA.availability[s.code] || {})[dayKey] || [];
        const leave = onLeave(s.code, iso);
        const blocked = !s.active || s.paused || leave;
        const reason = !s.active ? 'Inactive' : s.paused ? 'Paused' : leave ? leaveReason(s.code, iso) : '';

        let count = 0;
        const cells = SLOTS.map(slot => {
            const booked = duties.some(d => d.date === iso && d.assigned.includes(s.code) && d.slots.includes(slot));
            const isFree = !blocked && !booked && free.includes(slot);
            if (isFree) count++;
            const cls = booked ? 'is-booked' : isFree ? 'is-free' : 'is-busy';
            const t = booked ? 'On duty' : isFree ? 'Free' : (reason || 'Unavailable');
            return `<td class="avail-cell ${cls}" title="${esc(slot + ' · ' + t)}"></td>`;
        }).join('');

        return { cells, count, blocked, reason };
    }

    /**
     * Everyone for one weekday, or — with a staff member picked — that
     * person's whole week, one row per day. The day switch only means
     * something in the first mode, so it hides in the second.
     */
    function renderAvailability() {
        const one = availStaff ? staffByCode[availStaff] : null;

        el('availStaffLabel').textContent = one ? one.code + ' · ' + one.name : 'All staff';
        el('availDaySeg').hidden = !!one;
        el('availDaySeg').innerHTML = DAY_KEYS.map(k =>
            `<button type="button" class="seg-btn ${k === availDay ? 'active' : ''}" data-day="${k}">${esc(WEEKDAYS[k].slice(0, 3))}</button>`
        ).join('');

        el('availHead').innerHTML = `
            <tr>
                <th style="width:180px;">${one ? 'Day' : 'Staff'}</th>
                ${SLOTS.map(s => `<th class="avail-slot-head">${esc(s)}</th>`).join('')}
                <th style="width:90px;text-align:right;">Free</th>
            </tr>`;

        const rows = one
            ? DAY_KEYS.map(k => ({
                label: `<strong>${esc(WEEKDAYS[k])}</strong> <span class="wm-sum-courses">${esc(prettyDate(isoForDay(k)))}</span>`,
                r: availRow(one, k),
            }))
            : DATA.staff.map(s => ({
                label: `<div class="lec-identity">${codeBadge(s.code, 'staff')}<span class="lec-name">${esc(s.name)}</span></div>`,
                r: availRow(s, availDay),
            }));

        el('availBody').innerHTML = rows.map(({ label, r }) => `
            <tr class="${r.blocked ? 'avail-blocked' : ''}">
                <td>
                    ${label}
                    ${r.reason ? `<span class="wm-sum-courses">${esc(r.reason)}</span>` : ''}
                </td>
                ${r.cells}
                <td style="text-align:right;"><strong>${r.count}</strong></td>
            </tr>`).join('');
    }

    /** The staff picker's option list, filtered by its search box. */
    function renderStaffOptions() {
        const q = el('availStaffSearch').value.trim().toLowerCase();
        const opts = DATA.staff.filter(s => !q || (s.code + ' ' + s.name).toLowerCase().includes(q));
        el('availStaffList').innerHTML =
            (q ? '' : `<button type="button" class="sched-combo-opt ${availStaff ? '' : 'is-selected'}" data-staff="" role="option">All staff</button>`) +
            opts.map(s => `
                <button type="button" class="sched-combo-opt ${s.code === availStaff ? 'is-selected' : ''}" data-staff="${esc(s.code)}" role="option">
                    ${codeBadge(s.code, 'staff')}<span>${esc(s.name)}</span>
                </button>`).join('') ||
            '<p class="dir-empty">No staff match.</p>';
    }

    function toggleStaffPop(open) {
        el('availStaffPop').hidden = !open;
        el('availStaffBtn').setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            el('availStaffSearch').value = '';
            renderStaffOptions();
            el('availStaffSearch').focus();
        }
    }

    function render() {
        document.dispatchEvent(new CustomEvent('sched:changed', { detail: { rows: historyRows() } }));
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
        el('swapModalTitle').textContent = 'Add staff to ' + duty.course;

        const rows = DATA.staff.map(s => {
            const code = s.code;
            const already = duty.assigned.includes(code);
            const reasons = reasonsAgainst(code, duty);

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
    // workload_hub.js owns the tab bar and the panel visibility; this only
    // tracks which of its panels to render.
    document.addEventListener('hub:tab', e => {
        const map = { week: 'week', requests: 'requests', free: 'availability' };
        if (!map[e.detail.tab]) return;
        view = map[e.detail.tab];
        render();
    });

    el('autoAllocateBtn').addEventListener('click', () => {
        // First, anyone rostered on a day they are on leave hands over to the
        // cover they named — if that cover passes the same checks. Nobody else
        // is dropped automatically; that stays a Replace click.
        const swaps = [];
        duties.forEach(d => d.assigned.slice().forEach(code => {
            if (!onLeave(code, d.date)) return;
            const to = swapInCover(d, code);
            if (to) swaps.push(code + ' → ' + to + ' on ' + d.course);
        }));

        const open = duties.filter(d => d.assigned.length < d.headcount);
        if (!open.length && !swaps.length) {
            showAllocationResult('Nothing to allocate', []);
            el('allocModalBody').innerHTML = '<p class="dir-empty">Every duty this week is already fully staffed.</p>';
            return;
        }
        // Sequential on purpose: each duty's result feeds the next one's duty
        // counts, which is what keeps the spread even across the week.
        const results = open.map(d => ({ duty: d, result: allocate(d) }));
        render();

        const parts = [];
        if (open.length) parts.push('allocated ' + open.length + ' dut' + (open.length === 1 ? 'y' : 'ies'));
        if (swaps.length) parts.push(swaps.length + ' cover' + (swaps.length === 1 ? '' : 's') + ' swapped in');
        const title = parts.join(', ');
        showAllocationResult(title.charAt(0).toUpperCase() + title.slice(1), results);

        if (swaps.length) {
            el('allocModalBody').insertAdjacentHTML('afterbegin', `
                <div class="alloc-result is-ok">
                    <p class="alloc-line">
                        <i class="fa-solid fa-user-shield"></i>
                        Leave covers swapped in: <strong>${esc(swaps.join(', '))}</strong>
                    </p>
                </div>`);
        }
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
        const cover = e.target.closest('[data-cover]');
        if (cover) {
            const [id, code] = cover.dataset.cover.split('|');
            swapInCover(duties.find(x => x.id === id), code);
            render();
            return;
        }
        // No usable cover: drop them and let the allocator pick a replacement,
        // explaining the choice like any other fill.
        const replace = e.target.closest('[data-replace]');
        if (replace) {
            const [id, code] = replace.dataset.replace.split('|');
            const d = duties.find(x => x.id === id);
            takeOff(d, code, (dutyProblems(d)[code] || 'Could not do it') + ' — replaced');
            const result = allocate(d);
            result.picked.forEach(c => { d.via[c] = { how: 'replacement', note: 'replacing ' + code }; });
            render();
            showAllocationResult('Replaced ' + code + ' on ' + d.course, [{ duty: d, result }]);
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
            if (d && !d.assigned.includes(code)) {
                d.assigned.push(code);
                d.via[code] = { how: 'manual', note: 'added by hand' };
            }
            render();
            showSwap(id);
            return;
        }
        const rm = e.target.closest('[data-swap-remove]');
        if (rm) {
            const [id, code] = rm.dataset.swapRemove.split('|');
            const d = duties.find(x => x.id === id);
            if (d && d.assigned.includes(code)) takeOff(d, code, 'removed by hand');
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
                course_name: r.course_name || '',
                duty: r.duty,
                date: r.date,
                slots: r.slots,
                headcount: r.headcount,
                assigned: [],
                via: {},
            };
            duties.push(duty);
            return { duty, result: allocate(duty) };
        });

        requests = requests.filter(r => !selected.has(r.id));
        selected.clear();

        view = 'week';
        if (window.wmHubShow) window.wmHubShow('week');

        render();
        showAllocationResult('Scheduled ' + taken.length + ' request' + (taken.length === 1 ? '' : 's'), results);
    });

    el('availDaySeg').addEventListener('click', e => {
        const btn = e.target.closest('[data-day]');
        if (!btn) return;
        availDay = btn.dataset.day;
        renderAvailability();
    });

    // Staff picker: type to narrow, Enter takes the first match, a click
    // anywhere else closes it.
    el('availStaffBtn').addEventListener('click', () => toggleStaffPop(el('availStaffPop').hidden));
    el('availStaffSearch').addEventListener('input', renderStaffOptions);
    el('availStaffSearch').addEventListener('keydown', e => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const first = el('availStaffList').querySelector('[data-staff]');
        if (first) first.click();
    });
    el('availStaffList').addEventListener('click', e => {
        const opt = e.target.closest('[data-staff]');
        if (!opt) return;
        availStaff = opt.dataset.staff || null;
        toggleStaffPop(false);
        renderAvailability();
    });
    document.addEventListener('click', e => {
        if (!el('availStaffPop').hidden && !e.target.closest('#availStaffCombo')) toggleStaffPop(false);
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
        if (!el('availStaffPop').hidden) { toggleStaffPop(false); el('availStaffBtn').focus(); return; }
        ['allocModal', 'swapModal', 'inviteModal'].forEach(m => { if (!el(m).hidden) closeModal(m); });
    });

    render();
})();
