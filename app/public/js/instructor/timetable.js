// Instructor Timetable JS
// Drives: My Timetable <-> Student Timetable toggle, the Session Details /
// Request Support Staff / Request Time Change / My Requests side panels, and
// the Select Slots picking flow. Everything below is DOM-only demo state —
// nothing is sent to the server.
document.addEventListener('DOMContentLoaded', () => {
    const ttView = document.querySelector('.tt-view');
    const days = { mon: 'Monday', tue: 'Tuesday', wed: 'Wednesday', thu: 'Thursday', fri: 'Friday' };
    const dayKeys = Object.keys(days);
    const hours = [8, 9, 10, 11, 12, 13, 14, 15, 16];
    const workingHours = hours.filter(h => h !== 12);
    const myCourseCodes = window.__ttMyCourseCodes || [];

    function hourLabel(h) {
        const suffix = h < 12 ? 'AM' : 'PM';
        const display = h % 12 === 0 ? 12 : h % 12;
        return `${display} ${suffix}`;
    }

    function timeRange(startLabel, duration) {
        const m = startLabel.match(/^(\d+)\s+(AM|PM)$/);
        if (!m) return `${startLabel}`;
        let h = parseInt(m[1], 10);
        const isPm = m[2] === 'PM';
        if (h === 12) h = isPm ? 12 : 0; else if (isPm) h += 12;
        return `${startLabel} – ${hourLabel(h + duration)}`;
    }

    // ------------------------------------------------------------------
    // Requests state (seeded like the Figma "My Staff Requests" panel)
    // ------------------------------------------------------------------
    let requests = [
        {
            id: 1, code: 'CS3401', title: 'Fundamentals of Computing Lab',
            meta: 'Thu 10 AM–12 PM · Lab Assistant', status: 'approved',
            assigned: 'Mr. A. Karunaratne', submitted: 'Just now',
        },
        {
            id: 2, code: 'CS3401', title: 'Fundamentals of Computing Lab',
            meta: 'Mon 08:00 · Lab Assistant', status: 'pending',
            note: 'Need someone to assist with equipment.', submitted: '2 days ago',
        },
    ];
    let nextRequestId = 3;

    function pendingCount() {
        return requests.filter(r => r.status === 'pending').length;
    }

    function refreshBadge() {
        const badge = document.getElementById('myRequestsBadge');
        const n = pendingCount();
        if (n > 0) { badge.hidden = false; badge.textContent = n; } else { badge.hidden = true; }
    }
    refreshBadge();

    // ------------------------------------------------------------------
    // Side panel plumbing
    // ------------------------------------------------------------------
    const sidePanel = document.getElementById('ttSidePanel');
    const tspTitle = document.getElementById('tspTitle');
    const tspSubtitle = document.getElementById('tspSubtitle');
    const tspBody = document.getElementById('tspBody');
    const tspFooter = document.getElementById('tspFooter');
    document.getElementById('tspClose').addEventListener('click', closePanel);

    function openPanel(title, subtitle, bodyHtml, footerHtml) {
        tspTitle.textContent = title;
        tspSubtitle.textContent = subtitle || '';
        tspBody.innerHTML = bodyHtml;
        tspFooter.innerHTML = footerHtml || '';
        sidePanel.hidden = false;
    }

    function closePanel() {
        sidePanel.hidden = true;
        exitPicking();
    }

    function field(label, value) {
        return `<div><p class="tsp-field-label">${label}</p><p class="tsp-field-value">${value}</p></div>`;
    }

    // ------------------------------------------------------------------
    // Session Details panel
    // ------------------------------------------------------------------
    function openSessionDetails(block) {
        const d = block.dataset;
        const isAssignment = d.type === 'assignment';
        const body = `
            <span class="tsp-type-badge type-${d.type}">${d.type}</span>
            ${field('Course Code', d.code)}
            ${field('Course Name', d.title)}
            ${field('Venue', d.location || 'TBA')}
            ${field('Batch', d.batch || '—')}
            ${field('Date', d.date || '—')}
            ${field('Time', timeRange(d.start, parseInt(d.duration || '1', 10)))}
        `;
        const footer = isAssignment
            ? `<div class="btn-row">
                 <button type="button" class="btn-outline" id="tspReqStaffBtn">Request Support Staff</button>
                 <button type="button" class="btn-primary-sm" id="tspReqTimeBtn">Request Time Change</button>
               </div>`
            : `<button type="button" class="btn-outline" id="tspReqStaffBtn" style="width:100%">Request Support Staff</button>
               <p class="tsp-hint-disabled">Time change not available for ${d.type} sessions</p>`;

        openPanel('Session Details', '', body, footer);

        document.getElementById('tspReqStaffBtn').addEventListener('click', () => {
            openRequestStaff({
                slotsText: `${d.start} – ${hourLabel(parseInt(d.start, 10) + parseInt(d.duration || '1', 10))}, ${d.day}`,
                course: `${d.code} – ${d.title}`,
            });
        });
        if (isAssignment) {
            document.getElementById('tspReqTimeBtn').addEventListener('click', () => openRequestTimeChange(block));
        }
    }

    // ------------------------------------------------------------------
    // Request Support Staff panel
    // ------------------------------------------------------------------
    function openRequestStaff(ctx) {
        const body = `
            <div class="selected-slots-card">
                <div class="selected-slots-icon"><i class="fa-regular fa-clock"></i></div>
                <div>
                    <p class="field-label">SLOTS</p>
                    <p class="selected-slots-range">${ctx.slotsText}</p>
                </div>
            </div>
            <div>
                <p class="tsp-field-label">Course</p>
                <input type="text" class="tsp-input" id="rsCourse" value="${ctx.course || ''}" placeholder="e.g. CS3401 – Fundamentals of Computing Lab">
            </div>
            <div>
                <p class="tsp-field-label">Number of support staff needed</p>
                <div class="tsp-stepper">
                    <button type="button" id="rsMinus">−</button>
                    <input type="text" id="rsCount" value="1" readonly>
                    <button type="button" id="rsPlus">+</button>
                </div>
            </div>
            <div>
                <p class="tsp-field-label">Notes</p>
                <textarea class="tsp-textarea" id="rsNotes" rows="3" placeholder="Describe what support is needed..."></textarea>
            </div>
        `;
        const footer = `<div class="btn-row">
                <button type="button" class="btn-outline" id="rsCancel">Cancel</button>
                <button type="button" class="btn-primary-sm" id="rsSubmit">Submit</button>
            </div>`;
        openPanel('Request Support Staff', 'For selected time slot', body, footer);

        let count = 1;
        const countInput = document.getElementById('rsCount');
        document.getElementById('rsMinus').addEventListener('click', () => { count = Math.max(1, count - 1); countInput.value = count; });
        document.getElementById('rsPlus').addEventListener('click', () => { count += 1; countInput.value = count; });
        document.getElementById('rsCancel').addEventListener('click', closePanel);
        document.getElementById('rsSubmit').addEventListener('click', () => {
            const courseText = document.getElementById('rsCourse').value.trim() || 'Untitled session';
            const notes = document.getElementById('rsNotes').value.trim();
            const parts = courseText.split(/\s*–\s*/);
            requests.unshift({
                id: nextRequestId++, code: parts[0] || courseText, title: parts[1] || '',
                meta: `${ctx.slotsText} · ${count} staff needed`, status: 'pending',
                note: notes || 'No additional notes.', submitted: 'Just now',
            });
            refreshBadge();
            exitSelectMode();
            closePanel();
            ttToast('Support staff request submitted to the coordinator.');
        });
    }

    // ------------------------------------------------------------------
    // Request Time Change panel (+ "select from calendar" picking mode)
    // ------------------------------------------------------------------
    let preferredSlots = [];
    let picking = false;
    let currentBlockCtx = null;

    function renderTimeChangeBody() {
        const chips = preferredSlots.map((s, i) =>
            `<span class="slot-chip">${s} <button type="button" data-idx="${i}"><i class="fa-solid fa-xmark"></i></button></span>`
        ).join('') || '<span class="tsp-hint-disabled">No preferred slots selected yet</span>';

        return `
            <p class="tsp-req-summary">${currentBlockCtx.code} · ${currentBlockCtx.title}</p>
            <div class="selected-slots-card">
                <div class="selected-slots-icon"><i class="fa-regular fa-clock"></i></div>
                <div>
                    <p class="field-label">CURRENT SLOT</p>
                    <p class="selected-slots-range">${currentBlockCtx.day} · ${timeRange(currentBlockCtx.start, currentBlockCtx.duration)} · ${currentBlockCtx.location}</p>
                </div>
            </div>
            <div>
                <p class="tsp-field-label">Preferred Slots</p>
                <div class="tsp-slot-chips" id="rtcChips">${chips}</div>
                <button type="button" class="btn-outline-sm" id="rtcPickBtn" style="margin-top:8px;width:100%;justify-content:center;">
                    <i class="fa-regular fa-calendar"></i> ${picking ? 'Done selecting' : 'Select slots from calendar'}
                </button>
            </div>
            <div>
                <p class="tsp-field-label">Or add manually</p>
                <div style="display:flex; gap:6px;">
                    <select class="tsp-select" id="rtcDay">
                        ${dayKeys.map(k => `<option value="${k}">${days[k]}</option>`).join('')}
                    </select>
                    <select class="tsp-select" id="rtcHour">
                        ${workingHours.map(h => `<option value="${h}">${hourLabel(h)}</option>`).join('')}
                    </select>
                </div>
                <button type="button" class="tsp-req-simulate" id="rtcAddManual" style="margin-top:6px;">+ Add slot</button>
            </div>
            <div>
                <p class="tsp-field-label">Reason</p>
                <textarea class="tsp-textarea" id="rtcReason" rows="3" placeholder="Explain why you need the change..."></textarea>
            </div>
        `;
    }

    function wireTimeChangeBody() {
        tspBody.querySelectorAll('#rtcChips button').forEach(btn => {
            btn.addEventListener('click', () => {
                preferredSlots.splice(parseInt(btn.dataset.idx, 10), 1);
                tspBody.innerHTML = renderTimeChangeBody();
                wireTimeChangeBody();
            });
        });
        document.getElementById('rtcPickBtn').addEventListener('click', () => {
            picking = !picking;
            if (picking) enterPicking(); else exitPicking();
            tspBody.innerHTML = renderTimeChangeBody();
            wireTimeChangeBody();
        });
        document.getElementById('rtcAddManual').addEventListener('click', () => {
            const dayKey = document.getElementById('rtcDay').value;
            const h = parseInt(document.getElementById('rtcHour').value, 10);
            preferredSlots.push(`${days[dayKey]} ${hourLabel(h)}–${hourLabel(h + 1)}`);
            tspBody.innerHTML = renderTimeChangeBody();
            wireTimeChangeBody();
        });
    }

    function openRequestTimeChange(block) {
        const d = block.dataset;
        currentBlockCtx = {
            code: d.code, title: d.title, location: d.location,
            day: d.day, start: d.start, duration: parseInt(d.duration || '1', 10),
        };
        preferredSlots = [];
        picking = false;
        openPanel('Request Time Change', `${d.code} · ${d.title}`, renderTimeChangeBody(),
            `<div class="btn-row">
                <button type="button" class="btn-outline" id="rtcCancel">Cancel</button>
                <button type="button" class="btn-primary-sm" id="rtcSend">Send Request</button>
            </div>`);
        wireTimeChangeBody();
        document.getElementById('rtcCancel').addEventListener('click', () => { exitPicking(); closePanel(); });
        document.getElementById('rtcSend').addEventListener('click', () => {
            exitPicking();
            closePanel();
            ttToast('Time change request sent to coordinator.');
        });
    }

    function enterPicking() {
        ttView.classList.add('selecting');
        document.getElementById('legendHint').textContent = 'Click free slots to select · click again to deselect';
    }
    function exitPicking() {
        if (!picking) return;
        picking = false;
        ttView.classList.remove('selecting');
        document.getElementById('legendHint').textContent = 'Click any session for details · use Select Slots to request support staff';
    }

    // ------------------------------------------------------------------
    // My Requests panel
    // ------------------------------------------------------------------
    function openMyRequests() {
        renderMyRequests();
    }

    function renderMyRequests() {
        const total = requests.length;
        const pending = pendingCount();
        const cards = requests.map(r => `
            <div class="tsp-req-card" data-id="${r.id}">
                <div class="tsp-req-card-top">
                    <div>
                        <p class="tsp-req-course">${r.code}</p>
                        <p class="tsp-req-title">${r.title}</p>
                    </div>
                    <span class="tsp-req-status ${r.status}">${r.status.toUpperCase()}</span>
                </div>
                <p class="tsp-req-meta">${r.meta}</p>
                ${r.status === 'approved'
                    ? `<div class="tsp-req-assigned"><i class="fa-solid fa-user-check"></i> Assigned Staff: ${r.assigned}</div>`
                    : `<p class="tsp-req-note">"${r.note}"</p><button type="button" class="tsp-req-simulate" data-simulate="${r.id}">Simulate coordinator approval →</button>`}
                <p class="tsp-req-submitted">Submitted ${r.submitted}</p>
            </div>
        `).join('') || '<p class="tsp-hint-disabled">No requests yet</p>';

        openPanel('My Staff Requests', `${total} total · ${pending} pending`, cards, '');

        tspBody.querySelectorAll('[data-simulate]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.simulate, 10);
                const r = requests.find(x => x.id === id);
                if (r) {
                    r.status = 'approved';
                    r.assigned = 'Mr. A. Karunaratne';
                    delete r.note;
                }
                refreshBadge();
                renderMyRequests();
                ttToast('Request approved (simulated).');
            });
        });
    }

    document.getElementById('myRequestsBtn').addEventListener('click', openMyRequests);

    // ------------------------------------------------------------------
    // Grid click delegation: session blocks + free-cell picking
    // ------------------------------------------------------------------
    document.querySelector('.tt-grid')?.addEventListener('click', (e) => {
        const block = e.target.closest('.tt-block');
        if (block) { openSessionDetails(block); return; }

        const cell = e.target.closest('.tt-cell');
        if (!cell || cell.dataset.lunch) return;

        if (picking) {
            cell.classList.toggle('selected');
            if (cell.classList.contains('selected')) {
                preferredSlots.push(`${cell.dataset.day} ${cell.dataset.hourLabel}–${hourLabel(parseInt(cell.dataset.hour, 10) + 1)}`);
            } else {
                const label = `${cell.dataset.day} ${cell.dataset.hourLabel}–${hourLabel(parseInt(cell.dataset.hour, 10) + 1)}`;
                const idx = preferredSlots.indexOf(label);
                if (idx > -1) preferredSlots.splice(idx, 1);
            }
            tspBody.innerHTML = renderTimeChangeBody();
            wireTimeChangeBody();
            return;
        }

        if (ttView.classList.contains('select-mode')) {
            cell.classList.toggle('selected');
            updateSelectConfirmBar();
        }
    });

    // ------------------------------------------------------------------
    // "Select Slots" toolbar flow -> confirm bar -> Request Support Staff
    // ------------------------------------------------------------------
    const selectSlotsBtn = document.getElementById('selectSlotsBtn');
    const confirmBar = document.getElementById('selectConfirmBar');
    const confirmText = document.getElementById('selectConfirmText');
    const requestStaffFromSelectBtn = document.getElementById('requestStaffFromSelectBtn');

    function selectedCells() {
        return Array.from(document.querySelectorAll('#myTimetableSection .tt-cell.selected'));
    }

    function updateSelectConfirmBar() {
        const cells = selectedCells();
        confirmText.textContent = `${cells.length} slot${cells.length === 1 ? '' : 's'} selected`;
        requestStaffFromSelectBtn.disabled = cells.length === 0;
    }

    function enterSelectMode() {
        ttView.classList.add('select-mode', 'selecting');
        selectSlotsBtn.classList.add('active');
        confirmBar.hidden = false;
        updateSelectConfirmBar();
    }

    function exitSelectMode() {
        ttView.classList.remove('select-mode', 'selecting');
        selectSlotsBtn.classList.remove('active');
        confirmBar.hidden = true;
        selectedCells().forEach(c => c.classList.remove('selected'));
    }

    selectSlotsBtn.addEventListener('click', () => {
        if (ttView.classList.contains('select-mode')) exitSelectMode(); else enterSelectMode();
    });
    document.getElementById('cancelSelectBtn').addEventListener('click', exitSelectMode);
    requestStaffFromSelectBtn.addEventListener('click', () => {
        const cells = selectedCells();
        if (!cells.length) return;
        const first = cells[0];
        const hoursSel = cells.map(c => parseInt(c.dataset.hour, 10)).sort((a, b) => a - b);
        const slotsText = `${hourLabel(hoursSel[0])} – ${hourLabel(hoursSel[hoursSel.length - 1] + 1)}, ${first.dataset.day}`;
        openRequestStaff({ slotsText, course: '' });
    });

    // ------------------------------------------------------------------
    // My Timetable <-> Student Timetable mode toggle
    // ------------------------------------------------------------------
    const modeToggle = document.getElementById('ttModeToggle');
    const myFilters = document.getElementById('myTimetableFilters');
    const stFilters = document.getElementById('studentTimetableFilters');
    const ttActionsMy = document.getElementById('ttActionsMy');
    const myTimetableSection = document.getElementById('myTimetableSection');
    const studentTimetableSection = document.getElementById('studentTimetableSection');

    modeToggle.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-mode]');
        if (!btn) return;
        modeToggle.querySelectorAll('.segmented-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        closePanel();
        exitSelectMode();

        const isStudent = btn.dataset.mode === 'student';
        myFilters.hidden = isStudent;
        stFilters.hidden = !isStudent;
        ttActionsMy.hidden = isStudent;
        myTimetableSection.hidden = isStudent;
        studentTimetableSection.hidden = !isStudent;
        if (isStudent) renderStudentTimetable();
    });

    // ------------------------------------------------------------------
    // Student Timetable (read-only, hardcoded demo dataset)
    // ------------------------------------------------------------------
    const studentData = {
        'cs-1': [
            { day: 'mon', start: 8, duration: 1, code: 'CS1101', title: 'Intro to Programming', location: 'LT-201', type: 'lecture' },
            { day: 'mon', start: 10, duration: 1, code: 'CS1102', title: 'Discrete Mathematics', location: 'LT-202', type: 'lecture' },
            { day: 'tue', start: 13, duration: 2, code: 'CS1101', title: 'Programming Lab', location: 'Lab A-201', type: 'lab' },
            { day: 'wed', start: 9, duration: 1, code: 'CS1103', title: 'Digital Logic', location: 'LT-301', type: 'lecture' },
            { day: 'wed', start: 14, duration: 2, code: 'CS1103', title: 'Digital Logic Practical', location: 'Lab B-101', type: 'practical' },
            { day: 'thu', start: 8, duration: 1, code: 'CS1104', title: 'Calculus I', location: 'LT-101', type: 'lecture' },
            { day: 'fri', start: 10, duration: 1, code: 'CS1102', title: 'Discrete Math Tutorial', location: 'LT-202', type: 'tutorial' },
            { day: 'fri', start: 8, duration: 1, code: 'CS3401', title: 'Fundamentals of Computing Lab', location: 'Lab A-201', type: 'lab' },
        ],
    };

    function buildStudentGrid(dept, year) {
        const list = studentData[`${dept}-${year}`] || [];
        const occupied = {};
        list.forEach(s => {
            for (let i = 0; i < s.duration; i++) occupied[s.day] = occupied[s.day] || {};
            for (let i = 0; i < s.duration; i++) occupied[s.day][s.start + i] = i === 0 ? s : 'busy';
        });

        let html = '<div class="tt-grid-corner"></div>';
        dayKeys.forEach(k => { html += `<div class="tt-grid-day-head">${days[k]}</div>`; });

        hours.forEach((h, rowIndex) => {
            html += `<div class="tt-grid-time">${hourLabel(h)}</div>`;
            dayKeys.forEach((dayKey, colIndex) => {
                const cell = (occupied[dayKey] || {})[h];
                const isLunch = h === 12;
                const col = colIndex + 2;
                const row = rowIndex + 2;
                if (cell === 'busy') return;
                if (cell) {
                    const isMine = myCourseCodes.includes(cell.code);
                    html += `<div class="tt-block type-${cell.type}${isMine ? ' st-block-mine' : ''}"
                        style="grid-column:${col}; grid-row:${row} / span ${cell.duration};">
                        <p class="tt-block-code">${cell.code}</p>
                        <p class="tt-block-title">${cell.title}</p>
                        <p class="tt-block-loc">${cell.location}</p>
                    </div>`;
                } else {
                    html += `<div class="tt-cell ${isLunch ? 'tt-cell-lunch' : ''}" style="grid-column:${col}; grid-row:${row};">
                        ${isLunch && dayKey === 'wed' ? '<span class="lunch-label">Lunch Break</span>' : ''}
                    </div>`;
                }
            });
        });
        return { html, occupied };
    }

    function renderStudentTimetable() {
        const dept = document.querySelector('#stDeptToggle .segmented-btn.active').dataset.dept;
        const year = document.querySelector('#stYearToggle .segmented-btn.active').dataset.year;
        const { html, occupied } = buildStudentGrid(dept, year);
        document.getElementById('stGrid').innerHTML = html;

        const freeBar = document.getElementById('stFreeBar');
        freeBar.innerHTML = dayKeys.map(k => {
            const occCount = Object.keys(occupied[k] || {}).length;
            const free = workingHours.length - occCount;
            return `<span>${days[k].slice(0, 3)} <b>${free}h</b></span>`;
        }).join('');

        document.getElementById('stCaption').textContent =
            `${dept.toUpperCase()} Y${year} schedule · ★ = your courses · empty cells = students are free`;
    }

    document.getElementById('stDeptToggle').addEventListener('click', (e) => {
        const btn = e.target.closest('[data-dept]');
        if (!btn) return;
        document.querySelectorAll('#stDeptToggle .segmented-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        renderStudentTimetable();
    });
    document.getElementById('stYearToggle').addEventListener('click', (e) => {
        const btn = e.target.closest('[data-year]');
        if (!btn) return;
        document.querySelectorAll('#stYearToggle .segmented-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        renderStudentTimetable();
    });
});
