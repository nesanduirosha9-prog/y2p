// Instructor Leave JS: Request Leave modal with an inline calendar picker.
// DOM-only demo — submitting prepends a row to Upcoming Leaves, nothing persists.
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('requestLeaveModal');
    const openBtn = document.getElementById('requestLeaveBtn');
    const closeBtn = document.getElementById('closeLeaveModal');
    const cancelBtn = document.getElementById('cancelLeaveModal');
    if (!modal || !openBtn) return;

    let viewDate = new Date();
    let selectedDates = [];

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
            const isSelected = selectedDates.includes(dateStr);
            html += `<button type="button" class="lv-day${isSelected ? ' lv-day-selected' : ''}" data-date="${dateStr}">${d}</button>`;
        }
        grid.innerHTML = html;

        grid.querySelectorAll('.lv-day:not(.lv-day-empty)').forEach(btn => {
            btn.addEventListener('click', () => {
                const dateStr = btn.dataset.date;
                const idx = selectedDates.indexOf(dateStr);
                if (idx > -1) selectedDates.splice(idx, 1); else selectedDates.push(dateStr);
                renderCalendar();
                renderSelectedChips();
            });
        });
    }

    function renderSelectedChips() {
        const host = document.getElementById('lvSelectedDates');
        selectedDates.sort();
        host.innerHTML = selectedDates.map(d =>
            `<span class="lv-date-chip">${d} <button type="button" data-date="${d}"><i class="fa-solid fa-xmark"></i></button></span>`
        ).join('');
        host.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = selectedDates.indexOf(btn.dataset.date);
                if (idx > -1) selectedDates.splice(idx, 1);
                renderCalendar();
                renderSelectedChips();
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
        }
    });

    function openModal() {
        selectedDates = [];
        document.getElementById('lvType').value = '';
        document.getElementById('lvReason').value = '';
        document.getElementById('lvManualDate').value = '';
        viewDate = new Date();
        renderCalendar();
        renderSelectedChips();
        modal.hidden = false;
    }
    function closeModal() { modal.hidden = true; }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    document.getElementById('submitLeaveRequest').addEventListener('click', () => {
        const type = document.getElementById('lvType').value;
        const reason = document.getElementById('lvReason').value.trim();
        if (!type || selectedDates.length === 0) {
            window.ttToast?.('Please pick a leave type and at least one date.', { type: 'error', icon: 'fa-circle-exclamation' });
            return;
        }

        const dates = [...selectedDates].sort();
        const dateLabel = dates.length === 1 ? dates[0] : `${dates[0]} – ${dates[dates.length - 1]}`;

        const emptyState = document.querySelector('.lv-table-wrapper .lv-empty-state');
        if (emptyState) {
            const table = document.createElement('table');
            table.className = 'lv-table';
            table.innerHTML = `<thead><tr><th>TYPE</th><th>DATES</th><th>REASON</th><th>COVERING STAFF</th><th>STATUS</th></tr></thead><tbody></tbody>`;
            emptyState.replaceWith(table);
        }

        const tbody = document.querySelectorAll('.lv-table-wrapper')[0].querySelector('tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="lv-td-type">${type}</td>
            <td>${dateLabel}</td>
            <td>${reason || '—'}</td>
            <td>—</td>
            <td><span class="lv-status status-pending">Pending</span></td>
        `;
        tbody.prepend(row);

        closeModal();
        window.ttToast?.('Leave request submitted.');
    });
});
