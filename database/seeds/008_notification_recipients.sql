-- 008_notification_recipients.sql — attaches the notifications seeded in
-- 007 to both demo logins (TMO, TMF), with DIFFERENT read states per
-- recipient — the concrete proof that is_read is now per-person instead of
-- shared on the notification row (the bug this table exists to fix).
-- Depends on the @notif_* variables set in 007, which runs first on the
-- same connection.

INSERT IGNORE INTO notification_recipients (notification_id, staff_code, is_read, read_at) VALUES
    (@notif_timetable_updated, 'TMO', 0, NULL),
    (@notif_lecturer_added,    'TMO', 0, NULL),
    (@notif_venue_conflict,    'TMO', 1, NOW() - INTERVAL 12 HOUR),
    (@notif_new_semester,      'TMO', 1, NOW() - INTERVAL 1 DAY),
    (@notif_course_approved,   'TMO', 1, NOW() - INTERVAL 2 DAY),

    (@notif_timetable_updated, 'TMF', 1, NOW() - INTERVAL 5 MINUTE),
    (@notif_venue_conflict,    'TMF', 0, NULL),
    (@notif_new_semester,      'TMF', 0, NULL);
