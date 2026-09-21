-- 007_notifications.sql — sample notification feed. Ids are captured into
-- session variables so 008_notification_recipients.sql (run right after, on
-- the same connection within migrate.php) can attach recipients to them
-- without a second lookup. Re-runnable: clears both tables first (no
-- natural key to upsert a UUID on), then rebuilds.

DELETE FROM notification_recipients;
DELETE FROM notifications;

SET @notif_timetable_updated = UUID();
SET @notif_lecturer_added    = UUID();
SET @notif_venue_conflict    = UUID();
SET @notif_new_semester      = UUID();
SET @notif_course_approved   = UUID();

INSERT INTO notifications (id, type, title, body, created_at) VALUES
    (@notif_timetable_updated, 'info',    'Timetable Updated',          'CS Year 2 timetable has been modified for Week 7. Please review the changes.',        NOW() - INTERVAL 10 MINUTE),
    (@notif_lecturer_added,    'success', 'Lecturer Added Successfully', 'Dr. Patricia Ofori has been registered and assigned to CS3304 — Computer Graphics.', NOW() - INTERVAL 2 HOUR),
    (@notif_venue_conflict,    'warning', 'Venue Conflict Detected',    'LT-301 is double-booked on Wednesday at 10:00 AM for CS2201 and IS2202.',             NOW() - INTERVAL 1 DAY),
    (@notif_new_semester,      'info',    'New Academic Semester',       'Semester 2, 2023/2024 begins on February 5. Ensure all timetables are finalized.',    NOW() - INTERVAL 2 DAY),
    (@notif_course_approved,   'success', 'Course Approved',             'CS4403 — Advanced Cryptography has been approved and added to the course catalog.',   NOW() - INTERVAL 3 DAY);
