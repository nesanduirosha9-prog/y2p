// dashboard.js — sidebar drawer toggle for the shared dashboard shell
// (app/views/layouts/dashboard.php). Only relevant below the 1024px
// breakpoint (see dashboard.css) where the sidebar becomes an off-canvas
// drawer instead of a fixed column.
(function () {
    const sidebar = document.getElementById('dashSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggleBtn = document.getElementById('sidebarToggleBtn');

    if (!sidebar || !backdrop || !toggleBtn) {
        return;
    }

    function openSidebar() {
        sidebar.classList.add('open');
        backdrop.classList.add('visible');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        backdrop.classList.remove('visible');
    }

    toggleBtn.addEventListener('click', function () {
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    backdrop.addEventListener('click', closeSidebar);

    // A nav link click navigates away anyway, but close first so back/forward
    // navigation doesn't leave the drawer open.
    sidebar.querySelectorAll('.nav-item').forEach(function (link) {
        link.addEventListener('click', closeSidebar);
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 1024) {
            closeSidebar();
        }
    });
})();

// Profile photo, client-side only.
//
// `staff` has no avatar column yet, so a chosen photo is kept as a data URL in
// localStorage under the member's badge code and painted over every element the
// server marked with `data-avatar-for="<CODE>"` (the header chip, the menu
// header, the Settings picker). Server-rendered markup always contains the
// 3-letter code, so with no photo — or in a browser that has none stored — the
// page is already correct and this does nothing.
//
// Consequences of it being localStorage: the photo does not follow the member
// to another browser or device, and nobody else ever sees it. Swap the get/set
// below for a real upload once there is a column to save to.
window.StaffSyncAvatar = (function () {
    const PREFIX = 'staffsync_avatar_';

    function get(code) {
        try {
            return localStorage.getItem(PREFIX + code);
        } catch (e) {
            return null; // private mode / blocked site data
        }
    }

    function set(code, dataUrl) {
        try {
            localStorage.setItem(PREFIX + code, dataUrl);
            return true;
        } catch (e) {
            return false; // most likely QuotaExceededError on a large image
        }
    }

    function clear(code) {
        try {
            localStorage.removeItem(PREFIX + code);
        } catch (e) { /* nothing to do */ }
    }

    // Repaints every avatar slot on the page. Called on load and again whenever
    // the photo changes, so the header updates without a reload.
    function apply() {
        document.querySelectorAll('[data-avatar-for]').forEach(function (slot) {
            const code = slot.dataset.avatarFor;
            const stored = get(code);
            if (stored) {
                slot.innerHTML = '<img src="" alt="">';
                slot.firstChild.src = stored;
            } else {
                slot.textContent = code;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', apply);

    return { get: get, set: set, clear: clear, apply: apply };
})();

// Profile chip menu (views/components/user_chip.php). Its own IIFE, not part
// of the sidebar one above: that block returns early when the drawer elements
// are missing, which would silently take this with it.
(function () {
    const chip = document.getElementById('userChipBtn');
    const menu = document.getElementById('userMenu');

    if (!chip || !menu) {
        return;
    }

    function close() {
        menu.hidden = true;
        chip.setAttribute('aria-expanded', 'false');
    }

    function open() {
        menu.hidden = false;
        chip.setAttribute('aria-expanded', 'true');
    }

    chip.addEventListener('click', function (e) {
        e.stopPropagation();
        if (menu.hidden) {
            open();
        } else {
            close();
        }
    });

    // Clicking inside the menu should follow the link, not re-toggle via the
    // document handler below.
    menu.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    document.addEventListener('click', function () {
        if (!menu.hidden) {
            close();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !menu.hidden) {
            close();
            chip.focus();
        }
    });
})();
