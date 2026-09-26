// toast.js — the system's one notification toast, loaded in <head> by every
// layout (dashboard, auth, main). Styles: "Toast" section of components.css.
// Use this instead of alert() or a page-local toast.
//
//   ttToast('Saved.')                                  success (default)
//   ttToast.error('Could not save.')                   also .success/.warning/.info
//   ttToast(msg, { type, icon, duration })             full form
//   ttToast.flash(msg, opts)                           show on the NEXT page load
//                                                      (e.g. right before a redirect)
//
// type picks the icon + colour; icon overrides the icon only. Long messages
// stay up longer unless a duration is given. Toasts stack top-right (full
// width on phones) and are announced to screen readers.
(function () {
    const FLASH_KEY = 'ttToastFlash';
    const ICONS = {
        success: 'fa-circle-check',
        error: 'fa-circle-exclamation',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
    };

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

    function ttToast(message, opts) {
        opts = opts || {};
        message = String(message == null ? '' : message);
        const type = ICONS[opts.type] ? opts.type : 'success';
        const host = ensureHost();

        const toast = document.createElement('div');
        toast.className = 'tt-toast tt-toast-' + type;
        toast.innerHTML = '<i class="fa-solid ' + (opts.icon || ICONS[type]) + '"></i><span></span>';
        toast.querySelector('span').textContent = message;
        host.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add('show'));

        const duration = opts.duration || (message.length > 80 ? 6000 : 3500);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 250);
        }, duration);
    }

    Object.keys(ICONS).forEach(type => {
        ttToast[type] = (message, opts) => ttToast(message, Object.assign({}, opts, { type: type }));
    });

    ttToast.flash = function (message, opts) {
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
        if (queued && queued.message) ttToast(queued.message, queued.opts);
    }

    window.ttToast = ttToast;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showQueuedFlash);
    } else {
        showQueuedFlash();
    }
})();
