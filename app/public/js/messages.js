// Messages JS (academic staff + timetable officer): conversation switching,
// search, composer, and the Group Info panel. DOM-only demo state — nothing
// persists across reloads.
//
// The officer's conversations are all direct messages, so the view renders no
// Members button and no Group Info panel; every reference to them below is
// null-guarded rather than assumed present.
document.addEventListener('DOMContentLoaded', () => {
    const dataEl = document.getElementById('conversationsData');
    if (!dataEl) return;
    const conversations = JSON.parse(dataEl.textContent);
    let activeId = conversations.find((_, i) => document.querySelectorAll('.msg-list-item')[i]?.classList.contains('active'))?.id
        || conversations[0].id;

    const memberColors = ['#4d179a', '#1a3a6b', '#0f766e', '#9a3412', '#4338ca'];

    function initials(name) {
        return name.replace(' (You)', '').split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase();
    }

    function renderThread(conv) {
        document.getElementById('msgThreadAvatar').textContent = conv.avatar;
        document.getElementById('msgThreadAvatar').style.background = conv.color;
        document.getElementById('msgThreadName').textContent = conv.name;
        document.getElementById('msgThreadSub').innerHTML = conv.isGroup
            ? `Group &middot; ${conv.members.length} members`
            : 'Direct message';
        document.getElementById('msgComposerInput').placeholder = `Message ${conv.name}...`;

        const body = document.getElementById('msgThreadBody');
        body.innerHTML = conv.messages.map(m => `
            <div class="msg-row ${m.mine ? 'msg-mine' : 'msg-other'}">
                <div class="msg-bubble">
                    <p></p>
                    <span class="msg-time">${m.time}</span>
                </div>
            </div>
        `).join('');
        // set text via textContent to avoid escaping issues with message copy
        body.querySelectorAll('.msg-bubble p').forEach((p, i) => { p.textContent = conv.messages[i].text; });
        body.scrollTop = body.scrollHeight;
    }

    function selectConversation(id) {
        activeId = id;
        const conv = conversations.find(c => c.id === id);
        if (!conv) return;

        document.querySelectorAll('.msg-list-item').forEach(item => {
            const isActive = parseInt(item.dataset.convId, 10) === id;
            item.classList.toggle('active', isActive);
            if (isActive) item.querySelector('.msg-unread')?.remove();
        });

        renderThread(conv);
        const panel = document.getElementById('msgGroupInfo');
        if (panel && !panel.hidden) renderGroupInfo(conv);
    }

    document.getElementById('msgList').addEventListener('click', (e) => {
        const item = e.target.closest('.msg-list-item');
        if (!item) return;
        selectConversation(parseInt(item.dataset.convId, 10));
    });

    document.getElementById('msgSearchInput').addEventListener('input', (e) => {
        const q = e.target.value.trim().toLowerCase();
        document.querySelectorAll('.msg-list-item').forEach(item => {
            item.classList.toggle('filtered-out', q.length > 0 && !item.dataset.name.includes(q));
        });
    });

    function sendMessage() {
        const input = document.getElementById('msgComposerInput');
        const text = input.value.trim();
        if (!text) return;
        const conv = conversations.find(c => c.id === activeId);
        const time = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        conv.messages.push({ text, time, mine: true });
        renderThread(conv);
        input.value = '';
    }
    document.getElementById('msgSendBtn').addEventListener('click', sendMessage);
    document.getElementById('msgComposerInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); sendMessage(); }
    });

    function renderGroupInfo(conv) {
        document.getElementById('msgGroupAvatar').textContent = conv.avatar;
        document.getElementById('msgGroupAvatar').style.background = conv.color;
        document.getElementById('msgGroupName').textContent = conv.name;
        document.getElementById('msgGroupSub').textContent = conv.isGroup ? `Group · ${conv.members.length} members` : conv.subtitle;
        document.getElementById('msgGroupMembersList').innerHTML = conv.members.map((m, i) => `
            <div class="msg-group-member">
                <div class="msg-avatar" style="background:${memberColors[i % memberColors.length]}">${initials(m)}</div>
                <span>${m}</span>
            </div>
        `).join('');
    }

    // Absent entirely on a direct-messages-only page (timetable officer).
    const groupInfoPanel = document.getElementById('msgGroupInfo');
    const membersBtn = document.getElementById('msgMembersBtn');
    if (groupInfoPanel && membersBtn) {
        membersBtn.addEventListener('click', () => {
            const conv = conversations.find(c => c.id === activeId);
            if (groupInfoPanel.hidden) { renderGroupInfo(conv); groupInfoPanel.hidden = false; }
            else groupInfoPanel.hidden = true;
        });
        document.getElementById('msgCloseGroupInfo').addEventListener('click', () => { groupInfoPanel.hidden = true; });
    }
});
