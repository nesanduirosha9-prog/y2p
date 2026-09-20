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
