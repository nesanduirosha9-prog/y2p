-- 007_create_notifications.sql
-- The notification feed. Deliberately has NO `is_read` column: the EER
-- modeled `Receives` as M:N between Staff and Notification, but the old
-- schema put `is_read` on Notification itself — with M:N cardinality that
-- means one recipient marking a shared notification as read would mark it
-- read for every recipient. `is_read` now lives on `notification_recipients`
-- (008_create_notification_recipients.sql), one flag per (staff, notification)
-- pair, which is the only place it can correctly live.
--
-- Keyed by a UUID, not an auto-increment id or any of its own attributes:
-- two notifications can legitimately have identical type/title/body/time
-- (e.g. a broadcast resent), so there is no real-world attribute combination
-- guaranteed unique here.

CREATE TABLE notifications (
    id         CHAR(36) NOT NULL PRIMARY KEY,
    type       ENUM('info','success','warning') NOT NULL DEFAULT 'info',
    title      VARCHAR(150) NOT NULL,
    body       TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_feed (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
