<?php

// Instructor Messages View
$title = "Messages";

// Dummy data
$conversations = [
    ['id' => 1, 'name' => 'CS3401 – Fundamentals of Computing Lab', 'avatar' => 'CS', 'time' => '10:42 AM', 'preview' => 'Please bring your laptops.', 'unread' => 2, 'isGroup' => true],
    ['id' => 2, 'name' => 'Dr. N. Perera', 'avatar' => 'NP', 'time' => 'Yesterday', 'preview' => 'Thanks for covering the class.', 'unread' => 0, 'isGroup' => false],
];

$activeConv = $conversations[0];
$messages = [
    ['text' => 'Hi everyone, the lab setup is ready.', 'time' => '10:30 AM', 'mine' => false],
    ['text' => 'Please bring your laptops.', 'time' => '10:42 AM', 'mine' => true],
];
?>

<div class="msg-container">
    <!-- Conversation List -->
    <div class="msg-sidebar">
        <div class="msg-sidebar-header">
            <h2>Messages</h2>
            <div class="msg-search-box">
                <i class="fa-solid fa-search search-icon"></i>
                <input type="text" placeholder="Search conversations...">
            </div>
        </div>
        <div class="msg-list">
            <?php foreach($conversations as $c): ?>
                <button type="button" class="msg-list-item <?= $c['id'] === $activeConv['id'] ? 'active' : '' ?>">
                    <div class="msg-avatar" style="background: <?= $c['avatar'] === 'CS' ? '#4d179a' : '#1a3a6b' ?>;">
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
                <div class="msg-avatar" style="background: #4d179a;">CS</div>
                <div>
                    <p class="msg-thread-name"><?= htmlspecialchars($activeConv['name']) ?></p>
                    <p class="msg-thread-sub">Group &middot; 4 members</p>
                </div>
            </div>
            <button class="msg-btn-members">
                <i class="fa-solid fa-users"></i> Members <i class="fa-solid fa-chevron-down"></i>
            </button>
        </div>

        <div class="msg-thread-body">
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
                <input type="text" placeholder="Message <?= htmlspecialchars($activeConv['name']) ?>..." class="msg-input">
                <button class="msg-send-btn"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
</div>
