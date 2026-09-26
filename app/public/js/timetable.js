// Timetable interactions: lecturer/room filters, interactive slot side-panel
// (view, edit, save, delete), publication flow, and past years archive dropdown.
// Schedule / edit / delete persist through /timetable/sessions
// (TimetableSessionsController) and then reload the page — see saveSession().
// Publish still only writes localStorage, and the archive is hardcoded below.

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

    // ------------------------------------------------------------------
    // Persistence — TimetableSessionsController. After every successful
    // write the page reloads: the grid (which cells are free, which are
    // covered by a multi-hour block) is laid out server-side, which is more
    // reliable than patching free/busy cells by hand.
    // ------------------------------------------------------------------
    function sessionUrl(block) {
        return '/timetable/sessions/' + encodeURIComponent(block.dataset.location) +
            '/' + block.dataset.dayKey + '/' + block.dataset.startHour;
    }

    function sessionPayload(courseCode, roomCode, dayKey, startHour, duration, type) {
        return {
            course_code: courseCode,
            room_code: roomCode,
            day_of_week: dayKey,
            start_hour: startHour,
            duration_hours: duration,
            session_type: type,
            department: dept,
            semester: sem,
            year_of_study: year,
        };
    }

    function saveSession(method, url, payload, successMessage) {
        return fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: payload ? JSON.stringify(payload) : undefined,
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok || !result.data.success) {
                    throw new Error(result.data.message || 'Could not save the session.');
                }
                showToast(successMessage);
                setTimeout(function () { window.location.reload(); }, 800);
            })
            .catch(function (err) { showToast(err.message, false); });
    }

    // Escapes text for innerHTML — course titles are user-entered now.
    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    // "LT-301 (Lecture Hall, cap: 120)" — same wording as the schedule modal.
    function roomLabel(r) {
        const type = String(r.type || 'room').replace(/_/g, ' ').replace(/\b\w/g, ch => ch.toUpperCase());
        return `${r.code} (${type}${r.capacity ? ', cap: ' + r.capacity : ''})`;
    }

    // Toolbar filters. Panel forms are enhanced in openPanel().
    SearchableSelect.enhance(document);

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
        SearchableSelect.enhance(tspBody);
        sidePanel.hidden = false;
        document.body.classList.add('tt-panel-open');
    }

    function closePanel() {
        sidePanel.hidden = true;
        document.body.classList.remove('tt-panel-open');
    }

    if (tspClose) {
        tspClose.addEventListener('click', closePanel);
    }
    const tspBack = document.getElementById('tspBack');
    if (tspBack) {
        tspBack.addEventListener('click', closePanel);
    }
    // The panel floats over the page (.floating-panel): a click on its
    // backdrop (body::after) targets <body> itself.
    document.body.addEventListener('click', (e) => {
        if (e.target === document.body && !sidePanel.hidden) closePanel();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !sidePanel.hidden) closePanel();
    });

    function field(label, value) {
        return `<div><p class="tsp-field-label">${label}</p><p class="tsp-field-value">${esc(value)}</p></div>`;
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
            courseOptions += `<option value="${esc(code)}" ${sel}>${esc(code)} — ${esc(c.title)}</option>`;
        });

        let roomOptions = '';
        roomsList.forEach(r => {
            const sel = r.code === currentLocation ? 'selected' : '';
            roomOptions += `<option value="${esc(r.code)}" ${sel}>${esc(roomLabel(r))}</option>`;
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
                <select class="tsp-select" id="editCourseCode" data-searchable data-search-placeholder="Search by code or title…">${courseOptions}</select>
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
                <select class="tsp-select" id="editVenue" data-searchable data-search-placeholder="Search halls / labs…">${roomOptions}</select>
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
            // The URL carries the block's ORIGINAL key; the body the new values.
            const payload = sessionPayload(
                document.getElementById('editCourseCode').value,
                document.getElementById('editVenue').value,
                document.getElementById('editDay').value,
                parseInt(document.getElementById('editStartHour').value, 10),
                parseInt(document.getElementById('editDuration').value, 10),
                document.getElementById('editSessionType').value
            );
            saveSession('PUT', sessionUrl(block), payload, `Session ${payload.course_code} updated.`);
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
            saveSession('DELETE', sessionUrl(block), null, `Session ${d.code} deleted.`);
        });
    }

    // ------------------------------------------------------------------
    // Schedule Session — one side-panel form for both ways in: clicking an
    // empty cell (starts as one hour) and "Schedule Course" → select slots →
    // Confirm (starts as the selected hours). Duration stays editable.
    // ------------------------------------------------------------------
    function openScheduleForm(dayKey, hour, duration) {
        const dayName = DAY_LABELS[dayKey];

        let courseOptions = '';
        Object.keys(coursesMap).forEach(code => {
            const c = coursesMap[code];
            courseOptions += `<option value="${esc(code)}">${esc(code)} — ${esc(c.title)}</option>`;
        });

        let roomOptions = '';
        roomsList.forEach(r => {
            roomOptions += `<option value="${esc(r.code)}">${esc(roomLabel(r))}</option>`;
        });

        let durationOptions = '';
        [...new Set([1, 2, 3, duration])].sort((x, y) => x - y).forEach(dur => {
            durationOptions += `<option value="${dur}" ${dur === duration ? 'selected' : ''}>${dur} ${dur === 1 ? 'hour' : 'hours'}</option>`;
        });

        const body = `
            <div class="selected-slots-card" style="margin-bottom: 4px;">
                <div class="selected-slots-icon"><i class="fa-regular fa-clock"></i></div>
                <div>
                    <p class="field-label">Time</p>
                    <p class="selected-slots-range" id="newSlotRange">${dayName}, ${timeRange(hour, duration)}</p>
                </div>
            </div>
            <div>
                <p class="tsp-field-label">Course Module</p>
                <select class="tsp-select" id="newCourseCode" data-searchable data-search-placeholder="Search by code or title…">
                    <option value="">Select a course module...</option>
                    ${courseOptions}
                </select>
                <p class="field-hint" id="newLecturerHint">&nbsp;</p>
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
                <select class="tsp-select" id="newVenue" data-searchable data-search-placeholder="Search halls / labs…">
                    <option value="">Select a hall / lab...</option>
                    ${roomOptions}
                </select>
            </div>
            <div>
                <p class="tsp-field-label">Duration</p>
                <select class="tsp-select" id="newDuration">${durationOptions}</select>
            </div>
        `;

        const footer = `
            <div class="btn-row">
                <button type="button" class="btn-outline" id="cancelNewBtn">Cancel</button>
                <button type="button" class="btn-primary-sm" id="saveNewBtn">
                    <i class="fa-solid fa-plus"></i> Add to Timetable
                </button>
            </div>
        `;

        openPanel('Schedule Session', `Year ${year} ${dept.toUpperCase()} · Sem ${sem}`, body, footer);

        const courseSelect = document.getElementById('newCourseCode');
        const durationSelect = document.getElementById('newDuration');
        courseSelect.addEventListener('change', () => {
            const c = coursesMap[courseSelect.value];
            document.getElementById('newLecturerHint').textContent = c && c.lecturer ? 'Lecturer: ' + c.lecturer : ' ';
        });
        durationSelect.addEventListener('change', () => {
            document.getElementById('newSlotRange').textContent =
                `${dayName}, ${timeRange(hour, parseInt(durationSelect.value, 10))}`;
        });

        document.getElementById('cancelNewBtn').addEventListener('click', closePanel);

        document.getElementById('saveNewBtn').addEventListener('click', () => {
            const code = courseSelect.value;
            const venue = document.getElementById('newVenue').value;
            if (!code || !venue) {
                alert('Please select a course module and a venue.');
                return;
            }
            const payload = sessionPayload(
                code, venue, dayKey, hour,
                parseInt(durationSelect.value, 10),
                document.getElementById('newSessionType').value
            );
            saveSession('POST', '/timetable/sessions', payload, `Added ${code} to timetable.`);
        });
    }

    function openEmptySlotSchedule(cell) {
        if (isArchive || selecting) return;
        openScheduleForm(cell.dataset.dayKey, parseInt(cell.dataset.hour, 10), 1);
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
            const hoursArr = selectedCells.map(function (c) { return c.hour; });
            const minHour = Math.min.apply(null, hoursArr);
            const maxHour = Math.max.apply(null, hoursArr);
            if (maxHour - minHour + 1 !== selectedCells.length) {
                alert('Please select consecutive time slots.');
                return;
            }
            openScheduleForm(selectedCells[0].dayKey, minHour, selectedCells.length);
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
        updateDayView(true);
    }

    if (lecturerFilter) lecturerFilter.addEventListener('change', applyFilters);
    if (roomFilter) roomFilter.addEventListener('change', applyFilters);

    // ------------------------------------------------------------------
    // Day Navigator & Resolution-based Day Fitting (Mobile / Tablet)
    // ------------------------------------------------------------------
    function getContainerWidth() {
        const card = view.querySelector('.tt-grid-card');
        const w = card ? card.getBoundingClientRect().width : 0;
        return w > 0 ? w : window.innerWidth;
    }

    function getVisibleDaysCount() {
        const width = getContainerWidth();
        if (width < 420) return 1;
        if (width < 640) return 2;
        if (width < 900) return 3;
        return 5;
    }

    function getInitialDayIndex(visibleCount) {
        const today = new Date();
        const dayOfWeek = today.getDay(); // 0: Sun, 1: Mon, ... 5: Fri, 6: Sat
        let dayIdx = 0; // default Monday
        if (dayOfWeek >= 1 && dayOfWeek <= 5) {
            dayIdx = dayOfWeek - 1;
        }
        const maxStart = Math.max(0, 5 - visibleCount);
        return Math.min(dayIdx, maxStart);
    }

    let currentStartDayIndex = getInitialDayIndex(getVisibleDaysCount());

    function updateDayNavDisplay(visibleKeys, visibleCount) {
        let title = '';
        let sub = '';

        if (visibleCount === 1) {
            title = DAY_LABELS[visibleKeys[0]];
            sub = DAY_SHORT[visibleKeys[0]];
        } else {
            title = `${DAY_SHORT[visibleKeys[0]]} – ${DAY_SHORT[visibleKeys[visibleKeys.length - 1]]}`;
            sub = `${DAY_LABELS[visibleKeys[0]]} to ${DAY_LABELS[visibleKeys[visibleKeys.length - 1]]}`;
        }

        const navTitle = document.getElementById('dayNavTitle');
        const navDate = document.getElementById('dayNavDate');
        if (navTitle) navTitle.textContent = title;
        if (navDate) navDate.textContent = sub;

        const firstIdx = DAY_KEYS.indexOf(visibleKeys[0]);
        const lastIdx = DAY_KEYS.indexOf(visibleKeys[visibleKeys.length - 1]);
        const isAtStart = (firstIdx === 0);
        const isAtEnd = (lastIdx >= DAY_KEYS.length - 1);

        const prevBtn = document.getElementById('dayPrevBtn');
        const nextBtn = document.getElementById('dayNextBtn');
        if (prevBtn) prevBtn.disabled = isAtStart;
        if (nextBtn) nextBtn.disabled = isAtEnd;
    }

    function applyDayVisibilityToGrid(visibleKeys, visibleCount) {
        if (!grid) return;

        if (visibleCount >= 5) {
            grid.style.gridTemplateColumns = '';
            grid.style.minWidth = '';
            grid.style.width = '';

            grid.querySelectorAll('.tt-grid-day-head').forEach((head, idx) => {
                head.style.display = '';
                head.style.gridColumn = String(idx + 2);
            });

            grid.querySelectorAll('.tt-cell, .tt-block').forEach(el => {
                const k = el.dataset.dayKey;
                const originalCol = DAY_KEYS.indexOf(k) + 2;
                el.style.gridColumn = String(originalCol);

                if (el.classList.contains('tt-block')) {
                    const lecturer = lecturerFilter ? lecturerFilter.value : '';
                    const room = roomFilter ? roomFilter.value : '';
                    const blockLecturer = el.dataset.lecturer || (coursesMap[el.dataset.code] ? coursesMap[el.dataset.code].lecturer : '');
                    const matchesLecturer = !lecturer || (blockLecturer && blockLecturer.includes(lecturer));
                    const matchesRoom = !room || el.dataset.location === room;
                    el.style.display = (matchesLecturer && matchesRoom) ? '' : 'none';
                } else {
                    el.style.display = '';
                }
            });
            return;
        }

        grid.style.gridTemplateColumns = `54px repeat(${visibleCount}, minmax(0, 1fr))`;
        grid.style.minWidth = '0';
        grid.style.width = '100%';

        grid.querySelectorAll('.tt-grid-day-head').forEach(head => {
            const k = head.dataset.dayKey;
            if (visibleKeys.includes(k)) {
                const colPos = visibleKeys.indexOf(k) + 2;
                head.style.display = 'flex';
                head.style.gridColumn = String(colPos);
            } else {
                head.style.display = 'none';
            }
        });

        grid.querySelectorAll('.tt-cell, .tt-block').forEach(el => {
            const k = el.dataset.dayKey;
            if (visibleKeys.includes(k)) {
                const colPos = visibleKeys.indexOf(k) + 2;
                el.style.gridColumn = String(colPos);

                if (el.classList.contains('tt-block')) {
                    const lecturer = lecturerFilter ? lecturerFilter.value : '';
                    const room = roomFilter ? roomFilter.value : '';
                    const blockLecturer = el.dataset.lecturer || (coursesMap[el.dataset.code] ? coursesMap[el.dataset.code].lecturer : '');
                    const matchesLecturer = !lecturer || (blockLecturer && blockLecturer.includes(lecturer));
                    const matchesRoom = !room || el.dataset.location === room;
                    el.style.display = (matchesLecturer && matchesRoom) ? '' : 'none';
                } else {
                    el.style.display = '';
                }
            } else {
                el.style.display = 'none';
            }
        });

        const lunchCells = grid.querySelectorAll('.tt-cell-lunch');
        lunchCells.forEach(cell => {
            const k = cell.dataset.dayKey;
            const existingLabel = cell.querySelector('.lunch-label');
            if (visibleKeys.includes(k) && k === visibleKeys[0]) {
                if (!existingLabel) {
                    const span = document.createElement('span');
                    span.className = 'lunch-label';
                    span.textContent = 'Lunch Break';
                    cell.appendChild(span);
                }
            } else {
                if (existingLabel && k !== 'wed') {
                    existingLabel.remove();
                }
            }
        });
    }

    let lastVisibleCount = null;
    function updateDayView(force = false) {
        const visibleCount = getVisibleDaysCount();
        if (!force && visibleCount === lastVisibleCount) return;
        lastVisibleCount = visibleCount;

        const dayNav = document.getElementById('ttDayNav');

        if (visibleCount >= 5) {
            if (dayNav) dayNav.style.display = 'none';
            applyDayVisibilityToGrid(DAY_KEYS, 5);
            return;
        }

        if (dayNav) dayNav.style.display = 'flex';

        const maxStart = Math.max(0, 5 - visibleCount);
        currentStartDayIndex = Math.max(0, Math.min(currentStartDayIndex, maxStart));
        const visibleKeys = DAY_KEYS.slice(currentStartDayIndex, currentStartDayIndex + visibleCount);

        updateDayNavDisplay(visibleKeys, visibleCount);
        applyDayVisibilityToGrid(visibleKeys, visibleCount);
    }

    function goPrevDay() {
        if (currentStartDayIndex > 0) {
            currentStartDayIndex = Math.max(0, currentStartDayIndex - 1);
            updateDayView(true);
        }
    }

    function goNextDay() {
        const visibleCount = getVisibleDaysCount();
        const maxStart = Math.max(0, 5 - visibleCount);
        if (currentStartDayIndex < maxStart) {
            currentStartDayIndex = Math.min(maxStart, currentStartDayIndex + 1);
            updateDayView(true);
        }
    }

    document.getElementById('dayPrevBtn')?.addEventListener('click', goPrevDay);
    document.getElementById('dayNextBtn')?.addEventListener('click', goNextDay);

    const gridCard = view.querySelector('.tt-grid-card');
    if (gridCard) {
        let touchStartX = 0;
        let touchStartY = 0;

        gridCard.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
            }
        }, { passive: true });

        gridCard.addEventListener('touchend', (e) => {
            if (getVisibleDaysCount() >= 5) return;
            const touchEndX = e.changedTouches[0].clientX;
            const touchEndY = e.changedTouches[0].clientY;
            const diffX = touchEndX - touchStartX;
            const diffY = touchEndY - touchStartY;

            if (Math.abs(diffX) > 40 && Math.abs(diffX) > Math.abs(diffY) * 1.5) {
                if (diffX < 0) {
                    goNextDay();
                } else {
                    goPrevDay();
                }
            }
        }, { passive: true });
    }

    if (window.ResizeObserver && gridCard) {
        const ro = new ResizeObserver(() => {
            updateDayView();
        });
        ro.observe(gridCard);
    } else {
        let officerResizeTimer = null;
        window.addEventListener('resize', () => {
            clearTimeout(officerResizeTimer);
            officerResizeTimer = setTimeout(() => {
                updateDayView();
            }, 80);
        });
    }

    updateDayView(true);
});
