/* settings.js — shared "Account Settings" page behaviour, used by every
   role via timetable_officer/settings.php and instructor/settings.php.

   Only the Save button actually persists anything (POSTs name/phone/office/
   extension/bio to the form's data-action URL). Avatar upload, theme pills,
   and notification toggles below are preview/decorative only — there's no
   backing column for any of them on `staff` yet. */

document.addEventListener('DOMContentLoaded', () => {
    initAvatarUpload();
    initThemeSelection();
    initSaveButton();
});

function initAvatarUpload() {
    const avatarInput = document.getElementById('avatarFileInput');
    const avatarImg = document.getElementById('avatarImage');
    const avatarInitials = document.getElementById('avatarInitials');
    const removeBtn = document.getElementById('removeAvatarBtn');

    if (avatarInput) {
        avatarInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    avatarImg.src = event.target.result;
                    avatarImg.style.display = 'block';
                    if (avatarInitials) avatarInitials.style.display = 'none';
                    if (removeBtn) removeBtn.style.display = 'inline-flex';
                    showToast('Preview updated (not saved yet)');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', () => {
            avatarImg.src = '';
            avatarImg.style.display = 'none';
            if (avatarInitials) avatarInitials.style.display = 'block';
            removeBtn.style.display = 'none';
            if (avatarInput) avatarInput.value = '';
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
