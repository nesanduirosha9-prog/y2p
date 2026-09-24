// period_nav.js — the Week / Month / Semester / Year picker with previous and
// next arrows, shared by the Evaluations page and the lecturers' Evaluation
// History. Both navigate the same teaching calendar
// (WorkloadPrototypeData::calendar()), so "Week 5" means one week everywhere.
//
//   const nav = PeriodNav.create(containerEl, {
//       calendar,                                   // { current, semesters: [...] }
//       units: ['week', 'month', 'semester', 'year'],   // optionally + 'all'
//       onChange(period) { ... },                   // also called once on create
//   });
//
// A period is { unit, key, weeks: [week...], label, sub, isLatest } where each
// week is { start: 'YYYY-MM-DD' (Monday), number, semId, semName, year }.
//
// Only teaching weeks up to the current one exist: nothing can be evaluated in
// a future week, and a month between semesters has no weeks to show, so the
// arrows skip it.
(function () {
    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
        'August', 'September', 'October', 'November', 'December'];
    const SHORT = MONTHS.map(m => m.slice(0, 3));
    const UNIT_LABEL = { week: 'Week', month: 'Month', semester: 'Semester', year: 'Year', all: 'All' };
    const UNIT_NOUN = { week: 'week', month: 'month', semester: 'semester', year: 'year', all: 'period' };

    // Dates are handled in UTC so a timezone can never shift a Monday.
    const parse = iso => { const [y, m, d] = iso.split('-').map(Number); return new Date(Date.UTC(y, m - 1, d)); };
    const fmt = dt => dt.toISOString().slice(0, 10);
    const addDays = (iso, n) => { const d = parse(iso); d.setUTCDate(d.getUTCDate() + n); return fmt(d); };
    const dayMonth = iso => { const d = parse(iso); return d.getUTCDate() + ' ' + SHORT[d.getUTCMonth()]; };

    /** Every teaching week from the first semester up to the current week. */
    function teachingWeeks(calendar) {
        const out = [];
        calendar.semesters.forEach(sem => {
            for (let i = 0; i < sem.weeks; i++) {
                const start = addDays(sem.start, i * 7);
                if (start > calendar.current) break;
                out.push({ start, number: i + 1, semId: sem.id, semName: sem.name, year: sem.year });
            }
        });
        return out;
    }

    /** The Monday of the teaching week an ISO date falls in, or null outside term. */
    function weekOf(calendar, iso) {
        const d = parse(iso);
        const monday = fmt(new Date(d.getTime() - ((d.getUTCDay() + 6) % 7) * 86400000));
        return teachingWeeks(calendar).find(w => w.start === monday) || null;
    }

    function keyFor(unit, w) {
        if (unit === 'week') return w.start;
        if (unit === 'month') return w.start.slice(0, 7);
        if (unit === 'semester') return w.semId;
        if (unit === 'all') return 'all';           // one period: every week
        return w.year;
    }

    function describe(unit, weeks, isLatest) {
        const first = weeks[0];
        const last = weeks[weeks.length - 1];
        const count = weeks.length + ' teaching week' + (weeks.length === 1 ? '' : 's');
        if (unit === 'week') {
            const fri = addDays(first.start, 4);
            return {
                label: 'Week ' + first.number + ' · ' + first.semName,
                sub: dayMonth(first.start) + ' – ' + dayMonth(fri) + ' ' + parse(fri).getUTCFullYear() + (isLatest ? ' · this week' : ''),
            };
        }
        if (unit === 'month') {
            const d = parse(first.start);
            return {
                label: MONTHS[d.getUTCMonth()] + ' ' + d.getUTCFullYear(),
                sub: count + (isLatest ? ' so far' : ''),
            };
        }
        if (unit === 'semester') {
            return {
                label: first.semName + ' · ' + first.year,
                sub: 'Weeks ' + first.number + '–' + last.number + (isLatest ? ' so far' : ''),
            };
        }
        if (unit === 'all') {
            return { label: 'All time', sub: count + ' since ' + dayMonth(first.start) + ' ' + parse(first.start).getUTCFullYear() };
        }
        return { label: 'Academic year ' + first.year, sub: count + (isLatest ? ' so far' : '') };
    }

    function create(root, opts) {
        const calendar = opts.calendar;
        const units = opts.units || ['week', 'month', 'semester', 'year'];
        const weeks = teachingWeeks(calendar);
        const onChange = opts.onChange || function () {};

        let unit = units[0];
        let periods = [];
        let index = 0;

        function build() {
            const byKey = new Map();
            weeks.forEach(w => {
                const k = keyFor(unit, w);
                if (!byKey.has(k)) byKey.set(k, []);
                byKey.get(k).push(w);
            });
            periods = Array.from(byKey, ([key, ws]) => ({ key, weeks: ws }));
        }

        function period() {
            const p = periods[index];
            if (!p) return { unit, key: null, weeks: [], label: 'No teaching weeks', sub: '', isLatest: true };
            const isLatest = index === periods.length - 1;
            return Object.assign({ unit, key: p.key, weeks: p.weeks, isLatest }, describe(unit, p.weeks, isLatest));
        }

        root.classList.add('period-nav');
        root.innerHTML = `
            <div class="seg period-units" role="group" aria-label="Period">
                ${units.map(u => `<button type="button" class="seg-btn" data-unit="${u}">${UNIT_LABEL[u]}</button>`).join('')}
            </div>
            <div class="period-step">
                <button type="button" class="period-arrow" data-step="-1"><i class="fa-solid fa-chevron-left"></i></button>
                <div class="period-label" aria-live="polite"><strong></strong><span></span></div>
                <button type="button" class="period-arrow" data-step="1"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
            <button type="button" class="period-today" hidden><i class="fa-solid fa-rotate-left"></i> <span></span></button>`;

        const els = {
            label: root.querySelector('.period-label strong'),
            sub: root.querySelector('.period-label span'),
            prev: root.querySelector('[data-step="-1"]'),
            next: root.querySelector('[data-step="1"]'),
            today: root.querySelector('.period-today'),
        };

        function paint() {
            const p = period();
            root.querySelectorAll('[data-unit]').forEach(b => b.classList.toggle('active', b.dataset.unit === unit));
            els.label.textContent = p.label;
            els.sub.textContent = p.sub;
            els.prev.disabled = index <= 0;
            els.next.disabled = index >= periods.length - 1;
            const noun = UNIT_NOUN[unit];
            els.prev.setAttribute('aria-label', 'Previous ' + noun);
            els.prev.title = 'Previous ' + noun;
            els.next.setAttribute('aria-label', 'Next ' + noun);
            els.next.title = 'Next ' + noun;
            els.today.hidden = p.isLatest;
            els.today.querySelector('span').textContent = unit === 'week' ? 'Back to this week' : 'Back to this ' + noun;
            onChange(p);
        }

        function setUnit(next) {
            // Keep looking at the same stretch of time: pick the new period
            // that holds the last week of the one on screen.
            const anchor = (periods[index] && periods[index].weeks.slice(-1)[0]) || weeks[weeks.length - 1];
            unit = next;
            build();
            const k = anchor ? keyFor(unit, anchor) : null;
            index = Math.max(0, periods.findIndex(p => p.key === k));
            paint();
        }

        root.addEventListener('click', e => {
            const u = e.target.closest('[data-unit]');
            if (u) { if (u.dataset.unit !== unit) setUnit(u.dataset.unit); return; }
            const step = e.target.closest('[data-step]');
            if (step && !step.disabled) {
                index = Math.min(periods.length - 1, Math.max(0, index + Number(step.dataset.step)));
                paint();
                return;
            }
            if (e.target.closest('.period-today')) { index = periods.length - 1; paint(); }
        });

        build();
        index = periods.length - 1;
        paint();

        return { period, refresh: paint };
    }

    window.PeriodNav = { create, weekOf, teachingWeeks };
})();
