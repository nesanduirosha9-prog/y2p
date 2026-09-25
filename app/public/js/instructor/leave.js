// Instructor Leave JS: the Upcoming Leave and Leave History tables (history
// with date-range filtering) and the Request Leave panel (multi-date calendar
// picker + partial-day toggle) all render from the `leaveData` JSON payload
// embedded by the view — same JSON-payload + client-render approach as
// messages.js. DOM-only demo — nothing persists past a reload.
document.addEventListener('DOMContentLoaded', () => {
    const dataEl = document.getElementById('leaveData');
    if (!dataEl) return;

    const payload = JSON.parse(dataEl.textContent);
    const TODAY = payload.today;
    let leaves = payload.records;
    let nextId = Math.max(0, ...leaves.map(l => l.id)) + 1;

    const INSTRUCTORS = payload.instructors || [];
    const CURRENT_USER = payload.currentUser || '';
    // Only junior instructors, excluding the logged-in user
    const availableInstructors = INSTRUCTORS.filter(i => i.code !== CURRENT_USER);

    function isUpcoming(l) { return !l.cancelled && l.dates.some(d => d >= TODAY); }
    function isHistory(l) { return l.cancelled || l.dates.every(d => d < TODAY); }

    function sortedDates(l) { return [...l.dates].sort(); }

    function fmtTime(t) {
        if (!t) return '';
        const [h, m] = t.split(':').map(Number);
        const suffix = h >= 12 ? 'PM' : 'AM';
        const hour12 = h % 12 || 12;
        return `${hour12}:${String(m).padStart(2, '0')} ${suffix}`;
    }

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function isPartial(l) { return !!(l.timeFrom && l.timeTo); }

    const DAY = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    /** "Mon 12 Oct 2026" from an ISO date, read as a local calendar day. */
    function longDate(iso) {
        const [y, m, d] = iso.split('-').map(Number);
        const dt = new Date(y, m - 1, d);
        return `${DAY[dt.getDay()]} ${d} ${MON[m - 1]} ${y}`;
    }

    function dateCell(l) {
        const sorted = sortedDates(l);
        if (sorted.length === 1) return esc(longDate(sorted[0]));
        return `${esc(longDate(sorted[0]))}<div class="lv-cell-sub">to ${esc(longDate(sorted[sorted.length - 1]))}</div>`;
    }

    function durationCell(l) {
        if (isPartial(l)) {
            const [fh, fm] = l.timeFrom.split(':').map(Number);
            const [th, tm] = l.timeTo.split(':').map(Number);
            const hrs = Math.round(((th * 60 + tm) - (fh * 60 + fm)) / 6) / 10;
            return `${fmtTime(l.timeFrom)} – ${fmtTime(l.timeTo)}<div class="lv-cell-sub">Part day · ${hrs} hrs</div>`;
        }
        const n = l.dates.length;
        return `${n} full day${n === 1 ? '' : 's'}`;
    }

    /** Who covers which day: one line per date, code badge plus name. */
    function coverCell(l) {
        let staffList = [];
        if (Array.isArray(l.cover_staff) && l.cover_staff.length > 0) {
            staffList = l.cover_staff;
        } else if (l.perDayCover && Object.keys(l.perDayCover).length > 0) {
            staffList = Object.entries(l.perDayCover).map(([d, c]) => ({ date: d, code: c.code, name: c.name }));
        } else if (l.cover) {
            const matched = INSTRUCTORS.find(i => i.name === l.cover || i.code === l.cover);
            if (!matched) return esc(l.cover);
            staffList = [{ code: matched.code, name: matched.name, date: '' }];
        }
        staffList = staffList.filter(item => item && item.code);
        if (!staffList.length) return '<span class="lv-cell-sub">—</span>';

        return [...staffList]
            .sort((x, y) => (x.date || '').localeCompare(y.date || ''))
            .map(item => `
                <div class="lv-cover-line">
                    ${item.date ? `<span class="lv-cover-date">${esc(longDate(item.date).slice(0, -5))}</span>` : ''}
                    ${codeBadge(item.code, 'staff', { title: item.name || item.code })}
                    <span>${esc(item.name || item.code)}</span>
                </div>`).join('');
    }

    function reasonCell(l) {
        return l.reason && l.reason !== '—' ? esc(l.reason) : '<span class="lv-cell-sub">—</span>';
    }

    // ---- Upcoming / History rendering ----

    function renderUpcoming() {
        const tbody = document.getElementById('lvUpcomingList');
        const upcoming = leaves.filter(isUpcoming);
        document.getElementById('lvUpcomingCount').textContent =
            upcoming.length + (upcoming.length === 1 ? ' request' : ' requests');

        if (!upcoming.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="dir-empty">No upcoming leave.</td></tr>';
            return;
        }

        tbody.innerHTML = upcoming.map(l => {
            const canCancel = sortedDates(l)[0] >= TODAY;
            return `
                <tr>
                    <td><strong>${esc(l.type)}</strong></td>
                    <td>${dateCell(l)}</td>
                    <td>${durationCell(l)}</td>
                    <td>${reasonCell(l)}</td>
                    <td>${coverCell(l)}</td>
                    <td style="text-align: right;">
                        ${canCancel ? `<button type="button" class="btn-secondary-sm" data-cancel-id="${l.id}">Cancel</button>` : ''}
                    </td>
                </tr>`;
        }).join('');
    }

    document.getElementById('lvUpcomingList').addEventListener('click', (e) => {
        const btn = e.target.closest('[data-cancel-id]');
        if (!btn) return;
        const id = parseInt(btn.dataset.cancelId, 10);
        leaves = leaves.map(l => l.id === id ? { ...l, cancelled: true } : l);
        renderAll();
        window.ttToast?.('Leave cancelled.', { icon: 'fa-circle-check' });
    });

    function renderHistory() {
        const from = document.getElementById('lvFilterFrom').value;
        const to = document.getElementById('lvFilterTo').value;
        document.getElementById('lvClearFilter').hidden = !(from || to);

        const hist = leaves.filter(isHistory).filter(l => {
            const sorted = sortedDates(l);
            const first = sorted[0], last = sorted[sorted.length - 1];
            if (from && last < from) return false;
            if (to && first > to) return false;
            return true;
        });
        document.getElementById('lvHistoryCount').textContent =
            hist.length + (hist.length === 1 ? ' request' : ' requests');

        const tbody = document.getElementById('lvHistoryBody');
        if (!hist.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="dir-empty">No leave in this range.</td></tr>';
            return;
        }

        tbody.innerHTML = hist.map(l => `
            <tr>
                <td><strong>${esc(l.type)}</strong></td>
                <td>${dateCell(l)}</td>
                <td>${durationCell(l)}</td>
                <td>${reasonCell(l)}</td>
                <td>${coverCell(l)}</td>
                <td style="text-align: right;">
                    ${l.cancelled
                        ? '<span class="pill pill-muted">Cancelled</span>'
                        : '<span class="pill pill-active">Taken</span>'}
                </td>
            </tr>`).join('');
    }

    document.getElementById('lvFilterFrom').addEventListener('input', renderHistory);
    document.getElementById('lvFilterTo').addEventListener('input', renderHistory);
    document.getElementById('lvClearFilter').addEventListener('click', () => {
        document.getElementById('lvFilterFrom').value = '';
        document.getElementById('lvFilterTo').value = '';
        renderHistory();
    });

    function renderAll() {
        renderUpcoming();
        renderHistory();
    }

    // ---- Request Leave panel (docked, not a modal — see lvRequestPanel) ----
    const panel = document.getElementById('lvRequestPanel');
    const openBtn = document.getElementById('requestLeaveBtn');
    const closeBtn = document.getElementById('closeLeaveModal');
    const cancelBtn = document.getElementById('cancelLeaveModal');
    if (!panel || !openBtn) { renderAll(); return; }

    let viewDate = new Date();
    let selectedDates = [];
    let isPartialDay = false;
    let perDayCover = {}; // { 'YYYY-MM-DD': { code: 'MKO', name: 'Mr. Kojo Amoah' } }

    function fmt(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function formatDayLabel(dateStr) {
        const [y, m, d] = dateStr.split('-').map(Number);
        const dateObj = new Date(y, m - 1, d);
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        return `${dateStr} (${days[dateObj.getDay()]})`;
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
            const classes = ['lv-day'];
            if (selectedDates.includes(dateStr)) classes.push('lv-day-selected');
            if (dateStr === TODAY) classes.push('lv-day-today');
            html += `<button type="button" class="${classes.join(' ')}" data-date="${dateStr}">${d}</button>`;
        }
        grid.innerHTML = html;

        grid.querySelectorAll('.lv-day:not(.lv-day-empty)').forEach(btn => {
            btn.addEventListener('click', () => {
                const dateStr = btn.dataset.date;
                const idx = selectedDates.indexOf(dateStr);
                if (idx > -1) selectedDates.splice(idx, 1); else selectedDates.push(dateStr);
                renderCalendar();
                renderSelectedChips();
                renderPerDayCoverCards();
                validateForm();
            });
        });
    }

    function renderSelectedChips() {
        const host = document.getElementById('lvSelectedDates');
        const badge = document.getElementById('lvDateCountBadge');
        selectedDates.sort();

        if (badge) {
            if (selectedDates.length > 0) {
                badge.textContent = `${selectedDates.length} ${selectedDates.length === 1 ? 'Day' : 'Days'}`;
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        }

        host.innerHTML = selectedDates.map(d =>
            `<span class="lv-date-chip"><span><i class="fa-regular fa-calendar"></i> ${d}</span> <button type="button" data-date="${d}" title="Remove date"><i class="fa-solid fa-xmark"></i></button></span>`
        ).join('');
        host.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = selectedDates.indexOf(btn.dataset.date);
                if (idx > -1) selectedDates.splice(idx, 1);
                renderCalendar();
                renderSelectedChips();
                renderPerDayCoverCards();
                validateForm();
            });
        });
    }

    function renderPerDayCoverCards() {
        const container = document.getElementById('lvPerDayCoverContainer');
        const applyAllBtn = document.getElementById('lvApplyAllBtn');
        if (!container) return;

        // Purge unselected dates from perDayCover dictionary
        Object.keys(perDayCover).forEach(d => {
            if (!selectedDates.includes(d)) delete perDayCover[d];
        });

        if (selectedDates.length === 0) {
            container.innerHTML = `
                <div class="lv-no-dates-cover-hint" id="lvNoDatesCoverHint">
                    <i class="fa-regular fa-calendar-check"></i> Select dates from the calendar above to assign cover instructors.
                </div>
            `;
            if (applyAllBtn) applyAllBtn.style.display = 'none';
            return;
        }

        selectedDates.sort();

        const hasAnyAssignment = selectedDates.some(d => !!perDayCover[d]);
        if (applyAllBtn) {
            applyAllBtn.style.display = (selectedDates.length > 1 && hasAnyAssignment) ? 'inline-flex' : 'none';
        }

        let html = '<div class="lv-per-day-list">';
        selectedDates.forEach(d => {
            const assigned = perDayCover[d];
            const hasAssigned = !!assigned;

            let triggerHtml = '';
            if (hasAssigned) {
                triggerHtml = `
                    <span class="lv-combobox-selected-text">
                        <span class="code-badge code-badge--staff" style="margin-right: 6px;">${esc(assigned.code)}</span>
                        <strong>${esc(assigned.name)}</strong>
                    </span>
                    <button type="button" class="lv-combobox-clear-btn" data-clear-date="${esc(d)}" title="Clear assignment"><i class="fa-solid fa-xmark"></i></button>
                `;
            } else {
                triggerHtml = `
                    <span class="lv-combobox-selected-text">
                        <span class="lv-combobox-placeholder"><i class="fa-solid fa-user-plus" style="margin-right: 6px;"></i>Select cover instructor...</span>
                    </span>
                    <i class="fa-solid fa-chevron-down lv-combobox-chevron"></i>
                `;
            }

            const itemsHtml = availableInstructors.map(inst => {
                const isSel = hasAssigned && assigned.code === inst.code;
                return `
                    <div class="lv-combobox-item ${isSel ? 'selected' : ''}" data-date="${esc(d)}" data-code="${esc(inst.code)}" data-name="${esc(inst.name)}">
                        <span class="code-badge code-badge--staff">${esc(inst.code)}</span>
                        <span class="lv-combobox-name">${esc(inst.name)}</span>
                        ${isSel ? '<i class="fa-solid fa-check" style="color: #2563eb; font-size: 11px;"></i>' : ''}
                    </div>
                `;
            }).join('');

            html += `
                <div class="lv-per-day-card" data-date="${esc(d)}">
                    <div class="lv-per-day-card-header">
                        <span class="lv-card-date-badge"><i class="fa-regular fa-calendar"></i> ${esc(formatDayLabel(d))}</span>
                        ${hasAssigned ? '<span style="font-size: 11px; color: #10b981; font-weight: 700;"><i class="fa-solid fa-circle-check"></i> Assigned</span>' : '<span style="font-size: 11px; color: #ef4444; font-weight: 700;"><i class="fa-solid fa-circle-exclamation"></i> Required</span>'}
                    </div>
                    <div class="lv-combobox" data-date="${esc(d)}">
                        <div class="lv-combobox-trigger" tabindex="0">
                            ${triggerHtml}
                        </div>
                        <div class="lv-combobox-dropdown" hidden>
                            <div class="lv-combobox-search-wrap">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" class="lv-combobox-search-input" placeholder="Search instructor by name or code..." autocomplete="off">
                            </div>
                            <div class="lv-combobox-menu">
                                ${itemsHtml}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        container.innerHTML = html;
        validateForm();
    }

    // Combobox Event Delegation on Container
    const coverContainer = document.getElementById('lvPerDayCoverContainer');
    coverContainer?.addEventListener('click', (e) => {
        // Clear assignment
        const clearBtn = e.target.closest('.lv-combobox-clear-btn');
        if (clearBtn) {
            e.stopPropagation();
            const dateToClear = clearBtn.dataset.clearDate;
            if (dateToClear) {
                delete perDayCover[dateToClear];
                renderPerDayCoverCards();
                validateForm();
            }
            return;
        }

        // Click on trigger
        const trigger = e.target.closest('.lv-combobox-trigger');
        if (trigger) {
            const combobox = trigger.closest('.lv-combobox');
            const dropdown = combobox?.querySelector('.lv-combobox-dropdown');
            const wasOpen = combobox.classList.contains('open');

            // Close all open comboboxes
            document.querySelectorAll('.lv-combobox.open').forEach(cb => {
                cb.classList.remove('open');
                const dd = cb.querySelector('.lv-combobox-dropdown');
                if (dd) dd.hidden = true;
            });

            if (!wasOpen && dropdown) {
                combobox.classList.add('open');
                dropdown.hidden = false;
                const searchInput = dropdown.querySelector('.lv-combobox-search-input');
                if (searchInput) {
                    searchInput.value = '';
                    // Reset item visibility
                    dropdown.querySelectorAll('.lv-combobox-item').forEach(it => { it.style.display = ''; });
                    setTimeout(() => searchInput.focus(), 50);
                }
            }
            return;
        }

        // Click on item in dropdown
        const item = e.target.closest('.lv-combobox-item');
        if (item) {
            const d = item.dataset.date;
            const code = item.dataset.code;
            const name = item.dataset.name;

            if (code && name) {
                perDayCover[d] = { code, name };
            } else {
                delete perDayCover[d];
            }

            renderPerDayCoverCards();
            return;
        }
    });

    // Combobox Search Filtering
    coverContainer?.addEventListener('input', (e) => {
        const searchInput = e.target.closest('.lv-combobox-search-input');
        if (!searchInput) return;

        const q = searchInput.value.trim().toLowerCase();
        const menu = searchInput.closest('.lv-combobox-dropdown')?.querySelector('.lv-combobox-menu');
        if (!menu) return;

        let visibleCount = 0;
        menu.querySelectorAll('.lv-combobox-item:not(.lv-item-clear)').forEach(it => {
            const code = (it.dataset.code || '').toLowerCase();
            const name = (it.dataset.name || '').toLowerCase();
            const match = !q || code.includes(q) || name.includes(q);
            it.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        let emptyMsg = menu.querySelector('.lv-combobox-empty');
        if (visibleCount === 0 && q) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('div');
                emptyMsg.className = 'lv-combobox-empty';
                emptyMsg.textContent = 'No matching instructors found.';
                menu.appendChild(emptyMsg);
            }
            emptyMsg.style.display = 'block';
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }
    });

    // Close open dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.lv-combobox')) {
            document.querySelectorAll('.lv-combobox.open').forEach(cb => {
                cb.classList.remove('open');
                const dd = cb.querySelector('.lv-combobox-dropdown');
                if (dd) dd.hidden = true;
            });
        }
    });

    // Apply first selected instructor to all dates
    const applyAllBtn = document.getElementById('lvApplyAllBtn');
    applyAllBtn?.addEventListener('click', () => {
        const firstAssignedDate = selectedDates.find(d => !!perDayCover[d]);
        if (!firstAssignedDate) return;
        const firstCover = perDayCover[firstAssignedDate];

        selectedDates.forEach(d => {
            perDayCover[d] = { ...firstCover };
        });

        renderPerDayCoverCards();
        window.ttToast?.(`Applied ${firstCover.name} as cover for all selected days.`, { icon: 'fa-circle-check' });
    });

    document.getElementById('lvPrevMonth').addEventListener('click', () => {
        viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() - 1, 1);
        renderCalendar();
    });
    document.getElementById('lvNextMonth').addEventListener('click', () => {
        viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 1);
        renderCalendar();
    });
    function updateTimePreview() {
        const from = document.getElementById('lvTimeFrom')?.value;
        const to = document.getElementById('lvTimeTo')?.value;
        const previewEl = document.getElementById('lvTimePreviewText');
        if (!previewEl) return;
        if (from && to) {
            const [fh, fm] = from.split(':').map(Number);
            const [th, tm] = to.split(':').map(Number);
            const diffMins = (th * 60 + tm) - (fh * 60 + fm);
            const hrs = (diffMins / 60).toFixed(diffMins % 60 === 0 ? 0 : 1);
            const hrsLabel = diffMins > 0 ? ` (${hrs} hr${hrs !== '1' ? 's' : ''})` : '';
            previewEl.innerHTML = `<i class="fa-regular fa-clock"></i> ${fmtTime(from)} &ndash; ${fmtTime(to)}${hrsLabel}`;
        }
    }
    document.getElementById('lvTimeFrom')?.addEventListener('input', updateTimePreview);
    document.getElementById('lvTimeTo')?.addEventListener('input', updateTimePreview);

    // Leave Duration Segment (Full Day vs Time Range)
    const durationSeg = document.getElementById('lvDurationSeg');
    durationSeg?.addEventListener('click', (e) => {
        const btn = e.target.closest('.lv-seg-btn, .seg-btn');
        if (!btn) return;
        durationSeg.querySelectorAll('.lv-seg-btn, .seg-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        isPartialDay = (btn.dataset.duration === 'partial');
        const timeRow = document.getElementById('lvTimeInputsRow');
        if (timeRow) timeRow.hidden = !isPartialDay;
        if (isPartialDay) updateTimePreview();
    });

    function validateForm() {
        const type = document.getElementById('lvType')?.value;
        const hasDates = selectedDates.length > 0;
        const allCovered = hasDates && selectedDates.every(d => !!perDayCover[d]);
        const ok = !!(type && hasDates && allCovered);
        const submitBtn = document.getElementById('submitLeaveRequest');
        if (submitBtn) submitBtn.disabled = !ok;
    }
    document.getElementById('lvType')?.addEventListener('input', validateForm);
    document.getElementById('lvReason')?.addEventListener('input', validateForm);

    // The panel floats over the page (.floating-panel in components.css),
    // pinned top/right/bottom — it sizes itself, nothing to measure here.
    function openPanel() {
        selectedDates = [];
        isPartialDay = false;
        perDayCover = {};
        if (document.getElementById('lvType')) document.getElementById('lvType').value = '';
        if (document.getElementById('lvReason')) document.getElementById('lvReason').value = '';
        if (document.getElementById('lvTimeFrom')) document.getElementById('lvTimeFrom').value = '08:00';
        if (document.getElementById('lvTimeTo')) document.getElementById('lvTimeTo').value = '12:00';

        if (durationSeg) {
            durationSeg.querySelectorAll('.lv-seg-btn, .seg-btn').forEach(b => {
                b.classList.toggle('active', b.dataset.duration === 'full');
            });
        }
        const timeRow = document.getElementById('lvTimeInputsRow');
        if (timeRow) timeRow.hidden = true;

        viewDate = new Date();
        renderCalendar();
        renderSelectedChips();
        renderPerDayCoverCards();
        updateTimePreview();
        validateForm();
        panel.hidden = false;
        document.body.classList.add('lv-panel-open');
    }
    function closePanel() {
        panel.hidden = true;
        document.body.classList.remove('lv-panel-open');
    }

    const backBtn = document.getElementById('lvBackBtn');

    openBtn.addEventListener('click', openPanel);
    closeBtn.addEventListener('click', closePanel);
    cancelBtn.addEventListener('click', closePanel);
    backBtn?.addEventListener('click', closePanel);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && panel && !panel.hidden) {
            closePanel();
        }
    });
    // The panel floats over the page (.floating-panel): a click on its
    // backdrop (body::after) targets <body> itself.
    document.body.addEventListener('click', (e) => {
        if (e.target === document.body && panel && !panel.hidden) closePanel();
    });

    document.getElementById('submitLeaveRequest')?.addEventListener('click', () => {
        const type = document.getElementById('lvType')?.value;
        const reason = document.getElementById('lvReason')?.value.trim();
        const hasDates = selectedDates.length > 0;
        const allCovered = hasDates && selectedDates.every(d => !!perDayCover[d]);

        if (!type || !hasDates) {
            window.ttToast?.('Please select a leave type and dates.', { icon: 'fa-circle-exclamation' });
            return;
        }

        if (!allCovered) {
            window.ttToast?.('Please assign a cover instructor for each selected date.', { icon: 'fa-circle-exclamation' });
            return;
        }

        const coverStaffList = selectedDates.map(d => ({
            date: d,
            code: perDayCover[d].code,
            name: perDayCover[d].name
        }));

        const uniqueNames = Array.from(new Set(coverStaffList.map(c => c.name)));
        const coverSummary = uniqueNames.join(', ');

        const rec = {
            id: nextId++,
            type,
            dates: [...selectedDates].sort(),
            reason: reason || '—',
            cover: coverSummary,
            cover_staff: coverStaffList,
            perDayCover: { ...perDayCover },
            cancelled: false,
        };
        if (isPartialDay) {
            rec.timeFrom = document.getElementById('lvTimeFrom')?.value || '08:00';
            rec.timeTo = document.getElementById('lvTimeTo')?.value || '12:00';
        }
        leaves = [rec, ...leaves];
        closePanel();
        renderAll();
        window.ttToast?.('Leave request submitted successfully.', { icon: 'fa-circle-check' });
    });

    renderAll();
});
