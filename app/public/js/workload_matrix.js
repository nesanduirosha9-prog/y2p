// workload_matrix.js — Real-time filtering by Degree, Year, and Search for Course Workload Matrix.

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('wmSearchInput');
    const progSeg = document.getElementById('wmProgramSeg');
    const yearSeg = document.getElementById('wmYearSeg');
    const summaryBtn = document.getElementById('toggleAllocationSummaryBtn');
    const summaryCard = document.getElementById('allocationSummaryCard');
    const closeSummaryBtn = document.getElementById('closeSummaryCard');
    const table = document.getElementById('workloadMatrixTable');
    const emptyMsg = document.getElementById('wmEmptyMsg');

    if (!table) return;

    let currentProg = 'all';
    let currentYear = 'all';
    let searchQuery = '';

    function filterTable() {
        const rows = table.querySelectorAll('tbody tr.wm-data-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowYear = row.getAttribute('data-year') || '';
            const rowProg = row.getAttribute('data-prog') || '';
            const rowSearch = row.getAttribute('data-search') || '';

            const matchesProg = (currentProg === 'all' || rowProg === currentProg);
            const matchesYear = (currentYear === 'all' || rowYear === currentYear);
            const matchesSearch = (!searchQuery || rowSearch.includes(searchQuery));

            if (matchesProg && matchesYear && matchesSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (emptyMsg) {
            emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            searchQuery = this.value.trim().toLowerCase();
            filterTable();
        });
    }

    if (progSeg) {
        progSeg.addEventListener('click', function (e) {
            const btn = e.target.closest('.seg-btn');
            if (!btn) return;
            progSeg.querySelectorAll('.seg-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentProg = btn.getAttribute('data-prog') || 'all';
            filterTable();
        });
    }

    if (yearSeg) {
        yearSeg.addEventListener('click', function (e) {
            const btn = e.target.closest('.seg-btn');
            if (!btn) return;
            yearSeg.querySelectorAll('.seg-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentYear = btn.getAttribute('data-year') || 'all';
            filterTable();
        });
    }

    if (summaryBtn && summaryCard) {
        summaryBtn.addEventListener('click', function () {
            const isHidden = summaryCard.style.display === 'none';
            summaryCard.style.display = isHidden ? 'block' : 'none';
        });
    }

    if (closeSummaryBtn && summaryCard) {
        closeSummaryBtn.addEventListener('click', function () {
            summaryCard.style.display = 'none';
        });
    }
});
