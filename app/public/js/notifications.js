// notifications.js — the bell dropdown + "see all" modal shared by every
// dashboard page (app/views/components/notifications.php).
// 1. Dropdown: toggles open/closed, closes on an outside click.
// 2. Modal: "See all" opens it (and closes the dropdown first); closes on
//    the X button or a click on the backdrop.
// NOTE (gap): purely a display toggle — nothing here ever calls the
// backend to mark a notification read, so the unread badge count only
// changes on a full page reload.
document.addEventListener('DOMContentLoaded', () => {
    // Dropdown Logic
    const toggleBtn = document.getElementById('notificationToggleBtn');
    const dropdown = document.getElementById('notificationDropdown');

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
    }

    // Modal Logic
    const seeAllBtn = document.getElementById('seeAllNotifsBtn');
    const modal = document.getElementById('allNotifsModal');
    const closeModalBtn = document.getElementById('closeAllNotifsBtn');

    if (seeAllBtn && modal && closeModalBtn) {
        seeAllBtn.addEventListener('click', (e) => {
            e.preventDefault();
            dropdown.hidden = true; // Close dropdown when opening modal
            modal.hidden = false;
        });

        closeModalBtn.addEventListener('click', () => {
            modal.hidden = true;
        });

        // Close modal when clicking outside of the modal content
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.hidden = true;
            }
        });
    }
});
