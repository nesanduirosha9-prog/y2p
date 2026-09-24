// workload_hub.js — tab switching for the Workload page (/workload/distribution).
//
// Each panel lists the tabs that show it (data-hub-panel="week requests free").
// Switching a tab keeps ?tab= in the URL so a reload or a shared link opens the
// same tab, and fires `hub:tab` so scheduler.js can re-render the panel it
// owns. workload_matrix.js needs no notice: its panel is always rendered.
//
// Loaded LAST on the page: the first show() fires hub:tab, which scheduler.js
// must already be listening for.
(function () {
    const hub = document.getElementById('wmHub');
    const bar = document.getElementById('hubTabs');
    if (!hub || !bar) return;   // In-Charge: one tab, no bar

    function show(tab) {
        bar.querySelectorAll('[data-tab]').forEach(b => {
            const on = b.dataset.tab === tab;
            b.classList.toggle('active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        hub.querySelectorAll('[data-hub-panel]').forEach(p => {
            p.hidden = !p.dataset.hubPanel.split(' ').includes(tab);
        });
        hub.dataset.tab = tab;

        // On phones the bar scrolls sideways; bring the active tab into view.
        const active = bar.querySelector('.active');
        if (active && bar.scrollWidth > bar.clientWidth) {
            const a = active.getBoundingClientRect();
            const r = bar.getBoundingClientRect();
            bar.scrollLeft += a.left - r.left - (r.width - a.width) / 2;
        }

        const url = new URL(location.href);
        url.searchParams.set('tab', tab);
        history.replaceState(history.state, '', url);

        document.dispatchEvent(new CustomEvent('hub:tab', { detail: { tab } }));
    }

    bar.addEventListener('click', e => {
        const b = e.target.closest('[data-tab]');
        if (b) show(b.dataset.tab);
    });

    // scheduler.js jumps to "This week" after approving requests.
    window.wmHubShow = show;

    show(hub.dataset.tab);
})();
