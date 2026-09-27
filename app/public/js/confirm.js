// confirm.js — the system's one confirm dialog, loaded in <head> by the
// dashboard layout next to toast.js. Styles: "Confirm dialog" section of
// components.css. Use this instead of confirm(): the browser box reads
// "localhost says", and once Chrome's "don't allow this page to prompt
// again" is ticked, confirm() returns false without showing anything.
//
//   if (!(await ttConfirm('Delete TR101?'))) return;
//   ttConfirm(msg, { title, confirmText, cancelText, danger })  -> Promise<boolean>
//
// Resolves true on the confirm button; false on Cancel, Esc or a click on
// the backdrop. A blank line in the message starts a new paragraph. danger
// paints the confirm button red (deletes, cancels, deactivations).
(function () {
    let dialog = null;
    let settle = null; // resolves the open dialog's promise

    function build() {
        dialog = document.createElement('dialog');
        dialog.className = 'tt-confirm';
        dialog.innerHTML =
            '<div class="tt-confirm-box">' +
                '<h2 class="tt-confirm-title"></h2>' +
                '<div class="tt-confirm-body"></div>' +
                '<div class="tt-confirm-actions">' +
                    '<button type="button" class="tt-confirm-btn" data-answer="no"></button>' +
                    '<button type="button" class="tt-confirm-btn tt-confirm-btn-primary" data-answer="yes"></button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(dialog);

        dialog.addEventListener('click', e => {
            const btn = e.target.closest('[data-answer]');
            // The box fills the dialog, so a click on the dialog itself is the backdrop.
            if (btn || e.target === dialog) dialog.close(btn ? btn.dataset.answer : 'no');
        });
        // Every way out (buttons, Esc, backdrop) ends here. The event arrives
        // a task after close(), so a newer question may have reopened it by then.
        dialog.addEventListener('close', () => {
            if (!dialog.open) finish(dialog.returnValue === 'yes');
        });
    }

    function finish(answer) {
        const done = settle;
        settle = null;
        if (done) done(answer);
    }

    function ttConfirm(message, opts) {
        opts = opts || {};
        if (!dialog) build();
        if (dialog.open) { // a newer question replaces an unanswered one
            finish(false);
            dialog.close();
        }

        dialog.querySelector('.tt-confirm-title').textContent = opts.title || 'Are you sure?';
        const body = dialog.querySelector('.tt-confirm-body');
        body.replaceChildren(...String(message == null ? '' : message).split(/\n\s*\n/).map(text => {
            const p = document.createElement('p');
            p.textContent = text.trim();
            return p;
        }));
        const yes = dialog.querySelector('[data-answer="yes"]');
        yes.textContent = opts.confirmText || 'Confirm';
        yes.classList.toggle('tt-confirm-btn-danger', !!opts.danger);
        dialog.querySelector('[data-answer="no"]').textContent = opts.cancelText || 'Cancel';

        dialog.returnValue = '';
        return new Promise(resolve => {
            settle = resolve;
            dialog.showModal();
            yes.focus();
        });
    }

    window.ttConfirm = ttConfirm;
})();
