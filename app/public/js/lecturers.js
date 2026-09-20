// Staff Details interactions — tab switching between Lecturer Details /
// Junior Staff Details, plus search + program/year filters (client-side,
// independent per tab). The year/program filters keep a staff member whose
// taught courses include at least one match.
document.addEventListener('DOMContentLoaded', function () {
    const view = document.querySelector('.lecturers-view');
    if (!view) return;

    // --- Tabs (reuses the shared .seg/.seg-btn segmented-control look) ---
    const tabs = view.querySelectorAll('#staffTabGroup .seg-btn');
    const panels = view.querySelectorAll('.staff-tab-panel');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            panels.forEach(function (panel) {
                panel.hidden = panel.dataset.panel !== tab.dataset.tab;
            });
        });
    });

    // --- Search + filters (one instance per tab: "lecturer" / "junior") ---
    function wirePanel(prefix) {
        const tbody = document.querySelector('#' + prefix + 'Table tbody');
        const emptyMsg = document.getElementById(prefix + 'Empty');
        const searchInput = document.getElementById(prefix + 'Search');
        const programFilter = document.getElementById(prefix + 'ProgramFilter');
        const yearFilter = document.getElementById(prefix + 'YearFilter');
        if (!tbody || !searchInput) return;

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
            emptyMsg.hidden = visible !== 0;
        }

        searchInput.addEventListener('input', applyFilters);

        function wireSegmented(group, setter) {
            if (!group) return;
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
    }

    wirePanel('lecturer');
    wirePanel('junior');
});
