// Timetable interactions: lecturer/room filters, and the "Schedule Course" flow
// (select free slots -> confirm -> fill modal -> block appears in the grid).
//
// NOTE (gap): the `timetable_sessions` table and TimetableSessionModel::create()
// both exist server-side, but no route/controller action calls it — "Add to
// Timetable" here only mutates the DOM for this pageview, so a scheduled
// session disappears on reload instead of being saved.
document.addEventListener('DOMContentLoaded', function () {
    const view = document.querySelector('.tt-view');
    if (!view) return;

    const grid = view.querySelector('.tt-grid');
    const scheduleToggleBtn = document.getElementById('scheduleToggleBtn');
    const confirmBar = document.getElementById('confirmBar');
    const slotsCountText = document.getElementById('slotsCountText');
    const slotsDayText = document.getElementById('slotsDayText');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
    const confirmSelectionBtn = document.getElementById('confirmSelectionBtn');
    const legendHint = document.getElementById('legendHint');

    const scheduleModal = document.getElementById('scheduleModal');
    const closeScheduleModal = document.getElementById('closeScheduleModal');
    const modalBackBtn = document.getElementById('modalBackBtn');
    const addToTimetableBtn = document.getElementById('addToTimetableBtn');
    const selectedSlotsRange = document.getElementById('selectedSlotsRange');
    const selectedSlotsTotal = document.getElementById('selectedSlotsTotal');
    const courseModule = document.getElementById('courseModule');
    const lecturerHint = document.getElementById('lecturerHint');
    const venueInput = document.getElementById('venueInput');
    const sessionTypeToggle = document.getElementById('sessionTypeToggle');

    const detailsModal = document.getElementById('detailsModal');
    const closeDetailsModal = document.getElementById('closeDetailsModal');
    const closeDetailsBtn2 = document.getElementById('closeDetailsBtn2');

    const DAY_ORDER = ['mon', 'tue', 'wed', 'thu', 'fri'];
    const DAY_SHORT = { mon: 'Mon', tue: 'Tue', wed: 'Wed', thu: 'Thu', fri: 'Fri' };
    const HOURS = [8, 9, 10, 11, 12, 13, 14, 15, 16];

    function hourLabel(h) {
        const suffix = h < 12 ? 'AM' : 'PM';
        const display = h % 12 === 0 ? 12 : h % 12;
        return display + suffix;
    }

    let selecting = false;
    let selectedCells = []; // { el, dayKey, hour }

    // ---- Selection mode ----
    scheduleToggleBtn.addEventListener('click', function () {
        selecting ? exitSelectionMode() : enterSelectionMode();
    });

    function enterSelectionMode() {
        selecting = true;
        view.classList.add('selecting');
        scheduleToggleBtn.innerHTML = '<i class="fa-solid fa-xmark"></i> Cancel';
        scheduleToggleBtn.classList.add('danger');
        confirmBar.hidden = false;
        legendHint.textContent = 'Click free slots to select them, then confirm below';
    }

    function exitSelectionMode() {
        selecting = false;
        view.classList.remove('selecting');
        scheduleToggleBtn.innerHTML = '<i class="fa-solid fa-plus"></i> Schedule Course';
        scheduleToggleBtn.classList.remove('danger');
        confirmBar.hidden = true;
        legendHint.textContent = 'Click any session block for details';
        clearSelection();
    }

    function clearSelection() {
        selectedCells.forEach(function (c) {
            c.el.classList.remove('selected');
            const badge = c.el.querySelector('.selected-badge');
            if (badge) badge.remove();
        });
        selectedCells = [];
        updateConfirmBar();
    }

    clearSelectionBtn.addEventListener('click', clearSelection);

    function updateConfirmBar() {
        const n = selectedCells.length;
        slotsCountText.textContent = n + (n === 1 ? ' slot selected' : ' slots selected');
        confirmSelectionBtn.disabled = n === 0;
        if (n > 0) {
            slotsDayText.textContent = '— ' + dayFullLabel(selectedCells[0].dayKey);
        } else {
            slotsDayText.textContent = '';
        }
    }

    function dayFullLabel(dayKey) {
        return selectedCells.length ? selectedCells[0].el.dataset.day : dayKey;
    }

    grid.addEventListener('click', function (e) {
        const block = e.target.closest('.tt-block');
        if (block) {
            openDetails(block);
            return;
        }

        if (!selecting) return;
        const cell = e.target.closest('.tt-cell');
        if (!cell || cell.classList.contains('tt-cell-lunch')) return;

        const dayKey = cell.dataset.dayKey;
        const hour = parseInt(cell.dataset.hour, 10);
        const existingIndex = selectedCells.findIndex(function (c) { return c.el === cell; });

        if (existingIndex !== -1) {
            selectedCells.splice(existingIndex, 1);
            cell.classList.remove('selected');
            const badge = cell.querySelector('.selected-badge');
            if (badge) badge.remove();
            updateConfirmBar();
            return;
        }

        // Selecting a cell on a different day starts a fresh selection.
        if (selectedCells.length && selectedCells[0].dayKey !== dayKey) {
            clearSelection();
        }

        selectedCells.push({ el: cell, dayKey: dayKey, hour: hour });
        selectedCells.sort(function (a, b) { return a.hour - b.hour; });
        cell.classList.add('selected');
        const badge = document.createElement('span');
        badge.className = 'selected-badge';
        badge.innerHTML = '<i class="fa-solid fa-check"></i> Selected';
        cell.appendChild(badge);
        updateConfirmBar();
    });

    // ---- Confirm selection -> open scheduling modal ----
    confirmSelectionBtn.addEventListener('click', function () {
        if (!selectedCells.length) return;
        const first = selectedCells[0];
        const hours = selectedCells.map(function (c) { return c.hour; });
        const minHour = Math.min.apply(null, hours);
        const maxHour = Math.max.apply(null, hours);
        const duration = maxHour - minHour + 1;

        selectedSlotsRange.textContent = DAY_SHORT[first.dayKey] + ' ' + hourLabel(minHour) + '–' + hourLabel(maxHour + 1);
        selectedSlotsTotal.textContent = duration + (duration === 1 ? ' hour total' : ' hours total');

        courseModule.value = '';
        lecturerHint.innerHTML = '&nbsp;';
        venueInput.value = '';
        sessionTypeToggle.querySelectorAll('.type-btn').forEach(function (b, i) {
            b.classList.toggle('active', i === 0);
        });

        scheduleModal.hidden = false;
    });

    function hideScheduleModal() {
        scheduleModal.hidden = true;
    }
    closeScheduleModal.addEventListener('click', hideScheduleModal);
    modalBackBtn.addEventListener('click', hideScheduleModal);
    scheduleModal.addEventListener('click', function (e) {
        if (e.target === scheduleModal) hideScheduleModal();
    });

    courseModule.addEventListener('change', function () {
        const opt = courseModule.options[courseModule.selectedIndex];
        const lecturer = opt ? opt.dataset.lecturer : '';
        lecturerHint.textContent = lecturer ? ('Lecturer: ' + lecturer) : '';
        if (!lecturer) lecturerHint.innerHTML = '&nbsp;';
    });

    sessionTypeToggle.addEventListener('click', function (e) {
        const btn = e.target.closest('.type-btn');
        if (!btn) return;
        sessionTypeToggle.querySelectorAll('.type-btn').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
    });

    // ---- Add to timetable: replace selected cells with a new session block ----
    addToTimetableBtn.addEventListener('click', function () {
        const opt = courseModule.options[courseModule.selectedIndex];
        if (!courseModule.value) {
            alert('Please select a course module.');
            return;
        }

        const code = courseModule.value;
        const title = opt.textContent.split('—').slice(1).join('—').trim();
        const type = sessionTypeToggle.querySelector('.type-btn.active').dataset.type;
        const venue = venueInput.value.trim() || 'TBD';

        const first = selectedCells[0];
        const hours = selectedCells.map(function (c) { return c.hour; });
        const minHour = Math.min.apply(null, hours);
        const duration = selectedCells.length;
        const dayIndex = DAY_ORDER.indexOf(first.dayKey);
        const rowStart = HOURS.indexOf(minHour) + 2;

        const block = document.createElement('div');
        block.className = 'tt-block type-' + type;
        block.style.gridColumn = (dayIndex + 2);
        block.style.gridRow = rowStart + ' / span ' + duration;
        block.dataset.code = code;
        block.dataset.title = title;
        block.dataset.location = venue;
        block.dataset.type = type;
        block.dataset.day = first.el.dataset.day;
        block.dataset.start = hourLabel(minHour);
        block.dataset.duration = duration;
        block.innerHTML =
            '<p class="tt-block-code">' + code + '</p>' +
            '<p class="tt-block-title">' + title + '</p>' +
            '<p class="tt-block-loc">' + venue + '</p>';

        selectedCells.forEach(function (c) { c.el.remove(); });
        grid.appendChild(block);

        hideScheduleModal();
        exitSelectionMode();
    });

    // ---- Read-only session details ----
    function openDetails(block) {
        document.getElementById('detailsCode').textContent = block.dataset.code;
        document.getElementById('detailsTitle').textContent = block.dataset.title;
        document.getElementById('detailsLocation').textContent = block.dataset.location;
        document.getElementById('detailsWhen').textContent =
            block.dataset.day + ', ' + block.dataset.start + ' · ' + block.dataset.duration + 'h';
        document.getElementById('detailsType').textContent =
            block.dataset.type.charAt(0).toUpperCase() + block.dataset.type.slice(1);
        detailsModal.hidden = false;
    }

    function hideDetails() {
        detailsModal.hidden = true;
    }
    closeDetailsModal.addEventListener('click', hideDetails);
    closeDetailsBtn2.addEventListener('click', hideDetails);
    detailsModal.addEventListener('click', function (e) {
        if (e.target === detailsModal) hideDetails();
    });

    // ---- Lecturer / room filters ----
    const lecturerFilter = document.getElementById('lecturerFilter');
    const roomFilter = document.getElementById('roomFilter');
    const codeLecturerMap = {};
    courseModule.querySelectorAll('option[data-lecturer]').forEach(function (opt) {
        codeLecturerMap[opt.value] = opt.dataset.lecturer;
    });

    function applyFilters() {
        const lecturer = lecturerFilter.value;
        const room = roomFilter.value;
        grid.querySelectorAll('.tt-block').forEach(function (block) {
            const matchesLecturer = !lecturer || codeLecturerMap[block.dataset.code] === lecturer;
            const matchesRoom = !room || block.dataset.location === room;
            block.style.display = (matchesLecturer && matchesRoom) ? '' : 'none';
        });
    }

    lecturerFilter.addEventListener('change', applyFilters);
    roomFilter.addEventListener('change', applyFilters);
});
