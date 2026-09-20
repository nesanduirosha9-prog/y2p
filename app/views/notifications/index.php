<?php

// Notifications feed. $notifications / $unread are read from the database by
// NotificationsController (an empty database renders an empty feed). "Mark all
// as read" and per-card dismissal of the unread state run client-side
// (notifications.js) and do not persist across a reload.

$icons = [
    'info'    => 'fa-circle-info',
    'success' => 'fa-circle-check',
    'warning' => 'fa-triangle-exclamation',
];
?>

<div class="notifs-view">

    <div class="page-head">
        <div>
            <h2>Notifications</h2>
            <p class="page-head-sub"><span id="unreadCount"><?= (int)$unread ?></span> unread notification<?= $unread === 1 ? '' : 's' ?></p>
        </div>
        <?php if ($notifications): ?>
            <button type="button" class="link-action" id="markAllRead">Mark all as read</button>
        <?php endif; ?>
    </div>

    <div class="notif-list">
        <?php if (!$notifications): ?>
            <p class="dir-empty">No notifications.</p>
        <?php endif; ?>
        <?php foreach ($notifications as $n): ?>
            <div class="notif-card notif-<?= htmlspecialchars($n['type']) ?> <?= $n['read'] ? 'is-read' : 'is-unread' ?>">
                <span class="notif-icon"><i class="fa-solid <?= $icons[$n['type']] ?? 'fa-bell' ?>"></i></span>
                <div class="notif-content">
                    <p class="notif-title"><?= htmlspecialchars($n['title']) ?></p>
                    <p class="notif-body"><?= htmlspecialchars($n['body']) ?></p>
                    <p class="notif-time"><?= htmlspecialchars($n['time']) ?></p>
                </div>
                <span class="notif-dot" aria-label="Unread"></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="/js/notifications.js"></script>
