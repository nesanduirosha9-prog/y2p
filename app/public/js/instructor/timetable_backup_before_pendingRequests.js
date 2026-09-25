document.addEventListener('DOMContentLoaded', () => {
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

    // Form submission
    requestForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!currentSelectedGroup) return;

        // Map the group array to exactly what the backend expects
        const slotsPayload = currentSelectedGroup.map(slot => ({
            day_of_week: slot.day_of_week,
            start_hour: slot.start_hour,
            duration_hours: slot.duration_hours
        }));

        const payload = {
            slots: slotsPayload,
            for_how_many_weeks: weeksInput.value,
            description: document.getElementById('reqDescription').value
        };

        try {
            const response = await fetch('http://localhost:8001/timetable/scheduleRequest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                alert('Request submitted successfully!');
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
                div.innerHTML = `<span style="font-size: 1.1rem;">${displayTitle}</span>`;

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
            setTimeout(() => {
                if (domElements.length > 1) {
                    let pathData = '';

                    domElements.forEach((el, i) => {
                        // offsetLeft/offsetTop get the exact coordinates relative to the parent Grid
                        const x = el.offsetLeft + (el.offsetWidth / 2);
                        const y = el.offsetTop + (el.offsetHeight / 2);

                        if (i === 0) {
                            // Move to the very first block's center
                            pathData += `M ${x} ${y} `;
                        } else {
                            // Draw a straight line to the subsequent block(s)
                            pathData += `L ${x} ${y} `;
                        }
                    });

                    // Create the SVG path
                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    path.setAttribute('d', pathData);
                    path.setAttribute('stroke', color);
                    path.setAttribute('stroke-width', '4');
                    path.setAttribute('stroke-dasharray', '8, 8');
                    path.setAttribute('fill', 'none');
                    path.setAttribute('stroke-linecap', 'round');

                    svgOverlay.appendChild(path);
                }
            }, 50); // 50ms delay guarantees the DOM grid is fully painted
        });
    }
});