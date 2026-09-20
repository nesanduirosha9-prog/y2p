-- 101_24002275_timetable_sessions.sql — sample weekly sessions (Timetable Officer).
-- Ported from TimetableController::sessions(). The controller keyed these
-- "dept|sem|year": cs|1|1, cs|1|2, is|1|1  (sem is always 1 in the sample data).
-- Re-runnable: clears the table first, then rebuilds. This is the only feature
-- that writes to timetable_sessions, so the blanket DELETE is safe for dev.

DELETE FROM timetable_sessions;

INSERT INTO timetable_sessions
    (course_id, department, semester, year_of_study, day_of_week, start_hour, duration_hours, location, session_type)
SELECT c.id, v.department, v.semester, v.year_of_study, v.day_of_week, v.start_hour, v.duration_hours, v.location, v.session_type
FROM (
              SELECT 'CS1101' AS code, 'cs' AS department, 1 AS semester, 1 AS year_of_study, 'mon' AS day_of_week,  8 AS start_hour, 1 AS duration_hours, 'Lab A-201'  AS location, 'lab'      AS session_type
    UNION ALL SELECT 'CS1102',        'cs',                1,             1,                  'tue',               10,              2,                  'LT-301',                 'lecture'
    UNION ALL SELECT 'CS1103',        'cs',                1,             1,                  'wed',               14,              1,                  'Room B-105',             'tutorial'
    UNION ALL SELECT 'CS1101',        'cs',                1,             1,                  'thu',                9,              2,                  'Lab A-201',              'lab'
    UNION ALL SELECT 'CS2201',        'cs',                1,             2,                  'mon',                9,              2,                  'LT-401',                 'lecture'
    UNION ALL SELECT 'CS2202',        'cs',                1,             2,                  'tue',                8,              1,                  'Lab C-301',              'lab'
    UNION ALL SELECT 'CS2201',        'cs',                1,             2,                  'wed',               13,              1,                  'Room D-201',             'tutorial'
    UNION ALL SELECT 'CS2203',        'cs',                1,             2,                  'fri',               15,              1,                  'LT-302',                 'lecture'
    UNION ALL SELECT 'IS1101',        'is',                1,             1,                  'mon',                8,              1,                  'LT-201',                 'lecture'
    UNION ALL SELECT 'IS1103',        'is',                1,             1,                  'fri',               14,              1,                  'Lab B-101',              'lab'
) AS v
JOIN courses c ON c.code = v.code;
