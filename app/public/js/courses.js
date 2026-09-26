// Course Management interactions — search, program/year filters, and the
// Add/Edit/Delete course flow. The table is rendered from the database by
// CoursesController; add/edit/delete persist through POST /courses,
// PUT /courses/{code} and DELETE /courses/{code}, and the row is only
// redrawn once the server has confirmed the save.
document.addEventListener('DOMContentLoaded', function () {
    const view = document.querySelector('.courses-view');
    if (!view) return;

    const esc = function (s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    };

    // fetch() wrapper. Always JSON: Request::getBody() only parses PUT/DELETE
    // bodies when they are JSON. Rejects with the server's own message.
    function sendJson(method, url, body) {
        return fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: body ? JSON.stringify(body) : undefined,
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok || !result.data.success) {
                    throw new Error(result.data.message || 'Something went wrong.');
                }
                return result.data;
            });
    }

    const LECTURERS = JSON.parse(view.dataset.lecturers || '[]');
    const INSTRUCTORS = JSON.parse(view.dataset.instructors || '[]');
    const OPTION_SETS = { lecturers: LECTURERS, instructors: INSTRUCTORS };

    const table = document.getElementById('coursesTable');
    const tbody = table.querySelector('tbody');
    const emptyMsg = document.getElementById('coursesEmpty');
    const countEl = document.getElementById('courseCount');
    const searchInput = document.getElementById('courseSearch');
    const programFilter = document.getElementById('programFilter');
    const yearFilter = document.getElementById('yearFilter');

    let programValue = '';
    let yearValue = '';

    // ---- Filtering ----
    function applyFilters() {
        const q = searchInput.value.trim().toLowerCase();
        let visible = 0;
        tbody.querySelectorAll('tr').forEach(function (row) {
            const matchesSearch = !q || row.dataset.search.includes(q);
            const matchesProgram = !programValue || row.dataset.program === programValue;
            const matchesYear = !yearValue || row.dataset.year === yearValue;
            const show = matchesSearch && matchesProgram && matchesYear;
            row.hidden = !show;
            if (show) visible++;
        });
        countEl.textContent = visible;
        emptyMsg.hidden = visible !== 0;
    }

    searchInput.addEventListener('input', applyFilters);

    function wireSegmented(group, setter) {
        group.addEventListener('click', function (e) {
            const btn = e.target.closest('.seg-btn');
            if (!btn) return;
            group.querySelectorAll('.seg-btn').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            setter(btn.dataset.value);
            applyFilters();
        });
    }
    wireSegmented(programFilter, function (v) { programValue = v; });
    wireSegmented(yearFilter, function (v) { yearValue = v; });

    const modal = document.getElementById('courseModal');
    const modalTitle = document.getElementById('courseModalTitle');
    const modalSubtitle = document.getElementById('courseModalSubtitle');
    const form = document.getElementById('courseForm');
    const editingCode = document.getElementById('editingCode');
    const submitBtn = document.getElementById('courseSubmitBtn');
    const fieldCode = document.getElementById('fieldCode');
    const fieldName = document.getElementById('fieldName');
    const fieldCredits = document.getElementById('fieldCredits');
    const fieldYear = document.getElementById('fieldYear');
    const fieldProgram = document.getElementById('fieldProgram');
    const fieldSemester = document.getElementById('fieldSemester');

    // multi-select state: { lecturers: Set, instructors: Set }
    const picked = { lecturers: new Set(), instructors: new Set() };

    // The menu opens with a search box on top (same look as the searchable
    // dropdowns — .ss-search in components.css). A rebuild while open (a chip
    // removed) keeps whatever was typed.
    function buildMenu(msEl) {
        const name = msEl.dataset.name;
        const menu = msEl.querySelector('.multi-menu');
        const prev = menu.querySelector('.multi-search input');
        const query = prev ? prev.value : '';

        menu.innerHTML = '<div class="ss-search multi-search"><i class="fa-solid fa-magnifying-glass"></i>' +
            '<input type="text" class="ss-search-input" placeholder="Search by name or code…" autocomplete="off"></div>';
        menu.querySelector('input').value = query;

        OPTION_SETS[name].forEach(function (opt) {
            const row = document.createElement('div');
            row.className = 'multi-option' + (picked[name].has(opt.code) ? ' selected' : '');
            row.dataset.code = opt.code;
            row.dataset.search = (opt.label + ' ' + (opt.dept || '')).toLowerCase();
            row.innerHTML = '<span>' + esc(opt.label) + '</span>' +
                (opt.dept ? '<span class="opt-meta">' + esc(opt.dept) + '</span>' : '');
            menu.appendChild(row);
        });
        const empty = document.createElement('p');
        empty.className = 'multi-empty';
        empty.textContent = 'No matches.';
        menu.appendChild(empty);
        filterMenu(menu);
    }

    function filterMenu(menu) {
        const q = menu.querySelector('.multi-search input').value.trim().toLowerCase();
        let shown = 0;
        menu.querySelectorAll('.multi-option').forEach(function (row) {
            const match = !q || row.dataset.search.includes(q);
            row.hidden = !match;
            if (match) shown++;
        });
        menu.querySelector('.multi-empty').hidden = shown > 0;
    }

    function renderChips(msEl) {
        const name = msEl.dataset.name;
        const trigger = msEl.querySelector('.multi-trigger');
        const chevron = trigger.querySelector('i');
        trigger.querySelectorAll('.multi-chip, .multi-placeholder').forEach(function (n) { n.remove(); });

        if (picked[name].size === 0) {
            const ph = document.createElement('span');
            ph.className = 'multi-placeholder';
            ph.textContent = 'Select ' + name + '…';
            trigger.insertBefore(ph, chevron);
            return;
        }
        picked[name].forEach(function (code) {
            const chip = document.createElement('span');
            chip.className = 'multi-chip';
            chip.innerHTML = esc(code) + '<button type="button" data-remove="' + esc(code) + '">×</button>';
            trigger.insertBefore(chip, chevron);
        });
    }

    modal.querySelectorAll('.multi-select').forEach(function (msEl) {
        const name = msEl.dataset.name;
        const trigger = msEl.querySelector('.multi-trigger');
        const menu = msEl.querySelector('.multi-menu');

        trigger.addEventListener('click', function (e) {
            if (e.target.closest('[data-remove]')) {
                e.stopPropagation();
                picked[name].delete(e.target.closest('[data-remove]').dataset.remove);
                renderChips(msEl);
                if (msEl.classList.contains('open')) buildMenu(msEl);
                return;
            }
            const isOpen = msEl.classList.toggle('open');
            menu.hidden = !isOpen;
            if (isOpen) {
                menu.innerHTML = ''; // fresh open → empty search
                buildMenu(msEl);
                menu.querySelector('.multi-search input').focus();
            }
        });

        menu.addEventListener('input', function (e) {
            if (e.target.closest('.multi-search')) filterMenu(menu);
        });
        menu.addEventListener('keydown', function (e) {
            if (!e.target.closest('.multi-search')) return;
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                msEl.classList.remove('open');
                menu.hidden = true;
                trigger.focus();
            } else if (e.key === 'Enter') {
                // Enter toggles the first visible match, and never submits the form.
                e.preventDefault();
                const first = menu.querySelector('.multi-option:not([hidden])');
                if (first) first.click();
            }
        });

        menu.addEventListener('click', function (e) {
            e.stopPropagation();
            const optRow = e.target.closest('.multi-option');
            if (!optRow) return;
            const code = optRow.dataset.code;
            if (picked[name].has(code)) picked[name].delete(code);
            else picked[name].add(code);
            optRow.classList.toggle('selected', picked[name].has(code));
            renderChips(msEl);
        });
    });

    document.addEventListener('click', function (e) {
        modal.querySelectorAll('.multi-select.open').forEach(function (msEl) {
            if (!msEl.contains(e.target)) {
                msEl.classList.remove('open');
                msEl.querySelector('.multi-menu').hidden = true;
            }
        });
    });

    function openModal(mode, row) {
        form.reset();
        picked.lecturers.clear();
        picked.instructors.clear();

        if (mode === 'edit' && row) {
            modalTitle.textContent = 'Edit Course';
            if (modalSubtitle) modalSubtitle.textContent = 'Update course metadata and assigned academic staff';
            submitBtn.textContent = 'Save Changes';
            editingCode.value = row.dataset.code;
            fieldCode.value = row.dataset.code;
            fieldName.value = row.dataset.name;
            fieldCredits.value = row.dataset.credits;
            fieldYear.value = row.dataset.year;
            fieldProgram.value = row.dataset.program;
            fieldSemester.value = row.dataset.semester || '';
            fieldCode.disabled = true; // the code is the primary key — not editable
            (row.dataset.lecturers ? row.dataset.lecturers.split(',') : []).forEach(function (c) { picked.lecturers.add(c); });
            (row.dataset.instructors ? row.dataset.instructors.split(',') : []).forEach(function (c) { picked.instructors.add(c); });
        } else {
            modalTitle.textContent = 'Add New Course';
            if (modalSubtitle) modalSubtitle.textContent = 'Enter course details and staff assignments';
            submitBtn.textContent = 'Add Course';
            editingCode.value = '';
            fieldCode.disabled = false;
        }

        modal.querySelectorAll('.multi-select').forEach(function (msEl) {
            msEl.classList.remove('open');
            msEl.querySelector('.multi-menu').hidden = true;
            renderChips(msEl);
        });

        modal.hidden = false;
        refreshSubmitState();
    }

    function closeModal() { modal.hidden = true; }

    // Mirror the Figma: the submit button stays muted until the required
    // text/select fields are filled in.
    function refreshSubmitState() {
        const ready = fieldCode.value.trim() && fieldName.value.trim() &&
            fieldYear.value && fieldProgram.value && fieldSemester.value && parseInt(fieldCredits.value, 10) > 0;
        submitBtn.disabled = !ready;
    }
    [fieldCode, fieldName, fieldCredits, fieldYear, fieldProgram, fieldSemester].forEach(function (el) {
        el.addEventListener('input', refreshSubmitState);
        el.addEventListener('change', refreshSubmitState);
    });

    document.getElementById('addCourseBtn').addEventListener('click', function () { openModal('add'); });
    modal.querySelectorAll('[data-close]').forEach(function (b) { b.addEventListener('click', closeModal); });
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    // ---- Row actions ----
    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-act]');
        if (!btn) return;
        const row = btn.closest('tr');
        if (btn.dataset.act === 'edit') {
            openModal('edit', row);
        } else if (btn.dataset.act === 'delete') {
            const code = row.dataset.code;
            if (!confirm('Delete ' + code + '? This cannot be undone.')) return;
            sendJson('DELETE', '/courses/' + encodeURIComponent(code))
                .then(function () {
                    row.remove();
                    applyFilters();
                })
                .catch(function (err) { ttToast.error(err.message); });
        }
    });

    // ---- Save (add or update), then redraw the row from what was saved ----
    function tagRow(codes, kind) {
        return codes.map(function (c) { return codeBadge(c, kind); }).join('');
    }

    function renderRow(row, c) {
        row.dataset.code = c.code;
        row.dataset.name = c.title;
        row.dataset.credits = c.credits;
        row.dataset.year = c.year;
        row.dataset.semester = c.semester;
        row.dataset.program = c.program;
        row.dataset.lecturers = c.lecturers.join(',');
        row.dataset.instructors = c.instructors.join(',');
        row.dataset.search = (c.code + ' ' + c.title + ' ' + c.lecturers.join(' ') + ' ' + c.instructors.join(' ')).toLowerCase();
        row.innerHTML =
            '<td>' + codeBadge(c.code, 'course') + '</td>' +
            '<td>' + esc(c.title) + '</td>' +
            '<td>' + c.credits + '</td>' +
            '<td><span class="pill pill-year-' + c.year + '">Year ' + c.year + '</span></td>' +
            '<td><span class="pill pill-muted">' + esc(c.program) + '</span></td>' +
            '<td><div class="tag-row">' + tagRow(c.lecturers, 'lecturer') + '</div></td>' +
            '<td><div class="tag-row">' + tagRow(c.instructors, 'staff') + '</div></td>' +
            '<td><div class="tag-row">' +
                '<button type="button" class="icon-action" data-act="edit" title="Edit course"><i class="fa-solid fa-pen"></i></button>' +
                '<button type="button" class="icon-action danger" data-act="delete" title="Delete course"><i class="fa-regular fa-trash-can"></i></button>' +
            '</div></td>';
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const isEdit = editingCode.value !== '';
        const payload = {
            code: isEdit ? editingCode.value : fieldCode.value.trim().toUpperCase(),
            title: fieldName.value.trim(),
            credits: parseInt(fieldCredits.value, 10) || 0,
            year: parseInt(fieldYear.value, 10),
            semester: parseInt(fieldSemester.value, 10),
            program: fieldProgram.value,
            lecturers: Array.from(picked.lecturers),
            instructors: Array.from(picked.instructors),
        };

        submitBtn.disabled = true;
        const request = isEdit
            ? sendJson('PUT', '/courses/' + encodeURIComponent(payload.code), payload)
            : sendJson('POST', '/courses', payload);

        request
            .then(function () {
                let row = isEdit ? tbody.querySelector('tr[data-code="' + CSS.escape(payload.code) + '"]') : null;
                if (!row) {
                    row = document.createElement('tr');
                    tbody.appendChild(row);
                }
                renderRow(row, payload);
                closeModal();
                applyFilters();
            })
            .catch(function (err) { ttToast.error(err.message); })
            .finally(refreshSubmitState);
    });
});
