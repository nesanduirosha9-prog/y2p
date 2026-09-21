<?php
// components/notifications.php — the header bell dropdown + "see all" modal.
// Included inline (require_once, not render()) by layouts/dashboard.php, so
// it shares that scope's variables.
// 1. Query the feed + unread count for the logged-in staff member.
//    NOTE (gap): every dashboard controller already computes its own
//    'notificationCount' and passes it in — this file re-queries and
//    overwrites that variable with the same value, so it's a redundant
//    second DB round trip on every page load, not a correctness bug.
// 2. $icons maps notification `type` -> a Font Awesome class.
// 3. Only the 5 most recent show in the dropdown ($displayNotifs); "See
//    all" opens the modal with the full $globalNotifs list.
$notificationModel = new \app\models\NotificationModel();
$globalNotifs = $_SESSION['staff_code'] ?? null ? $notificationModel->all($_SESSION['staff_code']) : [];
$notificationCount = $_SESSION['staff_code'] ?? null ? $notificationModel->unreadCount($_SESSION['staff_code']) : 0;
$icons = ['info' => 'fa-circle-info', 'success' => 'fa-circle-check', 'warning' => 'fa-triangle-exclamation'];
$displayNotifs = array_slice($globalNotifs, 0, 5); // Show top 5
?>

<!-- Notification Bell & Dropdown Wrapper -->
<div class="notification-wrapper" style="position: relative;">
    <button class="icon-btn" type="button" title="Notifications" id="notificationToggleBtn">
        <i class="fa-solid fa-bell"></i>
        <?php if (!empty($notificationCount)): ?><span class="icon-btn-dot"></span><?php endif; ?>
    </button>
    
    <!-- Notification Dropdown -->
    <div class="notif-dropdown" id="notificationDropdown" hidden>
        <div class="notif-dropdown-header">
            <h3>Notifications</h3>
            <div class="notif-dropdown-actions">
                <button title="Mark all as read"><i class="fa-solid fa-check-double"></i></button>
                <button title="Settings"><i class="fa-solid fa-gear"></i></button>
            </div>
        </div>
        <div class="notif-dropdown-body">
            <?php if (empty($displayNotifs)): ?>
                <p class="notif-empty">No notifications.</p>
            <?php else: ?>
                <?php foreach ($displayNotifs as $n): ?>
                    <div class="notif-item <?= $n['read'] ? '' : 'unread' ?>">
                        <div class="notif-icon-wrap"><i class="fa-solid <?= $icons[$n['type']] ?? 'fa-bell' ?>"></i></div>
                        <div class="notif-content-wrap">
                            <p class="notif-title-text"><?= htmlspecialchars($n['title']) ?></p>
                            <p class="notif-time-text"><?= htmlspecialchars($n['time']) ?></p>
                            <a href="#" class="notif-link">View full notification</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="notif-dropdown-footer">
            <button type="button" id="seeAllNotifsBtn">See all</button>
        </div>
    </div>
</div>

<!-- Notification Modal ("See all") -->
<div class="modal-overlay" id="allNotifsModal" hidden>
    <div class="modal" style="max-width: 600px;">
        <div class="modal-head">
            <h3>All Notifications</h3>
            <button type="button" class="modal-close" id="closeAllNotifsBtn"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" style="max-height: 60vh; overflow-y: auto; padding: 0;">
            <?php if (empty($globalNotifs)): ?>
                <p class="notif-empty" style="padding: 2rem; text-align: center;">No notifications.</p>
            <?php else: ?>
                <?php foreach ($globalNotifs as $n): ?>
                    <div class="notif-item <?= $n['read'] ? '' : 'unread' ?>" style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--card-border);">
                        <div class="notif-icon-wrap"><i class="fa-solid <?= $icons[$n['type']] ?? 'fa-bell' ?>"></i></div>
                        <div class="notif-content-wrap">
                            <p class="notif-title-text"><?= htmlspecialchars($n['title']) ?></p>
                            <p class="notif-time-text"><?= htmlspecialchars($n['time']) ?></p>
                            <p class="notif-body-text" style="margin-top: 0.5rem; color: var(--muted); font-size: 0.9rem;"><?= htmlspecialchars($n['body']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn-primary-sm" style="width: 100%;">Mark all as read</button>
        </div>
    </div>
</div>
