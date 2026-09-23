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
//    all" opens the modal, which is a two-pane reader: the full list on the
//    left, the selected notification's body on the right (same master/detail
//    shape as the Messages screen, kept inside this one panel).
//
// Opening a notification — in either pane — POSTs to /notifications/{id}/read
// and the badge updates without a reload; see js/notifications.js.
$notificationModel = new \app\models\NotificationModel();
$globalNotifs = $_SESSION['staff_code'] ?? null ? $notificationModel->all($_SESSION['staff_code']) : [];
$notificationCount = $notificationCount ?? ($_SESSION['staff_code'] ?? null ? $notificationModel->unreadCount($_SESSION['staff_code']) : 0);
$icons = ['info' => 'fa-circle-info', 'success' => 'fa-circle-check', 'warning' => 'fa-triangle-exclamation'];
$displayNotifs = array_slice($globalNotifs, 0, 5); // Show top 5
?>

<!-- Notification Bell & Dropdown Wrapper -->
<div class="notification-wrapper" style="position: relative;">
    <button class="icon-btn" type="button" title="Notifications" id="notificationToggleBtn">
        <i class="fa-solid fa-bell"></i>
        <span class="icon-btn-dot" id="notifBellDot" <?= empty($notificationCount) ? 'hidden' : '' ?>></span>
    </button>

    <!-- Notification Dropdown -->
    <div class="notif-dropdown" id="notificationDropdown" hidden>
        <div class="notif-dropdown-header">
            <h3>Notifications <span class="notif-count-pill" id="notifCountPill" <?= empty($notificationCount) ? 'hidden' : '' ?>><?= (int) $notificationCount ?></span></h3>
            <div class="notif-dropdown-actions">
                <button type="button" title="Mark all as read" data-mark-all><i class="fa-solid fa-check-double"></i></button>
            </div>
        </div>
        <div class="notif-dropdown-body">
            <?php if (empty($displayNotifs)): ?>
                <p class="notif-empty">No notifications.</p>
            <?php else: ?>
                <?php foreach ($displayNotifs as $n): ?>
                    <button type="button" class="notif-item <?= $n['read'] ? '' : 'unread' ?>" data-notif-id="<?= htmlspecialchars($n['id']) ?>">
                        <div class="notif-icon-wrap notif-icon-<?= htmlspecialchars($n['type']) ?>"><i class="fa-solid <?= $icons[$n['type']] ?? 'fa-bell' ?>"></i></div>
                        <div class="notif-content-wrap">
                            <p class="notif-title-text"><?= htmlspecialchars($n['title']) ?></p>
                            <p class="notif-time-text"><?= htmlspecialchars($n['time']) ?></p>
                            <p class="notif-preview-text"><?= htmlspecialchars($n['body']) ?></p>
                        </div>
                        <span class="notif-unread-dot" aria-hidden="true"></span>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="notif-dropdown-footer">
            <button type="button" id="seeAllNotifsBtn">See all notifications</button>
        </div>
    </div>
</div>

<!-- Notification Modal ("See all") — list on the left, body on the right -->
<div class="modal-overlay" id="allNotifsModal" hidden>
    <div class="modal notif-modal" role="dialog" aria-modal="true" aria-labelledby="allNotifsTitle">
        <div class="modal-head">
            <h3 id="allNotifsTitle">All Notifications</h3>
            <button type="button" class="modal-close" id="closeAllNotifsBtn" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <?php if (empty($globalNotifs)): ?>
            <div class="notif-modal-empty">
                <i class="fa-regular fa-bell-slash"></i>
                <p>No notifications yet.</p>
            </div>
        <?php else: ?>
            <div class="notif-modal-body" id="notifModalBody">
                <!-- Master: the full feed -->
                <div class="notif-modal-list" id="notifModalList">
                    <?php foreach ($globalNotifs as $i => $n): ?>
                        <button type="button"
                                class="notif-item <?= $n['read'] ? '' : 'unread' ?> <?= $i === 0 ? 'active' : '' ?>"
                                data-notif-id="<?= htmlspecialchars($n['id']) ?>"
                                data-title="<?= htmlspecialchars($n['title']) ?>"
                                data-time="<?= htmlspecialchars($n['time']) ?>"
                                data-type="<?= htmlspecialchars($n['type']) ?>"
                                data-icon="<?= htmlspecialchars($icons[$n['type']] ?? 'fa-bell') ?>"
                                data-body="<?= htmlspecialchars($n['body']) ?>">
                            <div class="notif-icon-wrap notif-icon-<?= htmlspecialchars($n['type']) ?>"><i class="fa-solid <?= $icons[$n['type']] ?? 'fa-bell' ?>"></i></div>
                            <div class="notif-content-wrap">
                                <p class="notif-title-text"><?= htmlspecialchars($n['title']) ?></p>
                                <p class="notif-time-text"><?= htmlspecialchars($n['time']) ?></p>
                                <p class="notif-preview-text"><?= htmlspecialchars($n['body']) ?></p>
                            </div>
                            <span class="notif-unread-dot" aria-hidden="true"></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Detail: the selected notification -->
                <div class="notif-modal-detail" id="notifModalDetail">
                    <button type="button" class="notif-detail-back" id="notifDetailBack" aria-label="Back to list">
                        <i class="fa-solid fa-arrow-left"></i> All notifications
                    </button>
                    <div class="notif-detail-head">
                        <div class="notif-detail-icon notif-icon-info" id="notifDetailIcon"><i class="fa-solid fa-circle-info"></i></div>
                        <div>
                            <h4 class="notif-detail-title" id="notifDetailTitle"></h4>
                            <p class="notif-detail-time" id="notifDetailTime"></p>
                        </div>
                    </div>
                    <p class="notif-detail-body" id="notifDetailBody"></p>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn-primary-sm" id="markAllNotifsBtn" data-mark-all>Mark all as read</button>
            </div>
        <?php endif; ?>
    </div>
</div>
