// leave_cells.js — table cells shared by both leave pages, so the member's own
// Leave page (js/instructor/leave.js) and the Leave Requests page
// (js/leave_requests.js) render a leave record the same way. Styles live in
// css/leave_tables.css; code badges come from js/code_badge.js.
//
// A record is one LeaveRequestModel row: leave_type, start_date, end_date,
// time_from, time_to, reason, requester_rank, days: [{date, cover_code, cover_name}].
window.leaveCells = (function () {
    const DAY = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const TYPE = { sick: 'Sick leave', other: 'Other' };

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    // ISO date -> local calendar day (no timezone shift).
    function parseDate(iso) {
        const [y, m, d] = iso.slice(0, 10).split('-').map(Number);
        return new Date(y, m - 1, d);
    }
    const dayMonth = iso => { const d = parseDate(iso); return `${d.getDate()} ${MON[d.getMonth()]}`; };
    const shortDate = iso => `${DAY[parseDate(iso).getDay()]} ${dayMonth(iso)}`;

    function clock(hhmm) {
        const [h, m] = hhmm.split(':').map(Number);
        return `${h % 12 || 12}:${String(m).padStart(2, '0')} ${h >= 12 ? 'PM' : 'AM'}`;
    }

    // Senior staff are lecturers; everyone else wears the staff badge.
    const badge = (code, rank, name) => codeBadge(code, rank === 'senior' ? 'lecturer' : 'staff', { title: name });

    /** Type tag. */
    function type(r) {
        return `<span class="leave-type">${esc(TYPE[r.leave_type] || r.leave_type)}</span>`;
    }

    /** "9:00 AM – 12:00 PM", or "Full day". */
    function hours(r) {
        return r.time_from
            ? `<span class="leave-date">${esc(`${clock(r.time_from)} – ${clock(r.time_to)}`)}</span>`
            : '<span class="leave-muted">Full day</span>';
    }

    /** "Thu 8 Oct – Sat 10 Oct 2026", with the day count (or "On leave today") under it. */
    function dates(r, today) {
        const year = r.end_date.slice(0, 4);
        const sameYear = r.start_date.slice(0, 4) === year;
        const range = r.start_date === r.end_date
            ? `${shortDate(r.start_date)} ${year}`
            : `${shortDate(r.start_date)}${sameYear ? '' : ' ' + r.start_date.slice(0, 4)} – ${shortDate(r.end_date)} ${year}`;

        const n = r.days.length || 1;
        // Days are picked one by one, so a range can have gaps in it.
        const span = Math.round((parseDate(r.end_date) - parseDate(r.start_date)) / 864e5) + 1;
        let note = `${n} day${n === 1 ? '' : 's'}${r.days.length && span !== n ? ', not consecutive' : ''}`;
        let cls = 'leave-note';
        if (today && r.start_date <= today && r.end_date >= today) {
            note = 'On leave today';
            cls += ' leave-note-now';
        }
        return `<span class="leave-date">${esc(range)}</span><span class="${cls}">${esc(note)}</span>`;
    }

    /** One line per leave day, lined up with covers(); today's line is highlighted.
     *  A record without per-day rows falls back to the date range. */
    function days(r, today) {
        if (!r.days.length) return dates(r, today);
        return `<ul class="leave-stack">${r.days.map(d => `
            <li class="leave-date${d.date === today ? ' leave-note-now' : ''}">${esc(`${shortDate(d.date)} ${d.date.slice(0, 4)}`)}</li>`).join('')}</ul>`;
    }

    /** One cover badge per leave day (name on hover), in the same order as days(). */
    function covers(r) {
        if (!r.days.length) return '<span class="leave-none">None</span>';
        return `<ul class="leave-stack">${r.days.map(d => `
            <li>${badge(d.cover_code, r.requester_rank, `${shortDate(d.date)}: ${d.cover_name}`)}</li>`).join('')}</ul>`;
    }

    function reason(r) {
        return r.reason ? esc(r.reason) : '<span class="leave-none">Not given</span>';
    }

    return { esc, badge, type, hours, days, dates, covers, reason };
})();
