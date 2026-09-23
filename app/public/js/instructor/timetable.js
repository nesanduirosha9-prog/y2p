// Instructor Timetable JS
// Drives: My Timetable <-> Student Timetable toggle, the Session Details /
// Request Support Staff / Request Time Change / My Requests side panels, and
// the Select Slots picking flow. Everything below is DOM-only demo state —
// nothing is sent to the server.
document.addEventListener('DOMContentLoaded', () => {
    const ttView = document.querySelector('.tt-view');
    const days = { mon: 'Monday', tue: 'Tuesday', wed: 'Wednesday', thu: 'Thursday', fri: 'Friday' };
    const dayKeys = Object.keys(days);
    const hours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17];
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
    document.getElementById('tspBack')?.addEventListener('click', closePanel);

    function openPanel(title, subtitle, bodyHtml, footerHtml) {
        tspTitle.textContent = title;
        tspSubtitle.textContent = subtitle || '';
        tspBody.innerHTML = bodyHtml;
        tspFooter.innerHTML = footerHtml || '';
        sidePanel.hidden = false;
        document.body.classList.add('tt-panel-open');
    }

    function closePanel() {
        sidePanel.hidden = true;
        document.body.classList.remove('tt-panel-open');
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
    // Week navigator (cosmetic only — demo data always reflects "this week")
    // ------------------------------------------------------------------
    const weekStartStr = ttView.dataset.weekStart;
    const weekLabelEl = document.getElementById('weekRangeLabel');
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    let weekOffset = 0;

    function addDays(date, n) {
        const d = new Date(date);
        d.setDate(d.getDate() + n);
        return d;
    }

    function formatWeekRange(monday, friday) {
        const sameMonth = monday.getMonth() === friday.getMonth();
        const startPart = sameMonth ? `${monday.getDate()}` : `${monday.getDate()} ${monthNames[monday.getMonth()]}`;
        return `${startPart} – ${friday.getDate()} ${monthNames[friday.getMonth()]} ${friday.getFullYear()}`;
    }

    function updateWeekDisplay() {
        if (!weekStartStr) return;
        const baseMonday = addDays(new Date(`${weekStartStr}T00:00:00`), weekOffset * 7);
        const friday = addDays(baseMonday, 4);
        weekLabelEl.textContent = formatWeekRange(baseMonday, friday);
        dayKeys.forEach((key, i) => {
            const head = document.querySelector(`.tt-grid-day-head[data-day-key="${key}"] .tt-day-num`);
            if (head) head.textContent = addDays(baseMonday, i).getDate();
        });
        updateDayView(true);
    }

    document.getElementById('weekPrevBtn').addEventListener('click', () => { weekOffset -= 1; updateWeekDisplay(); });
    document.getElementById('weekNextBtn').addEventListener('click', () => { weekOffset += 1; updateWeekDisplay(); });

    // ------------------------------------------------------------------
    // Day Navigator & Resolution-based Day Fitting
    // ------------------------------------------------------------------
    const dayShort = { mon: 'Mon', tue: 'Tue', wed: 'Wed', thu: 'Thu', fri: 'Fri' };

    function getContainerWidth() {
        const section = document.querySelector('.tt-section:not([hidden])');
        const card = section?.querySelector('.tt-grid-card') || document.querySelector('.tt-grid-card');
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
        const dayOfWeek = today.getDay(); // 0: Sun, 1: Mon, 2: Tue, 3: Wed, 4: Thu, 5: Fri, 6: Sat
        let dayIdx = 0; // default Monday
        if (dayOfWeek >= 1 && dayOfWeek <= 5) {
            dayIdx = dayOfWeek - 1; // 0 for Mon, 1 for Tue, 2 for Wed, 3 for Thu, 4 for Fri
        }
        const maxStart = Math.max(0, 5 - visibleCount);
        return Math.min(dayIdx, maxStart);
    }

    let currentStartDayIndex = getInitialDayIndex(getVisibleDaysCount());

    function getDayDate(i) {
        if (!weekStartStr) return null;
        const baseMonday = addDays(new Date(`${weekStartStr}T00:00:00`), weekOffset * 7);
        return addDays(baseMonday, i);
    }

    function formatDayNavDate(date) {
        if (!date) return '';
        return `${date.getDate()} ${monthNames[date.getMonth()]} ${date.getFullYear()}`;
    }

    function formatDayNavDateShort(date) {
        if (!date) return '';
        return `${date.getDate()} ${monthNames[date.getMonth()]}`;
    }

    function updateDayNavDisplay(visibleKeys, visibleCount) {
        const firstIdx = dayKeys.indexOf(visibleKeys[0]);
        const lastIdx = dayKeys.indexOf(visibleKeys[visibleKeys.length - 1]);
        const firstDate = getDayDate(firstIdx);
        const lastDate = getDayDate(lastIdx);

        let title = '';
        let sub = '';

        if (visibleCount === 1) {
            title = days[visibleKeys[0]];
            sub = formatDayNavDate(firstDate);
        } else {
            title = `${dayShort[visibleKeys[0]]} – ${dayShort[visibleKeys[visibleKeys.length - 1]]}`;
            sub = `${formatDayNavDateShort(firstDate)} – ${formatDayNavDateShort(lastDate)}`;
        }

        const myTitle = document.getElementById('dayNavTitle');
        const mySub = document.getElementById('dayNavDate');
        if (myTitle) myTitle.textContent = title;
        if (mySub) mySub.textContent = sub;

        const stTitle = document.getElementById('stDayNavTitle');
        const stSub = document.getElementById('stDayNavDate');
        if (stTitle) stTitle.textContent = title;
        if (stSub) stSub.textContent = sub;

        // Boundary state: Monday only Next active, Friday only Prev active
        const isAtStart = (firstIdx === 0);
        const isAtEnd = (lastIdx >= dayKeys.length - 1);

        const prevBtn = document.getElementById('dayPrevBtn');
        const nextBtn = document.getElementById('dayNextBtn');
        if (prevBtn) prevBtn.disabled = isAtStart;
        if (nextBtn) nextBtn.disabled = isAtEnd;

        const stPrevBtn = document.getElementById('stDayPrevBtn');
        const stNextBtn = document.getElementById('stDayNextBtn');
        if (stPrevBtn) stPrevBtn.disabled = isAtStart;
        if (stNextBtn) stNextBtn.disabled = isAtEnd;
    }

    function applyDayVisibilityToGrid(gridEl, visibleKeys, visibleCount) {
        if (!gridEl) return;

        if (visibleCount >= 5) {
            gridEl.style.gridTemplateColumns = '';
            gridEl.style.minWidth = '';
            gridEl.style.width = '';

            gridEl.querySelectorAll('.tt-grid-day-head').forEach((head, idx) => {
                head.style.display = '';
                head.style.gridColumn = String(idx + 2);
            });

            gridEl.querySelectorAll('.tt-cell, .tt-block').forEach(el => {
                const k = el.dataset.dayKey;
                const originalCol = dayKeys.indexOf(k) + 2;
                el.style.display = '';
                el.style.gridColumn = String(originalCol);
            });
            return;
        }

        gridEl.style.gridTemplateColumns = `54px repeat(${visibleCount}, minmax(0, 1fr))`;
        gridEl.style.minWidth = '0';
        gridEl.style.width = '100%';

        gridEl.querySelectorAll('.tt-grid-day-head').forEach(head => {
            const k = head.dataset.dayKey;
            if (visibleKeys.includes(k)) {
                const colPos = visibleKeys.indexOf(k) + 2;
                head.style.display = 'flex';
                head.style.gridColumn = String(colPos);
            } else {
                head.style.display = 'none';
            }
        });

        gridEl.querySelectorAll('.tt-cell, .tt-block').forEach(el => {
            const k = el.dataset.dayKey;
            if (visibleKeys.includes(k)) {
                const colPos = visibleKeys.indexOf(k) + 2;
                el.style.display = '';
                el.style.gridColumn = String(colPos);
            } else {
                el.style.display = 'none';
            }
        });

        // Ensure the first visible day on row 6 displays the Lunch Break label
        const lunchCells = gridEl.querySelectorAll('.tt-cell-lunch');
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

        const dayNavs = document.querySelectorAll('.tt-day-nav');

        if (visibleCount >= 5) {
            dayNavs.forEach(nav => nav.style.display = 'none');
            const myGrid = document.querySelector('#myTimetableSection .tt-grid');
            if (myGrid) applyDayVisibilityToGrid(myGrid, dayKeys, 5);
            const stGrid = document.getElementById('stGrid');
            if (stGrid) applyDayVisibilityToGrid(stGrid, dayKeys, 5);
            return;
        }

        dayNavs.forEach(nav => nav.style.display = 'flex');

        const maxStart = Math.max(0, 5 - visibleCount);
        currentStartDayIndex = Math.max(0, Math.min(currentStartDayIndex, maxStart));
        const visibleKeys = dayKeys.slice(currentStartDayIndex, currentStartDayIndex + visibleCount);

        updateDayNavDisplay(visibleKeys, visibleCount);

        const myGrid = document.querySelector('#myTimetableSection .tt-grid');
        if (myGrid) applyDayVisibilityToGrid(myGrid, visibleKeys, visibleCount);
        const stGrid = document.getElementById('stGrid');
        if (stGrid) applyDayVisibilityToGrid(stGrid, visibleKeys, visibleCount);
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
    document.getElementById('stDayPrevBtn')?.addEventListener('click', goPrevDay);
    document.getElementById('stDayNextBtn')?.addEventListener('click', goNextDay);

    function bindSwipeGestures(element) {
        if (!element) return;
        let touchStartX = 0;
        let touchStartY = 0;

        element.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
            }
        }, { passive: true });

        element.addEventListener('touchend', (e) => {
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

    bindSwipeGestures(document.querySelector('#myTimetableSection .tt-grid-card'));
    bindSwipeGestures(document.querySelector('#studentTimetableSection .tt-grid-card'));

    if (window.ResizeObserver) {
        const ro = new ResizeObserver(() => {
            updateDayView();
        });
        document.querySelectorAll('.tt-grid-card').forEach(c => ro.observe(c));
    } else {
        let resizeTimer = null;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                updateDayView();
            }, 80);
        });
    }

    // Initial calculation on page load
    updateDayView(true);

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
    const ttLegendInline = document.getElementById('ttLegendInline');
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
        ttLegendInline.hidden = false;
        document.getElementById('chipMine').hidden = !isStudent;
        myTimetableSection.hidden = isStudent;
        studentTimetableSection.hidden = !isStudent;
        if (isStudent) renderStudentTimetable();
        updateDayView(true);
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
        // 1. Check if the Timetable Officer published a live schedule for this batch
        const storageKey = `staffsync_published_${dept}_1_${year}`;
        let list = null;
        try {
            const saved = localStorage.getItem(storageKey);
            if (saved) {
                list = JSON.parse(saved);
            }
        } catch (e) {
            console.warn('Error reading published timetable:', e);
        }

        // 2. Fallback to default mock dataset if nothing published yet
        if (!list || !list.length) {
            list = studentData[`${dept}-${year}`] || [];
        }

        const occupied = {};
        list.forEach(s => {
            for (let i = 0; i < s.duration; i++) occupied[s.day] = occupied[s.day] || {};
            for (let i = 0; i < s.duration; i++) occupied[s.day][s.start + i] = i === 0 ? s : 'busy';
        });

        let html = '<div class="tt-grid-corner"></div>';
        dayKeys.forEach(k => { html += `<div class="tt-grid-day-head" data-day-key="${k}">${days[k]}</div>`; });

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
                        data-day-key="${dayKey}"
                        style="grid-column:${col}; grid-row:${row} / span ${cell.duration};">
                        <p class="tt-block-code">${cell.code}</p>
                        <p class="tt-block-title">${cell.title}</p>
                        <p class="tt-block-loc">${cell.location}</p>
                    </div>`;
                } else {
                    html += `<div class="tt-cell ${isLunch ? 'tt-cell-lunch' : ''}" data-day-key="${dayKey}" style="grid-column:${col}; grid-row:${row};">
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

        const pubTime = localStorage.getItem(`staffsync_published_time_${dept}_1_${year}`);
        const pubNote = pubTime ? ` · Live published timetable (synced at ${pubTime})` : '';

        document.getElementById('stCaption').textContent =
            `${dept.toUpperCase()} Y${year} schedule · ★ = your courses · empty cells = students are free${pubNote}`;

        updateDayView(true);
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
