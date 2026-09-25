// js/instructor/mini-calendar.js
document.addEventListener('DOMContentLoaded', () => {
    const calendarWrap = document.getElementById('calendarPickerWrap');
    const calendarPopup = document.getElementById('miniCalendarPopup');
    const mcMonthLabel = document.getElementById('mcMonthLabel');
    const mcDaysGrid = document.getElementById('mcDaysGrid');
    const mcPrevMonth = document.getElementById('mcPrevMonth');
    const mcNextMonth = document.getElementById('mcNextMonth');

    if (!calendarWrap || !calendarPopup) return; // guard: panel not on this page

    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];

    // Same day-offset mapping used in timetable.js — kept local so this file has no dependency
    const dayOffset = { mon: 0, tue: 1, wed: 2, thu: 3, fri: 4 };

    let mcViewDate = new Date();
    let selectedDate = new Date();

    calendarWrap.addEventListener('click', (e) => {
        e.stopPropagation();
        calendarPopup.classList.toggle('open');
        if (calendarPopup.classList.contains('open')) {
            mcViewDate = new Date(selectedDate);
            renderCalendar();
        }
    });

    document.addEventListener('click', (e) => {
        if (!calendarPopup.contains(e.target) && !calendarWrap.contains(e.target)) {
            calendarPopup.classList.remove('open');
        }
    });

    mcPrevMonth.addEventListener('click', (e) => {
        e.stopPropagation();
        mcViewDate.setMonth(mcViewDate.getMonth() - 1);
        renderCalendar();
    });

    mcNextMonth.addEventListener('click', (e) => {
        e.stopPropagation();
        mcViewDate.setMonth(mcViewDate.getMonth() + 1);
        renderCalendar();
    });

    function renderCalendar() {
        mcMonthLabel.textContent = `${monthNames[mcViewDate.getMonth()]} ${mcViewDate.getFullYear()}`;
        mcDaysGrid.innerHTML = '';

        const year = mcViewDate.getFullYear();
        const month = mcViewDate.getMonth();
        const firstOfMonth = new Date(year, month, 1);
        const firstWeekday = (firstOfMonth.getDay() + 6) % 7;
        const startDate = new Date(year, month, 1 - firstWeekday);

        for (let i = 0; i < 42; i++) {
            const cellDate = new Date(startDate);
            cellDate.setDate(startDate.getDate() + i);

            const cell = document.createElement('div');
            cell.className = 'mc-day';
            cell.textContent = cellDate.getDate();

            if (cellDate.getMonth() !== month) cell.classList.add('mc-other-month');

            const dow = cellDate.getDay();
            if (dow === 0 || dow === 6) cell.classList.add('mc-weekend');

            if (isSameDate(cellDate, new Date())) cell.classList.add('mc-today');
            if (isSameDate(cellDate, selectedDate)) cell.classList.add('mc-selected');

            cell.addEventListener('click', (e) => {
                e.stopPropagation();
                selectedDate = new Date(cellDate);
                calendarPopup.classList.remove('open');
                onDateSelected(selectedDate);
            });

            mcDaysGrid.appendChild(cell);
        }
    }

    function isSameDate(a, b) {
        return a.getFullYear() === b.getFullYear() &&
            a.getMonth() === b.getMonth() &&
            a.getDate() === b.getDate();
    }

    function onDateSelected(date) {
        const weekDates = getWeekDates(date);

        Object.keys(dayOffset).forEach(dayKey => {
            const headerEl = document.querySelector(`.tt-grid-day-head[data-day-key="${dayKey}"] .tt-day-num`);
            if (headerEl) headerEl.textContent = weekDates[dayKey].getDate();
        });

        const weekPicker = document.getElementById('weekPicker');
        if (weekPicker) weekPicker.value = formatDateInput(date);

        // Let other scripts (timetable.js) react to the new week if needed
        document.dispatchEvent(new CustomEvent('weekChanged', { detail: { weekDates, selectedDate: date } }));
    }

    function getWeekDates(date) {
        const dow = (date.getDay() + 6) % 7;
        const monday = new Date(date);
        monday.setDate(date.getDate() - dow);

        const result = {};
        Object.keys(dayOffset).forEach(key => {
            const d = new Date(monday);
            d.setDate(monday.getDate() + dayOffset[key]);
            result[key] = d;
        });
        return result;
    }

    function formatDateInput(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }
});