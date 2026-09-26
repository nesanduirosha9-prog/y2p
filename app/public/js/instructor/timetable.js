document.addEventListener('DOMContentLoaded', () => {
    let course_code_clicked = '';
    const sessionBlocks = document.querySelectorAll('.tt-block');
    const grid = document.getElementById('timetableGrid');
    const svgOverlay = document.getElementById('connectionLines');

    // Day to CSS Grid Column mapping (Mon = 2, Tue = 3, etc.)
    const daysMap = { 'mon': 2, 'tue': 3, 'wed': 4, 'thu': 5, 'fri': 6 };
    // Distinct colors for different groups
    // const groupColors = ['#e63946', '#2a9d8f', '#e9c46a', '#f4a261', '#9c27b0'];
    const groupColors = [


        '#075985', // Deep Sky Blue
        '#713F12', // Dark Gold
        '#831843', // Deep Rose
        '#374151'  // Charcoal
    ];
    sessionBlocks.forEach(block => {
        block.addEventListener('click', async function () {

            // If assign mode is on, open the staff panel instead of the normal flow
            if (assignModeActive) {
                sessionBlocks.forEach(b => {
                    b.classList.remove('clicked-in-assign-mode');
                });
                block.classList.add('clicked-in-assign-mode');
                staffSearchSend.disabled = false;
                // openStaffAssignPanel({
                //     courseCode: this.dataset.courseCode,
                //     dayOfWeek: this.dataset.dayOfWeek,
                //     startHour: this.dataset.startHour
                // });
                return;
            }
            staffSearchSend.disabled = true;


            const payload = {
                course_code: this.dataset.courseCode,
                day_of_week: this.dataset.dayOfWeek,
                start_hour: this.dataset.startHour
            };

            try {
                const response = await fetch('http://localhost:8081/timetable/schedule', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (response.ok) {
                    course_code_clicked = block.dataset.courseCode;
                    const data = await response.json();
                    if (data.status === 'success') {
                        renderAvailableOptions(data.available);
                    }

                }
            } catch (error) {
                console.error('Error fetching details:', error);
            }
        });
    });
    //-------------------------------------------------------
    // --- NEW PANEL LOGIC ---
    const requestPanel = document.getElementById('slotRequestPanel');
    const closeBtn = document.getElementById('closePanelBtn');
    const requestForm = document.getElementById('slotRequestForm');
    const weeksInput = document.getElementById('reqWeeks');
    const weeksHint = document.getElementById('weeksHint');

    let currentSelectedGroup = null; // Store the clicked group data for submission

    // Close panel when clicking the X
    closeBtn.addEventListener('click', () => {
        requestPanel.classList.remove('open');
    });

    //-------------------------REQUEST detail panel---------------------------
    const detailPanel = document.getElementById('requestDetailPanel');
    const closeDetailBtn = document.getElementById('closeDetailPanelBtn');
    const detailForm = document.getElementById('requestDetailForm');
    const detailRequestId = document.getElementById('detailRequestId');
    const detailWeeks = document.getElementById('detailWeeks');
    const detailDescription = document.getElementById('detailDescription');
    const detailStatusBadge = document.getElementById('detailStatusBadge');
    const deleteRequestBtn = document.getElementById('deleteRequestBtn');
    const detailWeeksHint = document.getElementById('detailWeeksHint');
    let currentDetailGroup = null; // slots belonging to the request currently open in the panel

    closeDetailBtn.addEventListener('click', () => {
        detailPanel.classList.remove('open');
    });






    // UPDATE request
    detailForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const currentWeeks = parseInt(detailWeeks.getAttribute('max'));
        const enteredWeeks = parseInt(detailWeeks.value);

        if (enteredWeeks > currentWeeks) {
            ttToast.warning(`You can only reduce weeks to ${currentWeeks} or fewer, not increase it.`);
            return;
        }
        if (enteredWeeks < 1 || isNaN(enteredWeeks)) {
            ttToast.warning('Please enter a valid number of weeks.');
            return;
        }

        const payload = {
            request_id: detailRequestId.value,
            for_how_many_weeks: detailWeeks.value,
            description: detailDescription.value
        };

        try {
            const response = await fetch('http://localhost:8081/timetable/updateScheduleRequest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                window.location.reload();
                // ttToast('Request updated successfully!');
                // detailPanel.classList.remove('open');
                // Optional: refresh the description shown on the block(s) without a full reload
                if (currentDetailGroup) {
                    currentDetailGroup.forEach(s => { s.description = payload.description; s.weeks = payload.for_how_many_weeks; });
                }
            } else {
                ttToast.error('Error updating request.');
            }
        } catch (error) {
            console.error('Update error:', error);
            ttToast.error('Error updating request.');
        }
    });

    // DELETE (cancel) request
    deleteRequestBtn.addEventListener('click', async () => {
        if (!confirm('Cancel this request? This cannot be undone.')) return;

        const payload = { request_id: detailRequestId.value };

        try {
            const response = await fetch('http://localhost:8081/timetable/deleteScheduleRequest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                window.location.reload();
                //ttToast('Request cancelled.');
                //detailPanel.classList.remove('open');

                // Remove the now-deleted blocks and their connecting line(s) from the DOM
                if (currentDetailGroup) {
                    document.querySelectorAll(`.requested-slot`).forEach(el => {
                        // matches blocks belonging to this request_id via title/data, adjust if you add data-request-id
                    });
                }
                location.reload(); // simplest reliable way to resync grid + lines after a delete
            } else {
                ttToast.error('Error cancelling request.');
            }
        } catch (error) {
            console.error('Delete error:', error);
            ttToast.error('Error cancelling request.');
        }
    });

    //---------------------------calander include week
    // Tracks the Monday of whichever week is currently displayed
    let currentWeekMonday = getMondayOf(new Date()); // default: this week

    function getMondayOf(date) {
        const dow = (date.getDay() + 6) % 7; // Monday=0..Sunday=6
        const monday = new Date(date);
        monday.setDate(date.getDate() - dow);
        monday.setHours(0, 0, 0, 0);
        return monday;
    }

    function formatDateForApi(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`; // YYYY-MM-DD
    }

    document.addEventListener('weekChanged', (e) => {
        const { weekDates } = e.detail;
        currentWeekMonday = weekDates.mon; // Date object for Monday of the newly selected week
    });

    //--------------------------------------------------------------------------

    // Form submission
    requestForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!currentSelectedGroup) return;

        // Map the group array to exactly what the backend expects
        const slotsPayload = currentSelectedGroup.map(slot => ({
            day_of_week: slot.day_of_week,
            start_hour: slot.start_hour,
            duration_hours: slot.duration_hours,
        }));

        const payload = {
            slots: slotsPayload,
            for_how_many_weeks: weeksInput.value,
            description: document.getElementById('reqDescription').value,
            course_code: course_code_clicked,
            week_start_date: formatDateForApi(currentWeekMonday)
        };

        try {
            const response = await fetch('http://localhost:8081/timetable/scheduleRequest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                // ttToast('Request submitted successfully!');
                window.location.reload();
                requestPanel.classList.remove('open');
                requestForm.reset();

                // Optional: clear the suggested blocks and lines
                document.querySelectorAll('.available-slot').forEach(el => el.remove());
                document.getElementById('connectionLines').innerHTML = '';
            } else {
                ttToast.error('Error submitting request.');
            }
        } catch (error) {
            console.error('Submission error:', error);
        }
    });


    //line-draw factor out
    function drawGroupConnections(domElements, color, dashArray = '8, 8') {
        setTimeout(() => {
            if (domElements.length > 1) {
                let pathData = '';
                domElements.forEach((el, i) => {
                    const x = el.offsetLeft + (el.offsetWidth / 2);
                    const y = el.offsetTop + (el.offsetHeight / 2);
                    pathData += (i === 0 ? `M ${x} ${y} ` : `L ${x} ${y} `);
                });

                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', pathData);
                path.setAttribute('stroke', color);
                path.setAttribute('stroke-width', '3');
                path.setAttribute('stroke-dasharray', dashArray);
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke-linecap', 'round');

                svgOverlay.appendChild(path);
            }
        }, 50);
    }

    //-------------------------------------------------------

    function renderAvailableOptions(availableGroups) {
        // 1. Clear any previously drawn options and lines
        document.querySelectorAll('.available-slot').forEach(el => el.remove());
        svgOverlay.innerHTML = '';

        // 2. Iterate through groups (inner arrays)
        availableGroups.forEach((group, index) => {
            const color = groupColors[index % groupColors.length];
            const domElements = []; // Store rendered elements to calculate line coordinates later

            // 3. Render each block in the group
            group.forEach(slot => {
                const col = daysMap[slot.day_of_week];
                const row = parseInt(slot.start_hour) - 8 + 2; // (Hour 8 is Grid Row 2)
                const duration = parseInt(slot.duration_hours);

                const div = document.createElement('div');
                div.className = 'tt-block available-slot';
                div.style.gridColumn = col;
                div.style.gridRow = `${row} / span ${duration}`;
                div.style.borderColor = color;
                div.style.color = color;
                // div.innerHTML = `<span>#${index + 1}</span>`;
                // Display the 'available_weeks' from your backend response
                // We can format it slightly, e.g., capitalizing 'sem' to 'Sem' or showing 'Wk 1'
                let displayTitle = slot.available_weeks === 'sem' ? 'SEM' : `WK ${slot.available_weeks}`;
                div.innerHTML = `<span style="font-size: 0.8rem;">${displayTitle}</span>`;

                // CLICK EVENT: Open Panel and set constraints
                div.addEventListener('click', () => {
                    currentSelectedGroup = group;
                    requestPanel.classList.add('open');

                    // Reset the form
                    requestForm.reset();

                    // Calculate limits based on 'sem' or a number
                    if (slot.available_weeks === 'sem') {
                        weeksInput.removeAttribute('max'); // No hard HTML limit
                        weeksHint.innerText = 'Available for the full semester.';
                    } else {
                        const maxW = parseInt(slot.available_weeks);
                        weeksInput.setAttribute('max', maxW);
                        weeksHint.innerText = `Maximum allowed: ${maxW} weeks.`;
                    }
                });

                grid.appendChild(div);
                domElements.push(div);
            });

            // 4. Draw connecting lines between blocks in the same group
            // We use requestAnimationFrame to ensure the browser has positioned the divs before we calculate centers
            // 4. Draw connecting lines between blocks in the same group
            // We use setTimeout to ensure the browser has fully applied the CSS Grid layout
            drawGroupConnections(domElements, color);
        });
    }
    const statusColors = {
        pending: '#f4a261',
        approved: '#2a9d8f',
        rejected: '#e63946'
    };

    function renderRequestedSlots(requestGroups) {
        requestGroups.forEach(group => {
            const status = group[0].status;
            const color = statusColors[status] || '#9c27b0';
            const domElements = [];

            group.forEach(slot => {
                const col = daysMap[slot.day_of_week];
                const row = parseInt(slot.start_hour) - 8 + 2;
                const duration = parseInt(slot.duration_hours);

                const div = document.createElement('div');
                div.className = `tt-block requested-slot status-${status}`;
                div.style.gridColumn = col;
                div.style.gridRow = `${row} / span ${duration}`;
                div.style.borderColor = color;
                div.style.color = color;
                div.title = `Request #${slot.request_id} — ${status}`;
                const description = slot.description || '';
                const shortDescription =
                    description.length > 10
                        ? description.substring(0, 10) + '...'
                        : description;
                div.innerHTML = `
                <p class="tt-block-title">${slot.course_code ? slot.course_code : 'Requested'}</p>
                <p class="tt-block-description">${shortDescription}</p>

            `;

                div.addEventListener('click', () => {
                    currentDetailGroup = group;

                    detailRequestId.value = slot.request_id;
                    detailWeeks.value = slot.weeks || '';
                    detailDescription.value = slot.description || '';

                    // Restrict "Number of Weeks" to current value or less
                    const currentWeeks = parseInt(slot.weeks) || 1;
                    detailWeeks.setAttribute('max', currentWeeks);
                    detailWeeks.setAttribute('min', 1); // keep a sane floor
                    detailWeeksHint.innerText = `Maximum allowed: ${currentWeeks} weeks (current value).`;

                    detailStatusBadge.textContent = status;
                    detailStatusBadge.className = `panel-status status-${status}`;

                    detailPanel.classList.add('open');
                });

                grid.appendChild(div);
                domElements.push(div);
            });

            // Solid line distinguishes a submitted request from a dashed "available option" line
            drawGroupConnections(domElements, color, 'none');
        });
    }

    const requestedDataEl = document.getElementById('requestedSlotsData');
    if (requestedDataEl) {
        try {
            const requestGroups = JSON.parse(requestedDataEl.textContent);
            renderRequestedSlots(requestGroups);
        } catch (err) {
            console.error('Failed to parse requested slots data:', err);
        }
    }
    //--------------------------------------------------
    const staffFabContainer = document.getElementById('staffFabContainer');

    // How far (px) to translate the container so its center lands on .dash-body's center
    function getDesktopCenterOffset() {
        const dashBody = document.querySelector('.dash-body');
        if (!dashBody) return 0;

        const dashRect = dashBody.getBoundingClientRect();
        const containerRect = staffFabContainer.getBoundingClientRect();

        const dashCenterX = dashRect.left + (dashRect.width / 2);
        const containerCenterX = containerRect.left + (containerRect.width / 2);

        return dashCenterX - containerCenterX;
    }

    function isDesktop() {
        return window.innerWidth > 768;
    }

    //assign staff dummy------------------------
    // --- STAFF ASSIGN MODE ---
    const staffFab = document.getElementById('staffFab');

    const staffSearchBar = document.getElementById('staffSearchBar');
    const staffSearchCancel = document.getElementById('staffSearchCancel');
    const staffSearchSend = document.getElementById('staffSearchSend');
    const staffSearchInput = document.getElementById('staffSearchInput');

    const staffPanel = document.getElementById('staffAssignPanel');
    const closeStaffPanelBtn = document.getElementById('closeStaffPanelBtn');
    const staffListContainer = document.getElementById('staffListContainer');
    const staffAssignForm = document.getElementById('staffAssignForm');
    const staffPanelSessionLabel = document.getElementById('staffPanelSessionLabel');
    const staffAssignDescription = document.getElementById('staffAssignDescription');

    let assignModeActive = false;
    let currentAssignSession = null; // dataset of the clicked session block

    // Dummy staff data — replace with a fetch() to your backend later
    const dummyStaff = [
        { id: 1, name: 'Alice Fernando', selected: true },
        { id: 2, name: 'Brian Silva', selected: true },
        { id: 3, name: 'Chamari Perera', selected: true },
        { id: 4, name: 'Dinesh Kumar', selected: true },
        { id: 5, name: 'Erandi Jayasuriya', selected: true },
        { id: 6, name: 'Farhan Iqbal', selected: false },
        { id: 7, name: 'Gayan Wickrama', selected: false },
        { id: 8, name: 'Hasini Ranatunga', selected: false }
    ];

    // staffFab.addEventListener('click', () => {
    //     assignModeActive = !assignModeActive;
    //     staffFab.classList.toggle('active', assignModeActive);

    //     // Visually mark session blocks as clickable targets while assign mode is on
    //     document.querySelectorAll('.tt-block[data-course-code]').forEach(el => {
    //         el.classList.toggle('assign-mode-active', assignModeActive);
    //     });
    // });

    function collapseSearchBar() {
        assignModeActive = false;

        staffSearchBar.classList.remove('open');
        // staffFabContainer.style.transform = 'translateX(0)';

        setTimeout(() => {
            staffSearchBar.classList.add('collapsed');
            staffFab.classList.remove('hidden');
        }, 280); // match CSS transition duration

        document.querySelectorAll('.tt-block.assign-mode-active').forEach(el => {
            el.classList.remove('assign-mode-active');
        });
    }
    staffFab.addEventListener('click', () => {
        enterAssignMode();
    });

    // Collapse: search bar -> FAB, exit assign mode
    staffSearchSend.addEventListener('click', () => {
        renderStaffSuggestedSlots(dummyStaffSlotGroups);
        exitAssignMode();
    });
    staffSearchCancel.addEventListener('click', () => {
        exitAssignMode();
    });

    closeStaffPanelBtn.addEventListener('click', () => {
        staffPanel.classList.remove('open');
    });
    function enterAssignMode() {
        assignModeActive = true;

        staffFab.classList.add('hidden');
        staffSearchBar.classList.remove('collapsed');


        // Measure AFTER the bar is unhidden (container width is now the bar's width),
        // so the offset centers the actual expanded bar, not the collapsed button.
        // if (isDesktop()) {
        //     const offset = getDesktopCenterOffset();
        //     staffFabContainer.style.transform = `translateX(${offset}px)`;
        // }

        // small timeout lets 'collapsed' (display:none) clear before the opacity/scale transition runs
        requestAnimationFrame(() => staffSearchBar.classList.add('open'));

        staffSearchInput.value = '';
        staffSearchInput.placeholder = 'Click a session to assign staff...';
        staffSearchSend.disabled = true;

        document.querySelectorAll('.tt-block[data-course-code]').forEach(el => {
            el.classList.add('assign-mode-active');
        });
    }

    function exitAssignMode() {
        collapseSearchBar();
        currentAssignSession = null;
        sessionBlocks.forEach(block => {
            block.classList.remove('clicked-in-assign-mode');
            block.classList.remove('assign-mode-active');
        });
    }




    function getInitials(name) {
        return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
    }

    function renderStaffList() {
        staffListContainer.innerHTML = '';
        dummyStaff.forEach(staff => {
            const item = document.createElement('label');
            item.className = 'staff-item';
            item.innerHTML = `
            <input type="checkbox" value="${staff.id}" ${staff.selected ? 'checked' : ''}>
            <span class="staff-avatar">${getInitials(staff.name)}</span>
            <span class="staff-name">${staff.name}</span>
        `;
            staffListContainer.appendChild(item);
        });
    }

    function openStaffAssignPanel(sessionData) {
        currentAssignSession = sessionData;
        staffPanelSessionLabel.textContent =
            `${sessionData.courseCode} — ${sessionData.dayOfWeek.toUpperCase()} ${sessionData.startHour}:00`;

        renderStaffList();
        staffAssignDescription.value = '';
        staffPanel.classList.add('open');
    }

    staffAssignForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const selectedIds = Array.from(
            staffListContainer.querySelectorAll('input[type="checkbox"]:checked')
        ).map(cb => parseInt(cb.value));

        const payload = {
            course_code: currentAssignSession.courseCode,
            day_of_week: currentAssignSession.dayOfWeek,
            start_hour: currentAssignSession.startHour,
            staff_ids: selectedIds,
            description: staffAssignDescription.value
        };

        console.log('Assign payload (dummy, not sent yet):', payload);
        ttToast.info(`Assigned ${selectedIds.length} staff member(s). (Not yet wired to backend)`);

        staffPanel.classList.remove('open');

        // Exit assign mode after a successful assignment
        assignModeActive = false;
        staffFab.classList.remove('active');
        document.querySelectorAll('.tt-block.assign-mode-active').forEach(el => {
            el.classList.remove('assign-mode-active');
        });
    });





    // Dummy connected staff-slot suggestions
    const dummyStaffSlotGroups = [
        [
            { day_of_week: 'tue', start_hour: 10, duration_hours: 1 },
            { day_of_week: 'tue', start_hour: 15, duration_hours: 1 }
        ],
        [
            { day_of_week: 'wed', start_hour: 8, duration_hours: 1 },
            { day_of_week: 'thu', start_hour: 14, duration_hours: 1 }
        ]
    ];
    let state_of_staff_assign_click = false;

    function renderStaffSuggestedSlots(groups) {
        // Clear any previously rendered suggestions and their paths
        document.querySelectorAll('.staff-suggested-slot').forEach(el => el.remove());
        document.querySelectorAll('.staff-suggested-path').forEach(el => el.remove());

        groups.forEach((group, index) => {
            const color = groupColors[index % groupColors.length];
            const groupId = `staffgrp-${index}`;
            const domElements = [];

            group.forEach(slot => {
                const col = daysMap[slot.day_of_week];
                const row = parseInt(slot.start_hour) - 8 + 2;
                const duration = parseInt(slot.duration_hours);

                const div = document.createElement('div');
                div.className = 'tt-block staff-suggested-slot';
                div.dataset.groupId = groupId;
                div.style.gridColumn = col;
                div.style.gridRow = `${row} / span ${duration}`;
                div.style.borderColor = color;
                div.style.color = color;
                div.innerHTML = `<span>Slot</span>`;

                div.addEventListener('click', () => {
                    state_of_staff_assign_click = true;
                    handleStaffSuggestedSlotClick(groupId);
                });

                grid.appendChild(div);
                domElements.push(div);
            });

            // Reuse the exact same line-drawing pattern as renderAvailableOptions,
            // tagging the path so it can be found and recolored on click
            setTimeout(() => {
                if (domElements.length > 1) {
                    let pathData = '';
                    domElements.forEach((el, i) => {
                        const x = el.offsetLeft + (el.offsetWidth / 2);
                        const y = el.offsetTop + (el.offsetHeight / 2);
                        pathData += (i === 0 ? `M ${x} ${y} ` : `L ${x} ${y} `);
                    });

                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    path.setAttribute('d', pathData);
                    path.setAttribute('stroke', color);
                    path.setAttribute('stroke-width', '4');
                    path.setAttribute('stroke-dasharray', '8, 8');
                    path.setAttribute('fill', 'none');
                    path.setAttribute('stroke-linecap', 'round');
                    path.classList.add('staff-suggested-path');
                    path.dataset.groupId = groupId;

                    svgOverlay.appendChild(path);
                }
            }, 50);
        });
    }
    staffSearchInput.oninput = function () {
        if (staffSearchInput.value !== '') {
            if (state_of_staff_assign_click) {
                staffSearchSend.disabled = false;
            }
        }
    }

    // Click behavior branches on assignModeActive
    function handleStaffSuggestedSlotClick(groupId) {
        if (assignModeActive) {
            // Color both connected slots and the connecting path black
            document.querySelectorAll(`.staff-suggested-slot[data-group-id="${groupId}"]`).forEach(el => {
                el.classList.add('staff-slot-selected');
            });


            const path = document.querySelector(`.staff-suggested-path[data-group-id="${groupId}"]`);
            if (path) {
                path.setAttribute('stroke', '#000');
            }
        } else {
            openStaffSlotInfoPopup();
        }
    }

    // --- Info popup (assignModeActive == false) ---
    const staffSlotInfoModal = document.getElementById('staffSlotInfoModal');
    const closeStaffSlotInfoModal = document.getElementById('closeStaffSlotInfoModal');

    function openStaffSlotInfoPopup() {
        staffSlotInfoModal.classList.add('open');
    }

    closeStaffSlotInfoModal.addEventListener('click', () => {
        staffSlotInfoModal.classList.remove('open');
    });

    staffSlotInfoModal.addEventListener('click', (e) => {
        if (e.target === staffSlotInfoModal) { // click outside the box closes it too
            staffSlotInfoModal.classList.remove('open');
        }
    });
});