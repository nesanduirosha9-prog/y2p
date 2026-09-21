// common.js — shared helper for every instructor page (loaded once by
// the dashboard layout when $isInstructor is true).
// 1. ensureHost() — lazily creates the #ttToastHost container.
// 2. window.ttToast(message, opts) — global toast function other instructor
//    JS files call directly; opts: {type, icon, duration}.
(function () {
    function ensureHost() {
        let host = document.getElementById('ttToastHost');
        if (!host) {
            host = document.createElement('div');
            host.id = 'ttToastHost';
            host.className = 'tt-toast-host';
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
})();
