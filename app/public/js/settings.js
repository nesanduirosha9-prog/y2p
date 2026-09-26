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
    initRevokeCoordinator();
    initHandoverPanel();
});

// Account Handover (In-Charge only): Change and Add coordinator open the side
// panel instead of a separate page. Step 1 picks the new holder and asks the
// server to send them a code (POST /settings/handover/select); step 2 checks
// that code (POST /settings/handover/verify) and reloads onto this tab. The
// server re-checks everything, so the panel only ever offers, never decides.
// The Timetable Officer is one account that stays, so its step 1 asks for the
// new officer's email instead of picking someone from the list.
function initHandoverPanel() {
    const tab = document.getElementById('settings-panel-handover');
    const panel = document.getElementById('hoPanel');
    if (!tab || !panel) return;

    const $ = id => document.getElementById(id);
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, m => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]
    ));
    const personHtml = p => codeBadge(p.code, p.kind || 'staff') +
        `<span class="candidate-id"><span class="candidate-name">${esc(p.name)}</span>` +
        `<span class="candidate-email">${esc(p.email)}</span></span>`;

    const next = $('hoNext');
    const back = $('hoBack');
    let state = null;        // { position, label, from, candidates, toCode, step, officer }

    function showError(id, msg) {
        $(id).textContent = msg;
        $(id).hidden = !msg;
    }

    function picked() {
        return state.candidates.find(c => c.code === state.toCode) || null;
    }

    function newEmail() {
        return $('hoNewEmail').value.trim().toLowerCase();
    }

    function updateNext() {
        if (state.step === 'pick') {
            next.disabled = state.officer ? !$('hoNewEmail').checkValidity() || !newEmail() : !picked();
        } else {
            next.disabled = false;
        }
    }

    function renderCandidates() {
        const q = $('hoSearch').value.trim().toLowerCase();
        const list = state.candidates.filter(c => !q || (c.name + ' ' + c.email).toLowerCase().includes(q));
        const chosen = picked();
        $('hoCandidates').innerHTML = list.map(c => `
            <label class="candidate-row">
                <input type="radio" name="hoCandidate" value="${esc(c.code)}" ${chosen && chosen.code === c.code ? 'checked' : ''}>
                ${personHtml(c)}
            </label>`).join('');
        $('hoCandidatesEmpty').hidden = list.length > 0;
        updateNext();
    }

    function showStep(step) {
        state.step = step;
        $('hoStepPick').hidden = step !== 'pick';
        $('hoStepVerify').hidden = step !== 'verify';
        $('hoPanelSubtitle').textContent = step === 'verify'
            ? 'Enter the code they received'
            : state.officer ? 'Move the account to the new officer' : 'Pick who takes the role';
        back.textContent = step === 'pick' ? 'Cancel' : 'Back';
        next.textContent = step === 'pick' ? 'Send code' : 'Confirm change';
        updateNext();
    }

    function open(btn) {
        const d = btn.dataset;
        const from = d.code ? { code: d.code, name: d.name, email: d.email, kind: d.kind } : null;
        const officer = d.handover === 'timetable_officer';
        state = { position: d.handover, label: d.label, from: from, candidates: [], toCode: '', step: 'pick', officer: officer };

        $('hoPanelTitle').textContent = (from ? 'Change ' : 'Add ') + d.label;
        $('hoCurrentRow').hidden = !from;
        $('hoCurrent').innerHTML = from ? personHtml(from) : '';
        $('hoEmailRow').hidden = !officer;
        $('hoPickRow').hidden = officer;
        $('hoNewEmail').value = '';
        $('hoSearch').value = '';
        $('hoOtp').value = '';
        showError('hoPickError', '');
        showError('hoOtpError', '');
        $('hoCandidates').innerHTML = '';
        $('hoCandidatesEmpty').hidden = true;
        showStep('pick');

        panel.hidden = false;

        if (officer) {
            $('hoNewEmail').focus();
            return;
        }

        const url = '/settings/handover/candidates/' + encodeURIComponent(d.handover) +
            '?exclude=' + encodeURIComponent(from ? from.code : '');
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message);
                state.candidates = data.candidates;
                renderCandidates();
                $('hoSearch').focus();
            })
            .catch(err => showError('hoPickError', (err && err.message) || 'Could not load the list. Please try again.'));
    }

    function close() {
        panel.hidden = true;
        state = null;
    }

    function sendCode() {
        // The officer's account keeps its code; only the email is new.
        const to = state.officer
            ? { code: state.from.code, name: 'New Timetable Officer', email: newEmail(), kind: 'staff' }
            : picked();
        if (!to || (state.officer && !to.email)) return;
        next.disabled = true;
        next.textContent = 'Sending…';
        showError('hoPickError', '');

        fetch('/settings/handover/select', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                position: state.position,
                fromCode: state.from ? state.from.code : '',
                toCode: state.officer ? '' : to.code,
                newEmail: state.officer ? to.email : '',
            }),
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message);
                $('hoTarget').innerHTML = personHtml(to);
                showStep('verify');
                $('hoOtp').focus();
            })
            .catch(err => {
                showError('hoPickError', (err && err.message) || 'Could not send the code. Please try again.');
                showStep('pick');
            });
    }

    function confirmChange() {
        const otp = $('hoOtp').value.trim();
        if (otp.length !== 6) {
            showError('hoOtpError', 'Enter the full 6-digit code.');
            return;
        }
        next.disabled = true;
        showError('hoOtpError', '');

        fetch('/settings/handover/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ otp: otp }),
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message);
                window.location.hash = 'handover';
                window.location.reload();
            })
            .catch(err => {
                showError('hoOtpError', (err && err.message) || 'Verification failed.');
                next.disabled = false;
            });
    }

    tab.addEventListener('click', e => {
        const btn = e.target.closest('[data-handover]');
        if (btn) open(btn);
    });
    $('hoPanelClose').addEventListener('click', close);
    // The panel is a side drawer: a click on its dimmed backdrop closes it.
    panel.addEventListener('click', e => {
        if (e.target === panel) close();
    });
    back.addEventListener('click', () => (state && state.step === 'verify' ? showStep('pick') : close()));
    next.addEventListener('click', () => (state.step === 'pick' ? sendCode() : confirmChange()));
    $('hoSearch').addEventListener('input', renderCandidates);
    $('hoNewEmail').addEventListener('input', updateNext);
    $('hoNewEmail').addEventListener('keydown', e => {
        if (e.key === 'Enter' && !next.disabled) sendCode();
    });
    $('hoCandidates').addEventListener('change', e => {
        state.toCode = e.target.value;
        updateNext();
    });
    $('hoOtp').addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !panel.hidden) close();
    });
}

// Account Handover (In-Charge only): "Revoke" takes the Coordinator seat away.
// The server refuses the last Coordinator; the button is disabled for it too.
function initRevokeCoordinator() {
    const body = document.getElementById('handoverBody');
    if (!body) return;

    body.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-revoke]');
        if (!btn || btn.disabled) return;
        const name = btn.dataset.name || 'this person';
        if (!confirm('Revoke the Coordinator role from ' + name + '?\n\nThey stay on staff as Junior Staff. You can add them back later.')) return;

        btn.disabled = true;
        fetch('/settings/handover/revoke', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: btn.dataset.revoke })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.hash = 'handover';
                    window.location.reload();
                } else {
                    alert(data.message || 'Could not revoke the role.');
                    btn.disabled = false;
                }
            })
            .catch(() => {
                alert('Something went wrong. Please try again.');
                btn.disabled = false;
            });
    });
}

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
