// Instructor Settings JS: Save Changes, Passkeys, and Notification toggles.
// DOM-only demo — nothing persists across reloads.
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('setSaveChanges')?.addEventListener('click', () => {
        window.ttToast?.('Profile changes saved.');
    });

    document.getElementById('setAddPasskey')?.addEventListener('click', () => {
        const list = document.getElementById('setPasskeysList');
        const item = document.createElement('div');
        item.className = 'set-passkey-item';
        const today = new Date().toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
        item.innerHTML = `
            <div class="set-passkey-info">
                <div class="set-pk-icon"><i class="fa-solid fa-fingerprint"></i></div>
                <div>
                    <p class="set-pk-name">New Passkey</p>
                    <p class="set-pk-date">Added ${today}</p>
                </div>
            </div>
            <button type="button" class="set-remove-btn">Remove</button>
        `;
        list.appendChild(item);
        window.ttToast?.('Passkey added.');
    });

    document.getElementById('setPasskeysList')?.addEventListener('click', (e) => {
        if (!e.target.closest('.set-remove-btn')) return;
        const item = e.target.closest('.set-passkey-item');
        item.classList.add('removing');
        setTimeout(() => item.remove(), 150);
        window.ttToast?.('Passkey removed.', { icon: 'fa-circle-xmark' });
    });

    document.querySelectorAll('.set-toggle input[data-pref]').forEach(toggle => {
        toggle.addEventListener('change', () => {
            window.ttToast?.(`${toggle.checked ? 'Enabled' : 'Disabled'}: ${toggle.closest('.set-notif-item').querySelector('.set-notif-label').textContent}`);
        });
    });
});
