// Lecturer Details interactions — search + program/year filters, all
// client-side. The year/program filters keep a lecturer whose taught courses
// include at least one match.
document.addEventListener('DOMContentLoaded', function () {
    const view = document.querySelector('.lecturers-view');
    if (!view) return;

    const tbody = document.querySelector('#lecturersTable tbody');
    const emptyMsg = document.getElementById('lecturersEmpty');
    const countEl = document.getElementById('lecturerCount');
    const searchInput = document.getElementById('lecturerSearch');
    const programFilter = document.getElementById('programFilter');
    const yearFilter = document.getElementById('yearFilter');

    let programValue = '';
    let yearValue = '';

    function applyFilters() {
        const q = searchInput.value.trim().toLowerCase();
        let visible = 0;
        tbody.querySelectorAll('tr').forEach(function (row) {
            const programs = (row.dataset.programs || '').split(',');
            const years = (row.dataset.years || '').split(',');
            const matchesSearch = !q || row.dataset.search.includes(q);
            const matchesProgram = !programValue || programs.indexOf(programValue) !== -1;
            const matchesYear = !yearValue || years.indexOf(yearValue) !== -1;
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
});
