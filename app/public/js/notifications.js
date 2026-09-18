// Notifications feed interactions — "Mark all as read" and clicking a single
// unread card to clear its unread state. UI-only: nothing persists across a
// reload (there is no notifications table yet).
document.addEventListener('DOMContentLoaded', function () {
    const view = document.querySelector('.notifs-view');
    if (!view) return;

    const list = view.querySelector('.notif-list');
    const countEl = document.getElementById('unreadCount');
    const subEl = countEl ? countEl.parentElement : null;

    function refreshCount() {
        const unread = list.querySelectorAll('.notif-card.is-unread').length;
        if (countEl) countEl.textContent = unread;
        if (subEl) {
            subEl.lastChild.textContent = ' unread notification' + (unread === 1 ? '' : 's');
        }
    }

    function markRead(card) {
        card.classList.remove('is-unread');
        card.classList.add('is-read');
    }

    list.addEventListener('click', function (e) {
        const card = e.target.closest('.notif-card.is-unread');
        if (card) {
            markRead(card);
            refreshCount();
        }
    });

    const markAllBtn = document.getElementById('markAllRead');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function () {
            list.querySelectorAll('.notif-card.is-unread').forEach(markRead);
            refreshCount();
        });
    }
});
