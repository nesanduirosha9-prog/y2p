// notifications.js — the bell dropdown + "see all" modal shared by every
// dashboard page (app/views/components/notifications.php).
// 1. Dropdown: toggles open/closed, closes on an outside click.
// 2. Modal: "See all" opens a two-pane reader — the feed on the left, the
//    selected notification's body on the right. On phones the panes stack and
//    the list is swapped for the detail until Back is pressed.
// 3. Opening a notification (in either pane) POSTs /notifications/{id}/read
//    and the bell dot + header pill update from the count the server returns,
//    so "seen" survives a reload. "Mark all as read" POSTs /notifications/read-all.
//
// Rows for the same notification exist in both the dropdown and the modal, so
// every read is applied by id across the whole document, not just the row
// that was clicked.
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('notificationToggleBtn');
    const dropdown = document.getElementById('notificationDropdown');
    const bellDot = document.getElementById('notifBellDot');
    const countPill = document.getElementById('notifCountPill');

    // ------------------------------------------------------------------
    // Unread state
    // ------------------------------------------------------------------
    function applyUnreadCount(count) {
        if (typeof count !== 'number' || Number.isNaN(count)) return;
        if (bellDot) bellDot.hidden = count === 0;
        if (countPill) {
            countPill.hidden = count === 0;
            countPill.textContent = String(count);
        }
    }

    function clearUnreadStyling(id) {
        document.querySelectorAll(`.notif-item[data-notif-id="${CSS.escape(id)}"]`)
            .forEach(row => row.classList.remove('unread'));
    }

    // Optimistic: the row stops looking unread straight away, and the count
    // is reconciled with whatever the server reports. A failed request leaves
    // the styling cleared but the count untouched, so the next page load shows
    // the true state rather than a number this page invented.
    function markRead(id) {
        const row = document.querySelector(`.notif-item[data-notif-id="${CSS.escape(id)}"]`);
        if (!row || !row.classList.contains('unread')) return;

        clearUnreadStyling(id);

        fetch(`/notifications/${encodeURIComponent(id)}/read`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
        })
            .then(r => r.ok ? r.json() : null)
            .then(data => { if (data && data.success) applyUnreadCount(data.unread); })
            .catch(() => { /* offline or 4xx — the reload will show the truth */ });
    }

    function markAllRead() {
        document.querySelectorAll('.notif-item.unread').forEach(row => row.classList.remove('unread'));
        applyUnreadCount(0);

        fetch('/notifications/read-all', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
        })
            .then(r => r.ok ? r.json() : null)
            .then(data => { if (data && data.success) applyUnreadCount(data.unread); })
            .catch(() => { /* see markRead */ });
    }

    document.querySelectorAll('[data-mark-all]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            markAllRead();
        });
    });

    // ------------------------------------------------------------------
    // Dropdown
    // ------------------------------------------------------------------
    if (toggleBtn && dropdown) {
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.hidden = !dropdown.hidden;
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target) && e.target !== toggleBtn) {
                dropdown.hidden = true;
            }
        });

        // Prevent closing when clicking inside dropdown
        dropdown.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Clicking a row in the dropdown marks it seen and opens it in the
        // full reader, so the body is actually readable somewhere.
        dropdown.addEventListener('click', (e) => {
            const row = e.target.closest('.notif-item');
            if (!row) return;
            const id = row.dataset.notifId;
            markRead(id);
            openModal(id);
        });
    }

    // ------------------------------------------------------------------
    // Modal: master/detail
    // ------------------------------------------------------------------
    const seeAllBtn = document.getElementById('seeAllNotifsBtn');
    const modal = document.getElementById('allNotifsModal');
    const closeModalBtn = document.getElementById('closeAllNotifsBtn');
    const modalBody = document.getElementById('notifModalBody');
    const modalList = document.getElementById('notifModalList');
    const detailBack = document.getElementById('notifDetailBack');

    const detail = {
        icon: document.getElementById('notifDetailIcon'),
        title: document.getElementById('notifDetailTitle'),
        time: document.getElementById('notifDetailTime'),
        body: document.getElementById('notifDetailBody'),
    };

    function renderDetail(row) {
        if (!row || !detail.title) return;
        detail.title.textContent = row.dataset.title || '';
        detail.time.textContent = row.dataset.time || '';
        detail.body.textContent = row.dataset.body || '';
        detail.icon.className = `notif-detail-icon notif-icon-${row.dataset.type || 'info'}`;
        detail.icon.innerHTML = `<i class="fa-solid ${row.dataset.icon || 'fa-bell'}"></i>`;
    }

    function selectRow(row) {
        if (!row || !modalList) return;
        modalList.querySelectorAll('.notif-item').forEach(r => r.classList.remove('active'));
        row.classList.add('active');
        renderDetail(row);
        markRead(row.dataset.notifId);
    }

    // Seed the detail pane with the newest notification, but do NOT mark it
    // read — opening the modal is not the same as opening that one item.
    if (modalList) {
        renderDetail(modalList.querySelector('.notif-item.active') || modalList.querySelector('.notif-item'));

        modalList.addEventListener('click', (e) => {
            const row = e.target.closest('.notif-item');
            if (!row) return;
            selectRow(row);
            if (modalBody) modalBody.classList.add('show-detail'); // phones only
        });
    }

    if (detailBack && modalBody) {
        detailBack.addEventListener('click', () => modalBody.classList.remove('show-detail'));
    }

    function openModal(selectId) {
        if (!modal) return;
        if (dropdown) dropdown.hidden = true;
        modal.hidden = false;

        if (modalList) {
            const row = selectId
                ? modalList.querySelector(`.notif-item[data-notif-id="${CSS.escape(selectId)}"]`)
                : null;
            if (row) {
                modalList.querySelectorAll('.notif-item').forEach(r => r.classList.remove('active'));
                row.classList.add('active');
                renderDetail(row);
                row.scrollIntoView({ block: 'nearest' });
                if (modalBody) modalBody.classList.add('show-detail');
            } else if (modalBody) {
                modalBody.classList.remove('show-detail');
            }
        }
    }

    function closeModal() {
        if (modal) modal.hidden = true;
        if (modalBody) modalBody.classList.remove('show-detail');
    }

    if (seeAllBtn && modal) {
        seeAllBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(null);
        });
    }

    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);

    if (modal) {
        // Close when clicking the backdrop, not the panel itself.
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && !modal.hidden) closeModal();
    });
});
