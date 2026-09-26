// Lecture Halls interactions — client-side search, the Add / Edit Hall
// drawer and row Delete, all persisted:
//   POST   /lecture-halls          (LectureHallsController::store)
//   PUT    /lecture-halls/{code}   (LectureHallsController::update)
//   DELETE /lecture-halls/{code}   (LectureHallsController::destroy)
document.addEventListener('DOMContentLoaded', function () {
    const view = document.querySelector('.halls-view');
    if (!view) return;

    const TYPE_LABELS = {
        lecture_hall: 'Lecture Hall',
        lab: 'Laboratory',
    };

    // fetch() wrapper. Always JSON: Request::getBody() only parses PUT/DELETE
    // bodies when they are JSON. Resolves to the server's reply, rejects with
    // the server's own message on any failure.
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
        emptyMsg.textContent = q ? 'No halls match your search.' : 'No lecture halls yet.';
    }
    searchInput.addEventListener('input', applySearch);

    // --- Add / Edit drawer ---
    const modal = document.getElementById('hallModal');
    const form = document.getElementById('hallForm');
    const titleEl = document.getElementById('hallModalTitle');
    const subtitleEl = document.getElementById('hallModalSubtitle');
    const codeField = document.getElementById('hallEditCode');
    const nameField = document.getElementById('hallFieldName');
    const capacityField = document.getElementById('hallFieldCapacity');
    const typeField = document.getElementById('hallFieldType');
    const errorEl = document.getElementById('hallFormError');
    const submitBtn = document.getElementById('hallSubmitBtn');
    let activeRow = null; // null = Add mode

    function openModal(row) {
        activeRow = row || null;
        const isEdit = activeRow !== null;
        titleEl.textContent = isEdit ? 'Edit Lecture Hall' : 'Add Lecture Hall';
        subtitleEl.textContent = isEdit ? 'Update capacity and venue type configuration' : 'Register a new teaching space';
        submitBtn.textContent = isEdit ? 'Save Changes' : 'Add Hall';
        nameField.disabled = isEdit; // the code is the primary key — never editable
        codeField.value = isEdit ? row.dataset.code : '';
        nameField.value = isEdit ? row.dataset.code : '';
        capacityField.value = isEdit ? row.dataset.capacity : '';
        typeField.value = isEdit ? row.dataset.type : 'lecture_hall';
        errorEl.hidden = true;
        modal.hidden = false;
        if (!isEdit) nameField.focus();
    }

    function closeModal() {
        modal.hidden = true;
        activeRow = null;
    }

    function showError(message) {
        errorEl.textContent = message;
        errorEl.hidden = false;
    }

    document.getElementById('addHallBtn').addEventListener('click', function () { openModal(null); });
    modal.querySelectorAll('[data-close]').forEach(function (btn) { btn.addEventListener('click', closeModal); });
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    // --- Row actions ---
    tbody.addEventListener('click', function (e) {
        const row = e.target.closest('tr');
        if (e.target.closest('[data-edit-hall]')) {
            openModal(row);
        } else if (e.target.closest('[data-delete-hall]')) {
            deleteHall(row);
        }
    });

    function deleteHall(row) {
        const code = row.dataset.code;
        if (!confirm('Delete ' + code + '? This cannot be undone.')) return;
        sendJson('DELETE', '/lecture-halls/' + encodeURIComponent(code))
            .then(function () {
                row.remove();
                applySearch();
            })
            .catch(function (err) { ttToast.error(err.message); });
    }

    // --- Save (Add or Edit) ---
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const isEdit = activeRow !== null;
        const code = isEdit ? codeField.value : nameField.value.trim().toUpperCase();
        const capacity = parseInt(capacityField.value, 10);
        const type = typeField.value;

        if (!code) return showError('Enter a hall code.');
        if (!capacity || capacity < 1) return showError('Capacity must be a positive number.');

        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving…';

        const request = isEdit
            ? sendJson('PUT', '/lecture-halls/' + encodeURIComponent(code), { type: type, capacity: capacity })
            : sendJson('POST', '/lecture-halls', { code: code, type: type, capacity: capacity });

        request
            .then(function () {
                if (!isEdit) {
                    // New row: let the server render it, in sorted order.
                    window.location.reload();
                    return;
                }
                const typeLabel = TYPE_LABELS[type] || type;
                activeRow.dataset.type = type;
                activeRow.dataset.capacity = String(capacity);
                activeRow.querySelector('.hall-capacity').textContent = capacity;
                activeRow.querySelector('.hall-type').textContent = typeLabel;
                activeRow.dataset.search = (code + ' ' + typeLabel).toLowerCase();
                closeModal();
            })
            .catch(function (err) { showError(err.message); })
            .finally(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = isEdit ? 'Save Changes' : 'Add Hall';
            });
    });
});
