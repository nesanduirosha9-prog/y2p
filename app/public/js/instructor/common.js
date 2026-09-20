// Shared helpers for Instructor pages: toast notifications.
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
