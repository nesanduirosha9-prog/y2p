-- 008_create_notification_recipients.sql
-- The associative table that correctly implements the EER's `Receives`
-- (M:N, Staff <-> Notification), with `is_read` per (staff, notification)
-- pair instead of incorrectly shared on the Notification row. This is what
-- makes "mark as read" behave per-recipient instead of globally.

CREATE TABLE notification_recipients (
    notification_id CHAR(36)    NOT NULL,
    staff_code      VARCHAR(12) NOT NULL,
    is_read         TINYINT(1)  NOT NULL DEFAULT 0,
    read_at         TIMESTAMP   NULL,
    PRIMARY KEY (notification_id, staff_code),
    CONSTRAINT fk_nr_notification FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    CONSTRAINT fk_nr_staff        FOREIGN KEY (staff_code)      REFERENCES staff(code)        ON DELETE CASCADE,
    INDEX idx_nr_unread (staff_code, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
