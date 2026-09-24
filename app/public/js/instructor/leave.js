// Instructor Leave JS: stat cards, Upcoming Leaves, Leave History (with
// date-range filtering) and the Request Leave modal (multi-date calendar
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

    const ANNUAL_ALLOWANCE = 21;

    function isUpcoming(l) { return !l.cancelled && l.dates.some(d => d >= TODAY); }
    function isHistory(l) { return l.cancelled || l.dates.every(d => d < TODAY); }

    function sortedDates(l) { return [...l.dates].sort(); }

    function fmtDates(dates) {
        if (!dates.length) return '—';
        return dates.length === 1 ? dates[0] : `${dates[0]} – ${dates[dates.length - 1]}`;
    }

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

    function renderCoverBadges(l) {
        let staffList = [];
        if (Array.isArray(l.cover_staff) && l.cover_staff.length > 0) {
            staffList = l.cover_staff;
        } else if (l.perDayCover && Object.keys(l.perDayCover).length > 0) {
            staffList = Object.entries(l.perDayCover).map(([d, c]) => ({ date: d, code: c.code, name: c.name }));
        } else if (l.cover) {
            const matched = INSTRUCTORS.find(i => i.name === l.cover || i.code === l.cover);
            if (matched) {
                staffList = [{ code: matched.code, name: matched.name, date: '' }];
            } else {
                return `<span class="lv-cover-label">${esc(l.cover)}</span>`;
            }
        }

        if (!staffList.length) {
            return '<span style="color:#94a3b8;">&mdash;</span>';
        }

        // Group by instructor code preserving order of appearance
        const map = new Map();
        staffList.forEach(item => {
            if (!item || !item.code) return;
            if (!map.has(item.code)) {
                map.set(item.code, {
                    code: item.code,
                    name: item.name || item.code,
                    dates: []
                });
            }
            if (item.date) {
                map.get(item.code).dates.push(item.date);
            }
        });

        if (map.size === 0) {
            return '<span style="color:#94a3b8;">&mdash;</span>';
        }

        const badges = Array.from(map.values()).map(info => {
            let title = info.name;
            if (info.dates.length > 0) {
                title += ` (${info.dates.join(', ')})`;
            }
            return `<span class="code-badge code-badge--staff" title="${esc(title)}">${esc(info.code)}</span>`;
        });

        return `<div class="tag-row">${badges.join('')}</div>`;
    }

    // ---- Stats / Upcoming / History rendering ----

    function renderStats() {
        const upcoming = leaves.filter(isUpcoming);
        const hist = leaves.filter(isHistory);
        const daysUsed = hist.filter(l => !l.cancelled).reduce((sum, l) => sum + l.dates.length, 0);
        const upcomingDays = upcoming.reduce((sum, l) => sum + l.dates.length, 0);
        const totalRequests = leaves.filter(l => !l.cancelled).length;

        document.getElementById('lvStatBalance').textContent = ANNUAL_ALLOWANCE - daysUsed;
        document.getElementById('lvStatUsed').textContent = daysUsed;
        document.getElementById('lvStatUpcoming').textContent = upcomingDays;
        document.getElementById('lvStatTotal').textContent = totalRequests;
    }

    function renderUpcoming() {
        const list = document.getElementById('lvUpcomingList');
        const upcoming = leaves.filter(isUpcoming);

        if (!upcoming.length) {
            list.innerHTML = '<div class="lv-empty-state">No upcoming leave scheduled.</div>';
            return;
        }

        list.innerHTML = upcoming.map(l => {
            const sorted = sortedDates(l);
            const partial = isPartial(l);
            const canCancel = sorted[0] >= TODAY;
            const metaReason = (l.reason && l.reason !== '—') ? ` &middot; ${esc(l.reason)}` : '';
            const meta = partial
                ? `${fmtDates(sorted)} &middot; ${fmtTime(l.timeFrom)} &ndash; ${fmtTime(l.timeTo)}${metaReason}`
                : `${fmtDates(sorted)} &middot; ${l.dates.length} day${l.dates.length !== 1 ? 's' : ''}${metaReason}`;

            return `
                <div class="lv-row">
                    <span class="lv-row-dot ${partial ? 'lv-dot-purple' : 'lv-dot-amber'}"></span>
                    <div class="lv-row-info">
                        <div class="lv-row-type">${esc(l.type)}${partial ? ' <span class="lv-badge-partial">PARTIAL DAY</span>' : ''}</div>
                        <div class="lv-row-meta">${meta}</div>
                    </div>
                    <div class="lv-cover-badges-wrap">
                        <span class="lv-cover-hint-text">Cover:</span>
                        ${renderCoverBadges(l)}
                    </div>
                    ${canCancel ? `<button type="button" class="lv-btn-cancel" data-cancel-id="${l.id}">Cancel Leave</button>` : ''}
                </div>
            `;
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

        const tbody = document.getElementById('lvHistoryBody');
        if (!hist.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="lv-empty-state">No leave history found.</td></tr>';
            return;
        }

        tbody.innerHTML = hist.map(l => {
            const sorted = sortedDates(l);
            const partial = isPartial(l);
            const daysCell = partial
                ? '<span class="lv-badge-partial">Partial</span>'
                : `<strong style="color:#0f1c2e">${l.dates.length}</strong>`;
            const noteCell = l.cancelled
                ? '<span class="lv-status status-cancelled">Cancelled</span>'
                : '<span class="lv-status status-approved">Completed</span>';

            return `
                <tr>
                    <td class="lv-td-type">${esc(l.type)}</td>
                    <td>
                        ${fmtDates(sorted)}
                        ${partial ? `<div class="lv-td-time">${fmtTime(l.timeFrom)} &ndash; ${fmtTime(l.timeTo)}</div>` : ''}
                    </td>
                    <td>${daysCell}</td>
                    <td>${esc(l.reason)}</td>
                    <td>${renderCoverBadges(l)}</td>
                    <td>${noteCell}</td>
                </tr>
            `;
        }).join('');
    }

    document.getElementById('lvFilterFrom').addEventListener('input', renderHistory);
    document.getElementById('lvFilterTo').addEventListener('input', renderHistory);
    document.getElementById('lvClearFilter').addEventListener('click', () => {
        document.getElementById('lvFilterFrom').value = '';
        document.getElementById('lvFilterTo').value = '';
        renderHistory();
    });

    function renderAll() {
        renderStats();
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

    // The docked panel's height was a fixed calc() guess in CSS, and the guess
    // was short by the page's own padding — so its footer, the one holding
    // Submit, sat below the fold and the whole page had to be scrolled to reach
    // it. Measure instead: once the panel is on screen its own top edge says
    // exactly how much room is left, whatever the header is doing.
    //
    // Skipped in the two layouts where a height would be wrong: the fixed
    // full-screen overlay at <=768px (inset:0 already fills the viewport) and
    // the stacked column at <=1024px, where the panel sits below the main
    // column and is meant to grow with its content.
    const lvBody = document.querySelector('.lv-body');
    const PANEL_BOTTOM_GUTTER = 46; // .lv-body's 24px bottom padding + .dash-main's 22px

    function sizePanel() {
        if (!panel || panel.hidden) return;

        const isOverlay = getComputedStyle(panel).position === 'fixed';
        const isStacked = !lvBody || getComputedStyle(lvBody).flexDirection !== 'row';
        if (isOverlay || isStacked) {
            panel.style.height = '';
            panel.style.maxHeight = '';
            return;
        }

        const top = panel.getBoundingClientRect().top;
        const available = Math.max(360, window.innerHeight - top - PANEL_BOTTOM_GUTTER);
        panel.style.height = available + 'px';
        panel.style.maxHeight = available + 'px';
    }

    let panelResizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(panelResizeTimer);
        panelResizeTimer = setTimeout(sizePanel, 100);
    });

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
        // Hides .lv-header (see leave.css), so the class goes on before
        // sizePanel() measures — otherwise it measures the old position.
        document.body.classList.add('lv-panel-open');
        sizePanel();
    }
    function closePanel() {
        panel.hidden = true;
        document.body.classList.remove('lv-panel-open');
        panel.style.height = '';
        panel.style.maxHeight = '';
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
