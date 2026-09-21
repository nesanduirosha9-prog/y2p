// Lecture Halls interactions — client-side search, plus the Edit Hall
// modal which persists via PUT /lecture-halls/{code}
// (LectureHallsController::update -> RoomModel::update).
document.addEventListener('DOMContentLoaded', function () {
    const view = document.querySelector('.halls-view');
    if (!view) return;

    const TYPE_LABELS = {
        lab: 'Laboratory',
        lecture_hall: 'Lecture Hall',
        tutorial_room: 'Tutorial Room',
        other: 'Other',
    };

    // --- Search ---
    const tbody = document.querySelector('#hallsTable tbody');
    const emptyMsg = document.getElementById('hallsEmpty');
    const searchInput = document.getElementById('hallSearch');

    function applySearch() {
        const q = searchInput.value.trim().toLowerCase();
        let visible = 0;
        tbody.querySelectorAll('tr').forEach(function (row) {
            const show = !q || row.dataset.search.includes(q);
            row.hidden = !show;
            if (show) visible++;
        });
        emptyMsg.hidden = visible !== 0;
        emptyMsg.textContent = visible === 0 ? 'No halls match your search.' : 'No lecture halls yet.';
    }
    searchInput.addEventListener('input', applySearch);

    // --- Edit modal ---
    const modal = document.getElementById('hallModal');
    const form = document.getElementById('hallForm');
    const codeField = document.getElementById('hallEditCode');
    const nameField = document.getElementById('hallFieldName');
    const capacityField = document.getElementById('hallFieldCapacity');
    const typeField = document.getElementById('hallFieldType');
    const errorEl = document.getElementById('hallFormError');
    const submitBtn = document.getElementById('hallSubmitBtn');
    let activeRow = null;

    function openModalFor(row) {
        activeRow = row;
        codeField.value = row.dataset.code;
        nameField.value = row.dataset.code;
        capacityField.value = row.dataset.capacity;
        typeField.value = row.dataset.type;
        errorEl.hidden = true;
        modal.hidden = false;
    }

    function closeModal() {
        modal.hidden = true;
        activeRow = null;
    }

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-edit-hall]');
        if (!btn) return;
        openModalFor(btn.closest('tr'));
    });

    modal.querySelectorAll('[data-close]').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!activeRow) return;

        const code = codeField.value;
        const capacity = parseInt(capacityField.value, 10);
        const type = typeField.value;

        if (!capacity || capacity < 1) {
            errorEl.textContent = 'Capacity must be a positive number.';
            errorEl.hidden = false;
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving…';

        fetch('/lecture-halls/' + encodeURIComponent(code), {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: type, capacity: capacity }),
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok || !result.data.success) {
                    throw new Error(result.data.message || 'Could not save changes.');
                }
                activeRow.dataset.type = type;
                activeRow.dataset.capacity = String(capacity);
                activeRow.querySelector('.hall-capacity').textContent = capacity;
                const typeLabel = TYPE_LABELS[type] || type;
                activeRow.querySelector('.hall-type').textContent = typeLabel;
                activeRow.dataset.search = (code + ' ' + typeLabel).toLowerCase();
                closeModal();
            })
            .catch(function (err) {
                errorEl.textContent = err.message;
                errorEl.hidden = false;
            })
            .finally(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Changes';
            });
    });
});
