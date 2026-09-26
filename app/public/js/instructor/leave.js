// Instructor Leave JS: the Upcoming leave / History tabs (each with type and
// date-range filters) and the Request / Edit Leave panel (multi-date
// calendar picker + partial-day toggle + a cover person per date), rendered
// from the `leaveData` payload that LeaveController::index() embeds.
//
// Leave is approved on paper outside the system, so saving only records it —
// there is no status. It stays in Upcoming until its last day has passed,
// then moves to History. Before its first day it can be edited or cancelled.
//
// Saves go to the server (LeaveController): POST /leave creates, PUT
// /leave/{id} edits, DELETE /leave/{id} cancels. Each success answers with
// the saved record, which replaces the local copy. Table cells come from
// js/leave_cells.js, shared with the Coordinator's Leave Requests page.
document.addEventListener('DOMContentLoaded', () => {
    const dataEl = document.getElementById('leaveData');
    if (!dataEl || !window.leaveCells) return;

    const cells = window.leaveCells;
    const payload = JSON.parse(dataEl.textContent);
    const TODAY = payload.today;
    let leaves = payload.records || [];

    // Who may cover: same rank, active, never the member themselves. Covers
    // share the member's rank, so they share its badge style too.
    const COVERS = payload.covers || [];
    const COVER_BADGE = payload.rank === 'senior' ? 'code-badge--lecturer' : 'code-badge--staff';

    function isUpcoming(l) { return l.end_date >= TODAY; }

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

    function toast(message, isError) {
        window.ttToast?.(message, { icon: isError ? 'fa-circle-exclamation' : 'fa-circle-check' });
    }

    /** fetch() + JSON, resolving to { success, message, ... } even on a network or parse failure. */
    function send(url, method, body) {
        return fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: body === undefined ? undefined : JSON.stringify(body),
        })
            .then(res => res.json().catch(() => ({ success: false, message: 'Unexpected response from the server.' })))
            .catch(() => ({ success: false, message: 'Network error. Please try again.' }));
    }

    function cellsOf(l) {
        return `<td>${cells.type(l)}</td>
                <td>${cells.dates(l, TODAY)}</td>
                <td>${cells.covers(l)}</td>
                <td>${cells.reason(l)}</td>`;
    }

    // ---- Upcoming / History tabs ----
    // Same layout as the Leave Requests page (js/leave_requests.js): one
    // table per tab, each with its own type / date-range filters.
    const page = document.getElementById('lvPage');
    const BLANK = { type: 'all', from: '', to: '' };
    const TABS = {
        upcoming: {
            pick: l => isUpcoming(l),
            order: (a, b) => a.start_date.localeCompare(b.start_date),
            empty: 'No upcoming leave. Use Request Leave to record some.',
            columns: 5,
            filter: { ...BLANK },
        },
        history: {
            pick: l => !isUpcoming(l),
            order: (a, b) => b.start_date.localeCompare(a.start_date),
            empty: 'No past leave yet.',
            columns: 4,
            filter: { ...BLANK },
        },
    };

    function matches(l, f) {
        if (f.type !== 'all' && l.leave_type !== f.type) return false;
        // Overlap with the picked range, not containment.
        if (f.from && l.end_date < f.from) return false;
        if (f.to && l.start_date > f.to) return false;
        return true;
    }

    function rowOf(key, l) {
        if (key === 'history') return `<tr>${cellsOf(l)}</tr>`;
        // Same rule as the server: edit or cancel only before the first day.
        const actions = l.start_date > TODAY
            ? `<button type="button" class="btn-secondary-sm" data-edit-id="${esc(l.id)}">Edit</button>
               <button type="button" class="btn-secondary-sm" data-cancel-id="${esc(l.id)}">Cancel</button>`
            : '';
        return `<tr>${cellsOf(l)}<td class="leave-actions">${actions}</td></tr>`;
    }

    function renderTab(key) {
        const tab = TABS[key];
        const all = leaves.filter(tab.pick).sort(tab.order);
        const list = all.filter(l => matches(l, tab.filter));
        const filtered = Object.keys(BLANK).some(k => tab.filter[k] !== BLANK[k]);

        page.querySelector(`[data-rows="${key}"]`).innerHTML = list.length
            ? list.map(l => rowOf(key, l)).join('')
            : `<tr><td colspan="${tab.columns}" class="leave-empty">${esc(filtered && all.length ? 'No leave matches these filters.' : tab.empty)}</td></tr>`;
        page.querySelector(`[data-summary="${key}"]`).textContent = filtered
            ? `Showing ${list.length} of ${all.length}`
            : key === 'upcoming'
                ? 'Leave can be edited or cancelled up to the day before it starts.'
                : `${all.length} past leave record${all.length === 1 ? '' : 's'}`;
        page.querySelector(`[data-count="${key}"]`).textContent = all.length;
        page.querySelector(`[data-filters="${key}"] [data-clear]`).hidden = !filtered;
    }

    function renderAll() {
        Object.keys(TABS).forEach(renderTab);
    }

    Object.keys(TABS).forEach(key => {
        const tab = TABS[key];
        const bar = page.querySelector(`[data-filters="${key}"]`);
        bar.addEventListener('input', e => {
            const name = e.target.dataset.filter;
            if (!name) return;
            tab.filter[name] = e.target.value;
            renderTab(key);
        });
        bar.querySelector('[data-clear]').addEventListener('click', () => {
            tab.filter = { ...BLANK };
            bar.querySelectorAll('[data-filter]').forEach(input => { input.value = BLANK[input.dataset.filter]; });
            renderTab(key);
        });
    });

    // ?tab= kept in the URL so a reload opens the same tab.
    const tabBar = document.getElementById('lvTabs');
    tabBar.addEventListener('click', e => {
        const b = e.target.closest('[data-tab]');
        if (!b) return;
        tabBar.querySelectorAll('[data-tab]').forEach(x => {
            x.classList.toggle('active', x === b);
            x.setAttribute('aria-selected', x === b ? 'true' : 'false');
        });
        page.querySelectorAll('[data-panel]').forEach(p => { p.hidden = p.dataset.panel !== b.dataset.tab; });
        const url = new URL(location.href);
        url.searchParams.set('tab', b.dataset.tab);
        history.replaceState(history.state, '', url);
    });

    page.querySelector('[data-rows="upcoming"]').addEventListener('click', (e) => {
        const editBtn = e.target.closest('[data-edit-id]');
        if (editBtn) {
            const rec = leaves.find(l => l.id === editBtn.dataset.editId);
            if (rec) openPanel(rec);
            return;
        }

        const btn = e.target.closest('[data-cancel-id]');
        if (!btn) return;
        const id = btn.dataset.cancelId;
        if (!confirm('Cancel this leave? It will be removed, and your cover staff will be told they are no longer needed.')) return;

        btn.disabled = true;
        send('/leave/' + encodeURIComponent(id), 'DELETE').then(data => {
            if (!data.success) {
                btn.disabled = false;
                toast(data.message || 'Could not cancel the leave.', true);
                return;
            }
            leaves = leaves.filter(l => l.id !== id);
            renderAll();
            toast('Leave cancelled.');
        });
    });

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
    let editingId = null; // id of the leave being edited, null for a new one

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
            // Leave cannot be requested for a past date (the server refuses it too).
            const past = dateStr < TODAY;
            if (past) classes.push('lv-day-past');
            html += `<button type="button" class="${classes.join(' ')}" data-date="${dateStr}"${past ? ' disabled' : ''}>${d}</button>`;
        }
        grid.innerHTML = html;

        grid.querySelectorAll('.lv-day:not(.lv-day-empty):not(.lv-day-past)').forEach(btn => {
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
                    Select dates from the calendar above to assign cover staff.
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
                        <span class="code-badge ${COVER_BADGE}" style="margin-right: 6px;">${esc(assigned.code)}</span>
                        <strong>${esc(assigned.name)}</strong>
                    </span>
                    <button type="button" class="lv-combobox-clear-btn" data-clear-date="${esc(d)}" title="Clear assignment"><i class="fa-solid fa-xmark"></i></button>
                `;
            } else {
                triggerHtml = `
                    <span class="lv-combobox-selected-text">
                        <span class="lv-combobox-placeholder">Select cover staff…</span>
                    </span>
                    <i class="fa-solid fa-chevron-down lv-combobox-chevron"></i>
                `;
            }

            const itemsHtml = COVERS.map(inst => {
                const isSel = hasAssigned && assigned.code === inst.code;
                return `
                    <div class="lv-combobox-item ${isSel ? 'selected' : ''}" data-date="${esc(d)}" data-code="${esc(inst.code)}" data-name="${esc(inst.name)}">
                        <span class="code-badge ${COVER_BADGE}">${esc(inst.code)}</span>
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
                                <input type="text" class="lv-combobox-search-input" placeholder="Search by name or code…" autocomplete="off">
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
                emptyMsg.textContent = 'No matching staff found.';
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
    // With a record: edit mode, prefilled from that leave.
    function openPanel(rec) {
        editingId = rec && rec.id ? rec.id : null;
        selectedDates = rec && rec.id ? rec.days.map(d => d.date) : [];
        isPartialDay = !!(rec && rec.id && rec.time_from);
        perDayCover = {};
        if (rec && rec.id) {
            rec.days.forEach(d => { perDayCover[d.date] = { code: d.cover_code, name: d.cover_name }; });
        }
        document.getElementById('lvType').value = editingId ? rec.leave_type : '';
        document.getElementById('lvReason').value = editingId ? (rec.reason || '') : '';
        document.getElementById('lvTimeFrom').value = isPartialDay ? rec.time_from : '08:00';
        document.getElementById('lvTimeTo').value = isPartialDay ? rec.time_to : '12:00';

        document.getElementById('lvPanelTitle').textContent = editingId ? 'Edit Leave' : 'Request Leave';
        document.getElementById('lvPanelSub').textContent = editingId
            ? 'Change your leave before it starts'
            : 'Select dates and a cover for each day';
        document.getElementById('lvSubmitLabel').textContent = editingId ? 'Save Changes' : 'Submit';

        if (durationSeg) {
            durationSeg.querySelectorAll('.lv-seg-btn, .seg-btn').forEach(b => {
                b.classList.toggle('active', b.dataset.duration === (isPartialDay ? 'partial' : 'full'));
            });
        }
        const timeRow = document.getElementById('lvTimeInputsRow');
        if (timeRow) timeRow.hidden = !isPartialDay;

        // Open the calendar on the request's first month when editing.
        viewDate = editingId ? new Date(rec.start_date + 'T00:00:00') : new Date();
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

    openBtn.addEventListener('click', () => openPanel(null));
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

    const submitBtn = document.getElementById('submitLeaveRequest');
    submitBtn?.addEventListener('click', () => {
        const type = document.getElementById('lvType')?.value;
        const reason = document.getElementById('lvReason')?.value.trim();
        const hasDates = selectedDates.length > 0;
        const allCovered = hasDates && selectedDates.every(d => !!perDayCover[d]);

        if (!type || !hasDates) {
            toast('Please select a leave type and dates.', true);
            return;
        }
        if (!allCovered) {
            toast('Please assign a cover person for each selected date.', true);
            return;
        }

        const timeFrom = isPartialDay ? document.getElementById('lvTimeFrom').value : null;
        const timeTo = isPartialDay ? document.getElementById('lvTimeTo').value : null;
        if (isPartialDay && !(timeFrom && timeTo && timeFrom < timeTo)) {
            toast('The end time must be after the start time.', true);
            return;
        }

        const body = {
            leave_type: type,
            reason: reason || null,
            time_from: timeFrom,
            time_to: timeTo,
            days: [...selectedDates].sort().map(d => ({ date: d, cover_code: perDayCover[d].code })),
        };
        const wasEditing = editingId;

        submitBtn.disabled = true;
        send(wasEditing ? '/leave/' + encodeURIComponent(wasEditing) : '/leave', wasEditing ? 'PUT' : 'POST', body)
            .then(data => {
                if (!data.success) {
                    validateForm();
                    toast(data.message || 'Could not save the leave request.', true);
                    return;
                }
                leaves = wasEditing
                    ? leaves.map(l => l.id === wasEditing ? data.record : l)
                    : [data.record, ...leaves];
                closePanel();
                renderAll();
                toast(wasEditing ? 'Leave updated.' : 'Leave recorded.');
            });
    });

    renderAll();
});
