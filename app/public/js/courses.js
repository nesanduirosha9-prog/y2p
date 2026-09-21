// Course Management interactions — search, program/year filters, and the
// Add/Edit/Delete course flow. The table is rendered from the database by
// CoursesController; the add/edit/delete actions here only mutate the DOM for
// the current pageview and do not survive a reload.
document.addEventListener('DOMContentLoaded', function () {
    const view = document.querySelector('.courses-view');
    if (!view) return;

    const esc = function (s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    };

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

    // ---- Modal ----
    const modal = document.getElementById('courseModal');
    const modalTitle = document.getElementById('courseModalTitle');
    const form = document.getElementById('courseForm');
    const editingCode = document.getElementById('editingCode');
    const submitBtn = document.getElementById('courseSubmitBtn');
    const fieldCode = document.getElementById('fieldCode');
    const fieldName = document.getElementById('fieldName');
    const fieldCredits = document.getElementById('fieldCredits');
    const fieldYear = document.getElementById('fieldYear');
    const fieldProgram = document.getElementById('fieldProgram');

    // multi-select state: { lecturers: Set, instructors: Set }
    const picked = { lecturers: new Set(), instructors: new Set() };

    function buildMenu(msEl) {
        const name = msEl.dataset.name;
        const menu = msEl.querySelector('.multi-menu');
        menu.innerHTML = '';
        OPTION_SETS[name].forEach(function (opt) {
            const row = document.createElement('div');
            row.className = 'multi-option' + (picked[name].has(opt.code) ? ' selected' : '');
            row.dataset.code = opt.code;
            row.innerHTML = '<span>' + esc(opt.label) + '</span>' +
                (opt.dept ? '<span class="opt-meta">' + esc(opt.dept) + '</span>' : '');
            menu.appendChild(row);
        });
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
            if (isOpen) buildMenu(msEl);
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
            submitBtn.textContent = 'Save Changes';
            editingCode.value = row.dataset.code;
            fieldCode.value = row.dataset.code;
            fieldName.value = row.dataset.name;
            fieldCredits.value = row.dataset.credits;
            fieldYear.value = row.dataset.year;
            fieldProgram.value = row.dataset.program;
            (row.dataset.lecturers ? row.dataset.lecturers.split(',') : []).forEach(function (c) { picked.lecturers.add(c); });
            (row.dataset.instructors ? row.dataset.instructors.split(',') : []).forEach(function (c) { picked.instructors.add(c); });
        } else {
            modalTitle.textContent = 'Add New Course';
            submitBtn.textContent = 'Add Course';
            editingCode.value = '';
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
            fieldYear.value && fieldProgram.value && parseInt(fieldCredits.value, 10) > 0;
        submitBtn.disabled = !ready;
    }
    [fieldCode, fieldName, fieldCredits, fieldYear, fieldProgram].forEach(function (el) {
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
            if (confirm('Delete ' + row.dataset.code + '? This cannot be undone.')) {
                row.remove();
                applyFilters();
            }
        }
    });

    // ---- Save (add or update a row, DOM-only) ----
    function tagRow(codes, cls) {
        return codes.map(function (c) { return '<span class="tag ' + cls + '">' + esc(c) + '</span>'; }).join('');
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const code = fieldCode.value.trim().toUpperCase();
        const name = fieldName.value.trim();
        const credits = parseInt(fieldCredits.value, 10) || 0;
        const year = fieldYear.value;
        const program = fieldProgram.value;
        if (!code || !name || !year || !program) return;

        // Adding a code that already exists would create a duplicate row —
        // treat it as an edit of the existing one instead.
        if (!editingCode.value && tbody.querySelector('tr[data-code="' + CSS.escape(code) + '"]')) {
            editingCode.value = code;
        }

        const lecturers = Array.from(picked.lecturers);
        const instructors = Array.from(picked.instructors);
        const searchStr = (code + ' ' + name + ' ' + lecturers.join(' ') + ' ' + instructors.join(' ')).toLowerCase();

        let row = editingCode.value
            ? tbody.querySelector('tr[data-code="' + CSS.escape(editingCode.value) + '"]')
            : null;
        if (!row) {
            row = document.createElement('tr');
            tbody.appendChild(row);
        }

        row.dataset.code = code;
        row.dataset.name = name;
        row.dataset.credits = credits;
        row.dataset.year = year;
        row.dataset.program = program;
        row.dataset.lecturers = lecturers.join(',');
        row.dataset.instructors = instructors.join(',');
        row.dataset.search = searchStr;
        row.innerHTML =
            '<td class="cell-code">' + esc(code) + '</td>' +
            '<td>' + esc(name) + '</td>' +
            '<td>' + credits + '</td>' +
            '<td><span class="pill pill-year-' + year + '">Year ' + year + '</span></td>' +
            '<td><span class="pill pill-muted">' + program + '</span></td>' +
            '<td><div class="tag-row">' + tagRow(lecturers, 'tag-lecturer') + '</div></td>' +
            '<td><div class="tag-row">' + tagRow(instructors, 'tag-instructor') + '</div></td>' +
            '<td><div class="tag-row">' +
                '<button type="button" class="icon-action" data-act="edit" title="Edit course"><i class="fa-solid fa-pen"></i></button>' +
                '<button type="button" class="icon-action danger" data-act="delete" title="Delete course"><i class="fa-regular fa-trash-can"></i></button>' +
            '</div></td>';

        closeModal();
        applyFilters();
    });
});
