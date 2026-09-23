<?php

// Instructor Messages View — conversation switching, search, composer, and the
// Group Info panel are all driven client-side (js/instructor/messages.js) from
// the $conversationsData dataset below. DOM-only demo — nothing persists.
$title = "Messages";

$conversationsData = [
    [
        'id' => 1, 'name' => 'CS3401 Lab Group', 'subtitle' => 'CS3401 – Fundamentals of Computing Lab',
        'avatar' => 'CS', 'color' => '#4d179a', 'time' => '9:41 AM', 'preview' => 'Lab report template has been updated — please use the new version.',
        'unread' => 3, 'isGroup' => true, 'members' => ['Ms. T. Fernando (You)', 'Dr. N. Perera', 'Mr. K. Bandara'],
        'messages' => [
            ['text' => 'Good morning everyone. Today we\'ll cover memory allocation in C.', 'time' => '8:02 AM', 'mine' => false],
            ['text' => 'Dr. Perera, the lab report template has been updated — please check the shared drive.', 'time' => '8:45 AM', 'mine' => false],
            ['text' => 'Got it, thanks. I\'ll distribute it at the start of class.', 'time' => '9:10 AM', 'mine' => true],
            ['text' => 'Also, can someone confirm the projector in Lab A-201 is working?', 'time' => '9:35 AM', 'mine' => true],
            ['text' => 'Lab report template has been updated — please use the new version.', 'time' => '9:41 AM', 'mine' => false],
        ],
    ],
    [
        'id' => 2, 'name' => 'IT2301 Practical Group', 'subtitle' => 'IT2301 – Web Technologies Practical',
        'avatar' => 'IT', 'color' => '#0f766e', 'time' => '9:12 AM', 'preview' => "Don't forget the network config lab.",
        'unread' => 1, 'isGroup' => true, 'members' => ['Ms. T. Fernando (You)', 'Prof. A. Silva'],
        'messages' => [
            ['text' => 'Reminder: bring your laptops fully charged for today\'s practical.', 'time' => '8:50 AM', 'mine' => true],
            ['text' => "Don't forget the network config lab.", 'time' => '9:12 AM', 'mine' => false],
        ],
    ],
    [
        'id' => 3, 'name' => 'Dr. Nimal Perera', 'subtitle' => 'Dr. Nimal Perera',
        'avatar' => 'NP', 'color' => '#1a3a6b', 'time' => 'Yesterday', 'preview' => 'Please send the attendance sheet for las…',
        'unread' => 0, 'isGroup' => false, 'members' => ['Ms. T. Fernando (You)', 'Dr. N. Perera'],
        'messages' => [
            ['text' => 'Please send the attendance sheet for last week\'s lab.', 'time' => 'Yesterday · 4:12 PM', 'mine' => false],
            ['text' => 'Sure, sending it over now.', 'time' => 'Yesterday · 4:20 PM', 'mine' => true],
        ],
    ],
    [
        'id' => 4, 'name' => 'Prof. Anoma Silva', 'subtitle' => 'Prof. Anoma Silva',
        'avatar' => 'AS', 'color' => '#9a3412', 'time' => 'Monday', 'preview' => 'Thank you for the update.',
        'unread' => 0, 'isGroup' => false, 'members' => ['Ms. T. Fernando (You)', 'Prof. A. Silva'],
        'messages' => [
            ['text' => 'Could you cover my practical session next Tuesday?', 'time' => 'Monday · 11:00 AM', 'mine' => false],
            ['text' => 'Yes, happy to help — I\'ll confirm the room.', 'time' => 'Monday · 11:20 AM', 'mine' => true],
            ['text' => 'Thank you for the update.', 'time' => 'Monday · 11:25 AM', 'mine' => false],
        ],
    ],
    [
        'id' => 5, 'name' => 'Dept. Instructors Channel', 'subtitle' => 'Department of Computer Science',
        'avatar' => 'DI', 'color' => '#4338ca', 'time' => 'Monday', 'preview' => 'Reminder: staff meeting at 3 PM tomorrow.',
        'unread' => 2, 'isGroup' => true, 'members' => ['Ms. T. Fernando (You)', 'Dr. N. Perera', 'Prof. A. Silva', 'Mr. K. Bandara'],
        'messages' => [
            ['text' => 'Reminder: staff meeting at 3 PM tomorrow in the conference room.', 'time' => 'Monday · 2:00 PM', 'mine' => false],
        ],
    ],
];

$activeConv = $conversationsData[0];
$conversations = $conversationsData; 
$messages = $activeConv['messages'];
?>

<div class="msg-container">
    <!-- Conversation List -->
    <div class="msg-sidebar">
        <div class="msg-sidebar-header">
             
            <div class="msg-search-box">
                <i class="fa-solid fa-search search-icon"></i>
                <input type="text" placeholder="Search conversations..." id="msgSearchInput">
            </div>
        </div>
        <div class="msg-list" id="msgList">
            <?php foreach($conversations as $c): ?>
                <button type="button" class="msg-list-item <?= $c['id'] === $activeConv['id'] ? 'active' : '' ?>" data-conv-id="<?= $c['id'] ?>" data-name="<?= htmlspecialchars(strtolower($c['name'])) ?>">
                    <div class="msg-avatar" style="background: <?= htmlspecialchars($c['color']) ?>;">
                        <?= htmlspecialchars($c['avatar']) ?>
                    </div>
                    <div class="msg-item-content">
                        <div class="msg-item-top">
                            <p class="msg-item-name"><?= htmlspecialchars($c['name']) ?></p>
                            <span class="msg-item-time"><?= htmlspecialchars($c['time']) ?></span>
                        </div>
                        <p class="msg-item-preview"><?= htmlspecialchars($c['preview']) ?></p>
                    </div>
                    <?php if($c['unread'] > 0): ?>
                        <div class="msg-unread"><?= $c['unread'] ?></div>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Thread View -->
    <div class="msg-thread">
        <div class="msg-thread-header">
            <div class="msg-thread-info">
                <!-- Mobile Back Button -->
                <button type="button" class="msg-btn-back" id="msgBackBtn" aria-label="Back to conversations">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div class="msg-avatar" id="msgThreadAvatar" style="background: <?= htmlspecialchars($activeConv['color']) ?>;">
                    <?= htmlspecialchars($activeConv['avatar']) ?>
                </div>
                <div>
                    <p class="msg-thread-name" id="msgThreadName"><?= htmlspecialchars($activeConv['name']) ?></p>
                    <p class="msg-thread-sub" id="msgThreadSub"><?= $activeConv['isGroup'] ? 'Group &middot; ' . count($activeConv['members']) . ' members' : 'Direct message' ?></p>
                </div>
            </div>
            <button class="msg-btn-members" id="msgMembersBtn">
                <i class="fa-solid fa-users"></i> Members <i class="fa-solid fa-chevron-down"></i>
            </button>
        </div>

        <div class="msg-thread-body" id="msgThreadBody">
            <?php foreach($messages as $m): ?>
                <div class="msg-row <?= $m['mine'] ? 'msg-mine' : 'msg-other' ?>">
                    <div class="msg-bubble">
                        <p><?= htmlspecialchars($m['text']) ?></p>
                        <span class="msg-time"><?= htmlspecialchars($m['time']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="msg-composer">
            <div class="msg-input-wrap">
                <button class="msg-attach-btn"><i class="fa-solid fa-paperclip"></i></button>
                <input type="text" placeholder="Message <?= htmlspecialchars($activeConv['name']) ?>..." class="msg-input" id="msgComposerInput">
                <button class="msg-send-btn" id="msgSendBtn"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <!-- Group Info panel -->
    <aside class="msg-group-info" id="msgGroupInfo" hidden>
        <div class="msg-group-info-header">
            <h2>Group Info</h2>
            <button type="button" class="modal-close" id="msgCloseGroupInfo"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="msg-group-info-body">
            <div class="msg-group-avatar" id="msgGroupAvatar"></div>
            <p class="msg-group-name" id="msgGroupName"></p>
            <p class="msg-group-sub" id="msgGroupSub"></p>
            <p class="msg-group-members-label">MEMBERS</p>
            <div class="msg-group-members-list" id="msgGroupMembersList"></div>
        </div>
    </aside>

    <i id="toggleBtnARROW" class="fa-solid fa-arrow-right" style=
       "
       position: fixed;
       top: 50%;
       left: 10px;
       transform: translateY(-50%);
       z-index: 1000;
       cursor: pointer;
       width: 42px;
       height: 42px;
       display: flex;
       align-items: center;
       justify-content: center;
       background: #1e1d1dff;
       color: #ffffff;
       border-radius: 50%;
       box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
       font-size: 18px;
       transition: all 0.2s ease;
   "
    ></i>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const msgContainer = document.querySelector('.msg-container');
    const msgList = document.getElementById('msgList');
    const msgBackBtn = document.getElementById('msgBackBtn');

    // Show thread on mobile when a conversation is clicked
    if (msgList) {
        msgList.addEventListener('click', (e) => {
            const item = e.target.closest('.msg-list-item');
            if (item) {
                // By adding this class, CSS takes over and overlays the thread
                msgContainer.classList.add('show-thread');
            }
        });
    }


    // Go back to the conversation list on mobile
    if (msgBackBtn) {
        msgBackBtn.addEventListener('click', () => {
            msgContainer.classList.remove('show-thread');
        });
    }
});


    const side = document.getElementById('dashSidebar');
    const back = document.getElementById('sidebarBackdrop');
    const toggleBtnARROW = document.getElementById('toggleBtnARROW');

    if (!side || !back || !toggleBtnARROW) {
       console.log("Not working buttons")
    }

    function openSidebar() {
        side.classList.add('open');
        back.classList.add('visible');
    }

    function closeSidebar() {
        side.classList.remove('open');
        back.classList.remove('visible');
    }

    toggleBtnARROW.addEventListener('click', function () {
        console.log("clicked");
        if (side.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    back.addEventListener('click', closeSidebar);




</script>

<script type="application/json" id="conversationsData"><?= json_encode($conversationsData) ?></script>
<script src="/js/instructor/messages.js"></script>