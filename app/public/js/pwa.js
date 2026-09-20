// pwa.js — "Add to Home Screen" install prompt (Android/Chrome native
// prompt, iOS falls back to an instructional banner). Loaded by the
// generic main.php layout alongside register-sw.js.
(function () {
    // --- Detect platform ---
    const isIos = () => /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());

    const isInStandaloneMode = () =>
        ('standalone' in window.navigator && window.navigator.standalone) ||
        window.matchMedia('(display-mode: standalone)').matches;

    // Already installed / running as app -> do nothing
    if (isInStandaloneMode()) {
        return;
    }

    let deferredPrompt = null;

    // --- ANDROID / CHROME: capture native install prompt ---
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();          // stop the automatic mini-infobar
        deferredPrompt = e;          // save it to trigger manually later
        showInstallButton();
    });

    window.addEventListener('appinstalled', () => {
        hideInstallButton();
        deferredPrompt = null;
    });

    function showInstallButton() {
        const btn = document.getElementById('pwa-install-btn');
        if (btn) btn.classList.remove('hidden');
    }

    function hideInstallButton() {
        const btn = document.getElementById('pwa-install-btn');
        if (btn) btn.classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const installBtn = document.getElementById('pwa-install-btn');
        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log('Install prompt outcome:', outcome);
                deferredPrompt = null;
                hideInstallButton();
            });
        }

        // --- iOS: no beforeinstallprompt exists, show video modal instead ---
        if (isIos()) {
            const iosBanner = document.getElementById('pwa-ios-banner');
            if (iosBanner) iosBanner.classList.remove('hidden');
        }
    });
})();