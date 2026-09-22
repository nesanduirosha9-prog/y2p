// Timetable interactions: lecturer/room filters, interactive slot side-panel
// (view, edit, save, delete), publication flow, and past years archive dropdown.

document.addEventListener('DOMContentLoaded', function () {
    const view = document.querySelector('.tt-view');
    if (!view) return;

    const dept = view.dataset.dept || 'cs';
    const sem = parseInt(view.dataset.sem || '1', 10);
    const year = parseInt(view.dataset.year || '1', 10);

    const grid = document.getElementById('ttGrid') || view.querySelector('.tt-grid');
    const scheduleToggleBtn = document.getElementById('scheduleToggleBtn');
    const confirmBar = document.getElementById('confirmBar');
    const slotsCountText = document.getElementById('slotsCountText');
    const slotsDayText = document.getElementById('slotsDayText');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
    const confirmSelectionBtn = document.getElementById('confirmSelectionBtn');
    const legendHint = document.getElementById('legendHint');

    // Header elements
    const academicYearSelect = document.getElementById('academicYearSelect');
    const archiveNotice = document.getElementById('archiveNotice');
    const archiveYearLabel = document.getElementById('archiveYearLabel');
    const returnToCurrentYearBtn = document.getElementById('returnToCurrentYearBtn');
    const publishTimetableBtn = document.getElementById('publishTimetableBtn');
    const pubBadge = document.getElementById('pubBadge');

    // Publish Modal
    const publishModal = document.getElementById('publishModal');
    const closePublishModal = document.getElementById('closePublishModal');
    const cancelPublishBtn = document.getElementById('cancelPublishBtn');
    const confirmPublishBtn = document.getElementById('confirmPublishBtn');

    // Side Panel elements
    const sidePanel = document.getElementById('ttSidePanel');
    const tspTitle = document.getElementById('tspTitle');
    const tspSubtitle = document.getElementById('tspSubtitle');
    const tspClose = document.getElementById('tspClose');
    const tspBody = document.getElementById('tspBody');
    const tspFooter = document.getElementById('tspFooter');

    // Schedule Modal (for multi-slot selection)
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

    // Toast
    const ttToast = document.getElementById('ttToast');
    const toastMsg = document.getElementById('toastMsg');
    const toastIcon = document.getElementById('toastIcon');

    // Filters
    const lecturerFilter = document.getElementById('lecturerFilter');
    const roomFilter = document.getElementById('roomFilter');

    const DAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri'];
    const DAY_LABELS = { mon: 'Monday', tue: 'Tuesday', wed: 'Wednesday', thu: 'Thursday', fri: 'Friday' };
    const DAY_SHORT = { mon: 'Mon', tue: 'Tue', wed: 'Wed', thu: 'Thu', fri: 'Fri' };
    const HOURS = [8, 9, 10, 11, 12, 13, 14, 15, 16];

    const coursesMap = window.__ttCourses || {};
    const roomsList = window.__ttRooms || [];

    let isArchive = false;
    let selecting = false;
    let selectedCells = []; // { el, dayKey, hour }
    let toastTimeout = null;

    // Save initial grid HTML to restore when returning from archive
    let currentYearGridHtml = grid.innerHTML;

    function hourLabel(h) {
        const suffix = h < 12 ? 'AM' : 'PM';
        const display = h % 12 === 0 ? 12 : h % 12;
        return display + ' ' + suffix;
    }

    function timeRange(startHour, duration) {
        const s = parseInt(startHour, 10);
        const d = parseInt(duration || '1', 10);
        return hourLabel(s) + ' – ' + hourLabel(s + d);
    }

    function showToast(message, isSuccess = true) {
        if (toastTimeout) clearTimeout(toastTimeout);
        toastMsg.textContent = message;
        toastIcon.className = isSuccess ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation';
        toastIcon.style.color = isSuccess ? '#10b981' : '#f59e0b';
        ttToast.hidden = false;
        toastTimeout = setTimeout(() => {
            ttToast.hidden = true;
        }, 3500);
    }

    function setDraftStatus() {
        if (isArchive) return;
        pubBadge.className = 'pub-badge draft';
        pubBadge.innerHTML = '<i class="fa-solid fa-pen-ruler"></i> Unpublished Changes';
    }

    // ------------------------------------------------------------------
    // Side Panel Mechanics
    // ------------------------------------------------------------------
    function openPanel(title, subtitle, bodyHtml, footerHtml) {
        tspTitle.textContent = title;
        tspSubtitle.textContent = subtitle || '';
        tspBody.innerHTML = bodyHtml;
        tspFooter.innerHTML = footerHtml || '';
        sidePanel.hidden = false;
    }

    function closePanel() {
        sidePanel.hidden = true;
    }

    if (tspClose) {
        tspClose.addEventListener('click', closePanel);
    }

    function field(label, value) {
        return `<div><p class="tsp-field-label">${label}</p><p class="tsp-field-value">${value}</p></div>`;
    }

    // ------------------------------------------------------------------
    // Session Details: View Mode
    // ------------------------------------------------------------------
    function openSessionDetails(block) {
        const d = block.dataset;
        const startHour = parseInt(d.startHour || '8', 10);
        const duration = parseInt(d.duration || '1', 10);
        const courseTitle = d.title || (coursesMap[d.code] ? coursesMap[d.code].title : 'Course Module');
        const lecturer = d.lecturer || (coursesMap[d.code] ? coursesMap[d.code].lecturer : 'TBA');
        const venue = d.location || 'TBA';
        const type = d.type || 'lecture';
        const dayName = d.day || DAY_LABELS[d.dayKey] || 'Monday';

        const body = `
            <span class="tsp-type-badge type-${type}">${type}</span>
            ${field('Course Code', d.code)}
            ${field('Course Name', courseTitle)}
            ${field('Lecturer', lecturer || 'TBA')}
            ${field('Venue / Lecture Hall', venue)}
            ${field('Day', dayName)}
            ${field('Time', timeRange(startHour, duration))}
            ${field('Duration', duration + (duration === 1 ? ' hour' : ' hours'))}
            ${field('Batch', `Year ${year} ${dept.toUpperCase()} · Sem ${sem}`)}
        `;

        let footer = '';
        if (isArchive) {
            footer = `
                <div class="tsp-readonly-badge">
                    <i class="fa-solid fa-lock"></i> Past Year Timetable (Read-Only)
                </div>
            `;
        } else {
            footer = `
                <div class="btn-row">
                    <button type="button" class="btn-outline" id="tspEditBtn">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </button>
                    <button type="button" class="btn-danger" id="tspDeleteBtn">
                        <i class="fa-solid fa-trash-can"></i> Delete
                    </button>
                </div>
            `;
        }

        openPanel(d.code, courseTitle, body, footer);

        if (!isArchive) {
            document.getElementById('tspEditBtn').addEventListener('click', () => {
                openEditSessionForm(block);
            });
            document.getElementById('tspDeleteBtn').addEventListener('click', () => {
                promptDeleteSession(block);
            });
        }
    }

    // ------------------------------------------------------------------
    // Session Details: Edit Mode
    // ------------------------------------------------------------------
    function openEditSessionForm(block) {
        const d = block.dataset;
        const currentCode = d.code;
        const currentType = d.type || 'lecture';
        const currentLocation = d.location || '';
        const currentDayKey = d.dayKey || 'mon';
        const currentStartHour = parseInt(d.startHour || '8', 10);
        const currentDuration = parseInt(d.duration || '1', 10);

        let courseOptions = '';
        Object.keys(coursesMap).forEach(code => {
            const c = coursesMap[code];
            const sel = code === currentCode ? 'selected' : '';
            courseOptions += `<option value="${code}" ${sel}>${code} — ${c.title}</option>`;
        });

        let roomOptions = '';
        roomsList.forEach(r => {
            const sel = r.code === currentLocation ? 'selected' : '';
            roomOptions += `<option value="${r.code}" ${sel}>${r.code} (${r.type || 'Room'})</option>`;
        });

        let dayOptions = '';
        DAY_KEYS.forEach(k => {
            const sel = k === currentDayKey ? 'selected' : '';
            dayOptions += `<option value="${k}" ${sel}>${DAY_LABELS[k]}</option>`;
        });

        let hourOptions = '';
        HOURS.forEach(h => {
            if (h === 12) return; // lunch
            const sel = h === currentStartHour ? 'selected' : '';
            hourOptions += `<option value="${h}" ${sel}>${hourLabel(h)}</option>`;
        });

        let durationOptions = '';
        [1, 2, 3].forEach(dur => {
            const sel = dur === currentDuration ? 'selected' : '';
            durationOptions += `<option value="${dur}" ${sel}>${dur} ${dur === 1 ? 'hour' : 'hours'}</option>`;
        });

        const body = `
            <div>
                <p class="tsp-field-label">Course Module</p>
                <select class="tsp-select" id="editCourseCode">${courseOptions}</select>
            </div>
            <div>
                <p class="tsp-field-label">Session Type</p>
                <select class="tsp-select" id="editSessionType">
                    <option value="lecture" ${currentType === 'lecture' ? 'selected' : ''}>Lecture</option>
                    <option value="tutorial" ${currentType === 'tutorial' ? 'selected' : ''}>Tutorial</option>
                    <option value="lab" ${currentType === 'lab' ? 'selected' : ''}>Lab</option>
                    <option value="practical" ${currentType === 'practical' ? 'selected' : ''}>Practical</option>
                </select>
            </div>
            <div>
                <p class="tsp-field-label">Venue / Room</p>
                <select class="tsp-select" id="editVenue">${roomOptions}</select>
            </div>
            <div>
                <p class="tsp-field-label">Day</p>
                <select class="tsp-select" id="editDay">${dayOptions}</select>
            </div>
            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <p class="tsp-field-label">Start Time</p>
                    <select class="tsp-select" id="editStartHour">${hourOptions}</select>
                </div>
                <div style="flex: 1;">
                    <p class="tsp-field-label">Duration</p>
                    <select class="tsp-select" id="editDuration">${durationOptions}</select>
                </div>
            </div>
        `;

        const footer = `
            <div class="btn-row">
                <button type="button" class="btn-outline" id="cancelEditBtn">Cancel</button>
                <button type="button" class="btn-primary-sm" id="saveEditBtn">
                    <i class="fa-solid fa-check"></i> Save
                </button>
            </div>
        `;

        openPanel('Edit Session', `${d.code} · ${timeRange(currentStartHour, currentDuration)}`, body, footer);

        document.getElementById('cancelEditBtn').addEventListener('click', () => {
            openSessionDetails(block);
        });

        document.getElementById('saveEditBtn').addEventListener('click', () => {
            const newCode = document.getElementById('editCourseCode').value;
            const newType = document.getElementById('editSessionType').value;
            const newVenue = document.getElementById('editVenue').value;
            const newDayKey = document.getElementById('editDay').value;
            const newStartHour = parseInt(document.getElementById('editStartHour').value, 10);
            const newDuration = parseInt(document.getElementById('editDuration').value, 10);

            const courseInfo = coursesMap[newCode] || { title: 'Course Module', lecturer: 'TBA' };

            // Update DOM block attributes
            block.className = `tt-block type-${newType}`;
            block.dataset.code = newCode;
            block.dataset.title = courseInfo.title;
            block.dataset.location = newVenue;
            block.dataset.type = newType;
            block.dataset.day = DAY_LABELS[newDayKey];
            block.dataset.dayKey = newDayKey;
            block.dataset.start = hourLabel(newStartHour);
            block.dataset.startHour = newStartHour;
            block.dataset.duration = newDuration;
            block.dataset.lecturer = courseInfo.lecturer || 'TBA';

            // Calculate grid positioning
            const colIndex = DAY_KEYS.indexOf(newDayKey) + 2;
            const rowIndex = HOURS.indexOf(newStartHour) + 2;
            block.style.gridColumn = colIndex;
            block.style.gridRow = `${rowIndex} / span ${newDuration}`;

            block.innerHTML = `
                <p class="tt-block-code">${newCode}</p>
                <p class="tt-block-title">${courseInfo.title}</p>
                <p class="tt-block-loc">${newVenue}</p>
            `;

            setDraftStatus();
            showToast(`Session ${newCode} updated successfully.`);
            openSessionDetails(block);
        });
    }

    // ------------------------------------------------------------------
    // Session Details: Delete Confirmation
    // ------------------------------------------------------------------
    function promptDeleteSession(block) {
        const d = block.dataset;
        tspFooter.innerHTML = `
            <p style="font-size: 12px; color: #dc2626; font-weight: 600; text-align: center; margin: 0 0 6px;">
                Delete this session?
            </p>
            <div class="btn-row">
                <button type="button" class="btn-outline" id="tspCancelDelBtn">Cancel</button>
                <button type="button" class="btn-danger-confirm" id="tspConfirmDelBtn">
                    <i class="fa-solid fa-trash-can"></i> Delete
                </button>
            </div>
        `;

        document.getElementById('tspCancelDelBtn').addEventListener('click', () => {
            openSessionDetails(block);
        });

        document.getElementById('tspConfirmDelBtn').addEventListener('click', () => {
            const dayKey = d.dayKey;
            const startHour = parseInt(d.startHour, 10);
            const duration = parseInt(d.duration, 10);
            const code = d.code;

            // Remove block
            block.remove();

            // Restore empty cells in that time slot
            const colIndex = DAY_KEYS.indexOf(dayKey) + 2;
            for (let i = 0; i < duration; i++) {
                const h = startHour + i;
                const rowIndex = HOURS.indexOf(h) + 2;
                const isLunch = h === 12;

                const cell = document.createElement('div');
                cell.className = `tt-cell ${isLunch ? 'tt-cell-lunch' : ''}`;
                cell.style.gridColumn = colIndex;
                cell.style.gridRow = rowIndex;
                cell.dataset.day = DAY_LABELS[dayKey];
                cell.dataset.dayKey = dayKey;
                cell.dataset.hour = h;
                cell.dataset.hourLabel = hourLabel(h);
                if (isLunch) {
                    cell.dataset.lunch = '1';
                    if (dayKey === 'wed') cell.innerHTML = '<span class="lunch-label">Lunch Break</span>';
                }
                grid.appendChild(cell);
            }

            closePanel();
            setDraftStatus();
            showToast(`Session ${code} deleted.`);
        });
    }

    // ------------------------------------------------------------------
    // Empty Cell: Quick Schedule via Side Panel
    // ------------------------------------------------------------------
    function openEmptySlotSchedule(cell) {
        if (isArchive || selecting) return;

        const dayKey = cell.dataset.dayKey;
        const hour = parseInt(cell.dataset.hour, 10);
        const dayName = cell.dataset.day || DAY_LABELS[dayKey];

        let courseOptions = '';
        Object.keys(coursesMap).forEach(code => {
            const c = coursesMap[code];
            courseOptions += `<option value="${code}">${code} — ${c.title}</option>`;
        });

        let roomOptions = '';
        roomsList.forEach(r => {
            roomOptions += `<option value="${r.code}">${r.code} (${r.type || 'Room'})</option>`;
        });

        const body = `
            <div class="selected-slots-card" style="margin-bottom: 4px;">
                <div class="selected-slots-icon"><i class="fa-regular fa-clock"></i></div>
                <div>
                    <p class="field-label">Target Time</p>
                    <p class="selected-slots-range">${dayName}, ${hourLabel(hour)}</p>
                </div>
            </div>
            <div>
                <p class="tsp-field-label">Course Module</p>
                <select class="tsp-select" id="newCourseCode">
                    <option value="">Select a course module...</option>
                    ${courseOptions}
                </select>
            </div>
            <div>
                <p class="tsp-field-label">Session Type</p>
                <select class="tsp-select" id="newSessionType">
                    <option value="lecture">Lecture</option>
                    <option value="tutorial">Tutorial</option>
                    <option value="lab">Lab</option>
                    <option value="practical">Practical</option>
                </select>
            </div>
            <div>
                <p class="tsp-field-label">Venue / Room</p>
                <select class="tsp-select" id="newVenue">
                    <option value="">Select a hall / lab...</option>
                    ${roomOptions}
                </select>
            </div>
            <div>
                <p class="tsp-field-label">Duration</p>
                <select class="tsp-select" id="newDuration">
                    <option value="1">1 hour</option>
                    <option value="2">2 hours</option>
                </select>
            </div>
        `;

        const footer = `
            <div class="btn-row">
                <button type="button" class="btn-outline" id="cancelNewBtn">Cancel</button>
                <button type="button" class="btn-primary-sm" id="saveNewBtn">
                    <i class="fa-solid fa-plus"></i> Save
                </button>
            </div>
        `;

        openPanel('Schedule Session', `${dayName} at ${hourLabel(hour)}`, body, footer);

        document.getElementById('cancelNewBtn').addEventListener('click', closePanel);

        document.getElementById('saveNewBtn').addEventListener('click', () => {
            const code = document.getElementById('newCourseCode').value;
            const type = document.getElementById('newSessionType').value;
            const venue = document.getElementById('newVenue').value || 'TBA';
            const duration = parseInt(document.getElementById('newDuration').value, 10);

            if (!code) {
                alert('Please select a course module.');
                return;
            }

            const courseInfo = coursesMap[code] || { title: 'Course Module', lecturer: 'TBA' };
            const colIndex = DAY_KEYS.indexOf(dayKey) + 2;
            const rowIndex = HOURS.indexOf(hour) + 2;

            // Remove occupied cell(s)
            cell.remove();
            if (duration > 1) {
                const nextCell = grid.querySelector(`.tt-cell[data-day-key="${dayKey}"][data-hour="${hour + 1}"]`);
                if (nextCell) nextCell.remove();
            }

            // Create block
            const block = document.createElement('div');
            block.className = `tt-block type-${type}`;
            block.style.gridColumn = colIndex;
            block.style.gridRow = `${rowIndex} / span ${duration}`;
            block.dataset.code = code;
            block.dataset.title = courseInfo.title;
            block.dataset.location = venue;
            block.dataset.type = type;
            block.dataset.day = dayName;
            block.dataset.dayKey = dayKey;
            block.dataset.start = hourLabel(hour);
            block.dataset.startHour = hour;
            block.dataset.duration = duration;
            block.dataset.lecturer = courseInfo.lecturer || 'TBA';

            block.innerHTML = `
                <p class="tt-block-code">${code}</p>
                <p class="tt-block-title">${courseInfo.title}</p>
                <p class="tt-block-loc">${venue}</p>
            `;

            grid.appendChild(block);
            setDraftStatus();
            showToast(`Added ${code} to timetable.`);
            openSessionDetails(block);
        });
    }

    // ------------------------------------------------------------------
    // Grid Click Listener: Opens Session Details or Quick Schedule
    // ------------------------------------------------------------------
    grid.addEventListener('click', function (e) {
        const block = e.target.closest('.tt-block');
        if (block) {
            openSessionDetails(block);
            return;
        }

        const cell = e.target.closest('.tt-cell');
        if (!cell || cell.classList.contains('tt-cell-lunch')) return;

        if (isArchive) return;

        if (selecting) {
            // Handled by selection mode
            handleCellSelection(cell);
            return;
        }

        // Open quick schedule panel for this slot
        openEmptySlotSchedule(cell);
    });

    // ------------------------------------------------------------------
    // Multi-slot Selection Mode (via "Schedule Course" button)
    // ------------------------------------------------------------------
    scheduleToggleBtn.addEventListener('click', function () {
        if (isArchive) return;
        selecting ? exitSelectionMode() : enterSelectionMode();
    });

    function enterSelectionMode() {
        selecting = true;
        view.classList.add('selecting');
        scheduleToggleBtn.innerHTML = '<i class="fa-solid fa-xmark"></i> Cancel';
        scheduleToggleBtn.classList.add('danger');
        confirmBar.hidden = false;
        legendHint.textContent = 'Click free slots to select them, then confirm below';
        closePanel();
    }

    function exitSelectionMode() {
        selecting = false;
        view.classList.remove('selecting');
        scheduleToggleBtn.innerHTML = '<i class="fa-solid fa-plus"></i> Schedule Course';
        scheduleToggleBtn.classList.remove('danger');
        confirmBar.hidden = true;
        legendHint.textContent = 'Click any slot or session block for details & actions';
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

    if (clearSelectionBtn) clearSelectionBtn.addEventListener('click', clearSelection);

    function updateConfirmBar() {
        const n = selectedCells.length;
        slotsCountText.textContent = n + (n === 1 ? ' slot selected' : ' slots selected');
        confirmSelectionBtn.disabled = n === 0;
        if (n > 0) {
            slotsDayText.textContent = '— ' + selectedCells[0].el.dataset.day;
        } else {
            slotsDayText.textContent = '';
        }
    }

    function handleCellSelection(cell) {
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
    }

    if (confirmSelectionBtn) {
        confirmSelectionBtn.addEventListener('click', function () {
            if (!selectedCells.length) return;
            const first = selectedCells[0];
            const hoursArr = selectedCells.map(function (c) { return c.hour; });
            const minHour = Math.min.apply(null, hoursArr);
            const maxHour = Math.max.apply(null, hoursArr);
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
    }

    function hideScheduleModal() {
        scheduleModal.hidden = true;
    }
    if (closeScheduleModal) closeScheduleModal.addEventListener('click', hideScheduleModal);
    if (modalBackBtn) modalBackBtn.addEventListener('click', hideScheduleModal);

    if (courseModule) {
        courseModule.addEventListener('change', function () {
            const opt = courseModule.options[courseModule.selectedIndex];
            const lecturer = opt ? opt.dataset.lecturer : '';
            lecturerHint.textContent = lecturer ? ('Lecturer: ' + lecturer) : '';
            if (!lecturer) lecturerHint.innerHTML = '&nbsp;';
        });
    }

    if (sessionTypeToggle) {
        sessionTypeToggle.addEventListener('click', function (e) {
            const btn = e.target.closest('.type-btn');
            if (!btn) return;
            sessionTypeToggle.querySelectorAll('.type-btn').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
        });
    }

    if (addToTimetableBtn) {
        addToTimetableBtn.addEventListener('click', function () {
            const opt = courseModule.options[courseModule.selectedIndex];
            if (!courseModule.value) {
                alert('Please select a course module.');
                return;
            }

            const code = courseModule.value;
            const title = opt.textContent.split('—').slice(1).join('—').trim();
            const type = sessionTypeToggle.querySelector('.type-btn.active').dataset.type;
            const venue = venueInput.value.trim() || 'TBA';
            const lecturer = opt.dataset.lecturer || 'TBA';

            const first = selectedCells[0];
            const hoursArr = selectedCells.map(function (c) { return c.hour; });
            const minHour = Math.min.apply(null, hoursArr);
            const duration = selectedCells.length;
            const dayIndex = DAY_KEYS.indexOf(first.dayKey);
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
            block.dataset.dayKey = first.dayKey;
            block.dataset.start = hourLabel(minHour);
            block.dataset.startHour = minHour;
            block.dataset.duration = duration;
            block.dataset.lecturer = lecturer;
            block.innerHTML =
                '<p class="tt-block-code">' + code + '</p>' +
                '<p class="tt-block-title">' + title + '</p>' +
                '<p class="tt-block-loc">' + venue + '</p>';

            selectedCells.forEach(function (c) { c.el.remove(); });
            grid.appendChild(block);

            hideScheduleModal();
            exitSelectionMode();
            setDraftStatus();
            showToast(`Added ${code} to timetable.`);
            openSessionDetails(block);
        });
    }

    // ------------------------------------------------------------------
    // Publish Flow
    // ------------------------------------------------------------------
    if (publishTimetableBtn) {
        publishTimetableBtn.addEventListener('click', function () {
            if (isArchive) return;
            publishModal.hidden = false;
        });
    }

    if (closePublishModal) closePublishModal.addEventListener('click', () => { publishModal.hidden = true; });
    if (cancelPublishBtn) cancelPublishBtn.addEventListener('click', () => { publishModal.hidden = true; });

    if (confirmPublishBtn) {
        confirmPublishBtn.addEventListener('click', function () {
            // Collect all current session blocks from grid
            const currentSessions = [];
            grid.querySelectorAll('.tt-block').forEach(b => {
                currentSessions.push({
                    code: b.dataset.code,
                    title: b.dataset.title,
                    location: b.dataset.location,
                    type: b.dataset.type,
                    day: b.dataset.dayKey,
                    start: parseInt(b.dataset.startHour, 10),
                    duration: parseInt(b.dataset.duration, 10),
                    lecturer: b.dataset.lecturer || 'TBA'
                });
            });

            // Persist to localStorage for system-wide sync (including student timetable view)
            const storageKey = `staffsync_published_${dept}_${sem}_${year}`;
            try {
                localStorage.setItem(storageKey, JSON.stringify(currentSessions));
                localStorage.setItem(`staffsync_published_time_${dept}_${sem}_${year}`, new Date().toLocaleTimeString());
            } catch (err) {
                console.warn('LocalStorage save failed:', err);
            }

            // Update UI status badge
            pubBadge.className = 'pub-badge published';
            pubBadge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Published (Just now)';

            publishModal.hidden = true;
            showToast('Timetable published! Student timetable has been updated across the system.');
        });
    }

    // ------------------------------------------------------------------
    // Past Years Datasets & Archive Handling
    // ------------------------------------------------------------------
    const pastYearsData = {
        '2023/2024': {
            'cs': [
                { code: 'CS1101', title: 'Intro to Programming', location: 'LAB-A201', type: 'lab', day: 'mon', start: 8, duration: 2, lecturer: 'Prof. David Nkrumah' },
                { code: 'CS1102', title: 'Discrete Mathematics', location: 'LT-301', type: 'lecture', day: 'tue', start: 10, duration: 2, lecturer: 'Dr. Sarah Chen' },
                { code: 'CS1103', title: 'Computer Systems', location: 'ROOM-B105', type: 'tutorial', day: 'wed', start: 13, duration: 2, lecturer: 'Dr. Kofi Anning' },
                { code: 'CS1104', title: 'Calculus', location: 'LT-201', type: 'lecture', day: 'thu', start: 9, duration: 2, lecturer: 'Dr. Linda Osei' },
                { code: 'CS1101', title: 'Programming Practical', location: 'LAB-B101', type: 'practical', day: 'fri', start: 14, duration: 2, lecturer: 'Prof. David Nkrumah' }
            ],
            'is': [
                { code: 'IS1101', title: 'Foundations of IS', location: 'LT-201', type: 'lecture', day: 'mon', start: 9, duration: 2, lecturer: 'Dr. Kofi Anning' },
                { code: 'IS1102', title: 'Business Processes', location: 'LT-401', type: 'lecture', day: 'tue', start: 13, duration: 2, lecturer: 'Dr. Linda Osei' },
                { code: 'IS1103', title: 'Information Systems Lab', location: 'LAB-B101', type: 'lab', day: 'fri', start: 10, duration: 2, lecturer: 'Dr. Kofi Anning' }
            ]
        },
        '2022/2023': {
            'cs': [
                { code: 'CS1101', title: 'Intro to Programming', location: 'LAB-A201', type: 'lab', day: 'mon', start: 10, duration: 2, lecturer: 'Prof. David Nkrumah' },
                { code: 'CS1102', title: 'Discrete Mathematics', location: 'LT-401', type: 'lecture', day: 'wed', start: 8, duration: 2, lecturer: 'Dr. Sarah Chen' },
                { code: 'CS1103', title: 'Computer Systems', location: 'LT-302', type: 'lecture', day: 'thu', start: 13, duration: 2, lecturer: 'Dr. Kofi Anning' },
                { code: 'CS1104', title: 'Calculus', location: 'ROOM-D201', type: 'tutorial', day: 'fri', start: 9, duration: 2, lecturer: 'Dr. Linda Osei' }
            ],
            'is': [
                { code: 'IS1101', title: 'Foundations of IS', location: 'LT-301', type: 'lecture', day: 'mon', start: 8, duration: 2, lecturer: 'Dr. Kofi Anning' },
                { code: 'IS1102', title: 'Business Processes', location: 'LT-201', type: 'lecture', day: 'wed', start: 14, duration: 2, lecturer: 'Dr. Linda Osei' },
                { code: 'IS1103', title: 'Information Systems Lab', location: 'LAB-A201', type: 'lab', day: 'thu', start: 10, duration: 2, lecturer: 'Dr. Kofi Anning' }
            ]
        }
    };

    function renderPastYearGrid(yearKey) {
        const yearData = (pastYearsData[yearKey] && pastYearsData[yearKey][dept]) || [];
        const occupied = {};
        yearData.forEach(s => {
            for (let i = 0; i < s.duration; i++) {
                occupied[s.day] = occupied[s.day] || {};
                occupied[s.day][s.start + i] = (i === 0 ? s : 'busy');
            }
        });

        let html = '<div class="tt-grid-corner" style="grid-column: 1; grid-row: 1;"></div>';
        DAY_KEYS.forEach((k, colIndex) => {
            html += `<div class="tt-grid-day-head" style="grid-column: ${colIndex + 2}; grid-row: 1;">${DAY_LABELS[k]}</div>`;
        });

        HOURS.forEach((h, rowIndex) => {
            html += `<div class="tt-grid-time" style="grid-column: 1; grid-row: ${rowIndex + 2};">${hourLabel(h)}</div>`;
            DAY_KEYS.forEach((dayKey, colIndex) => {
                const cell = (occupied[dayKey] || {})[h];
                const isLunch = h === 12;
                const col = colIndex + 2;
                const row = rowIndex + 2;

                if (cell === 'busy') return;

                if (cell) {
                    html += `
                        <div class="tt-block type-${cell.type}"
                             style="grid-column:${col}; grid-row:${row} / span ${cell.duration};"
                             data-code="${cell.code}"
                             data-title="${cell.title}"
                             data-location="${cell.location}"
                             data-type="${cell.type}"
                             data-day="${DAY_LABELS[dayKey]}"
                             data-day-key="${dayKey}"
                             data-start="${hourLabel(h)}"
                             data-start-hour="${h}"
                             data-duration="${cell.duration}"
                             data-lecturer="${cell.lecturer || 'TBA'}">
                            <p class="tt-block-code">${cell.code}</p>
                            <p class="tt-block-title">${cell.title}</p>
                            <p class="tt-block-loc">${cell.location}</p>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="tt-cell ${isLunch ? 'tt-cell-lunch' : ''}" style="grid-column:${col}; grid-row:${row}; cursor: default;"
                             data-day="${DAY_LABELS[dayKey]}" data-day-key="${dayKey}" data-hour="${h}">
                            ${isLunch && dayKey === 'wed' ? '<span class="lunch-label">Lunch Break</span>' : ''}
                        </div>
                    `;
                }
            });
        });

        grid.innerHTML = html;
    }

    if (academicYearSelect) {
        academicYearSelect.addEventListener('change', function () {
            const selectedYear = academicYearSelect.value;
            closePanel();
            if (selecting) exitSelectionMode();

            if (selectedYear !== '2024/2025') {
                // Past Year: Read-only archive mode
                isArchive = true;
                archiveYearLabel.textContent = selectedYear;
                archiveNotice.hidden = false;
                publishTimetableBtn.disabled = true;
                publishTimetableBtn.style.opacity = '0.5';
                scheduleToggleBtn.disabled = true;
                scheduleToggleBtn.style.opacity = '0.5';

                renderPastYearGrid(selectedYear);
                legendHint.textContent = `Viewing ${selectedYear} archive · Sessions are read-only`;
            } else {
                // Return to Current Year
                isArchive = false;
                archiveNotice.hidden = true;
                publishTimetableBtn.disabled = false;
                publishTimetableBtn.style.opacity = '';
                scheduleToggleBtn.disabled = false;
                scheduleToggleBtn.style.opacity = '';

                grid.innerHTML = currentYearGridHtml;
                legendHint.textContent = 'Click any slot or session block for details & actions';
            }
        });
    }

    if (returnToCurrentYearBtn) {
        returnToCurrentYearBtn.addEventListener('click', function () {
            academicYearSelect.value = '2024/2025';
            academicYearSelect.dispatchEvent(new Event('change'));
        });
    }

    // ------------------------------------------------------------------
    // Lecturer & Room Filter Listeners
    // ------------------------------------------------------------------
    function applyFilters() {
        const lecturer = lecturerFilter ? lecturerFilter.value : '';
        const room = roomFilter ? roomFilter.value : '';
        grid.querySelectorAll('.tt-block').forEach(function (block) {
            const blockLecturer = block.dataset.lecturer || (coursesMap[block.dataset.code] ? coursesMap[block.dataset.code].lecturer : '');
            const matchesLecturer = !lecturer || (blockLecturer && blockLecturer.includes(lecturer));
            const matchesRoom = !room || block.dataset.location === room;
            block.style.display = (matchesLecturer && matchesRoom) ? '' : 'none';
        });
    }

    if (lecturerFilter) lecturerFilter.addEventListener('change', applyFilters);
    if (roomFilter) roomFilter.addEventListener('change', applyFilters);
});
