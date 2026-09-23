/* settings.js — shared "Account Settings" page behaviour, used by every
   role via the single views/settings.php.

   Only the Save button persists to the server (POSTs name/phone/office/
   extension/bio to the form's data-action URL). The avatar picker keeps the
   chosen photo in this browser via window.StaffSyncAvatar (js/dashboard.js) and
   repaints the header chip with it — `staff` has no avatar column yet. Theme
   pills and notification toggles below are decorative for the same reason. */

document.addEventListener('DOMContentLoaded', () => {
    initSettingsTabs();
    initAvatarUpload();
    initThemeSelection();
    initSaveButton();
});

function initSettingsTabs() {
    const tabsContainer = document.getElementById('settingsTabs');
    if (!tabsContainer) return;

    const panels = {
        profile: document.getElementById('settings-panel-profile'),
        handover: document.getElementById('settings-panel-handover')
    };

    function switchTab(tabKey) {
        if (!panels[tabKey]) return;
        tabsContainer.querySelectorAll('.settings-tab').forEach(t => {
            const isActive = t.dataset.tab === tabKey;
            t.classList.toggle('active', isActive);
            t.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        Object.entries(panels).forEach(([k, p]) => {
            if (p) p.hidden = (k !== tabKey);
        });
    }

    tabsContainer.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-tab]');
        if (!btn) return;
        switchTab(btn.dataset.tab);
    });

    if (window.location.hash === '#handover') {
        switchTab('handover');
    }
}

function initAvatarUpload() {
    const avatarInput = document.getElementById('avatarFileInput');
    const avatarImg = document.getElementById('avatarImage');
    const avatarInitials = document.getElementById('avatarInitials');
    const removeBtn = document.getElementById('removeAvatarBtn');

    // Show the picked file immediately (FileReader), then upload it. If the
    // upload is rejected the preview is rolled back to what the server has, so
    // the page never claims a photo that isn't saved.
    function showImage(src) {
        avatarImg.src = src;
        avatarImg.style.display = 'block';
        if (avatarInitials) avatarInitials.style.display = 'none';
        if (removeBtn) removeBtn.style.display = 'inline-flex';
    }

    function showInitials() {
        avatarImg.src = '';
        avatarImg.style.display = 'none';
        if (avatarInitials) avatarInitials.style.display = 'block';
        if (removeBtn) removeBtn.style.display = 'none';
        if (avatarInput) avatarInput.value = '';
    }

    // The member's 3-letter badge code — the key the photo is stored under, and
    // what shows when there is none.
    const code = avatarInitials ? avatarInitials.dataset.avatarCode : null;
    const store = window.StaffSyncAvatar;

    // Restore whatever this browser already has for them.
    if (code && store) {
        const saved = store.get(code);
        if (saved) showImage(saved);
    }

    if (avatarInput) {
        avatarInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                showToast('Image must be 2 MB or smaller', true);
                avatarInput.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                const dataUrl = event.target.result;
                showImage(dataUrl);

                if (!code || !store) return;
                if (store.set(code, dataUrl)) {
                    store.apply();            // repaint the header chip too
                    showToast('Profile photo updated');
                } else {
                    // Storage full or blocked — don't leave the page showing a
                    // photo that won't survive the next navigation.
                    showInitials();
                    store.apply();
                    showToast('Could not save that image in this browser', true);
                }
            };
            reader.readAsDataURL(file);
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', () => {
            showInitials();
            if (code && store) {
                store.clear(code);
                store.apply();
            }
            showToast('Profile photo removed');
        });
    }
}

function initThemeSelection() {
    const themePills = document.querySelectorAll('.sys-theme-pill');

    themePills.forEach(pill => {
        pill.addEventListener('click', () => {
            themePills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            // Not wired to an actual theme switch yet — see settings.css header comment.
        });
    });
}

function initSaveButton() {
    const saveBtn = document.getElementById('saveSettingsBtn');
    const form = document.getElementById('sysProfileForm');
    if (!saveBtn || !form) return;

    saveBtn.addEventListener('click', async () => {
        const name = form.querySelector('[name="name"]').value.trim();
        if (name === '') {
            showToast('Full name is required', true);
            return;
        }

        const payload = {
            name,
            phone: form.querySelector('[name="phone"]').value.trim(),
            office: form.querySelector('[name="office"]').value.trim(),
            extension: form.querySelector('[name="extension"]').value.trim(),
            bio: form.querySelector('[name="bio"]').value.trim(),
        };

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

        try {
            const res = await fetch(form.dataset.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            showToast(data.success ? 'Settings saved successfully!' : (data.message || 'Could not save settings'), !data.success);
        } catch (err) {
            showToast('Could not reach the server', true);
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fa-solid fa-check"></i> Save Changes';
        }
    });
}

function showToast(msg, isError) {
    let toast = document.getElementById('sysToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'sysToast';
        toast.className = 'sys-toast';
        document.body.appendChild(toast);
    }
    toast.classList.toggle('error', !!isError);
    toast.textContent = msg;
    toast.classList.add('show');

    clearTimeout(toast._hideTimer);
    toast._hideTimer = setTimeout(() => {
        toast.classList.remove('show');
    }, 2500);
}
