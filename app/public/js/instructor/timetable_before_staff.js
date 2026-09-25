document.addEventListener('DOMContentLoaded', () => {
    let course_code_clicked = '';
    const sessionBlocks = document.querySelectorAll('.tt-block');
    const grid = document.getElementById('timetableGrid');
    const svgOverlay = document.getElementById('connectionLines');

    // Day to CSS Grid Column mapping (Mon = 2, Tue = 3, etc.)
    const daysMap = { 'mon': 2, 'tue': 3, 'wed': 4, 'thu': 5, 'fri': 6 };
    // Distinct colors for different groups
    const groupColors = ['#e63946', '#2a9d8f', '#e9c46a', '#f4a261', '#9c27b0'];

    sessionBlocks.forEach(block => {
        block.addEventListener('click', async function () {
            const payload = {
                course_code: this.dataset.courseCode,
                day_of_week: this.dataset.dayOfWeek,
                start_hour: this.dataset.startHour
            };

            try {
                const response = await fetch('http://localhost:8001/timetable/schedule', {
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
            alert(`You can only reduce weeks to ${currentWeeks} or fewer, not increase it.`);
            return;
        }
        if (enteredWeeks < 1 || isNaN(enteredWeeks)) {
            alert('Please enter a valid number of weeks.');
            return;
        }

        const payload = {
            request_id: detailRequestId.value,
            for_how_many_weeks: detailWeeks.value,
            description: detailDescription.value
        };

        try {
            const response = await fetch('http://localhost:8001/timetable/updateScheduleRequest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                window.location.reload();
                // alert('Request updated successfully!');
                // detailPanel.classList.remove('open');
                // Optional: refresh the description shown on the block(s) without a full reload
                if (currentDetailGroup) {
                    currentDetailGroup.forEach(s => { s.description = payload.description; s.weeks = payload.for_how_many_weeks; });
                }
            } else {
                alert('Error updating request.');
            }
        } catch (error) {
            console.error('Update error:', error);
            alert('Error updating request.');
        }
    });

    // DELETE (cancel) request
    deleteRequestBtn.addEventListener('click', async () => {
        if (!confirm('Cancel this request? This cannot be undone.')) return;

        const payload = { request_id: detailRequestId.value };

        try {
            const response = await fetch('http://localhost:8001/timetable/deleteScheduleRequest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                window.location.reload();
                //alert('Request cancelled.');
                //detailPanel.classList.remove('open');

                // Remove the now-deleted blocks and their connecting line(s) from the DOM
                if (currentDetailGroup) {
                    document.querySelectorAll(`.requested-slot`).forEach(el => {
                        // matches blocks belonging to this request_id via title/data, adjust if you add data-request-id
                    });
                }
                location.reload(); // simplest reliable way to resync grid + lines after a delete
            } else {
                alert('Error cancelling request.');
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert('Error cancelling request.');
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
            const response = await fetch('http://localhost:8001/timetable/scheduleRequest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                // alert('Request submitted successfully!');
                window.location.reload();
                requestPanel.classList.remove('open');
                requestForm.reset();

                // Optional: clear the suggested blocks and lines
                document.querySelectorAll('.available-slot').forEach(el => el.remove());
                document.getElementById('connectionLines').innerHTML = '';
            } else {
                alert('Error submitting request.');
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
                path.setAttribute('stroke-width', '4');
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
                div.innerHTML = `<span style="font-size: 1rem;">${displayTitle}</span>`;

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

});