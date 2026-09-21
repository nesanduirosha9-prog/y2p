// Instructor Leave JS: stat cards, Upcoming Leaves, Leave History (with
// date-range filtering) and the Request Leave modal (multi-date calendar
// picker + partial-day toggle) all render from the `leaveData` JSON payload
// embedded by the view — same JSON-payload + client-render approach as
// instructor/messages.js. DOM-only demo — nothing persists past a reload.
document.addEventListener('DOMContentLoaded', () => {
    const dataEl = document.getElementById('leaveData');
    if (!dataEl) return;

    const payload = JSON.parse(dataEl.textContent);
    const TODAY = payload.today;
    let leaves = payload.records;
    let nextId = Math.max(0, ...leaves.map(l => l.id)) + 1;

    const ANNUAL_ALLOWANCE = 21;

    function isUpcoming(l) { return !l.cancelled && l.dates.some(d => d >= TODAY); }
    function isHistory(l) { return l.cancelled || l.dates.every(d => d < TODAY); }

    function sortedDates(l) { return [...l.dates].sort(); }

    function fmtDates(dates) {
        if (!dates.length) return '—';
        return dates.length === 1 ? dates[0] : `${dates[0]} – ${dates[dates.length - 1]}`;
    }

    function fmtTime(t) {
        const [h, m] = t.split(':').map(Number);
        const suffix = h >= 12 ? 'PM' : 'AM';
        const hour12 = h % 12 || 12;
        return `${hour12}:${String(m).padStart(2, '0')} ${suffix}`;
    }

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function isPartial(l) { return !!(l.timeFrom && l.timeTo); }

    // ---- Stats / Upcoming / History rendering ----

    function renderStats() {
        const upcoming = leaves.filter(isUpcoming);
        const hist = leaves.filter(isHistory);
        const daysUsed = hist.filter(l => !l.cancelled).reduce((sum, l) => sum + l.dates.length, 0);
        const upcomingDays = upcoming.reduce((sum, l) => sum + l.dates.length, 0);
        const totalRequests = leaves.filter(l => !l.cancelled).length;

        document.getElementById('lvStatBalance').textContent = ANNUAL_ALLOWANCE - daysUsed;
        document.getElementById('lvStatUsed').textContent = daysUsed;
        document.getElementById('lvStatUpcoming').textContent = upcomingDays;
        document.getElementById('lvStatTotal').textContent = totalRequests;
    }

    function renderUpcoming() {
        const list = document.getElementById('lvUpcomingList');
        const upcoming = leaves.filter(isUpcoming);

        if (!upcoming.length) {
            list.innerHTML = '<div class="lv-empty-state">No upcoming leave scheduled.</div>';
            return;
        }

        list.innerHTML = upcoming.map(l => {
            const sorted = sortedDates(l);
            const partial = isPartial(l);
            const canCancel = sorted[0] >= TODAY;
            const meta = partial
                ? `${fmtDates(sorted)} &middot; ${fmtTime(l.timeFrom)} &ndash; ${fmtTime(l.timeTo)} &middot; ${esc(l.reason)}`
                : `${fmtDates(sorted)} &middot; ${l.dates.length} day${l.dates.length !== 1 ? 's' : ''} &middot; ${esc(l.reason)}`;

            return `
                <div class="lv-row">
                    <span class="lv-row-dot ${partial ? 'lv-dot-purple' : 'lv-dot-amber'}"></span>
                    <div class="lv-row-info">
                        <div class="lv-row-type">${esc(l.type)}${partial ? ' <span class="lv-badge-partial">PARTIAL DAY</span>' : ''}</div>
                        <div class="lv-row-meta">${meta}</div>
                    </div>
                    ${l.cover ? `<span class="lv-cover-label">Cover: ${esc(l.cover)}</span>` : ''}
                    ${canCancel ? `<button type="button" class="lv-btn-cancel" data-cancel-id="${l.id}">Cancel Leave</button>` : ''}
                </div>
            `;
        }).join('');
    }

    document.getElementById('lvUpcomingList').addEventListener('click', (e) => {
        const btn = e.target.closest('[data-cancel-id]');
        if (!btn) return;
        const id = parseInt(btn.dataset.cancelId, 10);
        leaves = leaves.map(l => l.id === id ? { ...l, cancelled: true } : l);
        renderAll();
        window.ttToast?.('Leave cancelled.', { icon: 'fa-circle-check' });
    });

    function renderHistory() {
        const from = document.getElementById('lvFilterFrom').value;
        const to = document.getElementById('lvFilterTo').value;
        document.getElementById('lvClearFilter').hidden = !(from || to);

        const hist = leaves.filter(isHistory).filter(l => {
            const sorted = sortedDates(l);
            const first = sorted[0], last = sorted[sorted.length - 1];
            if (from && last < from) return false;
            if (to && first > to) return false;
            return true;
        });

        const tbody = document.getElementById('lvHistoryBody');
        if (!hist.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="lv-empty-state">No leave history found.</td></tr>';
            return;
        }

        tbody.innerHTML = hist.map(l => {
            const sorted = sortedDates(l);
            const partial = isPartial(l);
            const daysCell = partial
                ? '<span class="lv-badge-partial">Partial</span>'
                : `<strong style="color:#0f1c2e">${l.dates.length}</strong>`;
            const noteCell = l.cancelled
                ? '<span class="lv-status status-cancelled">Cancelled</span>'
                : '<span class="lv-status status-approved">Completed</span>';

            return `
                <tr>
                    <td class="lv-td-type">${esc(l.type)}</td>
                    <td>
                        ${fmtDates(sorted)}
                        ${partial ? `<div class="lv-td-time">${fmtTime(l.timeFrom)} &ndash; ${fmtTime(l.timeTo)}</div>` : ''}
                    </td>
                    <td>${daysCell}</td>
                    <td>${esc(l.reason)}</td>
                    <td>${esc(l.cover || '—')}</td>
                    <td>${noteCell}</td>
                </tr>
            `;
        }).join('');
    }

    document.getElementById('lvFilterFrom').addEventListener('input', renderHistory);
    document.getElementById('lvFilterTo').addEventListener('input', renderHistory);
    document.getElementById('lvClearFilter').addEventListener('click', () => {
        document.getElementById('lvFilterFrom').value = '';
        document.getElementById('lvFilterTo').value = '';
        renderHistory();
    });

    function renderAll() {
        renderStats();
        renderUpcoming();
        renderHistory();
    }

    // ---- Request Leave panel (docked, not a modal — see lvRequestPanel) ----
    const panel = document.getElementById('lvRequestPanel');
    const openBtn = document.getElementById('requestLeaveBtn');
    const closeBtn = document.getElementById('closeLeaveModal');
    const cancelBtn = document.getElementById('cancelLeaveModal');
    if (!panel || !openBtn) { renderAll(); return; }

    let viewDate = new Date();
    let selectedDates = [];
    let isPartialDay = false;

    function fmt(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function renderCalendar() {
        const label = document.getElementById('lvCalendarLabel');
        const grid = document.getElementById('lvCalendarGrid');
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        label.textContent = `${monthNames[viewDate.getMonth()]} ${viewDate.getFullYear()}`;

        const firstOfMonth = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
        const daysInMonth = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 0).getDate();
        const leadingBlanks = (firstOfMonth.getDay() + 6) % 7; // Monday-first grid

        let html = '';
        for (let i = 0; i < leadingBlanks; i++) html += '<button type="button" class="lv-day lv-day-empty" disabled></button>';
        for (let d = 1; d <= daysInMonth; d++) {
            const dateObj = new Date(viewDate.getFullYear(), viewDate.getMonth(), d);
            const dateStr = fmt(dateObj);
            const classes = ['lv-day'];
            if (selectedDates.includes(dateStr)) classes.push('lv-day-selected');
            if (dateStr === TODAY) classes.push('lv-day-today');
            html += `<button type="button" class="${classes.join(' ')}" data-date="${dateStr}">${d}</button>`;
        }
        grid.innerHTML = html;

        grid.querySelectorAll('.lv-day:not(.lv-day-empty)').forEach(btn => {
            btn.addEventListener('click', () => {
                const dateStr = btn.dataset.date;
                const idx = selectedDates.indexOf(dateStr);
                if (idx > -1) selectedDates.splice(idx, 1); else selectedDates.push(dateStr);
                renderCalendar();
                renderSelectedChips();
                validateForm();
            });
        });
    }

    function renderSelectedChips() {
        const host = document.getElementById('lvSelectedDates');
        const badge = document.getElementById('lvDateCountBadge');
        selectedDates.sort();

        if (badge) {
            if (selectedDates.length > 0) {
                badge.textContent = `${selectedDates.length} ${selectedDates.length === 1 ? 'Day' : 'Days'}`;
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        }

        host.innerHTML = selectedDates.map(d =>
            `<span class="lv-date-chip"><span><i class="fa-regular fa-calendar"></i> ${d}</span> <button type="button" data-date="${d}" title="Remove date"><i class="fa-solid fa-xmark"></i></button></span>`
        ).join('');
        host.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = selectedDates.indexOf(btn.dataset.date);
                if (idx > -1) selectedDates.splice(idx, 1);
                renderCalendar();
                renderSelectedChips();
                validateForm();
            });
        });
    }

    document.getElementById('lvPrevMonth').addEventListener('click', () => {
        viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() - 1, 1);
        renderCalendar();
    });
    document.getElementById('lvNextMonth').addEventListener('click', () => {
        viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 1);
        renderCalendar();
    });
    document.getElementById('lvAddManualDate').addEventListener('click', () => {
        const input = document.getElementById('lvManualDate');
        const val = input.value.trim();
        if (val && !selectedDates.includes(val)) {
            selectedDates.push(val);
            input.value = '';
            renderCalendar();
            renderSelectedChips();
            validateForm();
        }
    });

    function updateTimePreview() {
        const from = document.getElementById('lvTimeFrom').value;
        const to = document.getElementById('lvTimeTo').value;
        if (from && to) {
            document.getElementById('lvTimePreviewText').textContent = `Absent from ${fmtTime(from)} to ${fmtTime(to)}`;
        }
    }
    document.getElementById('lvTimeFrom').addEventListener('input', updateTimePreview);
    document.getElementById('lvTimeTo').addEventListener('input', updateTimePreview);

    function togglePartial() {
        isPartialDay = !isPartialDay;
        document.getElementById('lvPartialTrack').classList.toggle('on', isPartialDay);
        document.getElementById('lvPartialBody').hidden = !isPartialDay;
        document.getElementById('lvFulldayNote').hidden = isPartialDay;
        if (isPartialDay) updateTimePreview();
    }
    document.getElementById('lvPartialToggle').addEventListener('click', togglePartial);

    function validateForm() {
        const ok = document.getElementById('lvType').value &&
            selectedDates.length > 0 &&
            document.getElementById('lvReason').value.trim();
        document.getElementById('submitLeaveRequest').disabled = !ok;
    }
    document.getElementById('lvType').addEventListener('input', validateForm);
    document.getElementById('lvReason').addEventListener('input', validateForm);

    function openPanel() {
        selectedDates = [];
        isPartialDay = false;
        document.getElementById('lvType').value = '';
        document.getElementById('lvReason').value = '';
        document.getElementById('lvCover').value = '';
        document.getElementById('lvManualDate').value = '';
        document.getElementById('lvTimeFrom').value = '08:00';
        document.getElementById('lvTimeTo').value = '10:00';
        document.getElementById('lvPartialTrack').classList.remove('on');
        document.getElementById('lvPartialBody').hidden = true;
        document.getElementById('lvFulldayNote').hidden = false;
        viewDate = new Date();
        renderCalendar();
        renderSelectedChips();
        updateTimePreview();
        validateForm();
        panel.hidden = false;
    }
    function closePanel() { panel.hidden = true; }

    openBtn.addEventListener('click', openPanel);
    closeBtn.addEventListener('click', closePanel);
    cancelBtn.addEventListener('click', closePanel);

    document.getElementById('submitLeaveRequest').addEventListener('click', () => {
        const type = document.getElementById('lvType').value;
        const reason = document.getElementById('lvReason').value.trim();
        if (!type || !selectedDates.length || !reason) return;

        const rec = {
            id: nextId++,
            type,
            dates: [...selectedDates].sort(),
            reason,
            cover: document.getElementById('lvCover').value,
            cancelled: false,
        };
        if (isPartialDay) {
            rec.timeFrom = document.getElementById('lvTimeFrom').value;
            rec.timeTo = document.getElementById('lvTimeTo').value;
        }
        leaves = [rec, ...leaves];
        closePanel();
        renderAll();
        window.ttToast?.('Leave request submitted.');
    });

    renderAll();
});
