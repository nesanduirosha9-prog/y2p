-- 107_24002275_create_notifications.sql
-- Timetable Officer (index 24002275).
-- The notification feed. `type` drives the card's icon and tint; `is_read`
-- drives the unread dot and the sidebar badge count.

CREATE TABLE notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    type       ENUM('info','success','warning') NOT NULL DEFAULT 'info',
    title      VARCHAR(150) NOT NULL,
    body       TEXT NOT NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_feed (is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
