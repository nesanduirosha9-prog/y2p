// common.js — shared toast helper, loaded by the dashboard layout for
// instructor pages and by the auth layout (login / signup / forgot password).
// 1. ensureHost() — lazily creates the #ttToastHost container.
// 2. window.ttToast(message, opts) — global toast function other JS files
//    call directly; opts: {type, icon, duration}.
// 3. window.ttToast.flash(message, opts) — queues a toast for the *next*
//    page load (e.g. "Registration submitted" shown after the redirect to
//    /login); any queued toast is shown once the DOM is ready.
(function () {
    const FLASH_KEY = 'ttToastFlash';

    function ensureHost() {
        let host = document.getElementById('ttToastHost');
        if (!host) {
            host = document.createElement('div');
            host.id = 'ttToastHost';
            host.className = 'tt-toast-host';
            host.setAttribute('role', 'status');
            host.setAttribute('aria-live', 'polite');
            document.body.appendChild(host);
        }
        return host;
    }

    window.ttToast = function (message, opts) {
        opts = opts || {};
        const host = ensureHost();
        const toast = document.createElement('div');
        toast.className = 'tt-toast' + (opts.type ? ' tt-toast-' + opts.type : '');
        toast.innerHTML = '<i class="fa-solid ' + (opts.icon || 'fa-circle-check') + '"></i><span></span>';
        toast.querySelector('span').textContent = message;
        host.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add('show'));

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 250);
        }, opts.duration || 3200);
    };

    window.ttToast.flash = function (message, opts) {
        try {
            sessionStorage.setItem(FLASH_KEY, JSON.stringify({ message: message, opts: opts || {} }));
        } catch (e) { /* storage blocked — the toast is simply skipped */ }
    };

    function showQueuedFlash() {
        let queued = null;
        try {
            queued = JSON.parse(sessionStorage.getItem(FLASH_KEY) || 'null');
            sessionStorage.removeItem(FLASH_KEY);
        } catch (e) { return; }
        if (queued && queued.message) window.ttToast(queued.message, queued.opts);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showQueuedFlash);
    } else {
        showQueuedFlash();
    }
})();
