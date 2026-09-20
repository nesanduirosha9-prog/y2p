-- 106_24002275_notifications.sql — sample notification feed (Timetable Officer).
-- Re-runnable: clears the table first, then rebuilds (no natural key to upsert on).

DELETE FROM notifications;

INSERT INTO notifications (type, title, body, is_read, created_at) VALUES
    ('info',    'Timetable Updated',          'CS Year 2 timetable has been modified for Week 7. Please review the changes.',        0, NOW() - INTERVAL 10 MINUTE),
    ('success', 'Lecturer Added Successfully', 'Dr. Patricia Ofori has been registered and assigned to CS3304 — Computer Graphics.', 0, NOW() - INTERVAL 2 HOUR),
    ('warning', 'Venue Conflict Detected',    'LT-301 is double-booked on Wednesday at 10:00 AM for CS2201 and IS2202.',             1, NOW() - INTERVAL 1 DAY),
    ('info',    'New Academic Semester',       'Semester 2, 2023/2024 begins on February 5. Ensure all timetables are finalized.',    1, NOW() - INTERVAL 2 DAY),
    ('success', 'Course Approved',             'CS4403 — Advanced Cryptography has been approved and added to the course catalog.',   1, NOW() - INTERVAL 3 DAY);
