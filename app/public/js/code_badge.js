// code_badge.js — JS twin of ViewHelpers::codeBadge(). Keep the markup in sync.
//
// Loaded in the layout's <head>, not with dashboard.js at the end of <body>:
// page scripts sit inside $content and run before dashboard.js, and they call
// this while they render.
//
//   codeBadge('SCS 1308', 'course', { title: 'Foundations of Algorithms' })
//   codeBadge('TSR', 'staff', { classes: ['has-note'], inner: '<i …></i>' })
//
// kind: 'course' | 'lecturer' | 'staff'. `inner` is trusted HTML appended after
// the code (icons, a remove button) — callers escape anything user-supplied.
(function () {
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, m => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]
    ));

    window.codeBadge = function (code, kind, opts) {
        opts = opts || {};
        const cls = ['code-badge', 'code-badge--' + kind]
            .concat((opts.classes || []).filter(Boolean))
            .join(' ');
        const title = opts.title ? ` title="${esc(opts.title)}"` : '';
        return `<span class="${cls}"${title}>${esc(code)}${opts.inner || ''}</span>`;
    };
})();
